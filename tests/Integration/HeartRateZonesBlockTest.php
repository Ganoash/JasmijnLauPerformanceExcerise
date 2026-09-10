<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Blocks\HeartRateZonesBlock;
use LauPerformanceTraining\Repositories\HeartRateZonesRepository;

if (class_exists('WP_UnitTestCase')) {
	final class HeartRateZonesBlockTest extends \WP_UnitTestCase
	{
		public function test_block_invites_logged_out_visitors_to_login_and_plan_lactate_test(): void
		{
			wp_set_current_user(0);

			$html = (new HeartRateZonesBlock(new HeartRateZonesRepository()))->render();

			self::assertStringContainsString('Log in om je hartslagzones te bekijken.', $html);
			self::assertStringContainsString('Inloggen', $html);
			self::assertStringContainsString('/hartslag-zones/lactaattest/', $html);
		}

		public function test_block_renders_latest_computed_heart_rate_ranges(): void
		{
			$user_id = self::factory()->user->create();
			wp_set_current_user($user_id);

			(new HeartRateZonesRepository())->saveForUser(
				$user_id,
				[
					'lactate_test_date' => '2026-02-01',
					'zone_1_upper'      => 120,
					'zone_2_upper'      => 139,
					'zone_3_upper'      => 159,
					'zone_4_upper'      => 179,
				]
			);

			$html = (new HeartRateZonesBlock(new HeartRateZonesRepository()))->render();

			self::assertStringContainsString('Zone 1: tot en met 120 bpm', $html);
			self::assertStringContainsString('Zone 2: 121-139 bpm', $html);
			self::assertStringContainsString('Zone 3: 140-159 bpm', $html);
			self::assertStringContainsString('Zone 4: 160-179 bpm', $html);
			self::assertStringContainsString('Zone 5: vanaf 180 bpm', $html);
		}

		public function test_block_renders_lactate_test_link_when_logged_in_user_has_no_zones(): void
		{
			wp_set_current_user(self::factory()->user->create());

			$html = (new HeartRateZonesBlock(new HeartRateZonesRepository()))->render();

			self::assertStringContainsString('Nog geen hartslagzones bekend. Plan een lactaattest.', $html);
			self::assertStringContainsString('/hartslag-zones/lactaattest/', $html);
		}
	}
}
