<?php
declare(strict_types=1);

$plugin_root = dirname(__DIR__);
$autoload    = $plugin_root . '/vendor/autoload.php';
$wp_phpunit  = $plugin_root . '/vendor/wp-phpunit/wp-phpunit';

if (! is_readable($autoload)) {
	fwrite(STDERR, "Composer dependencies are missing. Run `composer install` before integration tests.\n");
	exit(1);
}

if (! is_readable($wp_phpunit . '/includes/bootstrap.php')) {
	fwrite(STDERR, "WordPress PHPUnit library is missing. Run `composer install` before integration tests.\n");
	exit(1);
}

require_once $autoload;

define('WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php');
define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $plugin_root . '/vendor/yoast/phpunit-polyfills');

require_once $wp_phpunit . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ($plugin_root): void {
		require $plugin_root . '/lau-performance-training.php';
		\LauPerformanceTraining\Activation\Activator::activate();
	}
);

require $wp_phpunit . '/includes/bootstrap.php';
