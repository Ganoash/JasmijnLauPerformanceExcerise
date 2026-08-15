<?php
declare(strict_types=1);

namespace LauPerformanceTraining;

use LauPerformanceTraining\Cron\SchemaCreationJob;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Support\DateFactory;

final class Plugin
{
	public function register(): void
	{
		$date_factory            = new DateFactory();
		$schema_repository       = new SchemaRepository();
		$training_repository     = new TrainingRepository();
		$schema_creation_service = new SchemaCreationService(
			$schema_repository,
			$training_repository,
			$date_factory
		);

		(new SchemaCreationJob($schema_creation_service))->register();

		add_action(
			'user_register',
			static function (int $user_id) use ($schema_creation_service): void {
				$schema_creation_service->createForUserRange($user_id);
			}
		);

		add_action(
			'delete_user',
			static function (int $user_id) use ($schema_repository): void {
				$schema_repository->deleteByUser($user_id);
			}
		);

		add_action(
			'init',
			static function (): void {
				if ((bool) get_option('lpt_flush_rewrite_rules', false)) {
					flush_rewrite_rules(false);
					delete_option('lpt_flush_rewrite_rules');
				}
			},
			20
		);
	}
}
