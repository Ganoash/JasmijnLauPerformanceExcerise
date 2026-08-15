<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Domain;

final class Schema
{
	public function __construct(
		public readonly int $id,
		public readonly int $userId,
		public readonly string $weekStartDate
	) {
	}

	/**
	 * @param array{id:string|int,user_id:string|int,week_start_date:string} $row
	 */
	public static function fromRow(array $row): self
	{
		return new self((int) $row['id'], (int) $row['user_id'], (string) $row['week_start_date']);
	}
}
