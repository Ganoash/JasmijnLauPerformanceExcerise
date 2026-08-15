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
