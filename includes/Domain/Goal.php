<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Domain;

final class Goal
{
	public function __construct(
		public readonly int $id,
		public readonly int $userId,
		public readonly string $name,
		public readonly string $goalDate,
		public readonly string $targetTime,
		public readonly string $actualTime,
		public readonly bool $active,
		public readonly string $createdAt,
		public readonly string $updatedAt,
	) {
	}

	/**
	 * @param array<string,mixed> $row
	 */
	public static function fromRow(array $row): self
	{
		return new self(
			(int) $row['id'],
			(int) $row['user_id'],
			(string) $row['name'],
			(string) $row['goal_date'],
			(string) ($row['target_time'] ?? ''),
			(string) ($row['actual_time'] ?? ''),
			(bool) (int) $row['active'],
			(string) $row['created_at'],
			(string) $row['updated_at']
		);
	}
}
