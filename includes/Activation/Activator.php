<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Activation;

final class Activator
{
	public static function activate(): void
	{
		(new DatabaseInstaller())->install();
		(new CapabilityInstaller())->install();

		if (! wp_next_scheduled('lpt_create_training_schemas')) {
			wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'lpt_create_training_schemas');
		}

		update_option('lpt_flush_rewrite_rules', '1', false);
	}

	public static function deactivate(): void
	{
		wp_clear_scheduled_hook('lpt_create_training_schemas');
		update_option('lpt_flush_rewrite_rules', '1', false);
	}
}
