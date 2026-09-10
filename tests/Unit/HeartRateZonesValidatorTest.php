<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use InvalidArgumentException;
use LauPerformanceTraining\Validation\HeartRateZonesValidator;
use PHPUnit\Framework\TestCase;

final class HeartRateZonesValidatorTest extends TestCase
{
	public function test_accepts_partial_contiguous_upper_bounds(): void
	{
		$result = (new HeartRateZonesValidator())->validate(
			[
				'lactate_test_date' => '2026-02-01',
				'zone_1_upper'      => '120',
				'zone_2_upper'      => '139',
				'zone_3_upper'      => '',
				'zone_4_upper'      => '',
			]
		);

		self::assertSame(120, $result['zone_1_upper']);
		self::assertSame(139, $result['zone_2_upper']);
		self::assertNull($result['zone_3_upper']);
		self::assertNull($result['zone_4_upper']);
	}

	public function test_rejects_missing_middle_zone(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Vul zones aaneengesloten vanaf zone 1 in.');

		(new HeartRateZonesValidator())->validate(
			[
				'lactate_test_date' => '2026-02-01',
				'zone_1_upper'      => '120',
				'zone_2_upper'      => '',
				'zone_3_upper'      => '159',
				'zone_4_upper'      => '',
			]
		);
	}

	public function test_rejects_non_increasing_bounds(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Elke zone moet een hogere bovengrens hebben dan de vorige zone.');

		(new HeartRateZonesValidator())->validate(
			[
				'lactate_test_date' => '2026-02-01',
				'zone_1_upper'      => '120',
				'zone_2_upper'      => '120',
				'zone_3_upper'      => '',
				'zone_4_upper'      => '',
			]
		);
	}
}
