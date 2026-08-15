<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use LauPerformanceTraining\Domain\Week;
use PHPUnit\Framework\TestCase;

final class WeekTest extends TestCase
{
	public function test_calculates_monday_start_date_in_amsterdam_timezone(): void
	{
		$date = new DateTimeImmutable('2026-08-20 23:30:00', new DateTimeZone('UTC'));
		$week = Week::fromDate($date);

		self::assertSame('2026-08-17', $week->startDate());
		self::assertSame('2026-08-23', $week->endDate());
		self::assertSame(34, $week->isoWeekNumber());
	}

	public function test_handles_sunday_as_same_week(): void
	{
		$week = Week::fromDateString('2026-08-23');

		self::assertSame('2026-08-17', $week->startDate());
	}

	public function test_can_move_between_weeks(): void
	{
		$week = Week::fromDateString('2026-08-17');

		self::assertSame('2026-08-24', $week->plusWeeks(1)->startDate());
		self::assertSame('2026-08-10', $week->plusWeeks(-1)->startDate());
	}
}
