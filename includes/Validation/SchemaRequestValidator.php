<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Validation;

use InvalidArgumentException;

final class SchemaRequestValidator
{
	/**
	 * @param mixed $input
	 * @return array<int,array{training_id:int,description:string,primary_training_type_id:int|null,linked_training_type_ids:int[],coach_comment:string}>
	 */
	public function validateTrainings(mixed $input): array
	{
		if (! is_array($input)) {
			throw new InvalidArgumentException('Ongeldige schema-invoer.');
		}

		$rows = [];
		foreach ($input as $row) {
			if (! is_array($row)) {
				continue;
			}

			$training_id = isset($row['training_id']) ? (int) $row['training_id'] : 0;
			if ($training_id <= 0) {
				throw new InvalidArgumentException('Training ontbreekt.');
			}

			$primary_id = isset($row['primary_training_type_id']) ? (int) $row['primary_training_type_id'] : 0;
			$linked_ids = is_array($row['linked_training_type_ids'] ?? null)
				? array_values(array_unique(array_filter(array_map('intval', $row['linked_training_type_ids']))))
				: [];

			$rows[] = [
				'training_id'               => $training_id,
				'description'               => $this->textarea($row['description'] ?? ''),
				'primary_training_type_id'  => $primary_id > 0 ? $primary_id : null,
				'linked_training_type_ids'  => $linked_ids,
				'coach_comment'             => $this->textarea($row['coach_comment'] ?? ''),
			];
		}

		return $rows;
	}

	private function textarea(mixed $value): string
	{
		if (function_exists('sanitize_textarea_field')) {
			return sanitize_textarea_field((string) $value);
		}

		return trim(strip_tags((string) $value));
	}
}
