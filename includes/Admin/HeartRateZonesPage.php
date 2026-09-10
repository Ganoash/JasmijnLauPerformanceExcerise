<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

use DateTimeImmutable;
use InvalidArgumentException;
use LauPerformanceTraining\Domain\HeartRateZones;
use LauPerformanceTraining\Repositories\HeartRateZonesRepository;
use LauPerformanceTraining\Support\Nonce;
use LauPerformanceTraining\Support\View;
use LauPerformanceTraining\Validation\HeartRateZonesValidator;

final class HeartRateZonesPage
{
	public function __construct(
		private readonly HeartRateZonesRepository $heart_rate_zones,
		private readonly HeartRateZonesValidator $validator,
		private readonly Nonce $nonce
	) {
	}

	public function register(): void
	{
		add_action('admin_post_lpt_save_heart_rate_zones', [$this, 'save']);
	}

	public function render(): void
	{
		if (! current_user_can('manage_training_schemas')) {
			wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'lau-performance-training'));
		}

		$user = $this->selectedUser();
		$latest = $user ? $this->heart_rate_zones->findLatestByUser((int) $user->ID) : null;

		View::render(
			'admin/heart-rate-zones.php',
			[
				'action_url' => admin_url('admin-post.php'),
				'error'      => isset($_GET['lpt_error']) ? sanitize_text_field(wp_unslash($_GET['lpt_error'])) : '',
				'latest'     => $latest,
				'nonce'      => $this->nonce->create(Nonce::HEART_RATE_ZONES_ACTION),
				'test_date'  => $this->testDate($latest),
				'user'       => $user,
			]
		);
	}

	public function save(): void
	{
		if (! current_user_can('manage_training_schemas')) {
			wp_die(esc_html__('Je hebt geen toegang om hartslagzones te wijzigen.', 'lau-performance-training'));
		}

		$nonce = isset($_POST['_lpt_nonce']) ? sanitize_text_field(wp_unslash($_POST['_lpt_nonce'])) : '';
		if (! $this->nonce->verify($nonce, Nonce::HEART_RATE_ZONES_ACTION)) {
			wp_die(esc_html__('Ongeldige beveiligingscode.', 'lau-performance-training'));
		}

		$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
		if ($user_id <= 0 || ! get_user_by('id', $user_id)) {
			wp_die(esc_html__('Onbekende gebruiker.', 'lau-performance-training'));
		}

		try {
			$this->heart_rate_zones->saveForUser($user_id, $this->validator->validate(wp_unslash($_POST)));

			wp_safe_redirect(admin_url('admin.php?page=lpt-heart-rate-zones&user_id=' . $user_id . '&updated=1'));
			exit;
		} catch (InvalidArgumentException $exception) {
			wp_safe_redirect(
				add_query_arg(
					[
						'lpt_error' => rawurlencode($exception->getMessage()),
						'user_id'   => $user_id,
					],
					admin_url('admin.php?page=lpt-heart-rate-zones')
				)
			);
			exit;
		}
	}

	private function selectedUser(): ?\WP_User
	{
		$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
		if ($user_id <= 0) {
			return null;
		}

		$user = get_user_by('id', $user_id);

		return $user instanceof \WP_User ? $user : null;
	}

	private function testDate(?HeartRateZones $latest): string
	{
		if ($latest) {
			return $latest->lactateTestDate;
		}

		return (new DateTimeImmutable('now', wp_timezone()))->format('Y-m-d');
	}
}
