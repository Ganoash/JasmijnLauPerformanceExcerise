<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Blocks;

use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\View;

final class DashboardSchemaBlock
{
	public function __construct(private readonly DateFactory $date_factory)
	{
	}

	public function register(): void
	{
		add_action('init', [$this, 'registerBlock']);
	}

	public function registerBlock(): void
	{
		wp_register_script(
			'lpt-dashboard-block',
			LPT_PLUGIN_URL . 'assets/frontend/dashboard-block.js',
			['wp-blocks', 'wp-element'],
			LPT_VERSION,
			true
		);

		wp_register_style(
			'lpt-dashboard-block',
			LPT_PLUGIN_URL . 'assets/frontend/dashboard-block.css',
			[],
			LPT_VERSION
		);

		register_block_type(
			'lau-performance-training/dashboard-schema',
			[
				'editor_script'   => 'lpt-dashboard-block',
				'style'           => 'lpt-dashboard-block',
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

		if (! is_user_logged_in()) {
			return '';
		}

		wp_enqueue_style('lpt-dashboard-block');

		$user_id = get_current_user_id();
		$current = Week::fromDate($this->date_factory->now());
		$links   = [
			'Vorige week'       => $current->plusWeeks(-1),
			'Huidige week'      => $current,
			'Volgende week'     => $current->plusWeeks(1),
			'Week na volgende'  => $current->plusWeeks(2),
		];

		ob_start();
		View::render(
			'frontend/dashboard-block.php',
			[
				'links'   => $links,
				'user_id' => $user_id,
			]
		);

		return (string) ob_get_clean();
	}
}
