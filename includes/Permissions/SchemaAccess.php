<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Permissions;

final class SchemaAccess
{
	public const CAP_MANAGE_SCHEMAS = 'manage_training_schemas';
	public const CAP_VIEW_ALL       = 'view_all_training_schemas';

	/** @var callable(string):bool */
	private $capability_checker;

	/**
	 * @param callable(string):bool|null $capability_checker
	 */
	public function __construct(?callable $capability_checker = null)
	{
		$this->capability_checker = $capability_checker ?? static fn (string $capability): bool => current_user_can($capability);
	}

	public function canViewSchema(int $current_user_id, int $schema_owner_id): bool
	{
		if ($current_user_id <= 0) {
			return false;
		}

		return $current_user_id === $schema_owner_id
			|| $this->hasCapability(self::CAP_VIEW_ALL)
			|| $this->hasCapability(self::CAP_MANAGE_SCHEMAS);
	}

	public function canViewAndEditSchema(int $current_user_id, int $schema_owner_id): bool
	{
		if ($current_user_id <= 0) {
			return false;
		}

		return $current_user_id === $schema_owner_id
			|| $this->hasCapability(self::CAP_MANAGE_SCHEMAS);
	}

	public function canUpdateFeedback(int $current_user_id, int $schema_owner_id): bool
	{
		return $this->canViewAndEditSchema($current_user_id, $schema_owner_id);
	}

	public function canUpdateCoachFields(int $current_user_id): bool
	{
		return $current_user_id > 0 && $this->hasCapability(self::CAP_MANAGE_SCHEMAS);
	}

	private function hasCapability(string $capability): bool
	{
		return (bool) call_user_func($this->capability_checker, $capability);
	}
}
