<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Support;

final class Nonce
{
	public const FRONTEND_FEEDBACK_ACTION = 'lpt_frontend_feedback';
	public const ADMIN_SCHEMA_ACTION      = 'lpt_admin_schema';
	public const TRAINING_TYPE_ACTION     = 'lpt_training_type';

	public function create(string $action): string
	{
		return wp_create_nonce($action);
	}

	public function verify(string $nonce, string $action): bool
	{
		return (bool) wp_verify_nonce($nonce, $action);
	}
}
