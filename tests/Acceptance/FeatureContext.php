<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance;

use Behat\Behat\Context\Context;
use LauPerformanceTraining\Activation\Activator;
use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\DistanceTotalService;
use LauPerformanceTraining\Services\FrontendFeedbackService;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Validation\DistanceValidator;
use PHPUnit\Framework\Assert;

final class FeatureContext implements Context
{
	private SchemaRepository $schemas;
	private TrainingRepository $trainings;
	private TrainingTypeRepository $trainingTypes;
	private SchemaCreationService $schemaCreation;
	private int $currentUserId = 0;
	private int $athleteUserId = 0;
	private int $athleteAUserId = 0;
	private int $athleteBUserId = 0;
	private int $coachUserId = 0;
	private int $schemaId = 0;
	private int $trainingId = 0;
	private bool $accessDenied = false;
	private ?float $previousRunningTotal = null;
	private ?float $latestRunningTotal = null;
	private string $savedField = '';
	private string $visibleExerciseName = '';
	private string $editorExerciseName = '';

	/** @var int[] */
	private array $createdUserIds = [];

	/** @var int[] */
	private array $createdTrainingTypeIds = [];

	public function __construct()
	{
		$this->bootWordPress();

		$this->schemas       = new SchemaRepository();
		$this->trainings     = new TrainingRepository();
		$this->trainingTypes = new TrainingTypeRepository();
		$this->schemaCreation = new SchemaCreationService(
			$this->schemas,
			$this->trainings,
			new DateFactory()
		);

		Activator::activate();
	}

	/**
	 * @AfterScenario
	 */
	public function cleanUpScenario(): void
	{
		global $wpdb;

		foreach ($this->createdUserIds as $userId) {
			$this->schemas->deleteByUser($userId);
			if (function_exists('wp_delete_user')) {
				wp_delete_user($userId);
			}
		}

		foreach ($this->createdTrainingTypeIds as $typeId) {
			$wpdb->delete($wpdb->prefix . 'lpt_training_type_links', ['training_type_id' => $typeId], ['%d']);
			$wpdb->delete($wpdb->prefix . 'lpt_training_types', ['id' => $typeId], ['%d']);
		}

		wp_set_current_user(0);

		$this->createdUserIds = [];
		$this->createdTrainingTypeIds = [];
	}

	/**
	 * @Given a logged-in athlete has a schema for week :weekStartDate
	 */
	public function loggedInAthleteHasSchemaForWeek(string $weekStartDate): void
	{
		$this->athleteUserId = $this->createUser('athlete');
		$this->currentUserId = $this->athleteUserId;
		wp_set_current_user($this->currentUserId);

		$typeId = $this->createTrainingType('Duurloop', 'running', 'kilometers', true);
		$this->schemaId = $this->schemaCreation->createForUserWeek($this->athleteUserId, $weekStartDate);
		$training = $this->findTraining(0, TrainingRepository::TIME_MORNING);
		$this->trainingId = $training->id;
		$this->trainings->updateCoachFields(
			$this->trainingId,
			[
				'description'              => 'Rustige duurloop',
				'primary_training_type_id' => $typeId,
				'coach_comment'            => '',
			]
		);

		$this->previousRunningTotal = $this->runningTotal();
	}

	/**
	 * @When the athlete opens their schema
	 */
	public function athleteOpensTheirSchema(): void
	{
		$schema = $this->schemas->findById($this->schemaId);
		Assert::assertNotNull($schema);
		Assert::assertTrue((new SchemaAccess())->canViewSchema($this->currentUserId, $schema->userId));
	}

	/**
	 * @When enters :distance as the actual distance for Monday morning
	 */
	public function entersActualDistanceForMondayMorning(string $distance): void
	{
		$service = new FrontendFeedbackService(
			$this->trainings,
			$this->schemas,
			new SchemaAccess(),
			new DistanceValidator()
		);
		$service->updateField($this->currentUserId, $this->trainingId, FrontendFeedbackService::FIELD_ACTUAL_DISTANCE, $distance);

		$this->savedField = FrontendFeedbackService::FIELD_ACTUAL_DISTANCE;
		$this->latestRunningTotal = $this->runningTotal();
	}

