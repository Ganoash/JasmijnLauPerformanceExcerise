<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

final class AdminMenu
{
	public function __construct(private readonly TrainingTypePage $training_type_page)
	{
	}

	public function register(): void
	{
		add_action('admin_menu', [$this, 'registerMenus']);
		$this->training_type_page->register();
	}

	public function registerMenus(): void
	{
		add_menu_page(
			'Training schema’s',
			'Training schema’s',
			'manage_training_schemas',
			'lpt-training',
			[$this->training_type_page, 'render'],
			'dashicons-clipboard',
			30
		);

		add_submenu_page(
			'lpt-training',
			'Oefeningen',
			'Oefeningen',
			'edit_training_types',
			'lpt-training-types',
			[$this->training_type_page, 'render']
		);
	}
}
