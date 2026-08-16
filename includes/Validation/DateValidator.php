<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Validation;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LauPerformanceTraining\Domain\Week;

final class DateValidator
{
	private const TIMEZONE = 'Europe/Amsterdam';

	public function weekFromRequestDate(string $date): Week
	{
		$this->assertValidRequestDate($date);

		return Week::fromDateString($date);
	}

	public function assertValidRequestDate(string $date): void
	{
		if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			throw new InvalidArgumentException('Ongeldige datum.');
		}

		$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone(self::TIMEZONE));
		$errors = DateTimeImmutable::getLastErrors();
		$has_errors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

		if (! $parsed || $has_errors || $parsed->format('Y-m-d') !== $date) {
			throw new InvalidArgumentException('Ongeldige datum.');
		}
	}
}
