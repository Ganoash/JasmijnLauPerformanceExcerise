<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Frontend;

final class RewriteRoutes
{
	public function __construct(private readonly SchemaPage $schema_page)
	{
	}

	public function register(): void
	{
		add_action('init', [$this, 'registerRoute']);
		add_filter('query_vars', [$this, 'queryVars']);
		add_action('template_redirect', [$this, 'renderSchemaPage']);
	}

	public function registerRoute(): void
	{
		add_rewrite_tag('%lpt_schema_page%', '1');
		add_rewrite_tag('%lpt_user_id%', '([0-9]+)');
		add_rewrite_tag('%lpt_week_start_date%', '([0-9]{4}-[0-9]{2}-[0-9]{2})');
		add_rewrite_rule(
			'^training-schema/([0-9]+)/([0-9]{4}-[0-9]{2}-[0-9]{2})/?$',
			'index.php?lpt_schema_page=1&lpt_user_id=$matches[1]&lpt_week_start_date=$matches[2]',
			'top'
		);
	}

	/**
	 * @param string[] $vars
	 * @return string[]
	 */
	public function queryVars(array $vars): array
	{
		$vars[] = 'lpt_schema_page';
		$vars[] = 'lpt_user_id';
		$vars[] = 'lpt_week_start_date';

		return $vars;
	}

	public function renderSchemaPage(): void
	{
		if ((string) get_query_var('lpt_schema_page') !== '1') {
			return;
		}

		$this->schema_page->render(
			(int) get_query_var('lpt_user_id'),
			(string) get_query_var('lpt_week_start_date')
		);
		exit;
	}
}
