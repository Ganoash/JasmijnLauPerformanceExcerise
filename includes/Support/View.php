<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Support;

final class View
{
	/**
	 * @param array<string,mixed> $data
	 */
	public static function render(string $template, array $data = []): void
	{
		$path = LPT_PLUGIN_DIR . 'templates/' . ltrim($template, '/');
		if (! is_readable($path)) {
			return;
		}

		extract($data, EXTR_SKIP);
		include $path;
	}
}
