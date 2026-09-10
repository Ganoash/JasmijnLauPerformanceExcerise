<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Validation;

use DateTimeImmutable;
use InvalidArgumentException;

final class HeartRateZonesValidator
{
	/**
	 * @param array<string,mixed> $input
	 * @return array{lactate_test_date:string,zone_1_upper:int|null,zone_2_upper:int|null,zone_3_upper:int|null,zone_4_upper:int|null}
	 */
	public function validate(array $input): array
	{
		$date = $this->text($input['lactate_test_date'] ?? '');
		if (! $this->validDate($date)) {
			throw new InvalidArgumentException('Vul een geldige lactaattestdatum in.');
		}

		$bounds = [
			'zone_1_upper' => $this->optionalInteger($input['zone_1_upper'] ?? ''),
			'zone_2_upper' => $this->optionalInteger($input['zone_2_upper'] ?? ''),
			'zone_3_upper' => $this->optionalInteger($input['zone_3_upper'] ?? ''),
			'zone_4_upper' => $this->optionalInteger($input['zone_4_upper'] ?? ''),
		];

		if ($bounds['zone_1_upper'] === null) {
			throw new InvalidArgumentException('Vul minimaal de bovengrens voor zone 1 in.');
		}

		$previous = null;
		foreach ($bounds as $field => $bound) {
			if ($bound === null) {
				$previous = null;
				continue;
			}

			if ($previous === null && $field !== 'zone_1_upper') {
				throw new InvalidArgumentException('Vul zones aaneengesloten vanaf zone 1 in.');
			}

			if ($previous !== null && $bound <= $previous) {
				throw new InvalidArgumentException('Elke zone moet een hogere bovengrens hebben dan de vorige zone.');
			}

			$previous = $bound;
		}

		return [
			'lactate_test_date' => $date,
			'zone_1_upper'      => $bounds['zone_1_upper'],
			'zone_2_upper'      => $bounds['zone_2_upper'],
			'zone_3_upper'      => $bounds['zone_3_upper'],
			'zone_4_upper'      => $bounds['zone_4_upper'],
		];
	}

	private function optionalInteger(mixed $value): ?int
	{
		$value = trim((string) $value);
		if ($value === '') {
			return null;
		}

		if (! ctype_digit($value)) {
			throw new InvalidArgumentException('Hartslagzones moeten hele getallen zijn.');
		}

		return (int) $value;
	}

	private function text(mixed $value): string
	{
		if (function_exists('sanitize_text_field')) {
			return sanitize_text_field((string) $value);
		}

		return trim(strip_tags((string) $value));
	}

	private function validDate(string $date): bool
	{
		$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
		$errors = DateTimeImmutable::getLastErrors();

		return $parsed instanceof DateTimeImmutable
			&& ($errors === false || ((int) $errors['warning_count'] === 0 && (int) $errors['error_count'] === 0))
			&& $parsed->format('Y-m-d') === $date;
	}
}
