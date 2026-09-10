<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Validation;

use DateTimeImmutable;
use InvalidArgumentException;

final class GoalValidator
{
	/**
	 * @param array<string,mixed> $input
	 * @return array{name:string,goal_date:string,target_time:string,actual_time:string,active:bool}
	 */
	public function validate(array $input): array
	{
		$name        = $this->text($input['name'] ?? '');
		$goal_date   = $this->date($input['goal_date'] ?? '');
		$actual_time = $this->text($input['actual_time'] ?? '');

		if ($name === '') {
			throw new InvalidArgumentException('Naam is verplicht.');
		}

		if ($actual_time !== '' && ! preg_match('/^\d{2}:\d{2}:\d{2}$/', $actual_time)) {
			throw new InvalidArgumentException('Behaalde eindtijd moet HH:MM:SS zijn.');
		}

		return [
			'name'        => $name,
			'goal_date'   => $goal_date,
			'target_time' => $this->text($input['target_time'] ?? ''),
			'actual_time' => $actual_time,
			'active'      => isset($input['active']) && (string) $input['active'] === '1',
		];
	}

	private function date(mixed $value): string
	{
		$date = $this->text($value);
		if ($date === '') {
			throw new InvalidArgumentException('Doeldatum is verplicht.');
		}

		$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
		if (! $parsed || $parsed->format('Y-m-d') !== $date) {
			throw new InvalidArgumentException('Ongeldige doeldatum.');
		}

		return $date;
	}

	private function text(mixed $value): string
	{
		if (function_exists('sanitize_text_field')) {
			return sanitize_text_field((string) $value);
		}

		return trim(strip_tags((string) $value));
	}

	private function textarea(mixed $value): string
	{
		if (function_exists('sanitize_textarea_field')) {
			return sanitize_textarea_field((string) $value);
		}

		return trim(strip_tags((string) $value));
	}
}
