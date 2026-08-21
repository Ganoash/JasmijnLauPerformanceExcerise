<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

use InvalidArgumentException;
use LauPerformanceTraining\Domain\Training;
use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Services\UserTrainingPreferenceService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Support\View;
use LauPerformanceTraining\Validation\DateValidator;
use LauPerformanceTraining\Validation\SchemaRequestValidator;
use RuntimeException;

final class SchemaEditorPage
{
	public function __construct(
		private readonly SchemaRepository $schemas,
		private readonly TrainingRepository $trainings,
		private readonly TrainingTypeRepository $training_types,
		private readonly SchemaCreationService $schema_creation_service,
		private readonly SchemaEditorService $schema_editor_service,
		private readonly SchemaRequestValidator $validator,
		private readonly DateValidator $date_validator,
		private readonly DateFactory $date_factory,
		private readonly Nonce $nonce,
		private readonly ?UserTrainingPreferenceService $user_preferences = null
	) {
	}

	public function register(): void
	{
		add_action('admin_post_lpt_save_schema', [$this, 'save']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
	}

	public function render(): void
	{
		if (! current_user_can('manage_training_schemas')) {
			wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'lau-performance-training'));
		}

		$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
		$user    = $user_id > 0 ? get_user_by('id', $user_id) : false;
		if (! $user) {
			wp_die(esc_html__('Gebruiker niet gevonden.', 'lau-performance-training'));
		}

		$error_message = null;
		if (isset($_GET['week_start_date'])) {
			$week_start_date = sanitize_text_field(wp_unslash($_GET['week_start_date']));
			try {
				$week = $this->date_validator->weekFromRequestDate($week_start_date);
			} catch (InvalidArgumentException $exception) {
				$week          = Week::fromDate($this->date_factory->now());
				$error_message = $exception->getMessage();
			}
		} else {
			$week = Week::fromDate($this->date_factory->now());
		}

		$schema_id = $this->schema_creation_service->createForUserWeek($user_id, $week);
		$schema    = $this->schemas->findById($schema_id);
		$show_time_of_day = $this->userPreferences()->trainingsPerDay($user_id) === 2;

