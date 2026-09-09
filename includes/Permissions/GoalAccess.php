<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Permissions;

final class GoalAccess
{
	/** @var callable(string):bool */
	private $capability_checker;

	/**
	 * @param callable(string):bool|null $capability_checker
	 */
	public function __construct(?callable $capability_checker = null)
	{
		$this->capability_checker = $capability_checker ?? static fn (string $capability): bool => current_user_can($capability);
	}

	public function canManageGoalUser(int $current_user_id, int $target_user_id): bool
	{
		if ($current_user_id <= 0 || $target_user_id <= 0) {
			return false;
		}

		return $current_user_id === $target_user_id
			|| (bool) call_user_func($this->capability_checker, SchemaAccess::CAP_MANAGE_SCHEMAS);
	}
}
