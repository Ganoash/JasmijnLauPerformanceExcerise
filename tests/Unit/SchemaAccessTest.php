<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use LauPerformanceTraining\Permissions\SchemaAccess;
use PHPUnit\Framework\TestCase;

final class SchemaAccessTest extends TestCase
{
	public function test_owner_can_view_and_update_own_feedback(): void
	{
		$access = new SchemaAccess(static fn (): bool => false);

		self::assertTrue($access->canViewSchema(10, 10));
		self::assertTrue($access->canUpdateFeedback(10, 10));
	}

	public function test_other_user_cannot_view_schema(): void
	{
		$access = new SchemaAccess(static fn (): bool => false);

		self::assertFalse($access->canViewSchema(11, 10));
	}

	public function test_coach_can_view_all_and_update_coach_fields(): void
	{
		$access = new SchemaAccess(
			static fn (string $capability): bool => in_array(
				$capability,
				[SchemaAccess::CAP_VIEW_ALL, SchemaAccess::CAP_MANAGE_SCHEMAS],
				true
			)
		);

		self::assertTrue($access->canViewSchema(11, 10));
		self::assertTrue($access->canUpdateCoachFields(11));
	}

	public function test_anonymous_user_cannot_view_or_update(): void
	{
		$access = new SchemaAccess(static fn (): bool => true);

		self::assertFalse($access->canViewSchema(0, 10));
		self::assertFalse($access->canUpdateCoachFields(0));
	}
}
