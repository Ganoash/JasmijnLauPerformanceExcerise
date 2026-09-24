<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Services\UserPaymentService;

if (class_exists('WP_UnitTestCase')) {
	final class UserPaymentServiceTest extends \WP_UnitTestCase
	{
		public function test_stores_next_payment_date_as_user_meta(): void
		{
			$user_id = self::factory()->user->create();
			$service = new UserPaymentService();

			$service->setNextPaymentDate($user_id, '2026-09-30');

			self::assertSame('2026-09-30', get_user_meta($user_id, UserPaymentService::META_NEXT_PAYMENT_DATE, true));
			self::assertSame('2026-09-30', $service->nextPaymentDate($user_id));
		}

		public function test_empty_payment_date_clears_user_meta(): void
		{
			$user_id = self::factory()->user->create();
			$service = new UserPaymentService();
			update_user_meta($user_id, UserPaymentService::META_NEXT_PAYMENT_DATE, '2026-09-30');

			$service->setNextPaymentDate($user_id, '');

			self::assertSame('', $service->nextPaymentDate($user_id));
		}

		public function test_advance_payment_date_moves_forward_four_weeks(): void
		{
			$user_id = self::factory()->user->create();
			$service = new UserPaymentService();

			$next = $service->advanceNextPaymentDate($user_id, '2026-01-30');

			self::assertSame('2026-02-27', $next);
			self::assertSame('2026-02-27', $service->nextPaymentDate($user_id));
			self::assertSame('2026-03-27', $service->advanceNextPaymentDate($user_id));
		}
	}
}
