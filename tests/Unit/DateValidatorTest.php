<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use InvalidArgumentException;
use LauPerformanceTraining\Validation\DateValidator;
use PHPUnit\Framework\TestCase;

final class DateValidatorTest extends TestCase
{
	public function test_accepts_valid_request_date(): void
	{
		$week = (new DateValidator())->weekFromRequestDate('2026-08-19');

		self::assertSame('2026-08-17', $week->startDate());
	}

	/**
	 * @dataProvider invalidDates
	 */
	public function test_rejects_invalid_request_date(string $date): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Ongeldige datum.');

		(new DateValidator())->weekFromRequestDate($date);
	}

	/**
	 * @return iterable<string,array{string}>
	 */
	public function invalidDates(): iterable
	{
		yield 'wrong shape' => ['2026-8-19'];
		yield 'invalid month' => ['2026-13-19'];
		yield 'invalid day' => ['2026-02-30'];
		yield 'text' => ['not-a-date'];
	}
}
