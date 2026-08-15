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

if (class_exists('WP_UnitTestCase')) {
	final class FrontendAjaxDieException extends \RuntimeException
	{
	}

	final class FrontendAjaxTest extends \WP_UnitTestCase
	{
		public function test_ajax_save_rejects_missing_nonce(): void
		{
			if (! defined('DOING_AJAX')) {
				define('DOING_AJAX', true);
			}

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

			add_filter(
				'wp_die_ajax_handler',
				static fn (): callable => static function (): void {
					throw new FrontendAjaxDieException();
				}
			);

			ob_start();
			try {
				$action->handle();
				self::fail('Expected the AJAX handler to reject the request.');
			} catch (FrontendAjaxDieException) {
				$response = (string) ob_get_clean();

				self::assertStringContainsString('Ongeldige beveiligingscode.', $response);
			}
		}
	}
}
