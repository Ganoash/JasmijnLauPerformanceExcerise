<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Domain;

final class TrainingType
{
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $category,
		public readonly string $unit,
		public readonly string $linkedUrl,
		public readonly bool $active,
	) {
	}

	/**
	 * @param array<string,mixed> $row
	 */
	public static function fromRow(array $row): self
	{
		return new self(
			(int) $row['id'],
			(string) $row['name'],
			(string) $row['category'],
			(string) $row['unit'],
			(string) ($row['linked_url'] ?? ''),
			(bool) (int) $row['active']
		);
	}
}
