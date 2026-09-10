<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Repositories\HeartRateZonesRepository;

if (class_exists('WP_UnitTestCase')) {
	final class HeartRateZonesRepositoryTest extends \WP_UnitTestCase
	{
		public function test_saves_and_finds_latest_heart_rate_zones_by_lactate_test_date(): void
		{
			$user_id = self::factory()->user->create();
			$repo = new HeartRateZonesRepository();

			$repo->saveForUser(
				$user_id,
				[
					'lactate_test_date' => '2026-02-01',
					'zone_1_upper'      => 120,
					'zone_2_upper'      => 139,
					'zone_3_upper'      => 159,
					'zone_4_upper'      => 179,
				]
			);
			$repo->saveForUser(
				$user_id,
				[
					'lactate_test_date' => '2026-04-01',
					'zone_1_upper'      => 122,
					'zone_2_upper'      => 142,
					'zone_3_upper'      => null,
					'zone_4_upper'      => null,
				]
			);

			$latest = $repo->findLatestByUser($user_id);

			self::assertNotNull($latest);
			self::assertSame('2026-04-01', $latest->lactateTestDate);
			self::assertSame(122, $latest->zone1Upper);
			self::assertSame(142, $latest->zone2Upper);
			self::assertNull($latest->zone3Upper);
		}

		public function test_saving_the_same_user_and_date_updates_existing_zones(): void
		{
			$user_id = self::factory()->user->create();
			$repo = new HeartRateZonesRepository();

			$first_id = $repo->saveForUser(
				$user_id,
				[
					'lactate_test_date' => '2026-02-01',
					'zone_1_upper'      => 120,
					'zone_2_upper'      => 139,
					'zone_3_upper'      => null,
					'zone_4_upper'      => null,
				]
			);
			$second_id = $repo->saveForUser(
				$user_id,
				[
					'lactate_test_date' => '2026-02-01',
					'zone_1_upper'      => 121,
					'zone_2_upper'      => 140,
					'zone_3_upper'      => 160,
					'zone_4_upper'      => null,
				]
			);

			$zones = $repo->findById($first_id);

			self::assertSame($first_id, $second_id);
			self::assertNotNull($zones);
			self::assertSame(121, $zones->zone1Upper);
			self::assertSame(160, $zones->zone3Upper);
		}
	}
}
