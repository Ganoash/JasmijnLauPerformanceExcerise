<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use InvalidArgumentException;
use LauPerformanceTraining\Validation\GoalValidator;
use PHPUnit\Framework\TestCase;

final class GoalValidatorTest extends TestCase
{
	public function test_accepts_valid_goal_fields(): void
	{
		$result = (new GoalValidator())->validate(
			[
				'name'        => 'Dam tot Damloop',
				'description' => 'Testwedstrijd',
				'goal_date'   => '2026-09-09',
				'target_time' => 'Onder 45 minuten',
				'actual_time' => '00:44:21',
				'active'      => '1',
			]
		);

		self::assertSame('Dam tot Damloop', $result['name']);
		self::assertSame('2026-09-09', $result['goal_date']);
		self::assertSame('Onder 45 minuten', $result['target_time']);
		self::assertSame('00:44:21', $result['actual_time']);
		self::assertTrue($result['active']);
	}

	public function test_allows_empty_optional_fields(): void
	{
		$result = (new GoalValidator())->validate(
			[
				'name'      => 'Marathon',
				'goal_date' => '2026-09-09',
			]
		);

		self::assertSame('', $result['description']);
		self::assertSame('', $result['target_time']);
		self::assertSame('', $result['actual_time']);
		self::assertFalse($result['active']);
	}

	public function test_rejects_missing_name(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new GoalValidator())->validate(['goal_date' => '2026-09-09']);
	}

	public function test_rejects_missing_date(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new GoalValidator())->validate(['name' => 'Marathon']);
	}

	public function test_rejects_invalid_date(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new GoalValidator())->validate(['name' => 'Marathon', 'goal_date' => '2026-02-30']);
	}

	public function test_rejects_non_exact_actual_time(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new GoalValidator())->validate(
			[
				'name'        => 'Marathon',
				'goal_date'   => '2026-09-09',
				'actual_time' => '42:15',
			]
		);
	}
}
