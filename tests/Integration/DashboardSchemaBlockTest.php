<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Blocks\DashboardSchemaBlock;
use LauPerformanceTraining\Support\DateFactory;

if (class_exists('WP_UnitTestCase')) {
	final class DashboardSchemaBlockTest extends \WP_UnitTestCase
	{
		public function test_block_renders_only_for_logged_in_users(): void
		{
			$block = new DashboardSchemaBlock(new DateFactory());

			wp_set_current_user(0);
			self::assertSame('', $block->render());

			wp_set_current_user(self::factory()->user->create());
			self::assertStringContainsString('/training-schema/', $block->render());
		}
	}
}
