<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use LauPerformanceTraining\Validation\SchemaRequestValidator;
use PHPUnit\Framework\TestCase;

final class SchemaRequestValidatorTest extends TestCase
{
	public function test_validates_coach_managed_fields_only(): void
	{
		$rows = (new SchemaRequestValidator())->validateTrainings(
			[
				[
					'training_id'              => '10',
					'description'              => 'Rustig lopen',
					'primary_training_type_id' => '2',
					'linked_training_type_ids' => ['2', '3', '3'],
					'coach_comment'            => 'Let op techniek',
					'actual_distance'          => '99',
				],
			]
		);

		self::assertSame(10, $rows[0]['training_id']);
		self::assertSame([2, 3], $rows[0]['linked_training_type_ids']);
		self::assertArrayNotHasKey('actual_distance', $rows[0]);
	}
}
