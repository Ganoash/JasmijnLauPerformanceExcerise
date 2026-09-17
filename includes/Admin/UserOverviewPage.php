<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Domain\Week;
use InvalidArgumentException;
use LauPerformanceTraining\Repositories\GoalRepository;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Services\UserPaymentService;
use LauPerformanceTraining\Services\UserTrainingPreferenceService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Support\View;

final class UserOverviewPage
{
	public function __construct(
		private readonly DateFactory $date_factory,
		private readonly ?UserTrainingPreferenceService $user_preferences = null,
		private readonly ?Nonce $nonce = null,
		private readonly ?SchemaRepository $schemas = null,
		private readonly ?TrainingRepository $trainings = null,
		private readonly ?UserPaymentService $payments = null,
	) {
	}

	public function register(): void
	{
		add_action('admin_post_lpt_save_user_training_preference', [$this, 'saveTrainingPreference']);
		add_action('admin_post_lpt_save_user_payment', [$this, 'savePayment']);
		add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
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

		$current_week = Week::fromDate($this->date_factory->now());
		$last_week    = $current_week->plusWeeks(-1);

		View::render(
			'admin/user-overview.php',
			[
				'action_url'                => admin_url('admin-post.php'),
				'current_week'              => $current_week->startDate(),
				'injury_comments'           => $this->injuryCommentsByUser($users, $current_week->startDate()),
				'last_week_injury_comments' => $this->injuryCommentsByUser($users, $last_week->startDate()),
				'nonce'                     => $this->nonce()->create(Nonce::USER_TRAINING_PREFERENCE_ACTION),
				'payment_dates'             => $this->paymentDates($users),
				'payment_nonce'             => $this->nonce()->create(Nonce::USER_PAYMENT_ACTION),
				'payment_overdue'           => $this->paymentOverdue($users),
				'search'                    => $search,
				'training_counts'           => $this->trainingCounts($users),
				'users'                     => $users,
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

	public function savePayment(): void
	{
		if (! current_user_can('manage_training_schemas')) {
			wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'lau-performance-training'));
		}

		$nonce = isset($_POST['_lpt_nonce']) ? sanitize_text_field(wp_unslash($_POST['_lpt_nonce'])) : '';
		if (! $this->nonce()->verify($nonce, Nonce::USER_PAYMENT_ACTION)) {
			wp_die(esc_html__('Ongeldige beveiligingscode.', 'lau-performance-training'));
		}

		$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
		$date    = isset($_POST['next_payment_date']) ? sanitize_text_field(wp_unslash($_POST['next_payment_date'])) : '';
		$action  = isset($_POST['payment_action']) ? sanitize_text_field(wp_unslash($_POST['payment_action'])) : 'save';

		try {
			if ($user_id > 0 && get_user_by('id', $user_id)) {
				if ($action === 'advance') {
					$this->payments()->advanceNextPaymentDate($user_id, $date);
				} else {
					$this->payments()->setNextPaymentDate($user_id, $date);
				}
			}

			wp_safe_redirect(admin_url('admin.php?page=lpt-training&payment_updated=1'));
			exit;
		} catch (InvalidArgumentException $exception) {
			wp_safe_redirect(
				add_query_arg(
					'lpt_error',
					rawurlencode($exception->getMessage()),
					admin_url('admin.php?page=lpt-training')
				)
			);
			exit;
		}
	}

	public function enqueueStyles(string $hook_suffix): void
	{
		if (! isset($_GET['page']) || $_GET['page'] !== 'lpt-training') {
			return;
		}

		wp_enqueue_style(
			'lpt-user-overview',
			LPT_PLUGIN_URL . 'assets/admin/user-overview.css',
			[],
			LPT_VERSION
		);
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

	/**
	 * @param \WP_User[] $users
	 * @return array<int,string>
	 */
	private function paymentDates(array $users): array
	{
		$dates = [];
		foreach ($users as $user) {
			$dates[(int) $user->ID] = $this->payments()->nextPaymentDate((int) $user->ID);
		}

		return $dates;
	}

	/**
	 * @param \WP_User[] $users
	 * @return array<int,bool>
	 */
	private function paymentOverdue(array $users): array
	{
		$overdue = [];
		foreach ($users as $user) {
			$date = $this->payments()->nextPaymentDate((int) $user->ID);
			$overdue[(int) $user->ID] = $this->payments()->isOverdue($date);
		}

		return $overdue;
	}

	/**
	 * @param \WP_User[] $users
	 * @return array<int,array<int,array{day:string,time_of_day:string,comment:string}>>
	 */
	private function injuryCommentsByUser(array $users, string $week_start_date): array
	{
		$comments = [];
		foreach ($users as $user) {
			$schema = $this->schemas()->findByUserAndWeek((int) $user->ID, $week_start_date);
			if (! $schema) {
				$comments[(int) $user->ID] = [];
				continue;
			}

			$comments[(int) $user->ID] = array_values(
				array_map(
					static fn (Training $training): array => [
						'day'         => (string) $training->dayIndex,
						'time_of_day' => $training->timeOfDay,
						'comment'     => $training->injuryComment,
					],
					array_filter(
						$this->trainings()->findBySchema($schema->id),
						static fn (Training $training): bool => $training->injuryComment !== ''
					)
				)
			);
		}

		return $comments;
	}

	private function userPreferences(): UserTrainingPreferenceService
	{
		return $this->user_preferences ?? new UserTrainingPreferenceService();
	}

	private function nonce(): Nonce
	{
		return $this->nonce ?? new Nonce();
	}

	private function schemas(): SchemaRepository
	{
		return $this->schemas ?? new SchemaRepository();
	}

	private function trainings(): TrainingRepository
	{
		return $this->trainings ?? new TrainingRepository();
	}

	private function payments(): UserPaymentService
	{
		return $this->payments ?? new UserPaymentService();
	}
}
