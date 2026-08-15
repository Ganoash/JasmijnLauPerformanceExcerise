<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Tests\Integration;

use LauPerformanceTraining\Admin\TrainingTypePage;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Validation\TrainingTypeValidator;

if (class_exists('WP_UnitTestCase')) {
	final class TrainingTypePageRenderTest extends \WP_UnitTestCase
	{
		public function test_training_type_form_explains_each_field(): void
		{
			wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

			ob_start();
			(new TrainingTypePage(
				new TrainingTypeRepository(),
				new TrainingTypeValidator(),
				new Nonce()
			))->render();
			$html = (string) ob_get_clean();

			self::assertStringContainsString('Naam die Jasmijn in het schema kiest', $html);
			self::assertStringContainsString('Sportgroep voor filtering en totalen', $html);
			self::assertStringContainsString('Meetwaarde voor geplande en uitgevoerde afstand', $html);
			self::assertStringContainsString('Zwemmen met meters wordt automatisch omgerekend naar kilometers', $html);
			self::assertStringContainsString('Optionele link naar uitleg, video of externe instructie', $html);
			self::assertStringContainsString('Uitgeschakelde oefeningen blijven zichtbaar in bestaande schema’s', $html);
		}
	}
}
