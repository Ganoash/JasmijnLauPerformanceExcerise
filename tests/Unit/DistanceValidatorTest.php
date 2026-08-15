<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use InvalidArgumentException;
use LauPerformanceTraining\Validation\DistanceValidator;
use PHPUnit\Framework\TestCase;

final class DistanceValidatorTest extends TestCase
{
	public function test_allows_empty_distance(): void
	{
		self::assertNull((new DistanceValidator())->normalize(''));
	}

	public function test_normalizes_decimal_comma(): void
	{
		self::assertSame(8.5, (new DistanceValidator())->normalize('8,5'));
	}

	public function test_rejects_negative_distance(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new DistanceValidator())->normalize('-1');
	}
}
