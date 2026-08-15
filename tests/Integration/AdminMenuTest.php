<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Admin\AdminMenu;
use LauPerformanceTraining\Admin\SchemaEditorPage;
use LauPerformanceTraining\Admin\TrainingTypePage;
use LauPerformanceTraining\Admin\UserOverviewPage;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\SchemaRequestValidator;
use LauPerformanceTraining\Validation\TrainingTypeValidator;

if (class_exists('WP_UnitTestCase')) {
	final class AdminMenuTest extends \WP_UnitTestCase
	{
		public function test_menu_shows_only_schema_overview_and_training_type_submenus(): void
		{
			global $menu, $submenu, $_registered_pages;

			$menu = [];
			$submenu = [];
			$_registered_pages = [];
			wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

			$this->adminMenu()->registerMenus();

			$visible = array_map(
				static fn (array $item): array => [$item[0], $item[2]],
				$submenu['lpt-training'] ?? []
			);

			self::assertSame(
				[
					['Schema’s bewerken', 'lpt-training'],
					['Oefeningen', 'lpt-training-types'],
				],
				$visible
			);
			self::assertNotContains('lpt-schema-editor', array_column($visible, 1));
			self::assertArrayHasKey(
				get_plugin_page_hookname('lpt-schema-editor', 'lpt-training'),
				$_registered_pages
			);
		}

		private function adminMenu(): AdminMenu
		{
			$schemas = new SchemaRepository();
			$trainings = new TrainingRepository();
			$trainingTypes = new TrainingTypeRepository();
			$dateFactory = new DateFactory();
			$schemaCreation = new SchemaCreationService($schemas, $trainings, $dateFactory);

			return new AdminMenu(
				new UserOverviewPage($dateFactory),
				new SchemaEditorPage(
					$schemas,
					$trainings,
					$trainingTypes,
					$schemaCreation,
					new SchemaEditorService($trainings, new SchemaAccess()),
					new SchemaRequestValidator(),
					$dateFactory,
					new Nonce()
				),
				new TrainingTypePage(
					$trainingTypes,
					new TrainingTypeValidator(),
					new Nonce()
				)
			);
		}
	}
}
