<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Validation;

use InvalidArgumentException;

final class DistanceValidator
{
	public function normalize(mixed $value): ?float
	{
		if ($value === null || $value === '') {
			return null;
		}

		if (is_string($value)) {
			$value = str_replace(',', '.', trim($value));
		}

		if (! is_numeric($value)) {
			throw new InvalidArgumentException('Afstand moet een getal zijn.');
		}

		$distance = round((float) $value, 2);
		if ($distance < 0) {
			throw new InvalidArgumentException('Afstand mag niet negatief zijn.');
		}

		return $distance;
	}
}
