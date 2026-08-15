<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use LauPerformanceTraining\Activation\Activator;

final class BootstrapContext extends BaseAcceptanceContext
{
	public function __construct()
	{
		parent::__construct();
		Activator::activate();
	}

	/**
	 * @AfterScenario
	 */
	public function cleanUpScenario(): void
	{
		global $wpdb;

		foreach ($this->state->createdUserIds as $userId) {
			$this->state->schemas->deleteByUser($userId);
			wp_delete_user($userId);
		}

		foreach ($this->state->createdTrainingTypeIds as $typeId) {
			$wpdb->delete($wpdb->prefix . 'lpt_training_type_links', ['training_type_id' => $typeId], ['%d']);
			$wpdb->delete($wpdb->prefix . 'lpt_training_types', ['id' => $typeId], ['%d']);
		}

		wp_set_current_user(0);
		$this->state->resetScenarioState();
	}
}
