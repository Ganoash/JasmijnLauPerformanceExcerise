<?php
declare(strict_types=1);

namespace LauPerformanceTraining;

final class Plugin
{
	public function register(): void
	{
		add_action(
			'init',
			static function (): void {
				if ((bool) get_option('lpt_flush_rewrite_rules', false)) {
					flush_rewrite_rules(false);
					delete_option('lpt_flush_rewrite_rules');
				}
			},
			20
		);
	}
}