	/**
	 * @When enters :comment as uitvoering
	 */
	public function entersUitvoering(string $comment): void
	{
		$service = new FrontendFeedbackService(
			$this->trainings,
			$this->schemas,
			new SchemaAccess(),
			new DistanceValidator()
		);
		$service->updateField($this->currentUserId, $this->trainingId, FrontendFeedbackService::FIELD_EXECUTION_COMMENT, $comment);

		$this->savedField = FrontendFeedbackService::FIELD_EXECUTION_COMMENT;
	}

	/**
	 * @Then the field is saved
	 */
	public function fieldIsSaved(): void
	{
		$training = $this->trainings->findById($this->trainingId);

		Assert::assertNotNull($training);
		Assert::assertNotSame('', $this->savedField);
		Assert::assertSame('Ging goed', $training->executionComment);
	}

	/**
	 * @Then the running total updates immediately on the page
	 */
	public function runningTotalUpdatesImmediately(): void
	{
		Assert::assertSame(0.0, $this->previousRunningTotal);
		Assert::assertSame(8.5, $this->latestRunningTotal);
	}

	/**
	 * @Given athlete A has a schema for week :weekStartDate
	 */
	public function athleteAHasSchemaForWeek(string $weekStartDate): void
	{
		$this->athleteAUserId = $this->createUser('athlete-a');
		$this->schemaId = $this->schemaCreation->createForUserWeek($this->athleteAUserId, $weekStartDate);
	}

	/**
	 * @Given athlete B is logged in
	 */
	public function athleteBIsLoggedIn(): void
	{
		$this->athleteBUserId = $this->createUser('athlete-b');
		$this->currentUserId = $this->athleteBUserId;
		wp_set_current_user($this->currentUserId);
	}

	/**
	 * @When athlete B opens athlete A's schema URL
	 */
	public function athleteBOpensAthleteASchemaUrl(): void
	{
		$this->accessDenied = ! (new SchemaAccess())->canViewSchema($this->athleteBUserId, $this->athleteAUserId);
	}

	/**
	 * @Then access is denied
	 */
	public function accessIsDenied(): void
	{
		Assert::assertTrue($this->accessDenied);
	}

	/**
	 * @Given an athlete has filled in feedback for Monday morning
	 */
	public function athleteHasFilledInFeedbackForMondayMorning(): void
	{
		$this->loggedInAthleteHasSchemaForWeek('2026-08-17');
		$this->entersActualDistanceForMondayMorning('8.5');
		$this->entersUitvoering('Ging goed');
	}

	/**
	 * @Given a coach opens the admin schema editor
	 */
	public function coachOpensTheAdminSchemaEditor(): void
	{
		$this->coachUserId = $this->createUser('coach', ['manage_training_schemas']);
		$this->currentUserId = $this->coachUserId;
		wp_set_current_user($this->currentUserId);

		Assert::assertTrue((new SchemaAccess())->canUpdateCoachFields($this->currentUserId));
	}

	/**
	 * @When the coach changes the Monday morning training description
	 */
	public function coachChangesMondayMorningDescription(): void
	{
		$service = new SchemaEditorService($this->trainings, new SchemaAccess());
		$service->saveWeek(
			$this->currentUserId,
			[
				[
					'training_id'              => $this->trainingId,
					'description'              => 'Nieuwe training',
					'primary_training_type_id' => null,
					'linked_training_type_ids' => [],
					'coach_comment'            => 'Rustig aan',
				],
			]
		);
	}

	/**
	 * @Then the new training description is saved
	 */
	public function newTrainingDescriptionIsSaved(): void
	{
		$training = $this->trainings->findById($this->trainingId);

		Assert::assertNotNull($training);
		Assert::assertSame('Nieuwe training', $training->description);
	}

	/**
	 * @Then the athlete feedback remains unchanged
	 */
	public function athleteFeedbackRemainsUnchanged(): void
	{
		$training = $this->trainings->findById($this->trainingId);

		Assert::assertNotNull($training);
		Assert::assertSame(8.5, $training->actualDistance);
		Assert::assertSame('Ging goed', $training->executionComment);
	}

