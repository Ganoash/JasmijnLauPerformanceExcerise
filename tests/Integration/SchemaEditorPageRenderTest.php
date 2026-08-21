<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Admin\SchemaEditorPage;
use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\DateValidator;
use LauPerformanceTraining\Validation\SchemaRequestValidator;

if (class_exists('WP_UnitTestCase')) {
	final class SchemaEditorPageRenderTest extends \WP_UnitTestCase
	{
		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();
		}

		public function test_extra_exercises_render_as_strength_checkboxes(): void
		{
			$user_id = self::factory()->user->create(['display_name' => 'Schema Athlete']);
			wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

			$training_types = new TrainingTypeRepository();
			$running_id     = $this->createTrainingType($training_types, 'Duurloop', 'running');
			$strength_id    = $this->createTrainingType($training_types, 'Core stability', 'strength');
			$mobility_id    = $this->createTrainingType($training_types, 'Mobiliteit', 'strength');

			$_GET['user_id'] = (string) $user_id;
			$_GET['week_start_date'] = '2026-08-17';

			ob_start();
			$this->schemaEditorPage($training_types)->render();
			$html = (string) ob_get_clean();

			self::assertStringNotContainsString('<select multiple', $html);
			self::assertMatchesRegularExpression($this->extraExerciseCheckboxPattern($strength_id), $html);
			self::assertMatchesRegularExpression($this->extraExerciseCheckboxPattern($mobility_id), $html);
			self::assertDoesNotMatchRegularExpression($this->extraExerciseCheckboxPattern($running_id), $html);
			self::assertStringContainsString('Selecteer een of meerdere krachtoefeningen', $html);
		}

		public function test_invalid_week_date_renders_admin_notice(): void
		{
			$user_id = self::factory()->user->create(['display_name' => 'Schema Athlete']);
			wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

			$_GET['user_id'] = (string) $user_id;
			$_GET['week_start_date'] = '2026-02-30';

			ob_start();
			$this->schemaEditorPage(new TrainingTypeRepository())->render();
			$html = (string) ob_get_clean();

			self::assertStringContainsString('Ongeldige datum.', $html);
			self::assertStringContainsString('Schema Athlete', $html);
		}

		private function schemaEditorPage(TrainingTypeRepository $training_types): SchemaEditorPage
		{
			$schemas = new SchemaRepository();
			$trainings = new TrainingRepository();
			$date_factory = new DateFactory();
			$schema_creation = new SchemaCreationService($schemas, $trainings, $date_factory);

			return new SchemaEditorPage(
				$schemas,
				$trainings,
				$training_types,
				$schema_creation,
				new SchemaEditorService($trainings, $training_types, new SchemaAccess()),
				new SchemaRequestValidator(),
				new DateValidator(),
				$date_factory,
				new Nonce()
			);
		}

		private function createTrainingType(
			TrainingTypeRepository $training_types,
			string $name,
			string $category
		): int {
			return $training_types->create(
				[
					'name'       => $name,
					'category'   => $category,
					'unit'       => 'kilometers',
                    'color'      => '#ffffff',
					'linked_url' => '',
					'active'     => true,
				]
			);
		}

		private function extraExerciseCheckboxPattern(int $training_type_id): string
		{
			return sprintf(
				'/<input[^>]+type="checkbox"[^>]+name="trainings\\[0\\]\\[linked_training_type_ids\\]\\[\\]"[^>]+value="%d"/s',
				$training_type_id
			);
		}
	}
}
