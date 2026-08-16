<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Acceptance\Context;

use LauPerformanceTraining\Frontend\RewriteRoutes;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\TrainingRepository;
use PHPUnit\Framework\Assert;

final class MissingSchemaCreationContext extends BaseAcceptanceContext
{
	/**
	 * @When the athlete opens their missing schema for week :weekStartDate
	 */
	public function athleteOpensTheirMissingSchemaForWeek(string $weekStartDate): void
	{
		Assert::assertNull($this->state->schemas->findByUserAndWeek($this->state->athleteUserId, $weekStartDate));
		Assert::assertTrue((new SchemaAccess())->canViewSchema($this->state->currentUserId, $this->state->athleteUserId));

		$this->state->schemaId = $this->state->schemaCreation->createForUserWeek($this->state->athleteUserId, $weekStartDate);
	}

	/**
	 * @When the coach opens the athlete schema editor for week :weekStartDate
	 */
	public function coachOpensTheAthleteSchemaEditorForWeek(string $weekStartDate): void
	{
		Assert::assertNull($this->state->schemas->findByUserAndWeek($this->state->athleteUserId, $weekStartDate));
		Assert::assertTrue((new SchemaAccess())->canUpdateCoachFields($this->state->currentUserId));

		$this->state->schemaId = $this->state->schemaCreation->createForUserWeek($this->state->athleteUserId, $weekStartDate);
	}

	/**
	 * @When the frontend schema URL :schemaUrl is resolved
	 */
	public function frontendSchemaUrlIsResolved(string $schemaUrl): void
	{
		$routes = new RewriteRoutes($this->schemaPageForRoutingTest());

		$routes->registerRoute();
		$this->state->resolvedSchemaUrl = $schemaUrl;
		$this->state->resolvedQueryVars = $this->resolveSchemaUrl($schemaUrl);
	}

	/**
	 * @Then WordPress routes it to user :userId and week :weekStartDate
	 */
	public function wordpressRoutesItToUserAndWeek(string $userId, string $weekStartDate): void
	{
		Assert::assertSame('1', $this->state->resolvedQueryVars['lpt_schema_page'] ?? null);
		Assert::assertSame($userId, $this->state->resolvedQueryVars['lpt_user_id'] ?? null);
		Assert::assertSame($weekStartDate, $this->state->resolvedQueryVars['lpt_week_start_date'] ?? null);
	}

	/**
	 * @Then an empty schema with fourteen training slots is created
	 */
	public function emptySchemaWithFourteenTrainingSlotsIsCreated(): void
	{
		$trainings = $this->state->trainings->findBySchema($this->state->schemaId);

		Assert::assertCount(14, $trainings);
		Assert::assertSame(TrainingRepository::TIME_MORNING, $trainings[0]->timeOfDay);
		Assert::assertSame(TrainingRepository::TIME_AFTERNOON, $trainings[1]->timeOfDay);

		foreach ($trainings as $training) {
			Assert::assertSame('', $training->description);
			Assert::assertNull($training->primaryTrainingTypeId);
			Assert::assertNull($training->actualDistance);
			Assert::assertSame('', $training->executionComment);
			Assert::assertSame('', $training->injuryComment);
			Assert::assertSame('', $training->coachComment);
		}
	}

	/**
	 * @return array<string,string>
	 */
	private function resolveSchemaUrl(string $schemaUrl): array
	{
		global $wp_rewrite;

		$path = trim((string) wp_parse_url($schemaUrl, PHP_URL_PATH), '/');
		$wp_rewrite->set_permalink_structure('/%postname%/');
		$rules = $wp_rewrite->rewrite_rules();

		foreach ($rules as $regex => $query) {
			if (preg_match('#^' . $regex . '#', $path, $matches) !== 1) {
				continue;
			}

			return $this->substituteRewriteMatches((string) $query, $matches);
		}

		Assert::fail('No rewrite rule matched ' . $schemaUrl);
	}

	/**
	 * @param string[] $matches
	 * @return array<string,string>
	 */
	private function substituteRewriteMatches(string $query, array $matches): array
	{
		$query = preg_replace_callback(
			'/\\$matches\\[(\\d+)\\]/',
			static fn (array $match): string => $matches[(int) $match[1]] ?? '',
			$query
		);

		Assert::assertIsString($query);

		parse_str((string) wp_parse_url($query, PHP_URL_QUERY), $vars);

		/** @var array<string,string> $vars */
		return $vars;
	}

	private function schemaPageForRoutingTest(): \LauPerformanceTraining\Frontend\SchemaPage
	{
		return new \LauPerformanceTraining\Frontend\SchemaPage(
			$this->state->schemas,
			$this->state->trainings,
			$this->state->trainingTypes,
			$this->state->schemaCreation,
			new SchemaAccess(),
			new \LauPerformanceTraining\Services\DistanceTotalService(),
			new \LauPerformanceTraining\Validation\DateValidator(),
			new \LauPerformanceTraining\Support\Nonce()
		);
	}
}
