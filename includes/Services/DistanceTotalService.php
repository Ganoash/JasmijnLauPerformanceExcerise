<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Services;

use LauPerformanceTraining\Domain\DistanceTotals;
use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Domain\TrainingType;

final class DistanceTotalService
{
	/**
	 * @param Training[] $trainings
	 * @param array<int,TrainingType|null> $primary_types
	 */
	public function calculate(array $trainings, array $primary_types): DistanceTotals
	{
		$running  = 0.0;
		$cycling  = 0.0;
		$swimming = 0.0;

		foreach ($trainings as $training) {
			if ($training->actualDistance === null) {
				continue;
			}

			$type = $primary_types[$training->id] ?? null;
			if (! $type instanceof TrainingType) {
				continue;
			}

			$category = strtolower($type->category);
			$unit     = strtolower($type->unit);
			$value    = $training->actualDistance;

			if ($category === 'running') {
				$running += $value;
			} elseif ($category === 'cycling') {
				$cycling += $value;
			} elseif ($category === 'swimming') {
				$swimming += $unit === 'meters' || $unit === 'meter' ? $value / 1000 : $value;
			}
		}

		return new DistanceTotals(round($running, 2), round($cycling, 2), round($swimming, 2));
	}
}
