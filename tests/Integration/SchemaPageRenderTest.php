<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Frontend\SchemaPage;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\DistanceTotalService;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\DateValidator;

if (class_exists('WP_UnitTestCase')) {
	final class SchemaPageRenderTest extends \WP_UnitTestCase
	{
		private string $previousTheme = '';

		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();
			$this->previousTheme = get_stylesheet();
		}

		public function tear_down(): void
		{
			if ($this->previousTheme !== '' && get_stylesheet() !== $this->previousTheme) {
				switch_theme($this->previousTheme);
			}

			parent::tear_down();
		}

		public function test_schema_page_renders_block_theme_header_and_footer_without_legacy_theme_file_deprecations(): void
		{
			if (! wp_get_theme('twentytwentyfive')->exists()) {
				self::markTestSkipped('Twenty Twenty-Five is required for block theme rendering coverage.');
			}

			switch_theme('twentytwentyfive');

			$user_id = self::factory()->user->create(['display_name' => 'Schema Athlete']);
			wp_set_current_user($user_id);

			$deprecations = [];
			set_error_handler(
				static function (int $errno, string $errstr) use (&$deprecations): bool {
					if (
						in_array($errno, [E_DEPRECATED, E_USER_DEPRECATED], true)
						&& str_contains($errstr, 'File Theme without')
					) {
						$deprecations[] = $errstr;
						return true;
					}

					return false;
				}
			);

			ob_start();
			$this->schemaPage()->render($user_id, '2026-08-10');
			$html = (string) ob_get_clean();
			restore_error_handler();

			self::assertSame([], $deprecations);
			self::assertStringContainsString('<body', $html);
			self::assertStringContainsString('wp-site-blocks', $html);
			self::assertStringContainsString('lpt-schema-page', $html);
			self::assertStringContainsString('Schema Athlete', $html);
			self::assertStringContainsString('Week 33', $html);
		}

		private function schemaPage(): SchemaPage
		{
			$schemas = new SchemaRepository();
			$trainings = new TrainingRepository();

			return new SchemaPage(
				$schemas,
				$trainings,
				new TrainingTypeRepository(),
				new SchemaCreationService($schemas, $trainings, new DateFactory()),
				new SchemaAccess(),
				new DistanceTotalService(),
				new DateValidator(),
				new Nonce()
			);
		}
	}
}
