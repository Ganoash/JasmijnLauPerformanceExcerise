# Lau Performance Training

WordPress plugin for weekly Lau Performance training schemas.

## Development

```sh
composer install
composer test
composer test:unit
composer test:integration
composer test:acceptance
composer stan
composer lint
composer format
npm install
npm run lint
npm run format:check
```

The plugin also includes a small fallback PSR-4 autoloader so it can load in a
local WordPress container before Composer dependencies are installed.

## Test Notes

Unit tests run through `phpunit.xml.dist`. Integration tests run through
`phpunit.integration.xml.dist` and use `wp-phpunit/wp-phpunit` against the local
Docker WordPress database with the isolated `wptests_` table prefix.

Acceptance scenarios live in `tests/Acceptance/features` and run through Behat
against the local WordPress bootstrap. They document the v1 workflows from a user
perspective while keeping the runner focused on service and permission behavior
instead of browser automation.

The Docker PHP container currently includes Composer but not Node/npm. Run the
npm scripts from the host, or install Node in the container image if you want all
checks to execute inside Docker.

## WordPress Usage

Activate the plugin in WordPress, then use the `Training schema’s` admin menu to
manage exercises and weekly schemas. Add the dynamic block
`lau-performance-training/dashboard-schema` to a logged-in user dashboard page to
show links for previous, current, next, and two-weeks-ahead schemas.

The frontend schema URL format is:

```text
/training-schema/{user_id}/{week_start_date}/
```

Opening an allowed missing schema creates the empty week with fourteen slots.
