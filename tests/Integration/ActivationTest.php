<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Activation\CapabilityInstaller;
use LauPerformanceTraining\Activation\DatabaseInstaller;

if (class_exists('WP_UnitTestCase')) {
	final class ActivationTest extends \WP_UnitTestCase
	{
		public function test_database_installer_creates_required_tables(): void
		{
			global $wpdb;

			(new DatabaseInstaller())->install();

			$tables = [
				$wpdb->prefix . 'lpt_schemas',
				$wpdb->prefix . 'lpt_trainings',
				$wpdb->prefix . 'lpt_training_types',
				$wpdb->prefix . 'lpt_training_type_links',
			];

			foreach ($tables as $table) {
				self::assertSame($table, $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)));
			}
		}

		public function test_capability_installer_grants_coach_capabilities(): void
		{
			(new CapabilityInstaller())->install();

			$coach = get_role('coach');

			self::assertNotNull($coach);
			self::assertTrue($coach->has_cap('manage_training_schemas'));
			self::assertTrue($coach->has_cap('view_all_training_schemas'));
			self::assertTrue($coach->has_cap('edit_training_types'));
		}
	}
}
