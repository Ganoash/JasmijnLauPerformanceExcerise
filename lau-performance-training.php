<?php
/**
 * Plugin Name: Lau Performance Training
 * Description: Weekly training schemas for Lau Performance athletes and coaches.
 * Version: 0.1.0
 * Author: Jasmijn Lau Performance
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

define('LPT_VERSION', '0.1.0');
define('LPT_PLUGIN_FILE', __FILE__);
define('LPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LPT_PLUGIN_BASENAME', plugin_basename(__FILE__));

$lpt_autoload = LPT_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($lpt_autoload)) {
	require_once $lpt_autoload;
} else {
	require_once LPT_PLUGIN_DIR . 'includes/autoload.php';
}

register_activation_hook(
	__FILE__,
	static function (): void {
		\LauPerformanceTraining\Activation\Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		\LauPerformanceTraining\Activation\Activator::deactivate();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		$plugin = new \LauPerformanceTraining\Plugin();
		$plugin->register();
	}
);
