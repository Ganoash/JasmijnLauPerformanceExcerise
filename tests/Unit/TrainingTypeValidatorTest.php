<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Unit;

use InvalidArgumentException;
use LauPerformanceTraining\Validation\TrainingTypeValidator;
use PHPUnit\Framework\TestCase;

final class TrainingTypeValidatorTest extends TestCase
{
	public function test_requires_name(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new TrainingTypeValidator())->validate(['category' => 'running', 'unit' => 'kilometers']);
	}

	public function test_sanitizes_valid_training_type(): void
	{
		$result = (new TrainingTypeValidator())->validate(
			[
				'name'       => 'Duurloop',
				'category'   => 'running',
				'unit'       => 'kilometers',
				'linked_url' => 'https://example.test/duurloop',
				'active'     => '1',
			]
		);

		self::assertSame('Duurloop', $result['name']);
		self::assertTrue($result['active']);
	}
}
