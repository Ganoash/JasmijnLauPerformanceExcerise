<?php
declare(strict_types=1);

const ABSPATH = '/var/www/html/';
const ARRAY_A = 'ARRAY_A';
const HOUR_IN_SECONDS = 3600;
const LPT_PLUGIN_DIR = '';
const LPT_PLUGIN_URL = '';
const LPT_VERSION = '0.1.0';

class WP_User
{
	public int $ID = 0;
	public string $display_name = '';
	public string $user_email = '';
}

class WP_Role
{
	public function add_cap(string $capability): void
	{
	}

	public function has_cap(string $capability): bool
	{
		return true;
	}
}

class wpdb
{
	public string $prefix = 'wp_';
	public int $insert_id = 0;

	public function get_charset_collate(): string
	{
		return '';
	}

	/**
	 * @param mixed[] $data
	 * @param string[] $format
	 */
	public function insert(string $table, array $data, array $format = []): int|false
	{
		return 1;
	}

	/**
	 * @param mixed[] $where
	 * @param string[] $where_format
	 */
	public function delete(string $table, array $where, array $where_format = []): int|false
	{
		return 1;
	}

	/**
	 * @param mixed[] $data
	 * @param mixed[] $where
	 * @param string[] $format
	 * @param string[] $where_format
	 */
	public function update(string $table, array $data, array $where, array $format = [], array $where_format = []): int|false
	{
		return 1;
	}

	public function prepare(string $query, mixed ...$args): string
	{
		return vsprintf(str_replace(['%d', '%s', '%f'], ['%s', '%s', '%s'], $query), $args);
	}

	public function query(string $query): int|false
	{
		return 1;
	}

	public function get_row(string $query, string $output = OBJECT): array|object|null
	{
		return null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_results(string $query, string $output = OBJECT): array
	{
		return [];
	}

	/**
	 * @return string[]
	 */
	public function get_col(string $query): array
	{
		return [];
	}

	public function get_var(string $query): mixed
	{
		return null;
	}
}

function add_action(string $hook_name, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): void {}
function add_filter(string $hook_name, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): void {}
function add_menu_page(string $page_title, string $menu_title, string $capability, string $menu_slug, callable|array|string $callback = '', string $icon_url = '', int|float|null $position = null): string { return ''; }
function add_option(string $option, mixed $value = '', string $deprecated = '', bool|string $autoload = true): bool { return true; }
function add_query_arg(string|array $key, mixed $value = null, ?string $url = null): string { return ''; }
function add_rewrite_rule(string $regex, string|array $query, string $after = 'bottom'): void {}
function add_rewrite_tag(string $tag, string $regex, string $query = ''): void {}
function add_role(string $role, string $display_name, array $capabilities = []): ?WP_Role { return new WP_Role(); }
function add_submenu_page(string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable|array|string $callback = '', int|float|null $position = null): string|false { return ''; }
function admin_url(string $path = '', string $scheme = 'admin'): string { return $path; }
function auth_redirect(): void {}
function block_template_part(string $part): void {}
function bloginfo(string $show = ''): void {}
function body_class(string|array $css_class = ''): void {}
function current_time(string $type, int|bool $gmt = 0): string { return '2026-08-15 00:00:00'; }
function current_user_can(string $capability, mixed ...$args): bool { return false; }
function dbDelta(string|array $queries = '', bool $execute = true): array { return []; }
function delete_option(string $option): bool { return true; }
function esc_html__(string $text, string $domain = 'default'): string { return $text; }
function esc_url_raw(string $url, array $protocols = []): string { return $url; }
function flush_rewrite_rules(bool $hard = true): void {}
function get_current_user_id(): int { return 0; }
function get_footer(?string $name = null, array $args = []): void {}
function get_header(?string $name = null, array $args = []): void {}
function get_option(string $option, mixed $default_value = false): mixed { return $default_value; }
function get_query_var(string $query_var, mixed $default_value = ''): mixed { return $default_value; }
function get_role(string $role): ?WP_Role { return new WP_Role(); }
function get_user_by(string $field, int|string $value): WP_User|false { return new WP_User(); }
function get_users(array $args = []): array { return []; }
function home_url(string $path = '', ?string $scheme = null): string { return $path; }
function is_user_logged_in(): bool { return true; }
function language_attributes(string $doctype = 'html'): void {}
function nocache_headers(): void {}
function register_block_type(string $block_type, array $args = []): mixed { return null; }
function sanitize_key(string $key): string { return $key; }
function sanitize_text_field(string $str): string { return trim(strip_tags($str)); }
function sanitize_textarea_field(string $str): string { return trim(strip_tags($str)); }
function status_header(int $code, string $description = ''): void {}
function update_option(string $option, mixed $value, bool|string|null $autoload = null): bool { return true; }
function wp_clear_scheduled_hook(string $hook, array $args = [], bool $wp_error = false): int|false { return 0; }
function wp_create_nonce(string|int $action = -1): string { return 'nonce'; }
function wp_die(string $message = '', string $title = '', array|string $args = []): never { exit($message); }
function wp_enqueue_script(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, array|bool $args = []): bool { return true; }
function wp_enqueue_style(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, string $media = 'all'): bool { return true; }
function wp_footer(): void {}
function wp_head(): void {}
function wp_body_open(): void {}
function wp_localize_script(string $handle, string $object_name, array $l10n): bool { return true; }
function wp_next_scheduled(string $hook, array $args = []): int|false { return false; }
function wp_register_script(string $handle, string|false $src, array $deps = [], string|bool|null $ver = false, array|bool $args = []): bool { return true; }
function wp_register_style(string $handle, string|false $src, array $deps = [], string|bool|null $ver = false, string $media = 'all'): bool { return true; }
function wp_safe_redirect(string $location, int $status = 302, string $x_redirect_by = 'WordPress'): bool { return true; }
function wp_schedule_event(int $timestamp, string $recurrence, string $hook, array $args = [], bool $wp_error = false): bool { return true; }
function wp_send_json_error(mixed $value = null, int $status_code = null, int $flags = 0): never { exit; }
function wp_send_json_success(mixed $value = null, int $status_code = null, int $flags = 0): never { exit; }
function wp_unslash(mixed $value): mixed { return $value; }
function wp_verify_nonce(string $nonce, string|int $action = -1): int|false { return 1; }

function absint(mixed $maybeint): int
{
	return abs((int) $maybeint);
}

function plugin_dir_url(string $file): string
{
	return '';
}

function sanitize_hex_color(string $color): ?string
{
    return '';
}