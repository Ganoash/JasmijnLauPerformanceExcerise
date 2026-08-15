<?php
declare(strict_types=1);

namespace LauPerformanceTraining\Activation;

final class DatabaseInstaller
{
	public function install(): void
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$schemas         = $wpdb->prefix . 'lpt_schemas';
		$trainings       = $wpdb->prefix . 'lpt_trainings';
		$links           = $wpdb->prefix . 'lpt_training_type_links';
		$types           = $wpdb->prefix . 'lpt_training_types';

		dbDelta(
			"CREATE TABLE {$schemas} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NOT NULL,
				week_start_date DATE NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY user_week (user_id, week_start_date),
				KEY user_id (user_id)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$types} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(190) NOT NULL,
				category VARCHAR(80) NOT NULL,
				unit VARCHAR(40) NOT NULL,
				linked_url TEXT NULL,
				active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY active (active),
				KEY category (category)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$trainings} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				schema_id BIGINT UNSIGNED NOT NULL,
				day_index TINYINT UNSIGNED NOT NULL,
				time_of_day VARCHAR(20) NOT NULL,
				description TEXT NULL,
				primary_training_type_id BIGINT UNSIGNED NULL,
				actual_distance DECIMAL(10,2) NULL,
				execution_comment TEXT NULL,
				injury_comment TEXT NULL,
				coach_comment TEXT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY schema_slot (schema_id, day_index, time_of_day),
				KEY schema_id (schema_id),
				KEY primary_training_type_id (primary_training_type_id)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$links} (
				training_id BIGINT UNSIGNED NOT NULL,
				training_type_id BIGINT UNSIGNED NOT NULL,
				PRIMARY KEY  (training_id, training_type_id),
				KEY training_type_id (training_type_id)
			) {$charset_collate};"
		);

		add_option('lpt_weeks_ahead', 2, '', false);
		update_option('lpt_db_version', LPT_VERSION, false);
	}
}
