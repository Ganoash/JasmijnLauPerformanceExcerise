<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Support\DateFactory;

if (class_exists('WP_UnitTestCase')) {
	final class SchemaEditorServiceTest extends \WP_UnitTestCase
	{
		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();
		}

		public function test_admin_schema_save_preserves_athlete_feedback(): void
		{
			$user_id   = self::factory()->user->create();
			$schemas   = new SchemaRepository();
			$trainings = new TrainingRepository();
			$schema_id = (new SchemaCreationService($schemas, $trainings, new DateFactory()))->createForUserWeek($user_id, '2026-08-17');
			$training  = $trainings->findBySchema($schema_id)[0];

			$trainings->updateFeedbackFields(
				$training->id,
				[
					'actual_distance'   => 8.5,
					'execution_comment' => 'Ging goed',
					'injury_comment'    => 'Geen pijn',
				]
			);

			$service = new SchemaEditorService(
				$trainings,
				new TrainingTypeRepository(),
				new SchemaAccess(static fn (): bool => true)
			);
			$service->saveWeek(
				1,
				[
					[
						'training_id'              => $training->id,
						'description'              => 'Nieuwe training',
						'primary_training_type_id' => null,
						'linked_training_type_ids' => [],
						'coach_comment'            => 'Rustig aan',
					],
				]
			);

			$updated = $trainings->findById($training->id);

			self::assertSame('Nieuwe training', $updated->description);
			self::assertSame(8.5, $updated->actualDistance);
			self::assertSame('Ging goed', $updated->executionComment);
			self::assertSame('Geen pijn', $updated->injuryComment);
		}

		public function test_admin_schema_save_allows_only_one_filled_training_slot(): void
		{
			$user_id        = self::factory()->user->create();
			$schemas        = new SchemaRepository();
			$trainings      = new TrainingRepository();
			$training_types = new TrainingTypeRepository();
			$schema_id      = (new SchemaCreationService($schemas, $trainings, new DateFactory()))->createForUserWeek($user_id, '2026-08-17');
			$slots          = $trainings->findBySchema($schema_id);
			$type_id        = $training_types->create(
				[
					'name'       => 'Duurloop',
					'category'   => 'running',
					'unit'       => 'kilometers',
					'linked_url' => '',
					'active'     => true,
				]
			);
			$strength_id = $training_types->create(
				[
					'name'       => 'Core stability',
					'category'   => 'strength',
					'unit'       => 'sets',
					'linked_url' => '',
					'active'     => true,
				]
			);
			$mobility_id = $training_types->create(
				[
					'name'       => 'Mobiliteit',
					'category'   => 'strength',
					'unit'       => 'sets',
					'linked_url' => '',
					'active'     => true,
				]
			);

			$rows = [];
			foreach ($slots as $index => $slot) {
				$rows[] = [
					'training_id'              => $slot->id,
					'description'              => $index === 0 ? 'Rustige duurloop' : '',
					'primary_training_type_id' => $index === 0 ? $type_id : null,
					'linked_training_type_ids' => $index === 0 ? [$strength_id, $type_id, $mobility_id] : [],
					'coach_comment'            => '',
				];
			}

			$service = new SchemaEditorService(
				$trainings,
				$training_types,
				new SchemaAccess(static fn (): bool => true)
			);
			$service->saveWeek(1, $rows);

			$filled = $trainings->findById($slots[0]->id);
			$empty  = $trainings->findById($slots[1]->id);

			self::assertSame('Rustige duurloop', $filled?->description);
			self::assertSame($type_id, $filled?->primaryTrainingTypeId);
			self::assertSame([$strength_id, $mobility_id], $trainings->linkedTypeIds($slots[0]->id));
			self::assertSame('', $empty?->description);
			self::assertNull($empty?->primaryTrainingTypeId);
		}
	}
}
