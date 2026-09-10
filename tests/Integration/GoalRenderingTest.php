<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Domain\DistanceTotals;
use LauPerformanceTraining\Domain\Goal;
use LauPerformanceTraining\Domain\Schema;
use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Repositories\GoalRepository;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Support\View;

if (class_exists('WP_UnitTestCase')) {
	final class GoalRenderingTest extends \WP_UnitTestCase
	{
		public function set_up(): void
		{
			parent::set_up();
			(new DatabaseInstaller())->install();
		}

		public function test_goals_page_template_renders_active_and_inactive_sections(): void
		{
			$user = self::factory()->user->create_and_get(['display_name' => 'Wedstrijd Atleet']);

			ob_start();
			View::render(
				'frontend/goals-page.php',
				[
					'action_url'     => admin_url('admin-post.php'),
					'active_goals'   => [$this->goal(1, (int) $user->ID, 'Actieve wedstrijd', '2026-09-09', true, '')],
					'completed'      => false,
					'edit_goal'      => null,
					'error_message'  => '',
					'inactive_goals' => [$this->goal(2, (int) $user->ID, 'Inactieve wedstrijd', '2026-10-09', false, '00:45:00')],
					'nonce'          => (new Nonce())->create(Nonce::GOAL_ACTION),
					'own_goals'      => true,
					'target_user_id' => (int) $user->ID,
					'user'           => $user,
				]
			);
			$html = (string) ob_get_clean();

			self::assertStringContainsString('Actieve wedstrijd', $html);
			self::assertStringContainsString('Inactieve wedstrijd', $html);
		}

		public function test_frontend_schedule_shows_goal_badge_in_each_visible_row_without_actual_time(): void
		{
			$user = self::factory()->user->create_and_get(['display_name' => 'Schema Athlete']);
			$goal = $this->goal(1, (int) $user->ID, 'Damloop', '2026-08-17', false, '00:42:15');

			ob_start();
			View::render(
				'frontend/schema-page.php',
				[
					'goals_by_date'     => ['2026-08-17' => [$goal]],
					'linked_types'       => [1 => [], 2 => []],
					'primary_types'      => [1 => null, 2 => null],
					'schema'             => new Schema(1, (int) $user->ID, '2026-08-17'),
					'show_time_of_day'   => true,
					'totals'             => new DistanceTotals(0.0, 0.0, 0.0),
					'trainings'          => [
						new Training(1, 1, 0, 'morning', 'Rustig', null, '', '', ''),
						new Training(2, 1, 0, 'afternoon', 'Loslopen', null, '', '', ''),
					],
					'user'               => $user,
					'week'               => Week::fromDateString('2026-08-17'),
				]
			);
			$html = (string) ob_get_clean();

			self::assertSame(2, substr_count($html, 'Damloop - Onder 45 minuten'));
			self::assertStringNotContainsString('00:42:15', $html);
		}

		public function test_admin_schedule_shows_goal_badge_once_per_day(): void
		{
			$user = self::factory()->user->create_and_get(['display_name' => 'Schema Athlete']);
			$goal = $this->goal(1, (int) $user->ID, 'Damloop', '2026-08-17', true, '00:42:15');

			ob_start();
			View::render(
				'admin/schema-editor.php',
				[
					'action_url'            => admin_url('admin-post.php'),
					'error_message'         => null,
					'frontend_url'          => home_url('/training-schema/' . $user->ID . '/2026-08-17/'),
					'goals_by_date'         => ['2026-08-17' => [$goal]],
					'linked_types'          => [1 => [], 2 => []],
					'linked_training_types' => [],
					'nonce'                 => (new Nonce())->create(Nonce::ADMIN_SCHEMA_ACTION),
					'schema'                => new Schema(1, (int) $user->ID, '2026-08-17'),
					'show_time_of_day'      => true,
					'training_types'        => [],
					'trainings'             => [
						new Training(1, 1, 0, 'morning', 'Rustig', null, '', '', ''),
						new Training(2, 1, 0, 'afternoon', 'Loslopen', null, '', '', ''),
					],
					'user'                  => $user,
					'week'                  => Week::fromDateString('2026-08-17'),
				]
			);
			$html = (string) ob_get_clean();

			self::assertSame(1, substr_count($html, 'Damloop - Onder 45 minuten'));
			self::assertStringNotContainsString('00:42:15', $html);
		}

		private function goal(int $id, int $user_id, string $name, string $date, bool $active, string $actual_time): Goal
		{
			return new Goal(
				$id,
				$user_id,
				$name,
				$date,
				'Onder 45 minuten',
				$actual_time,
				$active,
				'2026-01-01 00:00:00',
				'2026-01-01 00:00:00'
			);
		}

		/**
		 * @return array{name:string,goal_date:string,target_time:string,actual_time:string,active:bool}
		 */
		private function fields(string $name, string $date, bool $active, string $target_time): array
		{
			return [
				'name'        => $name,
				'goal_date'   => $date,
				'target_time' => $target_time,
				'actual_time' => '',
				'active'      => $active,
			];
		}
	}
}
