<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Domain;

use DateTimeImmutable;
use DateTimeZone;

final class Week
{
	private const TIMEZONE = 'Europe/Amsterdam';

	private DateTimeImmutable $start;

	private function __construct(DateTimeImmutable $start)
	{
		$this->start = $start->setTime(0, 0);
	}

	public static function fromDate(DateTimeImmutable $date): self
	{
		$amsterdam = $date->setTimezone(new DateTimeZone(self::TIMEZONE));
		$monday    = $amsterdam->modify('monday this week');

		return new self($monday);
	}

	public static function fromDateString(string $date): self
	{
		return self::fromDate(new DateTimeImmutable($date, new DateTimeZone(self::TIMEZONE)));
	}

	public function plusWeeks(int $weeks): self
	{
		return new self($this->start->modify(sprintf('%+d weeks', $weeks)));
	}

	public function startDate(): string
	{
		return $this->start->format('Y-m-d');
	}

	public function endDate(): string
	{
		return $this->start->modify('+6 days')->format('Y-m-d');
	}

	public function isoWeekNumber(): int
	{
		return (int) $this->start->format('W');
	}

	public function dayDate(int $day_index): string
	{
		if ($day_index < 0 || $day_index > 6) {
			throw new \InvalidArgumentException('Day index must be between 0 and 6.');
		}

		return $this->start->modify(sprintf('+%d days', $day_index))->format('Y-m-d');
	}
}
