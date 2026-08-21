<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use Behat\Behat\Context\Context;
use LauPerformanceTraining\Activation\Activator;
use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Services\DistanceTotalService;
use LauPerformanceTraining\Tests\Acceptance\Support\AcceptanceState;
use PHPUnit\Framework\Assert;

abstract class BaseAcceptanceContext implements Context
{
	protected AcceptanceState $state;

	public function __construct()
	{
		$this->state = AcceptanceState::instance();
		$this->bootWordPress();
	}

	protected function createLoggedInAthleteWithSchema(string $weekStartDate): void
	{
		$this->state->athleteUserId = $this->createUser('athlete');
		$this->state->currentUserId = $this->state->athleteUserId;
		wp_set_current_user($this->state->currentUserId);

		$typeId = $this->createTrainingType('Duurloop', 'running', 'kilometers', true);
		$this->state->schemaId = $this->state->schemaCreation->createForUserWeek($this->state->athleteUserId, $weekStartDate);
		$training = $this->findTraining(0, TrainingRepository::TIME_MORNING);
		$this->state->trainingId = $training->id;
		$this->state->trainings->updateCoachFields(
			$this->state->trainingId,
			[
				'description'              => 'Rustige duurloop',
				'primary_training_type_id' => $typeId,
				'coach_comment'            => '',
			]
		);

		$this->state->previousRunningTotal = $this->runningTotal();
	}

	protected function bootWordPress(): void
	{
		if (! defined('WP_USE_THEMES')) {
			define('WP_USE_THEMES', false);
		}

		if (! defined('ABSPATH')) {
			require getenv('LPT_WORDPRESS_BOOTSTRAP') ?: '/var/www/html/wp-load.php';
		}

		if (! class_exists(Activator::class)) {
			require dirname(__DIR__, 3) . '/lau-performance-training.php';
		}

		if (! function_exists('wp_delete_user')) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
	}

	/**
	 * @param string[] $capabilities
	 */
	protected function createUser(string $label, array $capabilities = []): int
	{
		$userId = wp_insert_user(
			[
				'user_login' => 'lpt_behat_' . $label . '_' . uniqid(),
				'user_pass'  => wp_generate_password(24),
				'user_email' => 'lpt-behat-' . $label . '-' . uniqid() . '@example.test',
				'role'       => 'subscriber',
			]
		);

		Assert::assertIsInt($userId);
		$this->state->createdUserIds[] = $userId;

		$user = get_user_by('id', $userId);
		Assert::assertInstanceOf(\WP_User::class, $user);
		foreach ($capabilities as $capability) {
			$user->add_cap($capability);
		}

		return $userId;
	}

	protected function createTrainingType(string $name, string $category, string $unit, bool $active): int
	{
		$typeId = $this->state->trainingTypes->create(
			[
				'name'       => $name,
				'category'   => $category,
				'unit'       => $unit,
				'color'      => '#ffffff',
				'linked_url' => '',
				'active'     => $active,
			]
		);

		$this->state->createdTrainingTypeIds[] = $typeId;

		return $typeId;
	}

	protected function findTraining(int $dayIndex, string $timeOfDay): Training
	{
		$training = $this->state->trainings->findBySlot($this->state->schemaId, $dayIndex, $timeOfDay);

		Assert::assertInstanceOf(Training::class, $training);

		return $training;
	}

	protected function runningTotal(): float
	{
		$trainings = $this->state->trainings->findBySchema($this->state->schemaId);
		$primaryTypes = [];
		foreach ($trainings as $training) {
			$primaryTypes[$training->id] = $training->primaryTrainingTypeId
				? $this->state->trainingTypes->find($training->primaryTrainingTypeId)
				: null;
		}

		return (new DistanceTotalService())->calculate($trainings, $primaryTypes)->runningKm;
	}

	/**
	 * @return string[]
	 */
	protected function activeTrainingTypeNames(): array
	{
		return array_map(
			static fn (TrainingType $type): string => $type->name,
			$this->state->trainingTypes->all(true)
		);
	}
}
