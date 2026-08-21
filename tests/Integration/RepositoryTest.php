<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;

if (class_exists('WP_UnitTestCase')) {
	final class RepositoryTest extends \WP_UnitTestCase
	{
		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();
		}

		public function test_schema_repository_creates_and_reads_schema(): void
		{
			$user_id = self::factory()->user->create();
			$repo    = new SchemaRepository();

			$schema_id = $repo->create($user_id, '2026-08-17');
			$schema    = $repo->findByUserAndWeek($user_id, '2026-08-17');

			self::assertGreaterThan(0, $schema_id);
			self::assertNotNull($schema);
			self::assertSame($user_id, $schema->userId);
		}

		public function test_training_repository_creates_fixed_slot(): void
		{
			$user_id    = self::factory()->user->create();
			$schema_id  = (new SchemaRepository())->create($user_id, '2026-08-17');
			$repository = new TrainingRepository();

			$training_id = $repository->createSlot($schema_id, 0, TrainingRepository::TIME_MORNING);
			$training    = $repository->findBySlot($schema_id, 0, TrainingRepository::TIME_MORNING);

			self::assertGreaterThan(0, $training_id);
			self::assertNotNull($training);
			self::assertSame(0, $training->dayIndex);
		}

		public function test_ensuring_visible_slots_does_not_delete_hidden_trainings(): void
		{
			$user_id    = self::factory()->user->create();
			$schema_id  = (new SchemaRepository())->create($user_id, '2026-08-17');
			$repository = new TrainingRepository();

			$repository->ensureSchemaSlots($schema_id, TrainingRepository::fixedSlots());
			$repository->ensureSchemaSlots($schema_id, TrainingRepository::fixedSlots(1));

			self::assertCount(14, $repository->findBySchema($schema_id));
			self::assertNotNull($repository->findBySlot($schema_id, 0, TrainingRepository::TIME_MORNING));
			self::assertNotNull($repository->findBySlot($schema_id, 0, TrainingRepository::TIME_AFTERNOON));
		}

		public function test_training_type_repository_keeps_inactive_types_queryable(): void
		{
			$repository = new TrainingTypeRepository();

			$type_id = $repository->create(
				[
					'name'       => 'Zwemmen rustig',
					'category'   => 'swimming',
					'unit'       => 'meters',
					'color'      => '#ffffff',
					'linked_url' => 'https://example.test/zwemmen',
					'active'     => false,
				]
			);

			self::assertNotNull($repository->find($type_id));
			self::assertCount(0, $repository->all(true));
		}

        public function test_training_type_serializes_color_correctly(): void
        {
            $repository = new TrainingTypeRepository();

			$type_id = $repository->create(
				[
					'name'       => 'Zwemmen rustig',
					'category'   => 'swimming',
					'unit'       => 'meters',
					'color'      => '#ffffff',
					'linked_url' => 'https://example.test/zwemmen',
					'active'     => true,
				]
			);

			self::assertSame($repository->find($type_id)->color,'#ffffff');
        }
	}
}
