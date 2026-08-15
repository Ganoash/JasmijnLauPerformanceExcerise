<?php
declare(strict_types=1);

define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'wordpress');
define('DB_USER', getenv('MYSQL_USER') ?: 'wordpress');
define('DB_PASSWORD', getenv('MYSQL_PASSWORD') ?: 'wordpress');
define('DB_HOST', getenv('MYSQL_HOST') ?: 'db');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

$table_prefix = getenv('WP_TESTS_TABLE_PREFIX') ?: 'wptests_';

define('WP_TESTS_DOMAIN', 'example.test');
define('WP_TESTS_EMAIL', 'admin@example.test');
define('WP_TESTS_TITLE', 'Lau Performance Training Tests');
define('WP_PHP_BINARY', PHP_BINARY);

define('AUTH_KEY', 'testing');
define('SECURE_AUTH_KEY', 'testing');
define('LOGGED_IN_KEY', 'testing');
define('NONCE_KEY', 'testing');
define('AUTH_SALT', 'testing');
define('SECURE_AUTH_SALT', 'testing');
define('LOGGED_IN_SALT', 'testing');
define('NONCE_SALT', 'testing');

define('WP_DEBUG', true);
define('WP_ENVIRONMENT_TYPE', 'local');
define('ABSPATH', getenv('WP_TESTS_WORDPRESS_DIR') ?: '/var/www/html/');
