<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\TrainingRepository;
use PHPUnit\Framework\Assert;

final class MissingSchemaCreationContext extends BaseAcceptanceContext
{
	/**
	 * @When the athlete opens their missing schema for week :weekStartDate
	 */
	public function athleteOpensTheirMissingSchemaForWeek(string $weekStartDate): void
	{
		Assert::assertNull($this->state->schemas->findByUserAndWeek($this->state->athleteUserId, $weekStartDate));
		Assert::assertTrue((new SchemaAccess())->canViewSchema($this->state->currentUserId, $this->state->athleteUserId));

		$this->state->schemaId = $this->state->schemaCreation->createForUserWeek($this->state->athleteUserId, $weekStartDate);
	}

	/**
	 * @When the coach opens the athlete schema editor for week :weekStartDate
	 */
	public function coachOpensTheAthleteSchemaEditorForWeek(string $weekStartDate): void
	{
		Assert::assertNull($this->state->schemas->findByUserAndWeek($this->state->athleteUserId, $weekStartDate));
		Assert::assertTrue((new SchemaAccess())->canUpdateCoachFields($this->state->currentUserId));

		$this->state->schemaId = $this->state->schemaCreation->createForUserWeek($this->state->athleteUserId, $weekStartDate);
	}

	/**
	 * @Then an empty schema with fourteen training slots is created
	 */
	public function emptySchemaWithFourteenTrainingSlotsIsCreated(): void
	{
		$trainings = $this->state->trainings->findBySchema($this->state->schemaId);

		Assert::assertCount(14, $trainings);
		Assert::assertSame(TrainingRepository::TIME_MORNING, $trainings[0]->timeOfDay);
		Assert::assertSame(TrainingRepository::TIME_AFTERNOON, $trainings[1]->timeOfDay);

		foreach ($trainings as $training) {
			Assert::assertSame('', $training->description);
			Assert::assertNull($training->primaryTrainingTypeId);
			Assert::assertNull($training->actualDistance);
			Assert::assertSame('', $training->executionComment);
			Assert::assertSame('', $training->injuryComment);
			Assert::assertSame('', $training->coachComment);
		}
	}
}