	/**
	 * @Given a training schema uses an exercise that is now inactive
	 */
	public function trainingSchemaUsesExerciseThatIsNowInactive(): void
	{
		$this->athleteUserId = $this->createUser('inactive-athlete');
		$typeId = $this->createTrainingType('Historische oefening', 'running', 'kilometers', false);
		$this->schemaId = $this->schemaCreation->createForUserWeek($this->athleteUserId, '2026-08-17');
		$training = $this->findTraining(0, TrainingRepository::TIME_MORNING);
		$this->trainingId = $training->id;

		$this->trainings->updateCoachFields(
			$this->trainingId,
			[
				'description'              => 'Historische training',
				'primary_training_type_id' => $typeId,
				'coach_comment'            => '',
			]
		);
		$this->trainings->replaceLinkedTypes($this->trainingId, [$typeId]);
	}

	/**
	 * @When the athlete opens the schema
	 */
	public function athleteOpensTheSchema(): void
	{
		$this->currentUserId = $this->athleteUserId;
		wp_set_current_user($this->currentUserId);

		$typeIds = $this->trainings->linkedTypeIds($this->trainingId);
		$type = $this->trainingTypes->find($typeIds[0] ?? 0);

		Assert::assertInstanceOf(TrainingType::class, $type);
		$this->visibleExerciseName = $type->name;
	}

	/**
	 * @Then the inactive exercise is still visible
	 */
	public function inactiveExerciseIsStillVisible(): void
	{
		Assert::assertSame('Historische oefening', $this->visibleExerciseName);
	}

	/**
	 * @Then the inactive exercise is not offered for new schema selections
	 */
	public function inactiveExerciseIsNotOfferedForNewSelections(): void
	{
		$activeNames = array_map(
			static fn (TrainingType $type): string => $type->name,
			$this->trainingTypes->all(true)
		);

		Assert::assertNotContains('Historische oefening', $activeNames);
	}

	private function bootWordPress(): void
	{
		if (! defined('WP_USE_THEMES')) {
			define('WP_USE_THEMES', false);
		}

		if (! defined('ABSPATH')) {
			require getenv('LPT_WORDPRESS_BOOTSTRAP') ?: '/var/www/html/wp-load.php';
		}

		if (! class_exists(Activator::class)) {
			require dirname(__DIR__, 2) . '/lau-performance-training.php';
		}

		if (! function_exists('wp_delete_user')) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
	}

	/**
	 * @param string[] $capabilities
	 */
	private function createUser(string $label, array $capabilities = []): int
	{
		$userId = wp_insert_user(
			[
				'user_login' => 'lpt_behat_' . $label . '_' . uniqid(),
				'user_pass'  => wp_generate_password(24),
				'user_email' => 'lpt-behat-' . $label . '-' . uniqid() . '@example.test',
				'role'       => 'subscriber',
			]
		);

		Assert::assertIsInt($userId);
		$this->createdUserIds[] = $userId;

		$user = get_user_by('id', $userId);
		Assert::assertInstanceOf(\WP_User::class, $user);
		foreach ($capabilities as $capability) {
			$user->add_cap($capability);
		}

		return $userId;
	}

	private function createTrainingType(string $name, string $category, string $unit, bool $active): int
	{
		$typeId = $this->trainingTypes->create(
			[
				'name'       => $name,
				'category'   => $category,
				'unit'       => $unit,
				'linked_url' => '',
				'active'     => $active,
			]
		);

		$this->createdTrainingTypeIds[] = $typeId;

		return $typeId;
	}

	private function findTraining(int $dayIndex, string $timeOfDay): object
	{
		$training = $this->trainings->findBySlot($this->schemaId, $dayIndex, $timeOfDay);

		Assert::assertNotNull($training);

		return $training;
	}

	private function runningTotal(): float
	{
		$trainings = $this->trainings->findBySchema($this->schemaId);
		$primaryTypes = [];
		foreach ($trainings as $training) {
			$primaryTypes[$training->id] = $training->primaryTrainingTypeId
				? $this->trainingTypes->find($training->primaryTrainingTypeId)
				: null;
		}

		return (new DistanceTotalService())->calculate($trainings, $primaryTypes)->runningKm;
	}
}
