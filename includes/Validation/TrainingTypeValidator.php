<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Validation;

use InvalidArgumentException;

final class TrainingTypeValidator
{
	/**
	 * @param array<string,mixed> $input
	 * @return array{name:string,category:string,unit:string,linked_url:string,active:bool}
	 */
	public function validate(array $input): array
	{
		$name     = $this->string($input['name'] ?? '');
		$category = $this->string($input['category'] ?? '');
		$unit     = $this->string($input['unit'] ?? '');

		if ($name === '') {
			throw new InvalidArgumentException('Naam is verplicht.');
		}

		if ($category === '') {
			throw new InvalidArgumentException('Categorie is verplicht.');
		}

		if ($unit === '') {
			throw new InvalidArgumentException('Eenheid is verplicht.');
		}

		return [
			'name'       => $name,
			'category'   => $category,
			'unit'       => $unit,
			'linked_url' => $this->url((string) ($input['linked_url'] ?? '')),
			'active'     => isset($input['active']) && (string) $input['active'] === '1',
		];
	}

	private function string(mixed $value): string
	{
		if (function_exists('sanitize_text_field')) {
			return sanitize_text_field((string) $value);
		}

		return trim(strip_tags((string) $value));
	}

	private function url(string $value): string
	{
		if (function_exists('esc_url_raw')) {
			return esc_url_raw($value);
		}

		$value = trim($value);
		return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
	}
}
