# Fida CRM v0.11.1 — Workspace currency provisioning hotfix

## Problem
New workspace signup can fail with `CurrencyService::seedForTenant(): Argument #2 ($base) must be of type string, null given`.

## Cause
The `tenants` migration gives `currency` a database default of `USD`, but Eloquent's newly created `Tenant` object does not automatically receive database default attributes. `WorkspaceProvisioner` used that unset property in `seedForTenant()`.

## Changes
- `WorkspaceProvisioner::create()` explicitly initializes `currency` as `USD`, `timezone` as `UTC`, and `status` as `trial`.
- `CurrencyService::seedForTenant()` accepts a nullable currency and normalizes missing/unsupported codes to `USD`.
- `CrmEnrichmentTest` now checks persisted workspace defaults and handles a null-currency seeding regression.

## Apply this patch
Copy the **three application/test files** from the hotfix ZIP to the matching paths in your installation. No new migration is required.

```bash
php artisan optimize:clear
php artisan test --filter=CrmEnrichmentTest
```

Retry new-workspace registration. A registration that failed within the database transaction should have been rolled back, so it normally doesn't leave a workspace record behind.

If you are applying the full-source ZIP to an existing installation, back up your application and database first and preserve your `.env`, database and storage files.

## Verification
PHP 8.4 syntax validation performed. Laravel feature tests still require Composer dependencies and a database in your deployment.
