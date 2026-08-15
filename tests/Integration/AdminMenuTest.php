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

			$admin_menu = $this->adminMenu();
			$admin_menu->registerMenus();
			$admin_menu->hideInternalSchemaEditorSubmenu();

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

		public function test_schema_editor_page_stays_accessible_until_wordpress_authorizes_the_request(): void
		{
			global $menu, $submenu, $_registered_pages, $pagenow, $plugin_page, $parent_file;

			$menu = [];
			$submenu = [];
			$_registered_pages = [];
			$pagenow = 'admin.php';
			$plugin_page = 'lpt-schema-editor';
			$parent_file = '';
			wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

			$admin_menu = $this->adminMenu();
			$admin_menu->registerMenus();

			self::assertContains('lpt-schema-editor', array_column($submenu['lpt-training'] ?? [], 2));
			self::assertTrue(user_can_access_admin_page());

			$admin_menu->hideInternalSchemaEditorSubmenu();
			self::assertNotContains('lpt-schema-editor', array_column($submenu['lpt-training'] ?? [], 2));
		}

		public function test_schema_editor_save_action_is_registered(): void
		{
			$this->adminMenu()->register();

			self::assertNotFalse(has_action('admin_post_lpt_save_schema'));
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
