<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Repositories;

use LauPerformanceTraining\Domain\Training;

final class TrainingRepository
{
	public const TIME_MORNING   = 'morning';
	public const TIME_AFTERNOON = 'afternoon';

	/**
	 * @return array<int,array{day_index:int,time_of_day:string}>
	 */
	public static function fixedSlots(): array
	{
		$slots = [];
		for ($day = 0; $day <= 6; $day++) {
			$slots[] = ['day_index' => $day, 'time_of_day' => self::TIME_MORNING];
			$slots[] = ['day_index' => $day, 'time_of_day' => self::TIME_AFTERNOON];
		}

		return $slots;
	}

	public function createSlot(int $schema_id, int $day_index, string $time_of_day): int
	{
		global $wpdb;

		$now = current_time('mysql');
		$inserted = $wpdb->insert(
			$this->table(),
			[
				'schema_id'   => $schema_id,
				'day_index'   => $day_index,
				'time_of_day' => $time_of_day,
				'created_at'  => $now,
				'updated_at'  => $now,
			],
			['%d', '%d', '%s', '%s', '%s']
		);

		if ($inserted !== false && $wpdb->insert_id > 0) {
			return (int) $wpdb->insert_id;
		}

		$existing = $this->findBySlot($schema_id, $day_index, $time_of_day);
		return $existing instanceof Training ? $existing->id : 0;
	}

	public function findById(int $training_id): ?Training
	{
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $training_id),
			ARRAY_A
		);

		return is_array($row) ? Training::fromRow($row) : null;
	}

	public function findBySlot(int $schema_id, int $day_index, string $time_of_day): ?Training
	{
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE schema_id = %d AND day_index = %d AND time_of_day = %s",
				$schema_id,
				$day_index,
				$time_of_day
			),
			ARRAY_A
		);

		return is_array($row) ? Training::fromRow($row) : null;
	}

	/**
	 * @return Training[]
	 */
	public function findBySchema(int $schema_id): array
	{
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE schema_id = %d ORDER BY day_index ASC, FIELD(time_of_day, 'morning', 'afternoon')",
				$schema_id
			),
			ARRAY_A
		);

		return array_map(static fn (array $row): Training => Training::fromRow($row), is_array($rows) ? $rows : []);
	}

	/**
	 * @param array{description:string,primary_training_type_id:int|null,coach_comment:string} $fields
	 */
	public function updateCoachFields(int $training_id, array $fields): void
	{
		global $wpdb;

		$wpdb->update(
			$this->table(),
			[
				'description'              => $fields['description'],
				'primary_training_type_id' => $fields['primary_training_type_id'],
				'coach_comment'            => $fields['coach_comment'],
				'updated_at'               => current_time('mysql'),
			],
			['id' => $training_id],
			['%s', '%d', '%s', '%s'],
			['%d']
		);
	}

	/**
	 * @param array{actual_distance:float|null,execution_comment:string,injury_comment:string} $fields
	 */
	public function updateFeedbackFields(int $training_id, array $fields): void
	{
		global $wpdb;

		$wpdb->update(
			$this->table(),
			[
				'actual_distance'   => $fields['actual_distance'],
				'execution_comment' => $fields['execution_comment'],
				'injury_comment'    => $fields['injury_comment'],
				'updated_at'        => current_time('mysql'),
			],
			['id' => $training_id],
			['%f', '%s', '%s', '%s'],
			['%d']
		);
	}

	/**
	 * @return int[]
	 */
	public function linkedTypeIds(int $training_id): array
	{
		global $wpdb;

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT training_type_id FROM {$this->linksTable()} WHERE training_id = %d ORDER BY training_type_id ASC",
				$training_id
			)
		);

		return array_map('intval', is_array($rows) ? $rows : []);
	}

	/**
	 * @param int[] $training_type_ids
	 */
	public function replaceLinkedTypes(int $training_id, array $training_type_ids): void
	{
		global $wpdb;

		$wpdb->delete($this->linksTable(), ['training_id' => $training_id], ['%d']);

		foreach (array_unique(array_filter(array_map('intval', $training_type_ids))) as $training_type_id) {
			$wpdb->insert(
				$this->linksTable(),
				[
					'training_id'      => $training_id,
					'training_type_id' => $training_type_id,
				],
				['%d', '%d']
			);
		}
	}

	private function table(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'lpt_trainings';
	}

	private function linksTable(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'lpt_training_type_links';
	}
}
