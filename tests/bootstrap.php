<?php
declare(strict_types=1);

$composer = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composer)) {
	require_once $composer;
} else {
	require_once dirname(__DIR__) . '/includes/autoload.php';
}
