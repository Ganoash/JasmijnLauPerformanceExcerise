<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Frontend;

use InvalidArgumentException;
use LauPerformanceTraining\Domain\Goal;
use LauPerformanceTraining\Permissions\GoalAccess;
use LauPerformanceTraining\Repositories\GoalRepository;
use LauPerformanceTraining\Services\GoalService;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Support\View;
use RuntimeException;

final class GoalsPage
{
	public function __construct(
		private readonly GoalRepository $goals,
		private readonly GoalService $goal_service,
		private readonly GoalAccess $access,
		private readonly Nonce $nonce
	) {
	}

	public function register(): void
	{
		add_action('admin_post_lpt_save_goal', [$this, 'save']);
		add_action('admin_post_lpt_delete_goal', [$this, 'delete']);
	}

	public function render(int $route_user_id = 0): void
	{
		if (! is_user_logged_in()) {
			auth_redirect();
		}

		$current_user_id = get_current_user_id();
		$user_id         = $route_user_id > 0 ? $route_user_id : $current_user_id;

		if (! $this->access->canManageGoalUser($current_user_id, $user_id)) {
			status_header(403);
			wp_die(esc_html__('Je hebt geen toegang tot deze wedstrijden.', 'lau-performance-training'));
		}

		$user = get_user_by('id', $user_id);
		if (! $user) {
			status_header(404);
			wp_die(esc_html__('Gebruiker niet gevonden.', 'lau-performance-training'));
		}

		wp_enqueue_style('lpt-goals', LPT_PLUGIN_URL . 'assets/frontend/goals.css', [], LPT_VERSION);
		wp_enqueue_script('lpt-goals', LPT_PLUGIN_URL . 'assets/frontend/goals.js', [], LPT_VERSION, true);

		$goals      = $this->goals->findByUser($user_id);
		$edit_goal  = $this->editGoal($user_id);
		$error      = isset($_GET['lpt_goal_error']) ? sanitize_text_field(wp_unslash($_GET['lpt_goal_error'])) : '';
		$content    = $this->content(
			[
				'action_url'      => admin_url('admin-post.php'),
				'active_goals'    => array_values(array_filter($goals, static fn (Goal $goal): bool => $goal->active)),
				'completed'       => isset($_GET['goal_completed']),
				'edit_goal'       => $edit_goal,
				'error_message'   => $error,
				'inactive_goals'  => array_values(array_filter($goals, static fn (Goal $goal): bool => ! $goal->active)),
				'nonce'           => $this->nonce->create(Nonce::GOAL_ACTION),
				'own_goals'       => $current_user_id === $user_id,
				'target_user_id'  => $user_id,
				'user'            => $user,
			]
		);

		if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
			$this->renderBlockThemeDocument($content);
			return;
		}

		get_header();
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function save(): void
	{
		if (! is_user_logged_in()) {
			auth_redirect();
		}

		$nonce = isset($_POST['_lpt_nonce']) ? sanitize_text_field(wp_unslash($_POST['_lpt_nonce'])) : '';
		if (! $this->nonce->verify($nonce, Nonce::GOAL_ACTION)) {
			wp_die(esc_html__('Ongeldige beveiligingscode.', 'lau-performance-training'));
		}

		$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
		$goal_id = isset($_POST['goal_id']) && (int) $_POST['goal_id'] > 0 ? (int) $_POST['goal_id'] : null;

		try {
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- GoalValidator sanitizes each field.
			$result = $this->goal_service->save(
				get_current_user_id(),
				$user_id,
				wp_unslash($_POST),
				$goal_id
			);
			// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$url = $this->redirectUrl($user_id);
			if ($result['actual_time_completed']) {
				$url = add_query_arg('goal_completed', '1', $url);
			}

			wp_safe_redirect($url);
			exit;
		} catch (InvalidArgumentException | RuntimeException $exception) {
			wp_safe_redirect(add_query_arg('lpt_goal_error', rawurlencode($exception->getMessage()), $this->redirectUrl($user_id)));
			exit;
		}
	}

	public function delete(): void
	{
		if (! is_user_logged_in()) {
			auth_redirect();
		}

		$nonce = isset($_POST['_lpt_nonce']) ? sanitize_text_field(wp_unslash($_POST['_lpt_nonce'])) : '';
		if (! $this->nonce->verify($nonce, Nonce::GOAL_ACTION)) {
			wp_die(esc_html__('Ongeldige beveiligingscode.', 'lau-performance-training'));
		}

		$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
		$goal_id = isset($_POST['goal_id']) ? (int) $_POST['goal_id'] : 0;

		try {
			$this->goal_service->delete(get_current_user_id(), $user_id, $goal_id);
			wp_safe_redirect($this->redirectUrl($user_id));
			exit;
		} catch (InvalidArgumentException | RuntimeException $exception) {
			wp_safe_redirect(add_query_arg('lpt_goal_error', rawurlencode($exception->getMessage()), $this->redirectUrl($user_id)));
			exit;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 */
	private function content(array $data): string
	{
		ob_start();
		View::render('frontend/goals-page.php', $data);

		return (string) ob_get_clean();
	}

	private function editGoal(int $user_id): ?Goal
	{
		$goal_id = isset($_GET['edit_goal_id']) ? (int) $_GET['edit_goal_id'] : 0;
		if ($goal_id <= 0) {
			return null;
		}

		$goal = $this->goals->findById($goal_id);

		return $goal instanceof Goal && $goal->userId === $user_id ? $goal : null;
	}

	private function redirectUrl(int $user_id): string
	{
		return get_current_user_id() === $user_id
			? home_url('/training-goals/')
			: home_url('/training-goals/' . $user_id . '/');
	}

	private function renderBlockThemeDocument(string $content): void
	{
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo('charset'); ?>" />
			<?php wp_head(); ?>
		</head>
		<body <?php body_class('lpt-goals-document'); ?>>
		<?php wp_body_open(); ?>
		<div class="wp-site-blocks">
			<?php block_template_part('header'); ?>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}
}
