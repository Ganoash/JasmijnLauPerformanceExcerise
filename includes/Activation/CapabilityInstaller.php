<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Activation;

final class CapabilityInstaller
{
	private const CAPABILITIES = [
		'manage_training_schemas',
		'view_all_training_schemas',
		'edit_training_types',
	];

	public function install(): void
	{
		add_role('coach', 'Coach', ['read' => true]);

		foreach (['administrator', 'coach'] as $role_name) {
			$role = get_role($role_name);
			if (! $role) {
				continue;
			}

			foreach (self::CAPABILITIES as $capability) {
				$role->add_cap($capability);
			}
		}
	}
}
