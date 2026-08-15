<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use LauPerformanceTraining\Permissions\SchemaAccess;
use PHPUnit\Framework\Assert;

final class SchemaPrivacyContext extends BaseAcceptanceContext
{
	/**
	 * @Given athlete A has a schema for week :weekStartDate
	 */
	public function athleteAHasSchemaForWeek(string $weekStartDate): void
	{
		$this->state->athleteAUserId = $this->createUser('athlete-a');
		$this->state->schemaId = $this->state->schemaCreation->createForUserWeek($this->state->athleteAUserId, $weekStartDate);
	}

	/**
	 * @Given athlete B is logged in
	 */
	public function athleteBIsLoggedIn(): void
	{
		$this->state->athleteBUserId = $this->createUser('athlete-b');
		$this->state->currentUserId = $this->state->athleteBUserId;
		wp_set_current_user($this->state->currentUserId);
	}

	/**
	 * @When athlete B opens athlete A's schema URL
	 */
	public function athleteBOpensAthleteASchemaUrl(): void
	{
		$this->state->accessDenied = ! (new SchemaAccess())->canViewSchema(
			$this->state->athleteBUserId,
			$this->state->athleteAUserId
		);
	}

	/**
	 * @Then access is denied
	 */
	public function accessIsDenied(): void
	{
		Assert::assertTrue($this->state->accessDenied);
	}
}
