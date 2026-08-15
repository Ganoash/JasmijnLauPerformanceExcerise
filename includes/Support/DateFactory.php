<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Support;

use DateTimeImmutable;
use DateTimeZone;

final class DateFactory
{
	public function now(): DateTimeImmutable
	{
		return new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam'));
	}
}
