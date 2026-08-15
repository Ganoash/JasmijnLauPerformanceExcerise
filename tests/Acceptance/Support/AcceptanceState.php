<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Support;

use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Support\DateFactory;

final class AcceptanceState
{
	private static ?self $instance = null;

	public SchemaRepository $schemas;
	public TrainingRepository $trainings;
	public TrainingTypeRepository $trainingTypes;
	public SchemaCreationService $schemaCreation;
	public int $currentUserId = 0;
	public int $athleteUserId = 0;
	public int $athleteAUserId = 0;
	public int $athleteBUserId = 0;
	public int $coachUserId = 0;
	public int $schemaId = 0;
	public int $trainingId = 0;
	public bool $accessDenied = false;
	public ?float $previousRunningTotal = null;
	public ?float $latestRunningTotal = null;
	public string $savedField = '';
	public string $visibleExerciseName = '';
	public string $dashboardHtml = '';
	public string $frontendFeedbackValue = '';
	public string $createdExerciseName = '';

	/** @var int[] */
	public array $createdUserIds = [];

	/** @var int[] */
	public array $createdTrainingTypeIds = [];

	private function __construct()
	{
		$this->schemas        = new SchemaRepository();
		$this->trainings      = new TrainingRepository();
		$this->trainingTypes  = new TrainingTypeRepository();
		$this->schemaCreation = new SchemaCreationService(
			$this->schemas,
			$this->trainings,
			new DateFactory()
		);
	}

	public static function instance(): self
	{
		if (! self::$instance instanceof self) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function resetScenarioState(): void
	{
		$this->currentUserId = 0;
		$this->athleteUserId = 0;
		$this->athleteAUserId = 0;
		$this->athleteBUserId = 0;
		$this->coachUserId = 0;
		$this->schemaId = 0;
		$this->trainingId = 0;
		$this->accessDenied = false;
		$this->previousRunningTotal = null;
		$this->latestRunningTotal = null;
		$this->savedField = '';
		$this->visibleExerciseName = '';
		$this->dashboardHtml = '';
		$this->frontendFeedbackValue = '';
		$this->createdExerciseName = '';
		$this->createdUserIds = [];
		$this->createdTrainingTypeIds = [];
	}
}
