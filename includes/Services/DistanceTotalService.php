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
	 * @param array<int,TrainingType[]> $linked_types
	 */
	public function calculate(array $trainings, array $primary_types, array $linked_types = []): DistanceTotals
	{
		$running  = 0.0;
		$cycling  = 0.0;
		$swimming = 0.0;

		foreach ($trainings as $training) {
			foreach ($this->distanceCategories($primary_types[$training->id] ?? null, $linked_types[$training->id] ?? []) as $category => $unit) {
				$value = match ($category) {
					'running' => $training->actualRunningDistance,
					'cycling' => $training->actualCyclingDistance,
					default => $training->actualSwimmingDistance,
				};

				if ($value === null) {
					continue;
				}

				if ($category === 'running') {
					$running += $value;
				} elseif ($category === 'cycling') {
					$cycling += $value;
				} elseif ($category === 'swimming') {
					$swimming += $unit === 'meters' || $unit === 'meter' ? $value / 1000 : $value;
				}
			}
		}

		return new DistanceTotals(round($running, 2), round($cycling, 2), round($swimming, 2));
	}

	/**
	 * @param TrainingType[] $linked_types
	 * @return array<string,string>
	 */
	private function distanceCategories(?TrainingType $primary_type, array $linked_types): array
	{
		$categories = [];
		foreach (array_filter([$primary_type, ...$linked_types]) as $type) {
			$category = strtolower($type->category);
			if (in_array($category, ['running', 'cycling', 'swimming'], true) && ! isset($categories[$category])) {
				$categories[$category] = strtolower($type->unit);
			}
		}

		return $categories;
	}
}
