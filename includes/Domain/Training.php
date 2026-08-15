<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Domain;

final class Training
{
	public function __construct(
		public readonly int $id,
		public readonly int $schemaId,
		public readonly int $dayIndex,
		public readonly string $timeOfDay,
		public readonly string $description,
		public readonly ?int $primaryTrainingTypeId,
		public readonly ?float $actualDistance,
		public readonly string $executionComment,
		public readonly string $injuryComment,
		public readonly string $coachComment,
	) {
	}

	/**
	 * @param array<string,mixed> $row
	 */
	public static function fromRow(array $row): self
	{
		$primary_id = isset($row['primary_training_type_id']) ? (int) $row['primary_training_type_id'] : 0;

		return new self(
			(int) $row['id'],
			(int) $row['schema_id'],
			(int) $row['day_index'],
			(string) $row['time_of_day'],
			(string) ($row['description'] ?? ''),
			$primary_id > 0 ? $primary_id : null,
			array_key_exists('actual_distance', $row) && $row['actual_distance'] !== null ? (float) $row['actual_distance'] : null,
			(string) ($row['execution_comment'] ?? ''),
			(string) ($row['injury_comment'] ?? ''),
			(string) ($row['coach_comment'] ?? '')
		);
	}
}
