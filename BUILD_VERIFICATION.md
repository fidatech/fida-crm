# Fida CRM SaaS v0.11.0 — Build verification

- PHP syntax lint: passed for all project PHP files (see package-level verification output).
- JavaScript syntax: passed for both application entrypoints.
- Blade structure/embedded PHP syntax: passed for all 27 templates with bundled offline structural analyzer.
- ZIP integrity: checked after packaging.
- **Not executed:** Laravel `view:cache`, route cache, PHPUnit suite, MySQL migrations, Vite production build, browser interactions, live Apollo requests or real Stripe billing. The build environment does not contain Composer dependencies (`vendor/autoload.php`), so those checks cannot be claimed as passing.

## Staging verification (required)

```bash
composer install
npm install
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan view:cache
php artisan route:cache
php artisan test
```

Configure your test database and mail settings before the runtime checks. Use a staging server and back up the current tenant database before migration. Verify role changes, denied actions, lead imports, currency snapshots and navigation with multiple tenant accounts.
