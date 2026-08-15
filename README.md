# Lau Performance Training

WordPress plugin for weekly Lau Performance training schemas.

## Development

```sh
composer install
composer test
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

Unit tests can run with the Composer-installed PHPUnit runner.

Integration tests are written for the WordPress core test suite. They are guarded
so a plain PHPUnit run does not fail when `WP_UnitTestCase` is unavailable.

Acceptance scenarios live in `tests/Acceptance/features`. They document the v1
workflows from a user perspective; a Behat/WordPress browser runner is not wired
in this repository yet.

The Docker PHP container currently includes Composer but not Node/npm. JavaScript
linting and formatting require Node to be installed in the environment running
the npm scripts.

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
