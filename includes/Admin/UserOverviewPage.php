<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

use LauPerformanceTraining\Domain\Week;
use LauPerformanceTraining\Support\DateFactory;
use LauPerformanceTraining\Support\View;

final class UserOverviewPage
{
	public function __construct(private readonly DateFactory $date_factory)
	{
	}

	public function render(): void
	{
		if (! current_user_can('manage_training_schemas')) {
			wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'lau-performance-training'));
		}

		$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
		$users  = get_users(
			[
				'search'         => $search !== '' ? '*' . $search . '*' : '',
				'search_columns' => ['user_login', 'user_email', 'display_name'],
				'number'         => 50,
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			]
		);

		View::render(
			'admin/user-overview.php',
			[
				'current_week' => Week::fromDate($this->date_factory->now())->startDate(),
				'search'       => $search,
				'users'        => $users,
			]
		);
	}
}
