<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Ajax\FrontendTrainingSaveAction;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Services\FrontendFeedbackService;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\DistanceValidator;

if (class_exists('WP_Ajax_UnitTestCase')) {
	final class FrontendAjaxTest extends \WP_Ajax_UnitTestCase
	{
		public function test_ajax_save_rejects_missing_nonce(): void
		{
			wp_set_current_user(self::factory()->user->create());
			$action = new FrontendTrainingSaveAction(
				new FrontendFeedbackService(
					new TrainingRepository(),
					new SchemaRepository(),
					new SchemaAccess(),
					new DistanceValidator()
				),
				new Nonce()
			);

			$this->expectException(\WPAjaxDieStopException::class);

			$action->handle();
		}
	}
}
