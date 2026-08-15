<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use LauPerformanceTraining\Blocks\DashboardSchemaBlock;
use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Support\DateFactory;
use PHPUnit\Framework\Assert;

final class DashboardSchemaBlockContext extends BaseAcceptanceContext
{
	/**
	 * @When the dashboard schema block is rendered
	 */
	public function dashboardSchemaBlockIsRendered(): void
	{
		$this->state->dashboardHtml = (new DashboardSchemaBlock(new DateFactory()))->render();
	}

	/**
	 * @Then the block links to last, current, next, and two-weeks-ahead schemas
	 */
	public function blockLinksToLastCurrentNextAndTwoWeeksAheadSchemas(): void
	{
		$current = Week::fromDate((new DateFactory())->now());
		$weeks   = [
			$current->plusWeeks(-1),
			$current,
			$current->plusWeeks(1),
			$current->plusWeeks(2),
		];

		foreach ($weeks as $week) {
			Assert::assertStringContainsString(
				home_url('/training-schema/' . $this->state->athleteUserId . '/' . $week->startDate() . '/'),
				$this->state->dashboardHtml
			);
		}

		Assert::assertSame(4, substr_count($this->state->dashboardHtml, '/training-schema/'));
	}

	/**
	 * @Then no dashboard schema links are shown
	 */
	public function noDashboardSchemaLinksAreShown(): void
	{
		Assert::assertSame('', $this->state->dashboardHtml);
	}
}
