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
