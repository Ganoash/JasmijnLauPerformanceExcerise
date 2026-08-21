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
		public readonly string $executionComment,
		public readonly string $injuryComment,
		public readonly string $coachComment,
		public readonly ?float $actualRunningDistance = null,
		public readonly ?float $actualCyclingDistance = null,
		public readonly ?float $actualSwimmingDistance = null,
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
			(string) ($row['execution_comment'] ?? ''),
			(string) ($row['injury_comment'] ?? ''),
			(string) ($row['coach_comment'] ?? ''),
			self::nullableFloat($row, 'actual_running_distance'),
			self::nullableFloat($row, 'actual_cycling_distance'),
			self::nullableFloat($row, 'actual_swimming_distance')
		);
	}

	/**
	 * @param array<string,mixed> $row
	 */
	private static function nullableFloat(array $row, string $key): ?float
	{
		if (array_key_exists($key, $row) && $row[$key] !== null) {
			return (float) $row[$key];
		}

		return null;
	}
}
