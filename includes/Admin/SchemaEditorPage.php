<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

use InvalidArgumentException;
use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Repositories\SchemaRepository;
use LauPerformanceTraining\Repositories\TrainingRepository;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Services\SchemaCreationService;
use LauPerformanceTraining\Services\SchemaEditorService;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Support\View;
use LauPerformanceTraining\Validation\SchemaRequestValidator;

final class SchemaEditorPage
{
	public function __construct(
		private readonly SchemaRepository $schemas,
		private readonly TrainingRepository $trainings,
		private readonly TrainingTypeRepository $training_types,
		private readonly SchemaCreationService $schema_creation_service,
		private readonly SchemaEditorService $schema_editor_service,
		private readonly SchemaRequestValidator $validator,
		private readonly DateFactory $date_factory,
		private readonly Nonce $nonce
	) {
	}

	public function register(): void
	{
		add_action('admin_post_lpt_save_schema', [$this, 'save']);
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

		$week = isset($_GET['week_start_date'])
			? Week::fromDateString(sanitize_text_field(wp_unslash($_GET['week_start_date'])))
			: Week::fromDate($this->date_factory->now());

		$schema_id = $this->schema_creation_service->createForUserWeek($user_id, $week);
		$schema    = $this->schemas->findById($schema_id);

		View::render(
			'admin/schema-editor.php',
			[
				'action_url'            => admin_url('admin-post.php'),
				'frontend_url'          => home_url('/training-schema/' . $user_id . '/' . $week->startDate() . '/'),
				'linked_types'          => $this->linkedTypeMap($schema_id),
				'linked_training_types' => $this->linkedTrainingTypesForEditor($schema_id),
				'nonce'                 => $this->nonce->create(Nonce::ADMIN_SCHEMA_ACTION),
				'schema'                => $schema,
				'training_types'        => $this->trainingTypesForEditor($schema_id),
				'trainings'             => $this->trainings->findBySchema($schema_id),
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
			$rows = $this->validator->validateTrainings($posted_trainings);
			$this->schema_editor_service->saveWeek(get_current_user_id(), $rows);

			wp_safe_redirect(
				admin_url(
					'admin.php?page=lpt-schema-editor&user_id=' . $user_id . '&week_start_date=' . rawurlencode($week_start_date) . '&updated=1'
				)
			);
			exit;
		} catch (InvalidArgumentException $exception) {
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
				static fn (TrainingType $type): bool => strtolower($type->category) === 'strength'
					&& ($type->active || in_array($type->id, $used_ids, true))
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
}
