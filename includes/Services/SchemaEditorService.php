<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Services;

use InvalidArgumentException;
use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use RuntimeException;

final class SchemaEditorService
{
	public function __construct(
		private readonly TrainingRepository $trainings,
		private readonly TrainingTypeRepository $training_types,
		private readonly SchemaAccess $access
	) {
	}

	/**
	 * @param array<int,array{training_id:int,description:string,primary_training_type_id:int|null,linked_training_type_ids:int[],coach_comment:string}> $rows
	 */
	public function saveWeek(int $current_user_id, int $schema_id, array $rows): void
	{
		if (! $this->access->canUpdateCoachFields($current_user_id)) {
			throw new RuntimeException('Geen toegang om schema’s te wijzigen.');
		}

		$schema_training_ids = $this->schemaTrainingIds($schema_id);
		$editable_type_ids   = $this->editableTrainingTypeIds($schema_id);

		foreach ($rows as $row) {
			if (! in_array($row['training_id'], $schema_training_ids, true)) {
				throw new InvalidArgumentException('Training hoort niet bij dit schema.');
			}

			if (
				$row['primary_training_type_id'] !== null
				&& ! in_array($row['primary_training_type_id'], $editable_type_ids, true)
			) {
				throw new InvalidArgumentException('Ongeldige primaire oefening.');
			}

			$this->trainings->updateCoachFields(
				$row['training_id'],
				[
					'description'              => $row['description'],
					'primary_training_type_id' => $row['primary_training_type_id'],
					'coach_comment'            => $row['coach_comment'],
				]
			);
			$this->trainings->replaceLinkedTypes($row['training_id'], $this->strengthTrainingTypeIds($row['linked_training_type_ids']));
		}
	}

	/**
	 * @return int[]
	 */
	private function schemaTrainingIds(int $schema_id): array
	{
		return array_map(
			static fn (Training $training): int => $training->id,
			$this->trainings->findBySchema($schema_id)
		);
	}

	/**
	 * @return int[]
	 */
	private function editableTrainingTypeIds(int $schema_id): array
	{
		$used_ids = [];
		foreach ($this->trainings->findBySchema($schema_id) as $training) {
			if ($training->primaryTrainingTypeId !== null) {
				$used_ids[] = $training->primaryTrainingTypeId;
			}

			foreach ($this->trainings->linkedTypeIds($training->id) as $linked_type_id) {
				$used_ids[] = $linked_type_id;
			}
		}

		$used_ids = array_unique($used_ids);

		return array_map(
			static fn (TrainingType $type): int => $type->id,
			array_filter(
				$this->training_types->all(false),
				static fn (TrainingType $type): bool => $type->active || in_array($type->id, $used_ids, true)
			)
		);
	}

	/**
	 * @param int[] $training_type_ids
	 * @return int[]
	 */
	private function strengthTrainingTypeIds(array $training_type_ids): array
	{
		$strength_ids = [];
		foreach (array_unique(array_filter(array_map('intval', $training_type_ids))) as $training_type_id) {
			$type = $this->training_types->find($training_type_id);
			if ($type && strtolower($type->category) === 'strength') {
				$strength_ids[] = $training_type_id;
			}
		}

		return $strength_ids;
	}
}
