<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Repositories;

use LauPerformanceTraining\Domain\HeartRateZones;

final class HeartRateZonesRepository
{
	public function findById(int $id): ?HeartRateZones
	{
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $id),
			ARRAY_A
		);

		return is_array($row) ? HeartRateZones::fromRow($row) : null;
	}

	public function findByUserAndDate(int $user_id, string $lactate_test_date): ?HeartRateZones
	{
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE user_id = %d AND lactate_test_date = %s",
				$user_id,
				$lactate_test_date
			),
			ARRAY_A
		);

		return is_array($row) ? HeartRateZones::fromRow($row) : null;
	}

	public function findLatestByUser(int $user_id): ?HeartRateZones
	{
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE user_id = %d ORDER BY lactate_test_date DESC, id DESC LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		return is_array($row) ? HeartRateZones::fromRow($row) : null;
	}

	/**
	 * @param array{lactate_test_date:string,zone_1_upper:int|null,zone_2_upper:int|null,zone_3_upper:int|null,zone_4_upper:int|null} $fields
	 */
	public function saveForUser(int $user_id, array $fields): int
	{
		$existing = $this->findByUserAndDate($user_id, $fields['lactate_test_date']);
		if ($existing) {
			$this->update($existing->id, $fields);

			return $existing->id;
		}

		return $this->create($user_id, $fields);
	}

	public function deleteByUser(int $user_id): void
	{
		global $wpdb;

		$wpdb->delete($this->table(), ['user_id' => $user_id], ['%d']);
	}

	/**
	 * @param array{lactate_test_date:string,zone_1_upper:int|null,zone_2_upper:int|null,zone_3_upper:int|null,zone_4_upper:int|null} $fields
	 */
	private function create(int $user_id, array $fields): int
	{
		global $wpdb;

		$now = current_time('mysql');
		$wpdb->insert(
			$this->table(),
			[
				'user_id'           => $user_id,
				'lactate_test_date' => $fields['lactate_test_date'],
				'zone_1_upper'      => $fields['zone_1_upper'],
				'zone_2_upper'      => $fields['zone_2_upper'],
				'zone_3_upper'      => $fields['zone_3_upper'],
				'zone_4_upper'      => $fields['zone_4_upper'],
				'created_at'        => $now,
				'updated_at'        => $now,
			],
			['%d', '%s', '%d', '%d', '%d', '%d', '%s', '%s']
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * @param array{lactate_test_date:string,zone_1_upper:int|null,zone_2_upper:int|null,zone_3_upper:int|null,zone_4_upper:int|null} $fields
	 */
	private function update(int $id, array $fields): void
	{
		global $wpdb;

		$wpdb->update(
			$this->table(),
			[
				'zone_1_upper' => $fields['zone_1_upper'],
				'zone_2_upper' => $fields['zone_2_upper'],
				'zone_3_upper' => $fields['zone_3_upper'],
				'zone_4_upper' => $fields['zone_4_upper'],
				'updated_at'   => current_time('mysql'),
			],
			['id' => $id],
			['%d', '%d', '%d', '%d', '%s'],
			['%d']
		);
	}

	private function table(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'lpt_heart_rate_zones';
	}
}
