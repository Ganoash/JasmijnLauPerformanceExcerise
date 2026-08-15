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
		$wpdb->insert(
			$this->table(),
			[
				'user_id'          => $user_id,
				'week_start_date'  => $week_start_date,
				'created_at'       => $now,
				'updated_at'       => $now,
			],
			['%d', '%s', '%s', '%s']
		);

		if ($wpdb->insert_id > 0) {
			return (int) $wpdb->insert_id;
		}

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

		$wpdb->delete($this->table(), ['user_id' => $user_id], ['%d']);
	}

	private function table(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'lpt_schemas';
	}
}
