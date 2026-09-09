<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Services;

use InvalidArgumentException;
use LauPerformanceTraining\Domain\Goal;
use LauPerformanceTraining\Permissions\GoalAccess;
use LauPerformanceTraining\Repositories\GoalRepository;
use LauPerformanceTraining\Validation\GoalValidator;
use RuntimeException;

final class GoalService
{
	/** @var callable(int):bool */
	private $user_exists;

	/**
	 * @param callable(int):bool|null $user_exists
	 */
	public function __construct(
		private readonly GoalRepository $goals,
		private readonly GoalValidator $validator,
		private readonly GoalAccess $access,
		?callable $user_exists = null
	) {
		$this->user_exists = $user_exists ?? static fn (int $user_id): bool => (bool) get_user_by('id', $user_id);
	}

	/**
	 * @param array<string,mixed> $input
	 * @return array{goal_id:int,actual_time_completed:bool}
	 */
	public function save(int $current_user_id, int $target_user_id, array $input, ?int $goal_id = null): array
	{
		$this->authorizeTarget($current_user_id, $target_user_id);
		$fields = $this->validator->validate($input);

		$existing = null;
		if ($goal_id !== null) {
			$existing = $this->existingGoalForUser($goal_id, $target_user_id);
		}

		if ($fields['active'] && $this->goals->activeCountForUser($target_user_id, $goal_id) >= 3) {
			throw new InvalidArgumentException('Een gebruiker kan maximaal 3 actieve doelen hebben.');
		}

		if ($this->goals->dateExistsForUser($target_user_id, $fields['goal_date'], $goal_id)) {
			throw new InvalidArgumentException('Er bestaat al een wedstrijd op deze datum.');
		}

		$completed = $fields['actual_time'] !== '' && (! $existing instanceof Goal || $existing->actualTime === '');

		if ($existing instanceof Goal) {
			$this->goals->update($existing->id, $fields);
			return [
				'goal_id'                => $existing->id,
				'actual_time_completed' => $completed,
			];
		}

		$new_goal_id = $this->goals->create($target_user_id, $fields);

		return [
			'goal_id'                => $new_goal_id,
			'actual_time_completed' => $completed,
		];
	}

	public function delete(int $current_user_id, int $target_user_id, int $goal_id): void
	{
		$this->authorizeTarget($current_user_id, $target_user_id);
		$this->goals->deleteForUser($goal_id, $target_user_id);
	}

	private function authorizeTarget(int $current_user_id, int $target_user_id): void
	{
		if (! (bool) call_user_func($this->user_exists, $target_user_id)) {
			throw new InvalidArgumentException('Gebruiker niet gevonden.');
		}

		if (! $this->access->canManageGoalUser($current_user_id, $target_user_id)) {
			throw new RuntimeException('Je hebt geen toegang tot deze doelen.');
		}
	}

	private function existingGoalForUser(int $goal_id, int $target_user_id): Goal
	{
		$goal = $this->goals->findById($goal_id);
		if (! $goal || $goal->userId !== $target_user_id) {
			throw new InvalidArgumentException('Wedstrijd niet gevonden.');
		}

		return $goal;
	}
}
