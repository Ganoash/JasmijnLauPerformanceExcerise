<?php
declare(strict_types=1);

spl_autoload_register(
	static function (string $class): void {
		$prefix = 'LauPerformanceTraining\\';
		if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
			return;
		}

		$relative_class = substr($class, strlen($prefix));
		$file           = __DIR__ . '/' . str_replace('\\', '/', $relative_class) . '.php';

		if (is_readable($file)) {
			require_once $file;
		}
	}
);
