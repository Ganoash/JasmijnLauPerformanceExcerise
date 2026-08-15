<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Services\FrontendFeedbackService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Validation\DistanceValidator;
use PHPUnit\Framework\Assert;

final class CoachSchemaEditorContext extends BaseAcceptanceContext
{
	/**
	 * @Given an athlete has filled in feedback for Monday morning
	 */
	public function athleteHasFilledInFeedbackForMondayMorning(): void
	{
		$this->createLoggedInAthleteWithSchema('2026-08-17');

		$service = new FrontendFeedbackService(
			$this->state->trainings,
			$this->state->schemas,
			new SchemaAccess(),
			new DistanceValidator()
		);
		$service->updateField($this->state->currentUserId, $this->state->trainingId, FrontendFeedbackService::FIELD_ACTUAL_DISTANCE, '8.5');
		$service->updateField($this->state->currentUserId, $this->state->trainingId, FrontendFeedbackService::FIELD_EXECUTION_COMMENT, 'Ging goed');
	}

	/**
	 * @Given a coach opens the admin schema editor
	 */
	public function coachOpensTheAdminSchemaEditor(): void
	{
		$this->state->coachUserId = $this->createUser('coach', ['manage_training_schemas']);
		$this->state->currentUserId = $this->state->coachUserId;
		wp_set_current_user($this->state->currentUserId);

		Assert::assertTrue((new SchemaAccess())->canUpdateCoachFields($this->state->currentUserId));
	}

	/**
	 * @When the coach changes the Monday morning training description
	 */
	public function coachChangesMondayMorningDescription(): void
	{
		$service = new SchemaEditorService($this->state->trainings, new SchemaAccess());
		$service->saveWeek(
			$this->state->currentUserId,
			[
				[
					'training_id'              => $this->state->trainingId,
					'description'              => 'Nieuwe training',
					'primary_training_type_id' => null,
					'linked_training_type_ids' => [],
					'coach_comment'            => 'Rustig aan',
				],
			]
		);
	}

	/**
	 * @Then the new training description is saved
	 */
	public function newTrainingDescriptionIsSaved(): void
	{
		$training = $this->state->trainings->findById($this->state->trainingId);

		Assert::assertNotNull($training);
		Assert::assertSame('Nieuwe training', $training->description);
	}

	/**
	 * @Then the athlete feedback remains unchanged
	 */
	public function athleteFeedbackRemainsUnchanged(): void
	{
		$training = $this->state->trainings->findById($this->state->trainingId);

		Assert::assertNotNull($training);
		Assert::assertSame(8.5, $training->actualDistance);
		Assert::assertSame('Ging goed', $training->executionComment);
	}
}
