<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Admin;

final class AdminMenu
{
	private const MENU_SLUG          = 'lpt-training';
	private const SCHEMA_EDITOR_SLUG = 'lpt-schema-editor';

	public function __construct(
		private readonly UserOverviewPage $user_overview_page,
		private readonly SchemaEditorPage $schema_editor_page,
		private readonly TrainingTypePage $training_type_page
	) {
	}

	public function register(): void
	{
		add_action('admin_menu', [$this, 'registerMenus']);
		add_action('admin_head', [$this, 'hideInternalSchemaEditorSubmenu']);
		$this->user_overview_page->register();
		$this->schema_editor_page->register();
		$this->training_type_page->register();
	}

	public function registerMenus(): void
	{
		add_menu_page(
			'Training schema’s',
			'Training schema’s',
			'manage_training_schemas',
			self::MENU_SLUG,
			[$this->user_overview_page, 'render'],
			'dashicons-clipboard',
			30
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Schema’s bewerken',
			'Schema’s bewerken',
			'manage_training_schemas',
			self::MENU_SLUG,
			[$this->user_overview_page, 'render']
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Schema bewerken',
			'Schema bewerken',
			'manage_training_schemas',
			self::SCHEMA_EDITOR_SLUG,
			[$this->schema_editor_page, 'render']
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Oefeningen',
			'Oefeningen',
			'edit_training_types',
			'lpt-training-types',
			[$this->training_type_page, 'render']
		);
	}

	public function hideInternalSchemaEditorSubmenu(): void
	{
		global $submenu;

		if (! isset($submenu[self::MENU_SLUG]) || ! is_array($submenu[self::MENU_SLUG])) {
			return;
		}

		$submenu[self::MENU_SLUG] = array_values(
			array_filter(
				$submenu[self::MENU_SLUG],
				static fn (array $item): bool => ($item[2] ?? '') !== self::SCHEMA_EDITOR_SLUG
			)
		);
	}
}
