<?php
declare(strict_types=1);

namespace LauPerformanceTraining;

use LauPerformanceTraining\Admin\AdminMenu;
use LauPerformanceTraining\Admin\SchemaEditorPage;
use LauPerformanceTraining\Admin\TrainingTypePage;
use LauPerformanceTraining\Admin\UserOverviewPage;
use LauPerformanceTraining\Cron\SchemaCreationJob;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\SchemaRequestValidator;
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
		$schema_access = new SchemaAccess();
		$nonce         = new Nonce();

		$training_type_page = new TrainingTypePage(
			$training_type_repository,
			new TrainingTypeValidator(),
			$nonce
		);
		$schema_editor_page = new SchemaEditorPage(
			$schema_repository,
			$training_repository,
			$training_type_repository,
			$schema_creation_service,
			new SchemaEditorService($training_repository, $schema_access),
			new SchemaRequestValidator(),
			$date_factory,
			$nonce
		);

		(new AdminMenu(new UserOverviewPage($date_factory), $schema_editor_page, $training_type_page))->register();
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
