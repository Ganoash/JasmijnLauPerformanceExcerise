<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Ajax;

use InvalidArgumentException;
use LauPerformanceTraining\Services\FrontendFeedbackService;
use LauPerformanceTraining\Support\Nonce;
use RuntimeException;

final class FrontendTrainingSaveAction
{
	public function __construct(
		private readonly FrontendFeedbackService $feedback_service,
		private readonly Nonce $nonce
	) {
	}

	public function register(): void
	{
		add_action('wp_ajax_lpt_save_training_feedback', [$this, 'handle']);
	}

	public function handle(): void
	{
		if (! is_user_logged_in()) {
			wp_send_json_error(['message' => 'Je moet ingelogd zijn.'], 401);
		}

		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! $this->nonce->verify($nonce, Nonce::FRONTEND_FEEDBACK_ACTION)) {
			wp_send_json_error(['message' => 'Ongeldige beveiligingscode.'], 403);
		}

		$training_id = isset($_POST['training_id']) ? (int) $_POST['training_id'] : 0;
		$field       = isset($_POST['field']) ? sanitize_key(wp_unslash($_POST['field'])) : '';
		$value       = isset($_POST['value']) ? sanitize_textarea_field(wp_unslash($_POST['value'])) : '';

		try {
			$training = $this->feedback_service->updateField(get_current_user_id(), $training_id, $field, $value);

			wp_send_json_success(
				[
					'training_id' => $training->id,
					'field'       => $field,
				]
			);
		} catch (InvalidArgumentException $exception) {
			wp_send_json_error(['message' => $exception->getMessage()], 400);
		} catch (RuntimeException $exception) {
			wp_send_json_error(['message' => $exception->getMessage()], 403);
		}
	}
}
