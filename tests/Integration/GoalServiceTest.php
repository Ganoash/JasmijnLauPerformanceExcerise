<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use InvalidArgumentException;
use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Permissions\GoalAccess;
use LauPerformanceTraining\Repositories\GoalRepository;
use LauPerformanceTraining\Services\GoalService;
use LauPerformanceTraining\Validation\GoalValidator;

if (class_exists('WP_UnitTestCase')) {
	final class GoalServiceTest extends \WP_UnitTestCase
	{
		private GoalRepository $goals;
		private int $user_id;

		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();

			$this->goals = new GoalRepository();
			$this->user_id = self::factory()->user->create();
		}

		public function test_blocks_fourth_active_goal(): void
		{
			$service = $this->service();

			$service->save($this->user_id, $this->user_id, $this->input('Een', '2026-09-01'));
			$service->save($this->user_id, $this->user_id, $this->input('Twee', '2026-09-02'));
			$service->save($this->user_id, $this->user_id, $this->input('Drie', '2026-09-03'));

			$this->expectException(InvalidArgumentException::class);
			$service->save($this->user_id, $this->user_id, $this->input('Vier', '2026-09-04'));
		}

		public function test_blocks_duplicate_date_for_same_user(): void
		{
			$service = $this->service();

			$service->save($this->user_id, $this->user_id, $this->input('Een', '2026-09-01'));

			$this->expectException(InvalidArgumentException::class);
			$service->save($this->user_id, $this->user_id, $this->input('Twee', '2026-09-01', false));
		}

		public function test_allows_duplicate_date_for_different_users(): void
		{
			$other_user_id = self::factory()->user->create();
			$service = $this->service();

			$service->save($this->user_id, $this->user_id, $this->input('Een', '2026-09-01'));
			$service->save($other_user_id, $other_user_id, $this->input('Twee', '2026-09-01'));

			self::assertCount(1, $this->goals->findByUser($this->user_id));
			self::assertCount(1, $this->goals->findByUser($other_user_id));
		}

		public function test_detects_actual_time_empty_to_filled_transition_once(): void
		{
			$service = $this->service();

			$result = $service->save($this->user_id, $this->user_id, $this->input('Een', '2026-09-01'));
			$goal_id = $result['goal_id'];

			$completed = $service->save(
				$this->user_id,
				$this->user_id,
				$this->input('Een', '2026-09-01', true, '00:42:15'),
				$goal_id
			);
			$already_completed = $service->save(
				$this->user_id,
				$this->user_id,
				$this->input('Een', '2026-09-01', true, '00:41:59'),
				$goal_id
			);

			self::assertTrue($completed['actual_time_completed']);
			self::assertFalse($already_completed['actual_time_completed']);
		}

		private function service(): GoalService
		{
			return new GoalService(
				$this->goals,
				new GoalValidator(),
				new GoalAccess(static fn (): bool => false)
			);
		}

		/**
		 * @return array{name:string,goal_date:string,target_time:string,description:string,actual_time:string,active:string}
		 */
		private function input(string $name, string $date, bool $active = true, string $actual_time = ''): array
		{
			return [
				'name'        => $name,
				'goal_date'   => $date,
				'target_time' => '',
				'description' => '',
				'actual_time' => $actual_time,
				'active'      => $active ? '1' : '0',
			];
		}
	}
}
