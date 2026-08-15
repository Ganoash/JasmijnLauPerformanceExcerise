<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Repositories;

use LauPerformanceTraining\Domain\Schema;

final class SchemaRepository
{
	public function create(int $user_id, string $week_start_date): int
	{
		global $wpdb;

		$now = current_time('mysql');
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$this->table()} (user_id, week_start_date, created_at, updated_at) VALUES (%d, %s, %s, %s)",
				$user_id,
				$week_start_date,
				$now,
				$now
			)
		);

		$existing = $this->findByUserAndWeek($user_id, $week_start_date);
		if ($existing instanceof Schema) {
			return $existing->id;
		}

		return 0;
	}

	public function findById(int $schema_id): ?Schema
	{
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $schema_id),
			ARRAY_A
		);

		return is_array($row) ? Schema::fromRow($row) : null;
	}

	public function findByUserAndWeek(int $user_id, string $week_start_date): ?Schema
	{
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE user_id = %d AND week_start_date = %s",
				$user_id,
				$week_start_date
			),
			ARRAY_A
		);

		return is_array($row) ? Schema::fromRow($row) : null;
	}

	/**
	 * @return Schema[]
	 */
	public function findByUser(int $user_id): array
	{
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE user_id = %d ORDER BY week_start_date DESC",
				$user_id
			),
			ARRAY_A
		);

		return array_map(static fn (array $row): Schema => Schema::fromRow($row), is_array($rows) ? $rows : []);
	}

	public function deleteByUser(int $user_id): void
	{
		global $wpdb;

		$schema_ids = $wpdb->get_col(
			$wpdb->prepare("SELECT id FROM {$this->table()} WHERE user_id = %d", $user_id)
		);
		$schema_ids = array_map('intval', is_array($schema_ids) ? $schema_ids : []);

		if ($schema_ids !== []) {
			$placeholders = implode(', ', array_fill(0, count($schema_ids), '%d'));
			$trainings    = $wpdb->prefix . 'lpt_trainings';
			$links        = $wpdb->prefix . 'lpt_training_type_links';

			$training_ids = $wpdb->get_col(
				$wpdb->prepare("SELECT id FROM {$trainings} WHERE schema_id IN ({$placeholders})", ...$schema_ids)
			);
			$training_ids = array_map('intval', is_array($training_ids) ? $training_ids : []);

			if ($training_ids !== []) {
				$training_placeholders = implode(', ', array_fill(0, count($training_ids), '%d'));
				$wpdb->query(
					$wpdb->prepare("DELETE FROM {$links} WHERE training_id IN ({$training_placeholders})", ...$training_ids)
				);
			}

			$wpdb->query(
				$wpdb->prepare("DELETE FROM {$trainings} WHERE schema_id IN ({$placeholders})", ...$schema_ids)
			);
		}

		$wpdb->delete($this->table(), ['user_id' => $user_id], ['%d']);
	}

	private function table(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'lpt_schemas';
	}
}
