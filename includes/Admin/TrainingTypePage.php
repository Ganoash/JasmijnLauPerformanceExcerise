<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

use InvalidArgumentException;
use LauPerformanceTraining\Domain\TrainingType;
use LauPerformanceTraining\Repositories\TrainingTypeRepository;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Support\View;
use LauPerformanceTraining\Validation\TrainingTypeValidator;

final class TrainingTypePage
{
	public function __construct(
		private readonly TrainingTypeRepository $training_types,
		private readonly TrainingTypeValidator $validator,
		private readonly Nonce $nonce
	) {
	}

	public function register(): void
	{
		add_action('admin_post_lpt_save_training_type', [$this, 'save']);
	}

	public function render(): void
	{
		if (! current_user_can('edit_training_types')) {
			wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'lau-performance-training'));
		}

		$editing = $this->editingType();

		View::render(
			'admin/training-types.php',
			[
				'action_url' => admin_url('admin-post.php'),
				'editing'    => $editing,
				'error'      => isset($_GET['lpt_error']) ? sanitize_text_field(wp_unslash($_GET['lpt_error'])) : '',
				'nonce'      => $this->nonce->create(Nonce::TRAINING_TYPE_ACTION),
				'types'      => $this->training_types->all(false),
			]
		);
	}

	public function save(): void
	{
		if (! current_user_can('edit_training_types')) {
			wp_die(esc_html__('Je hebt geen toegang om oefeningen te wijzigen.', 'lau-performance-training'));
		}

		$nonce = isset($_POST['_lpt_nonce']) ? sanitize_text_field(wp_unslash($_POST['_lpt_nonce'])) : '';
		if (! $this->nonce->verify($nonce, Nonce::TRAINING_TYPE_ACTION)) {
			wp_die(esc_html__('Ongeldige beveiligingscode.', 'lau-performance-training'));
		}

		try {
			$fields = $this->validator->validate(wp_unslash($_POST));
			$id     = isset($_POST['training_type_id']) ? (int) $_POST['training_type_id'] : 0;

			if ($id > 0) {
				$this->training_types->update($id, $fields);
			} else {
				$this->training_types->create($fields);
			}

			wp_safe_redirect(admin_url('admin.php?page=lpt-training-types&updated=1'));
			exit;
		} catch (InvalidArgumentException $exception) {
			wp_safe_redirect(
				add_query_arg(
					'lpt_error',
					rawurlencode($exception->getMessage()),
					admin_url('admin.php?page=lpt-training-types')
				)
			);
			exit;
		}
	}

	private function editingType(): ?TrainingType
	{
		$id = isset($_GET['training_type_id']) ? (int) $_GET['training_type_id'] : 0;
		if ($id <= 0) {
			return null;
		}

		return $this->training_types->find($id);
	}
}
