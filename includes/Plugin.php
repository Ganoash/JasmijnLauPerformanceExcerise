<?php
declare(strict_types=1);

namespace LauPerformanceTraining;

use LauPerformanceTraining\Admin\AdminMenu;
use LauPerformanceTraining\Admin\TrainingTypePage;
use LauPerformanceTraining\Cron\SchemaCreationJob;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\TrainingTypeValidator;

final class Plugin
{
	public function register(): void
	{
		$date_factory            = new DateFactory();
		$schema_repository       = new SchemaRepository();
		$training_repository     = new TrainingRepository();
		$training_type_repository = new TrainingTypeRepository();
		$schema_creation_service = new SchemaCreationService(
			$schema_repository,
			$training_repository,
			$date_factory
		);

		$training_type_page = new TrainingTypePage(
			$training_type_repository,
			new TrainingTypeValidator(),
			new Nonce()
		);

		(new AdminMenu($training_type_page))->register();
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
