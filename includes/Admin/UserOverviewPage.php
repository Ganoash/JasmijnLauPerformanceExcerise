<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
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
		private readonly ?TrainingRepository $trainings = null
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

		$current_week = Week::fromDate($this->date_factory->now())->startDate();

		View::render(
			'admin/user-overview.php',
			[
				'action_url'      => admin_url('admin-post.php'),
				'current_week'    => $current_week,
				'injury_comments' => $this->injuryCommentsByUser($users, $current_week),
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
}
