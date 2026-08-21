<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Services;

use InvalidArgumentException;
use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Validation\DistanceValidator;
use RuntimeException;

final class FrontendFeedbackService
{
	public const FIELD_ACTUAL_RUNNING_DISTANCE  = 'actual_running_distance';
	public const FIELD_ACTUAL_CYCLING_DISTANCE  = 'actual_cycling_distance';
	public const FIELD_ACTUAL_SWIMMING_DISTANCE = 'actual_swimming_distance';
	public const FIELD_EXECUTION_COMMENT        = 'execution_comment';
	public const FIELD_INJURY_COMMENT           = 'injury_comment';

	private const ALLOWED_FIELDS = [
		self::FIELD_ACTUAL_RUNNING_DISTANCE,
		self::FIELD_ACTUAL_CYCLING_DISTANCE,
		self::FIELD_ACTUAL_SWIMMING_DISTANCE,
		self::FIELD_EXECUTION_COMMENT,
		self::FIELD_INJURY_COMMENT,
	];

	public function __construct(
		private readonly TrainingRepository $trainings,
		private readonly SchemaRepository $schemas,
		private readonly SchemaAccess $access,
		private readonly DistanceValidator $distance_validator
	) {
	}

	public function updateField(int $current_user_id, int $training_id, string $field, mixed $value): Training
	{
		if (! in_array($field, self::ALLOWED_FIELDS, true)) {
			throw new InvalidArgumentException('Dit veld mag niet worden bijgewerkt.');
		}

		$training = $this->trainings->findById($training_id);
		if (! $training instanceof Training) {
			throw new RuntimeException('Training niet gevonden.');
		}

		$schema = $this->schemas->findById($training->schemaId);
		if (! $schema || ! $this->access->canUpdateFeedback($current_user_id, $schema->userId)) {
			throw new RuntimeException('Geen toegang tot dit schema.');
		}

		$fields = [
			self::FIELD_ACTUAL_RUNNING_DISTANCE  => $training->actualRunningDistance,
			self::FIELD_ACTUAL_CYCLING_DISTANCE  => $training->actualCyclingDistance,
			self::FIELD_ACTUAL_SWIMMING_DISTANCE => $training->actualSwimmingDistance,
			self::FIELD_EXECUTION_COMMENT        => $training->executionComment,
			self::FIELD_INJURY_COMMENT           => $training->injuryComment,
		];

		if (in_array($field, $this->distanceFields(), true)) {
			$fields[$field] = $this->distance_validator->normalize($value);
		} else {
			$fields[$field] = sanitize_textarea_field((string) $value);
		}

		$this->trainings->updateFeedbackFields($training_id, $fields);

		$updated = $this->trainings->findById($training_id);
		if (! $updated instanceof Training) {
			throw new RuntimeException('Training kon niet opnieuw worden geladen.');
		}

		return $updated;
	}

	/**
	 * @return string[]
	 */
	private function distanceFields(): array
	{
		return [
			self::FIELD_ACTUAL_RUNNING_DISTANCE,
			self::FIELD_ACTUAL_CYCLING_DISTANCE,
			self::FIELD_ACTUAL_SWIMMING_DISTANCE,
		];
	}
}
