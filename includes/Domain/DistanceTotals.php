<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Domain;

final class DistanceTotals
{
	public function __construct(
		public readonly float $runningKm,
		public readonly float $cyclingKm,
		public readonly float $swimmingKm
	) {
	}
}
