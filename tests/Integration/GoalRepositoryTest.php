<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Repositories\GoalRepository;

if (class_exists('WP_UnitTestCase')) {
	final class GoalRepositoryTest extends \WP_UnitTestCase
	{
		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();
		}

		public function test_creates_and_retrieves_goals_ordered_by_date(): void
		{
			$user_id = self::factory()->user->create();
			$goals   = new GoalRepository();

			$later_id = $goals->create($user_id, $this->fields('Marathon', '2026-10-18', true));
			$earlier_id = $goals->create($user_id, $this->fields('Tien kilometer', '2026-09-09', true));

			$result = $goals->findByUser($user_id);

			self::assertSame($earlier_id, $result[0]->id);
			self::assertSame($later_id, $result[1]->id);
			self::assertSame('Tien kilometer', $goals->findById($earlier_id)?->name);
		}

		public function test_finds_active_and_week_range_goals(): void
		{
			$user_id = self::factory()->user->create();
			$goals   = new GoalRepository();

			$goals->create($user_id, $this->fields('Actief', '2026-09-09', true));
			$goals->create($user_id, $this->fields('Inactief', '2026-09-10', false));
			$goals->create($user_id, $this->fields('Later', '2026-10-01', true));

			self::assertCount(2, $goals->findActiveByUser($user_id));
			self::assertCount(2, $goals->findByUserAndDateRange($user_id, '2026-09-07', '2026-09-13'));
		}

		public function test_counts_active_goals_and_detects_duplicate_dates(): void
		{
			$user_id = self::factory()->user->create();
			$goals   = new GoalRepository();

			$goal_id = $goals->create($user_id, $this->fields('Actief', '2026-09-09', true));
			$goals->create($user_id, $this->fields('Inactief', '2026-09-10', false));

			self::assertSame(1, $goals->activeCountForUser($user_id));
			self::assertSame(0, $goals->activeCountForUser($user_id, $goal_id));
			self::assertTrue($goals->dateExistsForUser($user_id, '2026-09-09'));
			self::assertFalse($goals->dateExistsForUser($user_id, '2026-09-09', $goal_id));
		}

		public function test_deletes_by_user(): void
		{
			$user_id = self::factory()->user->create();
			$other_user_id = self::factory()->user->create();
			$goals = new GoalRepository();

			$goals->create($user_id, $this->fields('Actief', '2026-09-09', true));
			$goals->create($other_user_id, $this->fields('Andere atleet', '2026-09-09', true));

			$goals->deleteByUser($user_id);

			self::assertSame([], $goals->findByUser($user_id));
			self::assertCount(1, $goals->findByUser($other_user_id));
		}

		/**
		 * @return array{name:string,description:string,goal_date:string,target_time:string,actual_time:string,active:bool}
		 */
		private function fields(string $name, string $date, bool $active): array
		{
			return [
				'name'        => $name,
				'description' => '',
				'goal_date'   => $date,
				'target_time' => '',
				'actual_time' => '',
				'active'      => $active,
			];
		}
	}
}