		View::render(
			'admin/schema-editor.php',
			[
				'action_url'            => admin_url('admin-post.php'),
				'error_message'         => $error_message,
				'frontend_url'          => home_url('/training-schema/' . $user_id . '/' . $week->startDate() . '/'),
				'linked_types'          => $this->linkedTypeMap($schema_id),
				'linked_training_types' => $this->linkedTrainingTypesForEditor($schema_id),
				'nonce'                 => $this->nonce->create(Nonce::ADMIN_SCHEMA_ACTION),
				'schema'                => $schema,
				'show_time_of_day'      => $show_time_of_day,
				'training_types'        => $this->trainingTypesForEditor($schema_id),
				'trainings'             => $this->visibleTrainings($schema_id, $show_time_of_day),
				'user'                  => $user,
				'week'                  => $week,
			]
		);
	}

	public function save(): void
	{
		if (! current_user_can('manage_training_schemas')) {
			wp_die(esc_html__('Je hebt geen toegang om schema’s te wijzigen.', 'lau-performance-training'));
		}

		$nonce = isset($_POST['_lpt_nonce']) ? sanitize_text_field(wp_unslash($_POST['_lpt_nonce'])) : '';
		if (! $this->nonce->verify($nonce, Nonce::ADMIN_SCHEMA_ACTION)) {
			wp_die(esc_html__('Ongeldige beveiligingscode.', 'lau-performance-training'));
		}

		$user_id         = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
		$week_start_date = isset($_POST['week_start_date']) ? sanitize_text_field(wp_unslash($_POST['week_start_date'])) : '';
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each nested training row is sanitized by sanitizePostedTrainingRow().
		$posted_trainings = isset($_POST['trainings']) && is_array($_POST['trainings'])
			? array_map([$this, 'sanitizePostedTrainingRow'], wp_unslash($_POST['trainings']))
			: [];
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		try {
			$week = $this->date_validator->weekFromRequestDate($week_start_date);
			if ($user_id <= 0 || ! get_user_by('id', $user_id)) {
				throw new InvalidArgumentException('Gebruiker niet gevonden.');
			}

			$schema_id = $this->schema_creation_service->createForUserWeek($user_id, $week);
			$rows = $this->validator->validateTrainings($posted_trainings);
			$this->schema_editor_service->saveWeek(get_current_user_id(), $schema_id, $rows);

			wp_safe_redirect(
				admin_url(
					'admin.php?page=lpt-schema-editor&user_id=' . $user_id . '&week_start_date=' . rawurlencode($week->startDate()) . '&updated=1'
				)
			);
			exit;
		} catch (InvalidArgumentException | RuntimeException $exception) {
			wp_safe_redirect(
				add_query_arg(
					'lpt_error',
					rawurlencode($exception->getMessage()),
					admin_url('admin.php?page=lpt-schema-editor&user_id=' . $user_id . '&week_start_date=' . rawurlencode($week_start_date))
				)
			);
			exit;
		}
	}

	/**
	 * @param mixed $row
	 * @return array<string,mixed>
	 */
	public function sanitizePostedTrainingRow(mixed $row): array
	{
		if (! is_array($row)) {
			return [];
		}

		return [
			'training_id'               => isset($row['training_id']) ? (int) $row['training_id'] : 0,
			'description'               => isset($row['description']) ? sanitize_textarea_field((string) $row['description']) : '',
			'primary_training_type_id'  => isset($row['primary_training_type_id']) ? (int) $row['primary_training_type_id'] : 0,
			'linked_training_type_ids'  => isset($row['linked_training_type_ids']) && is_array($row['linked_training_type_ids'])
				? array_map('intval', $row['linked_training_type_ids'])
				: [],
			'coach_comment'             => isset($row['coach_comment']) ? sanitize_textarea_field((string) $row['coach_comment']) : '',
		];
	}

	/**
	 * @return array<int,int[]>
	 */
	private function linkedTypeMap(int $schema_id): array
	{
		$map = [];
		foreach ($this->trainings->findBySchema($schema_id) as $training) {
			$map[$training->id] = $this->trainings->linkedTypeIds($training->id);
		}

		return $map;
	}

    /**
     * @param string $hook_suffix
     * @return void
     */
    public function enqueueScripts(string $hook_suffix): void
    {
        if (
            ! isset($_GET['page'])
            || 'lpt-schema-editor' !== $_GET['page']
        ) {
            return;
        }

        $user_id = isset($_GET['user_id'])
            ? absint($_GET['user_id'])
            : get_current_user_id();

        wp_enqueue_script(
            'lpt-schema-editor',
            LPT_PLUGIN_URL . 'assets/admin/schema-view.js',
            [],
            LPT_VERSION,
            true
        );

        wp_localize_script(
            'lpt-schema-editor',
            'lptSchemaEditor',
            [
                'userId' => (string) $user_id,
            ]
        );
    }

	/**
	 * @return TrainingType[]
	 */
	private function linkedTrainingTypesForEditor(int $schema_id): array
	{
		$used_ids = [];
		foreach ($this->trainings->findBySchema($schema_id) as $training) {
			foreach ($this->trainings->linkedTypeIds($training->id) as $linked_type_id) {
				$used_ids[] = $linked_type_id;
			}
		}

		$used_ids = array_unique($used_ids);

		return array_values(
			array_filter(
				$this->training_types->all(false),
				static fn (TrainingType $type): bool => $type->active || in_array($type->id, $used_ids, true)
			)
		);
	}

	/**
	 * @return TrainingType[]
	 */
	private function trainingTypesForEditor(int $schema_id): array
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

		return array_values(
			array_filter(
				$this->training_types->all(false),
				static fn (TrainingType $type): bool => $type->active || in_array($type->id, $used_ids, true)
			)
		);
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
