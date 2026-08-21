<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Services;

use LauPerformanceTraining\Repositories\TrainingRepository;

final class UserTrainingPreferenceService
{
	public const META_TRAININGS_PER_DAY = 'lpt_trainings_per_day';

	public function trainingsPerDay(int $user_id): int
	{
		$value = (int) get_user_meta($user_id, self::META_TRAININGS_PER_DAY, true);

		return $value === 1 ? 1 : 2;
	}

	public function setTrainingsPerDay(int $user_id, int $trainings_per_day): void
	{
		update_user_meta($user_id, self::META_TRAININGS_PER_DAY, $trainings_per_day === 1 ? 1 : 2);
	}

	/**
	 * @return array<int,array{day_index:int,time_of_day:string}>
	 */
	public function slotsForUser(int $user_id): array
	{
		return TrainingRepository::fixedSlots($this->trainingsPerDay($user_id));
	}
}
