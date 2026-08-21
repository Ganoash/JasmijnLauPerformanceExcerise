<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use InvalidArgumentException;
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
					'actual_running_distance'  => 8.5,
					'actual_cycling_distance'  => null,
					'actual_swimming_distance' => null,
					'execution_comment'        => 'Ging goed',
					'injury_comment'           => 'Geen pijn',
				]
			);

			$service = new SchemaEditorService(
				$trainings,
				new TrainingTypeRepository(),
				new SchemaAccess(static fn (): bool => true)
			);
			$service->saveWeek(
				1,
				$schema_id,
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
			self::assertSame(8.5, $updated->actualRunningDistance);
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
                    'color'      => '#ffffff',
					'linked_url' => '',
					'active'     => true,
				]
			);
			$strength_id = $training_types->create(
				[
					'name'       => 'Core stability',
					'category'   => 'strength',
					'unit'       => 'sets',
                    'color'      => '#ffffff',
					'linked_url' => '',
					'active'     => true,
				]
			);
			$mobility_id = $training_types->create(
				[
					'name'       => 'Mobiliteit',
					'category'   => 'strength',
					'unit'       => 'sets',
                    'color'      => '#ffffff',
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
			$service->saveWeek(1, $schema_id, $rows);

			$filled = $trainings->findById($slots[0]->id);
			$empty  = $trainings->findById($slots[1]->id);

			self::assertSame('Rustige duurloop', $filled?->description);
			self::assertSame($type_id, $filled?->primaryTrainingTypeId);
			self::assertSame([$type_id, $strength_id, $mobility_id], $trainings->linkedTypeIds($slots[0]->id));
			self::assertSame('', $empty?->description);
			self::assertNull($empty?->primaryTrainingTypeId);
		}

		public function test_rejects_training_rows_from_another_schema(): void
		{
			$user_a_id = self::factory()->user->create();
			$user_b_id = self::factory()->user->create();
			$schemas = new SchemaRepository();
			$trainings = new TrainingRepository();
			$schema_creation = new SchemaCreationService($schemas, $trainings, new DateFactory());
			$schema_a_id = $schema_creation->createForUserWeek($user_a_id, '2026-08-17');
			$schema_b_id = $schema_creation->createForUserWeek($user_b_id, '2026-08-17');
			$foreign_training = $trainings->findBySchema($schema_b_id)[0];

			$service = new SchemaEditorService(
				$trainings,
				new TrainingTypeRepository(),
				new SchemaAccess(static fn (): bool => true)
			);

			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessage('Training hoort niet bij dit schema.');

			$service->saveWeek(
				1,
				$schema_a_id,
				[
					[
						'training_id'              => $foreign_training->id,
						'description'              => 'Verkeerd schema',
						'primary_training_type_id' => null,
						'linked_training_type_ids' => [],
						'coach_comment'            => '',
					],
				]
			);
		}

		public function test_rejects_inactive_primary_training_type_that_is_not_already_used(): void
		{
			$user_id = self::factory()->user->create();
			$schemas = new SchemaRepository();
			$trainings = new TrainingRepository();
			$training_types = new TrainingTypeRepository();
			$schema_id = (new SchemaCreationService($schemas, $trainings, new DateFactory()))->createForUserWeek($user_id, '2026-08-17');
			$training = $trainings->findBySchema($schema_id)[0];
			$inactive_type_id = $training_types->create(
				[
					'name'       => 'Verborgen training',
					'category'   => 'running',
					'unit'       => 'kilometers',
                    'color'      => '#ffffff',
					'linked_url' => '',
					'active'     => false,
				]
			);

			$service = new SchemaEditorService(
				$trainings,
				$training_types,
				new SchemaAccess(static fn (): bool => true)
			);

			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessage('Ongeldige primaire oefening.');

			$service->saveWeek(
				1,
				$schema_id,
				[
					[
						'training_id'              => $training->id,
						'description'              => 'Training',
						'primary_training_type_id' => $inactive_type_id,
						'linked_training_type_ids' => [],
						'coach_comment'            => '',
					],
				]
			);
		}
	}
}
