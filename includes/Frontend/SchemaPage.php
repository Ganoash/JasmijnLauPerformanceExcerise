<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Frontend;

use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Support\View;

final class SchemaPage
{
	public function __construct(
		private readonly SchemaRepository $schemas,
		private readonly TrainingRepository $trainings,
		private readonly TrainingTypeRepository $training_types,
		private readonly SchemaCreationService $schema_creation_service,
		private readonly SchemaAccess $access
	) {
	}

	public function render(int $user_id, string $week_start_date): void
	{
		if (! is_user_logged_in()) {
			auth_redirect();
		}

		$current_user_id = get_current_user_id();
		if (! $this->access->canViewSchema($current_user_id, $user_id)) {
			status_header(403);
			wp_die(esc_html__('Je hebt geen toegang tot dit schema.', 'lau-performance-training'));
		}

		$week      = Week::fromDateString($week_start_date);
		$schema_id = $this->schema_creation_service->createForUserWeek($user_id, $week);
		$schema    = $this->schemas->findById($schema_id);
		$user      = get_user_by('id', $user_id);

		if (! $schema || ! $user) {
			status_header(404);
			wp_die(esc_html__('Schema niet gevonden.', 'lau-performance-training'));
		}

		wp_enqueue_style(
			'lpt-schema-view',
			LPT_PLUGIN_URL . 'assets/frontend/schema-view.css',
			[],
			LPT_VERSION
		);

		get_header();
		View::render(
			'frontend/schema-page.php',
			[
				'linked_types' => $this->linkedTypesByTraining($schema_id),
				'primary_types' => $this->primaryTypesByTraining($schema_id),
				'schema'       => $schema,
				'trainings'    => $this->trainings->findBySchema($schema_id),
				'user'         => $user,
				'week'         => $week,
			]
		);
		get_footer();
	}

	/**
	 * @return array<int,TrainingType|null>
	 */
	private function primaryTypesByTraining(int $schema_id): array
	{
		$map = [];
		foreach ($this->trainings->findBySchema($schema_id) as $training) {
			$map[$training->id] = $training->primaryTrainingTypeId
				? $this->training_types->find($training->primaryTrainingTypeId)
				: null;
		}

		return $map;
	}

	/**
	 * @return array<int,TrainingType[]>
	 */
	private function linkedTypesByTraining(int $schema_id): array
	{
		$map = [];
		foreach ($this->trainings->findBySchema($schema_id) as $training) {
			$map[$training->id] = [];
			foreach ($this->trainings->linkedTypeIds($training->id) as $type_id) {
				$type = $this->training_types->find($type_id);
				if ($type instanceof TrainingType) {
					$map[$training->id][] = $type;
				}
			}
		}

		return $map;
	}
}
