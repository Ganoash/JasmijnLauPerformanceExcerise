<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Admin\UserOverviewPage;
use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;

if (class_exists('WP_UnitTestCase')) {
	final class UserOverviewPageRenderTest extends \WP_UnitTestCase
	{
		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();
		}

		public function test_renders_current_and_last_week_injury_comments_for_coach_overview(): void
		{
			$athlete_id = self::factory()->user->create(['display_name' => 'Blessure Atleet']);
			wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

			$schemas           = new SchemaRepository();
			$trainings         = new TrainingRepository();
			$date_factory      = new DateFactory();
			$current_week      = Week::fromDate($date_factory->now());
			$last_week         = $current_week->plusWeeks(-1);
			$schema_creation   = new SchemaCreationService($schemas, $trainings, $date_factory);
			$current_schema_id = $schema_creation->createForUserWeek($athlete_id, $current_week);
			$last_schema_id    = $schema_creation->createForUserWeek($athlete_id, $last_week);
			$current_training  = $trainings->findBySchema($current_schema_id)[0];
			$last_training     = $trainings->findBySchema($last_schema_id)[0];

			$trainings->updateFeedbackFields(
				$current_training->id,
				[
					'actual_running_distance'  => null,
					'actual_cycling_distance'  => null,
					'actual_swimming_distance' => null,
					'execution_comment'        => '',
					'injury_comment'           => 'Knie zeurt',
				]
			);
			$trainings->updateFeedbackFields(
				$last_training->id,
				[
					'actual_running_distance'  => null,
					'actual_cycling_distance'  => null,
					'actual_swimming_distance' => null,
					'execution_comment'        => '',
					'injury_comment'           => 'Enkel stijf',
				]
			);

			ob_start();
			(new UserOverviewPage($date_factory, null, new Nonce(), $schemas, $trainings))->render();
			$html = (string) ob_get_clean();

			self::assertStringContainsString('Klachten deze week', $html);
			self::assertStringContainsString('Klachten vorige week', $html);
			self::assertStringContainsString('Blessure Atleet', $html);
			self::assertStringContainsString('Knie zeurt', $html);
			self::assertStringContainsString('Enkel stijf', $html);
		}
	}
}
