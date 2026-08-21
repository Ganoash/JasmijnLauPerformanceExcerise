<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Services\FrontendFeedbackService;
use LauPerformanceTraining\Validation\DistanceValidator;
use PHPUnit\Framework\Assert;

final class AthleteSchemaContext extends BaseAcceptanceContext
{
	/**
	 * @Given a logged-in athlete has a schema for week :weekStartDate
	 */
	public function loggedInAthleteHasSchemaForWeek(string $weekStartDate): void
	{
		$this->createLoggedInAthleteWithSchema($weekStartDate);
	}

	/**
	 * @When the athlete opens their schema
	 */
	public function athleteOpensTheirSchema(): void
	{
		$schema = $this->state->schemas->findById($this->state->schemaId);
		Assert::assertNotNull($schema);
		Assert::assertTrue((new SchemaAccess())->canViewSchema($this->state->currentUserId, $schema->userId));
	}

	/**
	 * @When enters :distance as the actual distance for Monday morning
	 */
	public function entersActualDistanceForMondayMorning(string $distance): void
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
			FrontendFeedbackService::FIELD_ACTUAL_RUNNING_DISTANCE,
			$distance
		);

		$this->state->savedField = FrontendFeedbackService::FIELD_ACTUAL_RUNNING_DISTANCE;
		$this->state->latestRunningTotal = $this->runningTotal();
	}

	/**
	 * @When enters :comment as uitvoering
	 */
	public function entersUitvoering(string $comment): void
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
			FrontendFeedbackService::FIELD_EXECUTION_COMMENT,
			$comment
		);

		$this->state->savedField = FrontendFeedbackService::FIELD_EXECUTION_COMMENT;
	}

	/**
	 * @Then the field is saved
	 */
	public function fieldIsSaved(): void
	{
		$training = $this->state->trainings->findById($this->state->trainingId);

		Assert::assertNotNull($training);
		Assert::assertNotSame('', $this->state->savedField);
		Assert::assertSame('Ging goed', $training->executionComment);
	}

	/**
	 * @Then the running total updates immediately on the page
	 */
	public function runningTotalUpdatesImmediately(): void
	{
		Assert::assertSame(0.0, $this->state->previousRunningTotal);
		Assert::assertSame(8.5, $this->state->latestRunningTotal);
	}
}
