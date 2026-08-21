<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use InvalidArgumentException;
use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Services\FrontendFeedbackService;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Validation\DistanceValidator;
use RuntimeException;

if (class_exists('WP_UnitTestCase')) {
	final class FrontendFeedbackServiceTest extends \WP_UnitTestCase
	{
		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();
		}

		public function test_updates_only_allowed_feedback_field(): void
		{
			$user_id     = self::factory()->user->create();
			$schemas     = new SchemaRepository();
			$trainings   = new TrainingRepository();
			$schema_id   = (new SchemaCreationService($schemas, $trainings, new DateFactory()))->createForUserWeek($user_id, '2026-08-17');
			$training    = $trainings->findBySchema($schema_id)[0];
			$service     = new FrontendFeedbackService($trainings, $schemas, new SchemaAccess(static fn (): bool => false), new DistanceValidator());

			$service->updateField($user_id, $training->id, FrontendFeedbackService::FIELD_EXECUTION_COMMENT, 'Ging goed');
			$updated = $trainings->findById($training->id);

			self::assertSame('Ging goed', $updated->executionComment);
			self::assertSame('', $updated->injuryComment);
		}

		public function test_updates_sport_specific_distance_field(): void
		{
			$user_id   = self::factory()->user->create();
			$schemas   = new SchemaRepository();
			$trainings = new TrainingRepository();
			$schema_id = (new SchemaCreationService($schemas, $trainings, new DateFactory()))->createForUserWeek($user_id, '2026-08-17');
			$training  = $trainings->findBySchema($schema_id)[0];
			$service   = new FrontendFeedbackService($trainings, $schemas, new SchemaAccess(static fn (): bool => false), new DistanceValidator());

			$service->updateField($user_id, $training->id, FrontendFeedbackService::FIELD_ACTUAL_CYCLING_DISTANCE, '42.5');
			$updated = $trainings->findById($training->id);

			self::assertSame(42.5, $updated?->actualCyclingDistance);
			self::assertNull($updated?->actualRunningDistance);
			self::assertNull($updated?->actualSwimmingDistance);
		}

		public function test_rejects_unauthorized_schema_access(): void
		{
			$owner_id    = self::factory()->user->create();
			$other_id    = self::factory()->user->create();
			$schemas     = new SchemaRepository();
			$trainings   = new TrainingRepository();
			$schema_id   = (new SchemaCreationService($schemas, $trainings, new DateFactory()))->createForUserWeek($owner_id, '2026-08-17');
			$training    = $trainings->findBySchema($schema_id)[0];
			$service     = new FrontendFeedbackService($trainings, $schemas, new SchemaAccess(static fn (): bool => false), new DistanceValidator());

			$this->expectException(RuntimeException::class);

			$service->updateField($other_id, $training->id, FrontendFeedbackService::FIELD_INJURY_COMMENT, 'Pijn');
		}

		public function test_rejects_view_all_only_user_feedback_updates(): void
		{
			$owner_id = self::factory()->user->create();
			$viewer_id = self::factory()->user->create();
			$schemas = new SchemaRepository();
			$trainings = new TrainingRepository();
			$schema_id = (new SchemaCreationService($schemas, $trainings, new DateFactory()))->createForUserWeek($owner_id, '2026-08-17');
			$training = $trainings->findBySchema($schema_id)[0];
			$service = new FrontendFeedbackService(
				$trainings,
				$schemas,
				new SchemaAccess(static fn (string $capability): bool => $capability === SchemaAccess::CAP_VIEW_ALL),
				new DistanceValidator()
			);

			$this->expectException(RuntimeException::class);

			$service->updateField($viewer_id, $training->id, FrontendFeedbackService::FIELD_EXECUTION_COMMENT, 'Ging goed');
		}

		public function test_rejects_unauthorized_field(): void
		{
			$service = new FrontendFeedbackService(new TrainingRepository(), new SchemaRepository(), new SchemaAccess(), new DistanceValidator());

			$this->expectException(InvalidArgumentException::class);

			$service->updateField(1, 1, 'coach_comment', 'Niet toegestaan');
		}
	}
}
