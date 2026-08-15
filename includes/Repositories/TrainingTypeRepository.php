<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Repositories;

use LauPerformanceTraining\Domain\TrainingType;

final class TrainingTypeRepository
{
	/**
	 * @return TrainingType[]
	 */
	public function all(bool $active_only = false): array
	{
		global $wpdb;

		$where = $active_only ? 'WHERE active = 1' : '';
		$rows  = $wpdb->get_results(
			"SELECT * FROM {$this->table()} {$where} ORDER BY name ASC",
			ARRAY_A
		);

		return array_map(static fn (array $row): TrainingType => TrainingType::fromRow($row), is_array($rows) ? $rows : []);
	}

	public function find(int $training_type_id): ?TrainingType
	{
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $training_type_id),
			ARRAY_A
		);

		return is_array($row) ? TrainingType::fromRow($row) : null;
	}

	/**
	 * @param array{name:string,category:string,unit:string,linked_url:string,active:bool} $fields
	 */
	public function create(array $fields): int
	{
		global $wpdb;

		$now = current_time('mysql');
		$wpdb->insert(
			$this->table(),
			[
				'name'       => $fields['name'],
				'category'   => $fields['category'],
				'unit'       => $fields['unit'],
				'linked_url' => $fields['linked_url'],
				'active'     => $fields['active'] ? 1 : 0,
				'created_at' => $now,
				'updated_at' => $now,
			],
			['%s', '%s', '%s', '%s', '%d', '%s', '%s']
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * @param array{name:string,category:string,unit:string,linked_url:string,active:bool} $fields
	 */
	public function update(int $training_type_id, array $fields): void
	{
		global $wpdb;

		$wpdb->update(
			$this->table(),
			[
				'name'       => $fields['name'],
				'category'   => $fields['category'],
				'unit'       => $fields['unit'],
				'linked_url' => $fields['linked_url'],
				'active'     => $fields['active'] ? 1 : 0,
				'updated_at' => current_time('mysql'),
			],
			['id' => $training_type_id],
			['%s', '%s', '%s', '%s', '%d', '%s'],
			['%d']
		);
	}

	private function table(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'lpt_training_types';
	}
}
