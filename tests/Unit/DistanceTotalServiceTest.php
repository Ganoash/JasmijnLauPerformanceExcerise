<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Services\DistanceTotalService;
use PHPUnit\Framework\TestCase;

final class DistanceTotalServiceTest extends TestCase
{
	public function test_converts_swimming_meters_to_kilometers(): void
	{
		$trainings = [
			new Training(1, 1, 0, 'morning', '', 1, 8.5, '', '', ''),
			new Training(2, 1, 0, 'afternoon', '', 2, 2500.0, '', '', ''),
		];
		$types = [
			1 => new TrainingType(1, 'Lopen', 'running', 'kilometers', '#ffffff', '', true),
			2 => new TrainingType(2, 'Zwemmen', 'swimming', 'meters', '#ffffff', '', true),
		];

		$totals = (new DistanceTotalService())->calculate($trainings, $types);

		self::assertSame(8.5, $totals->runningKm);
		self::assertSame(2.5, $totals->swimmingKm);
	}
}
