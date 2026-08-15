<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Services\FrontendFeedbackService;
use LauPerformanceTraining\Validation\DistanceValidator;
use PHPUnit\Framework\Assert;

final class FrontendCoachFeedbackContext extends BaseAcceptanceContext
{
	/**
	 * @Given an athlete has a schema for week :weekStartDate
	 */
	public function athleteHasSchemaForWeek(string $weekStartDate): void
	{
		$this->state->athleteUserId = $this->createUser('athlete');
		$this->state->schemaId = $this->state->schemaCreation->createForUserWeek($this->state->athleteUserId, $weekStartDate);
		$this->state->trainingId = $this->findTraining(0, TrainingRepository::TIME_MORNING)->id;
	}

	/**
	 * @When the coach opens the athlete schema URL
	 */
	public function coachOpensTheAthleteSchemaUrl(): void
	{
		$this->state->accessDenied = ! (new SchemaAccess())->canViewSchema(
			$this->state->currentUserId,
			$this->state->athleteUserId
		);
	}

	/**
	 * @When saves :comment as the injury comment for Monday morning
	 */
	public function savesInjuryCommentForMondayMorning(string $comment): void
	{
		$service = new FrontendFeedbackService(
			$this->state->trainings,
			$this->state->schemas,
			new SchemaAccess(),
			new DistanceValidator()
		);
		$service->updateField(
			$this->state->currentUserId,
			$this->state->trainingId,
			FrontendFeedbackService::FIELD_INJURY_COMMENT,
			$comment
		);

		$this->state->frontendFeedbackValue = $comment;
	}

	/**
	 * @Then access is allowed
	 */
	public function accessIsAllowed(): void
	{
		Assert::assertFalse($this->state->accessDenied);
	}

	/**
	 * @Then the injury comment is saved
	 */
	public function injuryCommentIsSaved(): void
	{
		$training = $this->state->trainings->findById($this->state->trainingId);

		Assert::assertNotNull($training);
		Assert::assertSame($this->state->frontendFeedbackValue, $training->injuryComment);
	}
}
