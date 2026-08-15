<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Cron;

use LauPerformanceTraining\Services\SchemaCreationService;

final class SchemaCreationJob
{
	public function __construct(private readonly SchemaCreationService $schema_creation_service)
	{
	}

	public function register(): void
	{
		add_action('lpt_create_training_schemas', [$this, 'run']);
	}

	public function run(): void
	{
		$this->schema_creation_service->createForAllUsers();
	}
}
