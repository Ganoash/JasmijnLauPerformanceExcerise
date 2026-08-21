<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Services\UserTrainingPreferenceService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Support\View;

final class UserOverviewPage
{
	public function __construct(
		private readonly DateFactory $date_factory,
		private readonly ?UserTrainingPreferenceService $user_preferences = null,
		private readonly ?Nonce $nonce = null
	) {
	}

	public function register(): void
	{
		add_action('admin_post_lpt_save_user_training_preference', [$this, 'saveTrainingPreference']);
	}

	public function render(): void
	{
		if (! current_user_can('manage_training_schemas')) {
			wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'lau-performance-training'));
		}

		$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
		$users  = get_users(
			[
				'search'         => $search !== '' ? '*' . $search . '*' : '',
				'search_columns' => ['user_login', 'user_email', 'display_name'],
				'number'         => 50,
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			]
		);

		View::render(
			'admin/user-overview.php',
			[
				'action_url'      => admin_url('admin-post.php'),
				'current_week'    => Week::fromDate($this->date_factory->now())->startDate(),
				'nonce'           => $this->nonce()->create(Nonce::USER_TRAINING_PREFERENCE_ACTION),
				'search'          => $search,
				'training_counts' => $this->trainingCounts($users),
				'users'           => $users,
			]
		);
	}

	public function saveTrainingPreference(): void
	{
		if (! current_user_can('manage_training_schemas')) {
			wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'lau-performance-training'));
		}

		$nonce = isset($_POST['_lpt_nonce']) ? sanitize_text_field(wp_unslash($_POST['_lpt_nonce'])) : '';
		if (! $this->nonce()->verify($nonce, Nonce::USER_TRAINING_PREFERENCE_ACTION)) {
			wp_die(esc_html__('Ongeldige beveiligingscode.', 'lau-performance-training'));
		}

		$user_id           = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
		$trainings_per_day = isset($_POST['trainings_per_day']) ? (int) $_POST['trainings_per_day'] : 2;

		if ($user_id > 0 && get_user_by('id', $user_id)) {
			$this->userPreferences()->setTrainingsPerDay($user_id, $trainings_per_day);
		}

		wp_safe_redirect(admin_url('admin.php?page=lpt-training&updated=1'));
		exit;
	}

	/**
	 * @param \WP_User[] $users
	 * @return array<int,int>
	 */
	private function trainingCounts(array $users): array
	{
		$counts = [];
		foreach ($users as $user) {
			$counts[(int) $user->ID] = $this->userPreferences()->trainingsPerDay((int) $user->ID);
		}

		return $counts;
	}

	private function userPreferences(): UserTrainingPreferenceService
	{
		return $this->user_preferences ?? new UserTrainingPreferenceService();
	}

	private function nonce(): Nonce
	{
		return $this->nonce ?? new Nonce();
	}
}
