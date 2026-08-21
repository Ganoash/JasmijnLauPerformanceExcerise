<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Frontend;

use InvalidArgumentException;
use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Permissions\SchemaAccess;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\DistanceTotalService;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\UserTrainingPreferenceService;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Support\View;
use LauPerformanceTraining\Validation\DateValidator;

final class SchemaPage
{
	public function __construct(
		private readonly SchemaRepository $schemas,
		private readonly TrainingRepository $trainings,
		private readonly TrainingTypeRepository $training_types,
		private readonly SchemaCreationService $schema_creation_service,
		private readonly SchemaAccess $access,
		private readonly DistanceTotalService $distance_totals,
		private readonly DateValidator $date_validator,
		private readonly Nonce $nonce,
		private readonly ?UserTrainingPreferenceService $user_preferences = null
	) {
	}

	public function render(int $user_id, string $week_start_date): void
	{
		if (! is_user_logged_in()) {
			auth_redirect();
		}

		$current_user_id = get_current_user_id();
		if (! $this->access->canViewSchema($current_user_id, $user_id)) {
			status_header(403);
			wp_die(esc_html__('Je hebt geen toegang tot dit schema.', 'lau-performance-training'));
		}

		try {
			$week = $this->date_validator->weekFromRequestDate($week_start_date);
		} catch (InvalidArgumentException) {
			status_header(404);
			wp_die(esc_html__('Schema niet gevonden.', 'lau-performance-training'));
		}

		$schema_id = $this->schema_creation_service->createForUserWeek($user_id, $week);
		$schema    = $this->schemas->findById($schema_id);
		$user      = get_user_by('id', $user_id);

		if (! $schema || ! $user) {
			status_header(404);
			wp_die(esc_html__('Schema niet gevonden.', 'lau-performance-training'));
		}

		wp_enqueue_style(
			'lpt-schema-view',
			LPT_PLUGIN_URL . 'assets/frontend/schema-view.css',
			[],
			LPT_VERSION
		);
		wp_enqueue_script(
			'lpt-schema-view',
			LPT_PLUGIN_URL . 'assets/frontend/schema-view.js',
			[],
			LPT_VERSION,
			true
		);
		wp_localize_script(
			'lpt-schema-view',
			'lptSchemaView',
			[
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => $this->nonce->create(Nonce::FRONTEND_FEEDBACK_ACTION),
                'userId'    => (string) $user_id,
		        'schemaUrl' => home_url('/training-schema/'),
			]
		);

		$show_time_of_day = $this->userPreferences()->trainingsPerDay($user_id) === 2;
		$trainings     = $this->visibleTrainings($schema_id, $show_time_of_day);
		$primary_types = $this->primaryTypesByTraining($schema_id);
		$linked_types  = $this->linkedTypesByTraining($schema_id);
		$content       = $this->schemaContent(
			[
				'linked_types'  => $linked_types,
				'primary_types' => $primary_types,
				'schema'        => $schema,
				'show_time_of_day' => $show_time_of_day,
				'totals'        => $this->distance_totals->calculate($trainings, $primary_types, $linked_types),
				'trainings'     => $trainings,
				'user'          => $user,
				'week'          => $week,
			]
		);

		if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
			$this->renderBlockThemeDocument($content);
			return;
		}

		get_header();
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		get_footer();
	}

	/**
	 * @param array<string,mixed> $data
	 */
	private function schemaContent(array $data): string
	{
		ob_start();
		View::render('frontend/schema-page.php', $data);

		return (string) ob_get_clean();
	}

	private function renderBlockThemeDocument(string $content): void
	{
		$header = $this->blockTemplatePart('header');
		$footer = $this->blockTemplatePart('footer');
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo('charset'); ?>" />
			<?php wp_head(); ?>
		</head>
		<body <?php body_class('lpt-schema-document'); ?>>
		<?php wp_body_open(); ?>
		<div class="wp-site-blocks">
			<?php echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}

	private function blockTemplatePart(string $slug): string
	{
		ob_start();
		block_template_part($slug);

		return (string) ob_get_clean();
	}

	/**
	 * @return array<int,TrainingType|null>
	 */
	private function primaryTypesByTraining(int $schema_id): array
	{
		$map = [];
		foreach ($this->trainings->findBySchema($schema_id) as $training) {
			$map[$training->id] = $training->primaryTrainingTypeId
				? $this->training_types->find($training->primaryTrainingTypeId)
				: null;
		}

		return $map;
	}

	/**
	 * @return array<int,TrainingType[]>
	 */
	private function linkedTypesByTraining(int $schema_id): array
	{
		$map = [];
		foreach ($this->trainings->findBySchema($schema_id) as $training) {
			$map[$training->id] = [];
			foreach ($this->trainings->linkedTypeIds($training->id) as $type_id) {
				$type = $this->training_types->find($type_id);
				if ($type instanceof TrainingType) {
					$map[$training->id][] = $type;
				}
			}
		}

		return $map;
	}

	private function userPreferences(): UserTrainingPreferenceService
	{
		return $this->user_preferences ?? new UserTrainingPreferenceService();
	}

	/**
	 * @return Training[]
	 */
	private function visibleTrainings(int $schema_id, bool $show_time_of_day): array
	{
		$trainings = $this->trainings->findBySchema($schema_id);
		if ($show_time_of_day) {
			return $trainings;
		}

		return array_values(
			array_filter(
				$trainings,
				static fn (Training $training): bool => $training->timeOfDay === TrainingRepository::TIME_AFTERNOON
			)
		);
	}
}
