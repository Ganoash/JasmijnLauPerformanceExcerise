<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Support;

final class GoalFormatter
{
	private const MONTHS = [
		1  => 'jan',
		2  => 'feb',
		3  => 'mrt',
		4  => 'apr',
		5  => 'mei',
		6  => 'jun',
		7  => 'jul',
		8  => 'aug',
		9  => 'sep',
		10 => 'okt',
		11 => 'nov',
		12 => 'dec',
	];

	public static function date(string $date): string
	{
		$timestamp = strtotime($date);
		if ($timestamp === false) {
			return $date;
		}

		$month = self::MONTHS[(int) gmdate('n', $timestamp)] ?? gmdate('M', $timestamp);

		return gmdate('d', $timestamp) . ' ' . $month . ' ' . gmdate('Y', $timestamp);
	}

	public static function targetTime(string $target_time): string
	{
		return $target_time !== '' ? $target_time : 'Geen streeftijd';
	}
}
