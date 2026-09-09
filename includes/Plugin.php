<?php
declare(strict_types=1);

namespace LauPerformanceTraining;

use LauPerformanceTraining\Admin\AdminMenu;
use LauPerformanceTraining\Admin\SchemaEditorPage;
use LauPerformanceTraining\Admin\TrainingTypePage;
use LauPerformanceTraining\Admin\UserOverviewPage;
use LauPerformanceTraining\Activation\DatabaseInstaller;
use LauPerformanceTraining\Ajax\FrontendTrainingSaveAction;
use LauPerformanceTraining\Blocks\DashboardSchemaBlock;
use LauPerformanceTraining\Cron\SchemaCreationJob;
use LauPerformanceTraining\Frontend\GoalsPage;
use LauPerformanceTraining\Frontend\RewriteRoutes;
use LauPerformanceTraining\Frontend\SchemaPage;
use LauPerformanceTraining\Permissions\GoalAccess;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\GoalRepository;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\DistanceTotalService;
use LauPerformanceTraining\Services\FrontendFeedbackService;
use LauPerformanceTraining\Services\GoalService;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Services\UserTrainingPreferenceService;
use LauPerformanceTraining\Shortcodes\GoalsLinkShortcode;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\DateValidator;
use LauPerformanceTraining\Validation\DistanceValidator;
use LauPerformanceTraining\Validation\GoalValidator;
use LauPerformanceTraining\Validation\SchemaRequestValidator;
use LauPerformanceTraining\Validation\TrainingTypeValidator;

final class Plugin
{
	public function register(): void
	{
		$database_installer       = new DatabaseInstaller();
		$date_factory            = new DateFactory();
		$goal_repository         = new GoalRepository();
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
		$goal_access   = new GoalAccess();
		$goal_service  = new GoalService($goal_repository, new GoalValidator(), $goal_access);
		$date_validator = new DateValidator();
		$nonce         = new Nonce();

		add_action('init', [$database_installer, 'maybeUpgrade'], 5);

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
			$user_preferences,
			$goal_repository
		);
		$goals_page = new GoalsPage($goal_repository, $goal_service, $goal_access, $nonce);

		(new AdminMenu(
			new UserOverviewPage($date_factory, $user_preferences, $nonce, $schema_repository, $training_repository, $goal_repository),
			$schema_editor_page,
			$training_type_page
		))->register();
		$goals_page->register();
		(new GoalsLinkShortcode($goal_repository))->register();
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
				$user_preferences,
				$goal_repository
			),
			$goals_page
		))->register();

		add_action(
			'user_register',
			static function (int $user_id) use ($schema_creation_service): void {
				$schema_creation_service->createForUserRange($user_id);
			}
		);

		add_filter( 'login_redirect', static function ( $redirect_to, $request, $user ) {
            if ( isset( $user->roles ) && is_array( $user->roles ) ) {
                if ( in_array( 'administrator', $user->roles ) ) {
                    return $redirect_to; // Admins go to the dashboard
                }
            }
            return home_url(); // Everyone else goes to the home page
        }, 10, 3 );


    add_action( 'login_enqueue_scripts', static function () { ?>
                <style type="text/css">
                    #login h1 a, .login h1 a {
                        background-image: url(https://jasmijnlauperformance.nl/wp-content/uploads/2026/09/Orange-White-Bold-Running-Event-Poster-1.png);
                        height: 80px;
                        width: 320px;
                        background-size: contain;
                        background-repeat: no-repeat;
                        padding-bottom: 10px;
                    }
                </style>
        <?php });


		add_action(
			'delete_user',
			static function (int $user_id) use ($schema_repository, $goal_repository): void {
				$schema_repository->deleteByUser($user_id);
				$goal_repository->deleteByUser($user_id);
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
