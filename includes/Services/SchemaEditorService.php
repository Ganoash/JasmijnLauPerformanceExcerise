<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Services;

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
	public function saveWeek(int $current_user_id, array $rows): void
	{
		if (! $this->access->canUpdateCoachFields($current_user_id)) {
			throw new RuntimeException('Geen toegang om schema’s te wijzigen.');
		}

		foreach ($rows as $row) {
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
