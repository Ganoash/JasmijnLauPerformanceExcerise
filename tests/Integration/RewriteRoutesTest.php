<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Frontend\RewriteRoutes;
use LauPerformanceTraining\Frontend\SchemaPage;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\DistanceTotalService;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\DateValidator;

if (class_exists('WP_UnitTestCase')) {
	final class RewriteRoutesTest extends \WP_UnitTestCase
	{
		public function test_registers_schema_query_vars(): void
		{
			$schemas = new SchemaRepository();
			$trainings = new TrainingRepository();
			$routes = new RewriteRoutes(
				new SchemaPage(
					$schemas,
					$trainings,
					new TrainingTypeRepository(),
					new SchemaCreationService($schemas, $trainings, new DateFactory()),
					new SchemaAccess(static fn (): bool => false),
					new DistanceTotalService(),
					new DateValidator(),
					new Nonce()
				)
			);

			$vars = $routes->queryVars([]);

			self::assertContains('lpt_schema_page', $vars);
			self::assertContains('lpt_user_id', $vars);
			self::assertContains('lpt_week_start_date', $vars);
		}
	}
}
