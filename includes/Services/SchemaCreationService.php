<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Services;

use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Support\DateFactory;

final class SchemaCreationService
{
	public function __construct(
		private readonly SchemaRepository $schemas,
		private readonly TrainingRepository $trainings,
		private readonly DateFactory $date_factory
	) {
	}

	public function createForUserWeek(int $user_id, Week|string $week): int
	{
		$week_start_date = $week instanceof Week ? $week->startDate() : Week::fromDateString($week)->startDate();
		$schema_id       = $this->schemas->create($user_id, $week_start_date);

		foreach (TrainingRepository::fixedSlots() as $slot) {
			$this->trainings->createSlot($schema_id, $slot['day_index'], $slot['time_of_day']);
		}

		return $schema_id;
	}

	/**
	 * @return int[]
	 */
	public function createForUserRange(int $user_id, ?int $weeks_ahead = null): array
	{
		$created_or_existing_ids = [];
		$current_week            = Week::fromDate($this->date_factory->now());
		$weeks_ahead             = $weeks_ahead ?? $this->configuredWeeksAhead();

		for ($offset = 0; $offset <= $weeks_ahead; $offset++) {
			$created_or_existing_ids[] = $this->createForUserWeek($user_id, $current_week->plusWeeks($offset));
		}

		return $created_or_existing_ids;
	}

	public function createForAllUsers(?int $weeks_ahead = null): void
	{
		$user_ids = get_users(['fields' => 'ID']);

		foreach ($user_ids as $user_id) {
			$this->createForUserRange((int) $user_id, $weeks_ahead);
		}
	}

	private function configuredWeeksAhead(): int
	{
		return max(0, (int) get_option('lpt_weeks_ahead', 2));
	}
}
