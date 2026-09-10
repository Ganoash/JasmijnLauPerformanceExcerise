<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Blocks;

use LauPerformanceTraining\Repositories\HeartRateZonesRepository;
use LauPerformanceTraining\Support\View;

final class HeartRateZonesBlock
{
	public function __construct(private readonly HeartRateZonesRepository $heart_rate_zones)
	{
	}

	public function register(): void
	{
		add_action('init', [$this, 'registerBlock']);
	}

	public function registerBlock(): void
	{
		wp_register_script(
			'lpt-heart-rate-zones-block',
			LPT_PLUGIN_URL . 'assets/frontend/heart-rate-zones-block.js',
			['wp-blocks', 'wp-element'],
			LPT_VERSION,
			true
		);

		wp_register_style(
			'lpt-heart-rate-zones-block',
			LPT_PLUGIN_URL . 'assets/frontend/heart-rate-zones-block.css',
			[],
			LPT_VERSION
		);

		register_block_type(
			'lau-performance-training/heart-rate-zones',
			[
				'editor_script'   => 'lpt-heart-rate-zones-block',
				'style'           => 'lpt-heart-rate-zones-block',
				'render_callback' => [$this, 'render'],
			]
		);
	}

	/**
	 * @param array<string,mixed> $attributes
	 */
	public function render(array $attributes = []): string
	{
		unset($attributes);

		wp_enqueue_style('lpt-heart-rate-zones-block');

		$user_id = get_current_user_id();
		$zones = $user_id > 0 ? $this->heart_rate_zones->findLatestByUser($user_id) : null;

		ob_start();
		View::render(
			'frontend/heart-rate-zones-block.php',
			[
				'lactate_test_url' => home_url('/hartslag-zones/lactaattest/'),
				'login_url'        => wp_login_url((string) get_permalink()),
				'logged_in'        => $user_id > 0,
				'zones'            => $zones,
			]
		);

		return (string) ob_get_clean();
	}
}
