<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use LauPerformanceTraining\Permissions\GoalAccess;
use LauPerformanceTraining\Permissions\SchemaAccess;
use PHPUnit\Framework\TestCase;

final class GoalAccessTest extends TestCase
{
	public function test_owner_can_manage_own_goals(): void
	{
		$access = new GoalAccess(static fn (): bool => false);

		self::assertTrue($access->canManageGoalUser(10, 10));
	}

	public function test_coach_can_manage_other_user_goals(): void
	{
		$access = new GoalAccess(static fn (string $capability): bool => $capability === SchemaAccess::CAP_MANAGE_SCHEMAS);

		self::assertTrue($access->canManageGoalUser(11, 10));
	}

	public function test_other_regular_user_cannot_manage_goals(): void
	{
		$access = new GoalAccess(static fn (): bool => false);

		self::assertFalse($access->canManageGoalUser(11, 10));
	}
}
