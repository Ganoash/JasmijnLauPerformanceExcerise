<?php
declare(strict_types=1);

namespace LauPerformanceTraining;

use LauPerformanceTraining\Admin\AdminMenu;
use LauPerformanceTraining\Admin\SchemaEditorPage;
use LauPerformanceTraining\Admin\TrainingTypePage;
use LauPerformanceTraining\Admin\UserOverviewPage;
use LauPerformanceTraining\Ajax\FrontendTrainingSaveAction;
use LauPerformanceTraining\Blocks\DashboardSchemaBlock;
use LauPerformanceTraining\Cron\SchemaCreationJob;
use LauPerformanceTraining\Frontend\RewriteRoutes;
use LauPerformanceTraining\Frontend\SchemaPage;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\DistanceTotalService;
use LauPerformanceTraining\Services\FrontendFeedbackService;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Services\UserTrainingPreferenceService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\DateValidator;
use LauPerformanceTraining\Validation\DistanceValidator;
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
		$user_preferences        = new UserTrainingPreferenceService();
		$schema_creation_service = new SchemaCreationService(
			$schema_repository,
			$training_repository,
			$date_factory
		);
		$schema_access = new SchemaAccess();
		$date_validator = new DateValidator();
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
			new SchemaEditorService($training_repository, $training_type_repository, $schema_access),
			new SchemaRequestValidator(),
			$date_validator,
			$date_factory,
			$nonce,
			$user_preferences
		);

		(new AdminMenu(
			new UserOverviewPage($date_factory, $user_preferences, $nonce),
			$schema_editor_page,
			$training_type_page
		))->register();
		(new SchemaCreationJob($schema_creation_service))->register();
		(new DashboardSchemaBlock($date_factory))->register();
		(new FrontendTrainingSaveAction(
			new FrontendFeedbackService(
				$training_repository,
				$schema_repository,
				$schema_access,
				new DistanceValidator()
			),
			$nonce
		))->register();
		(new RewriteRoutes(
			new SchemaPage(
				$schema_repository,
				$training_repository,
				$training_type_repository,
				$schema_creation_service,
				$schema_access,
				new DistanceTotalService(),
				$date_validator,
				$nonce,
				$user_preferences
			)
		))->register();

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
					flush_rewrite_rules(true);
					delete_option('lpt_flush_rewrite_rules');
				}
			},
			20
		);
	}
}
