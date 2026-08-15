<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Support\DateFactory;

if (class_exists('WP_UnitTestCase')) {
	final class SchemaCreationServiceTest extends \WP_UnitTestCase
	{
		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();
		}

		public function test_schema_creation_creates_fourteen_trainings(): void
		{
			$user_id   = self::factory()->user->create();
			$schemas   = new SchemaRepository();
			$trainings = new TrainingRepository();
			$service   = new SchemaCreationService($schemas, $trainings, new DateFactory());

			$schema_id = $service->createForUserWeek($user_id, '2026-08-17');

			self::assertCount(14, $trainings->findBySchema($schema_id));
		}

		public function test_duplicate_schema_creation_is_idempotent(): void
		{
			$user_id   = self::factory()->user->create();
			$schemas   = new SchemaRepository();
			$trainings = new TrainingRepository();
			$service   = new SchemaCreationService($schemas, $trainings, new DateFactory());

			$first_id  = $service->createForUserWeek($user_id, '2026-08-17');
			$second_id = $service->createForUserWeek($user_id, '2026-08-17');

			self::assertSame($first_id, $second_id);
			self::assertCount(14, $trainings->findBySchema($first_id));
		}

		public function test_deleting_user_deletes_their_schemas(): void
		{
			$user_id = self::factory()->user->create();
			$schemas = new SchemaRepository();
			$service = new SchemaCreationService($schemas, new TrainingRepository(), new DateFactory());

			$service->createForUserWeek($user_id, '2026-08-17');
			$schemas->deleteByUser($user_id);

			self::assertSame([], $schemas->findByUser($user_id));
		}
	}
}
