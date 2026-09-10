<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Repositories;

use LauPerformanceTraining\Domain\Goal;

final class GoalRepository
{
	public function findById(int $id): ?Goal
	{
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $id),
			ARRAY_A
		);

		return is_array($row) ? Goal::fromRow($row) : null;
	}

	/**
	 * @return Goal[]
	 */
	public function findByUser(int $user_id): array
	{
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE user_id = %d ORDER BY goal_date ASC, id ASC",
				$user_id
			),
			ARRAY_A
		);

		return array_map(static fn (array $row): Goal => Goal::fromRow($row), is_array($rows) ? $rows : []);
	}

	/**
	 * @return Goal[]
	 */
	public function findActiveByUser(int $user_id): array
	{
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE user_id = %d AND active = 1 ORDER BY goal_date ASC, id ASC",
				$user_id
			),
			ARRAY_A
		);

		return array_map(static fn (array $row): Goal => Goal::fromRow($row), is_array($rows) ? $rows : []);
	}

	/**
	 * @return Goal[]
	 */
	public function findByUserAndDateRange(int $user_id, string $start_date, string $end_date): array
	{
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE user_id = %d AND goal_date BETWEEN %s AND %s ORDER BY goal_date ASC, id ASC",
				$user_id,
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		return array_map(static fn (array $row): Goal => Goal::fromRow($row), is_array($rows) ? $rows : []);
	}

	/**
	 * @param array{name:string,goal_date:string,target_time:string,actual_time:string,active:bool} $fields
	 */
	public function create(int $user_id, array $fields): int
	{
		global $wpdb;

		$now = current_time('mysql');
		$wpdb->insert(
			$this->table(),
			[
				'user_id'     => $user_id,
				'name'        => $fields['name'],
				'goal_date'   => $fields['goal_date'],
				'target_time' => $fields['target_time'],
				'actual_time' => $fields['actual_time'],
				'active'      => $fields['active'] ? 1 : 0,
				'created_at'  => $now,
				'updated_at'  => $now,
			],
			['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * @param array{name:string,goal_date:string,target_time:string,actual_time:string,active:bool} $fields
	 */
	public function update(int $goal_id, array $fields): void
	{
		global $wpdb;

		$wpdb->update(
			$this->table(),
			[
				'name'        => $fields['name'],
				'goal_date'   => $fields['goal_date'],
				'target_time' => $fields['target_time'],
				'actual_time' => $fields['actual_time'],
				'active'      => $fields['active'] ? 1 : 0,
				'updated_at'  => current_time('mysql'),
			],
			['id' => $goal_id],
			['%s', '%s', '%s', '%s', '%s', '%d', '%s'],
			['%d']
		);
	}

	public function deleteForUser(int $goal_id, int $user_id): void
	{
		global $wpdb;

		$wpdb->delete($this->table(), ['id' => $goal_id, 'user_id' => $user_id], ['%d', '%d']);
	}

	public function deleteByUser(int $user_id): void
	{
		global $wpdb;

		$wpdb->delete($this->table(), ['user_id' => $user_id], ['%d']);
	}

	public function activeCountForUser(int $user_id, ?int $excluding_goal_id = null): int
	{
		global $wpdb;

		if ($excluding_goal_id !== null) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table()} WHERE user_id = %d AND active = 1 AND id != %d",
					$user_id,
					$excluding_goal_id
				)
			);
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table()} WHERE user_id = %d AND active = 1",
				$user_id
			)
		);
	}

	public function dateExistsForUser(int $user_id, string $goal_date, ?int $excluding_goal_id = null): bool
	{
		global $wpdb;

		if ($excluding_goal_id !== null) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table()} WHERE user_id = %d AND goal_date = %s AND id != %d",
					$user_id,
					$goal_date,
					$excluding_goal_id
				)
			) > 0;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table()} WHERE user_id = %d AND goal_date = %s",
				$user_id,
				$goal_date
			)
		) > 0;
	}

	private function table(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'lpt_goals';
	}
}
