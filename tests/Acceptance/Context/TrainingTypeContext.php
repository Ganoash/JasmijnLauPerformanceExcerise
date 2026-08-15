<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Repositories\TrainingRepository;
use PHPUnit\Framework\Assert;

final class TrainingTypeContext extends BaseAcceptanceContext
{
	/**
	 * @Given a training schema uses an exercise that is now inactive
	 */
	public function trainingSchemaUsesExerciseThatIsNowInactive(): void
	{
		$this->state->athleteUserId = $this->createUser('inactive-athlete');
		$typeId = $this->createTrainingType('Historische oefening', 'running', 'kilometers', false);
		$this->state->schemaId = $this->state->schemaCreation->createForUserWeek($this->state->athleteUserId, '2026-08-17');
		$training = $this->findTraining(0, TrainingRepository::TIME_MORNING);
		$this->state->trainingId = $training->id;

		$this->state->trainings->updateCoachFields(
			$this->state->trainingId,
			[
				'description'              => 'Historische training',
				'primary_training_type_id' => $typeId,
				'coach_comment'            => '',
			]
		);
		$this->state->trainings->replaceLinkedTypes($this->state->trainingId, [$typeId]);
	}

	/**
	 * @When the athlete opens the schema
	 */
	public function athleteOpensTheSchema(): void
	{
		$this->state->currentUserId = $this->state->athleteUserId;
		wp_set_current_user($this->state->currentUserId);

		$typeIds = $this->state->trainings->linkedTypeIds($this->state->trainingId);
		$type = $this->state->trainingTypes->find($typeIds[0] ?? 0);

		Assert::assertInstanceOf(TrainingType::class, $type);
		$this->state->visibleExerciseName = $type->name;
	}

	/**
	 * @Then the inactive exercise is still visible
	 */
	public function inactiveExerciseIsStillVisible(): void
	{
		Assert::assertSame('Historische oefening', $this->state->visibleExerciseName);
	}

	/**
	 * @Then the inactive exercise is not offered for new schema selections
	 */
	public function inactiveExerciseIsNotOfferedForNewSelections(): void
	{
		Assert::assertNotContains('Historische oefening', $this->activeTrainingTypeNames());
	}
}
