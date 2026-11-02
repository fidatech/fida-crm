# Fida CRM SaaS v0.11.0 — Access, currencies and prospect discovery

This is an upgrade to v0.10, **not** a new standalone Laravel framework installation.

## Changes

- Tenant-specific roles and permission matrix, including immutable seeded presets and custom roles.
- Permission enforcement for entity views, edits, imports, exports, campaigns, sales, service replies, APIs, team management and payments.
- Team invite and role assignment use workspace role definitions; role escalation protections.
- Currency management screen, ISO catalog, base currency, manual exchange rates, rate history and per-document/deal base-currency snapshots.
- Apollo Organization Search API integration with encrypted per-tenant credentials, bounded searches, saved searches, recurring schedules, review queue, source metadata and explicit approval into Companies + Leads.
- Section-scoped navigation, related quick links and enhanced administration pages.
- Feature tests for access isolation, role enforcement, rates and mocked discovery provider.

## Upgrade

Back up the database and files, then deploy the **full source**. In the Laravel project directory:

```bash
composer install --no-dev --optimize-autoloader
npm ci || npm install
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan view:cache
php artisan route:cache
php artisan config:cache
```

For staging tests use `composer install` (including dev dependencies), `php artisan test` and `php artisan view:cache`.

For recurring lead discovery, configure Laravel's scheduler with **one** cron entry:

```cron
* * * * * cd /absolute/path/to/fida-crm && php artisan schedule:run >> /dev/null 2>&1
```

Apollo keys are entered inside each workspace under Sales → Prospect discovery → Connect Apollo API. Provider credits and API entitlements are not included in this source package; do not place credentials in frontend code. Every run fetches at most 20 companies by default (adjust `APOLLO_DISCOVERY_PAGE_SIZE`, hard-capped at 25). Search runs and errors can be reviewed from the UI.

## Important limits

- No unlicensed scraping, guessed emails, auto-sent cold emails or invented persons. Only Apollo-sourced organizations become review candidates; imported leads represent companies, not individuals.
- The fit score is heuristic and not a validated conversion forecast. The existing AI assistant is separate and optional.
- Currency rates are entered manually. No external FX-feed integration or accounting-grade revaluation is claimed. Existing amounts retain their historical rates. Changing the base currency is blocked after deals or sales documents exist.
- Existing payment logic uses two decimal places. Zero- and three-decimal currency handling and accounting reconciliation still require separate engineering.
- No full runtime deployment, Laravel Blade compilation, real Apollo API search or MySQL concurrency testing was possible in this environment. See `BUILD_VERIFICATION.md` for required checks.
