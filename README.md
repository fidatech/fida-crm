# Fida CRM · New-build source package

**Build:** 2026-10-14 | **Version:** 0.10.0 CRM workspace development candidate | **Stack:** Laravel 13, PHP 8.4+, MySQL 8+, Blade, Bootstrap 5, Vite, Fida Admin v2.

This project is a **fresh CRM application**, not a repackaged copy of the earlier Fida CRM SaaS source. It incorporates the **original Fida Admin v2 design-system stylesheet** and a purpose-built CRM interaction layout with a navy app rail, light data workspace and Fida amber accent colors. No HubSpot assets or proprietary code are included.

## Install on a fresh Linux server

1. Install PHP 8.4+ with extensions required by Laravel (notably PDO MySQL, mbstring, openssl, tokenizer, fileinfo, xml, ctype), Composer 2, Node.js 22+, npm and MySQL 8+.
2. Extract the ZIP, enter `fida-crm-nova`, and create an empty UTF8MB4 MySQL database and database user.
3. `cp .env.example .env` and set `APP_URL`, database credentials, email transport, `APP_ENV=production`, `APP_DEBUG=false`. For local development use `APP_ENV=local`, `APP_DEBUG=true`.
4. `composer install --no-dev --optimize-autoloader` (omit `--no-dev` if running tests on your staging server).
5. `php artisan key:generate`
6. `php artisan migrate --force`
7. `npm install && npm run build`
8. Point the web server document root at **`public/`**, never the project root. Ensure `storage/` and `bootstrap/cache/` are writable. Enable HTTPS and add your reverse proxy configuration.
9. `php artisan optimize:clear && php artisan view:cache && php artisan route:cache`
10. Run a queue worker and set cron `* * * * * cd /path/to/fida-crm-nova && php artisan schedule:run > /dev/null 2>&1`.
11. Register at `/register`; your new trial workspace is provisioned with a pipeline and seven stages. Login at `/login`.
12. Test the UI and permissions on a staging system before enabling billing, marketing, third-party AI or real customer data.

### Administrator setup

The initial owner is a workspace owner, **not** a SaaS platform operator. Grant platform admin manually through an authorized database change, e.g. `UPDATE users SET platform_admin=1 WHERE email='verified-owner@example.com';` executed by the operator. The platform console is at `/platform` and lets an operator define subscription plans. Do not ship default superadmin credentials.

### SaaS payments

To use Stripe checkout, configure `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, and create plans with Stripe Price IDs in the operator console. Register webhook `https://your-host/billing/stripe/webhook` for `checkout.session.completed`, `customer.subscription.updated`, and `customer.subscription.deleted`. Run Stripe webhook/replay tests before collecting money. This integration has **not** been validated against a live Stripe account.

### Outbound email & reminders

Set `MAIL_MAILER=smtp`, SMTP host/port/credentials and `MAIL_FROM_*` variables. Email campaigns require creating contacts, email templates, a campaign with channel `email`, then selecting a template and queuing recipients. `php artisan crm:send-campaigns` processes up to 40 pending messages per execution; also scheduled every minute. Configure and test your email provider, unsubscribe domain, bounce handling and suppression policies before external outreach. Password reset and invitations use the same mail transport. Automated activity reminders require further deduplication safeguards before production use.

### Optional AI assistance

Set `CRM_AI_API_KEY` and `CRM_AI_MODEL` to use the optional API-backed account summary endpoint. Invoking the assistant transmits selected workspace record fields and activities to that third party. No request is sent until a signed-in team member initiates one. The provider configuration is not validated live.

### API

Create an API key in Workspace settings. Use bearer token `Authorization: Bearer fida_...` on `/api/v1/{entity}`. Keys are stored as SHA-256 hashes; returned only on creation. Tenant access is fixed to the key's workspace and the user's membership and role. Revoking a key is immediate.

### What is implemented

- User registration, login, password recovery, trial workspace provisioning, team membership and invitations, workspace switching and roles.
- Tenant-scoped relational tables and CRUD for 18 CRM domains: contacts, companies, leads, deals, tasks, meetings, tickets, articles, products, campaigns, templates, segments, forms, pipelines, stages, workflows, saved views and custom fields.
- Lead conversion into contacts, optional companies and opportunities; duplicate email/domain checks, customer record activity history and connected records.
- Sales stages and drag-and-drop deal movements saved by API with stage-to-pipeline checks.
- Quotations, invoices, item catalogs, server-calculated line totals, tax and discounts, accepted quote conversion, status rules, partial payments.
- Internal support ticket messages, private uploaded file attachments, notes and an audit log.
- Tenant email-campaign queues, templates, basic segmentation, signed unsubscribe links, form-based lead capture.
- Rule-driven workflow actions (create task, assign owner, set status) with run status logs, and report pages.
- Workspace operator console, plans, Stripe checkout and webhook handling code, API keys, optional AI account summaries.
- Search, CSV import/export, role restrictions, responsive institutional amber interface, Fida Admin v2 stylesheet, active sidebar auto-scroll, contextual Create menu, and charts-free light rendering.

### Tests / verification

Run `composer install` (with dev dependencies), `php artisan view:cache`, `php artisan route:cache`, and `php artisan test --filter=BladeCompilationTest` followed by `php artisan test --filter=CrmWorkspaceTest` on a separate test database. The feature tests cover trial onboarding, tenant boundaries, invoice lifecycle, viewer write prevention and core Blade views. **Tests have not been executed in this build environment** because Composer dependencies and PDO SQLite are unavailable. PHP lint and JavaScript syntax validation are separate, weaker checks.

### Production caveats

The package is a substantial functional implementation and a **development candidate**, not a verified complete HubSpot clone. It does not contain Salesforce/HubSpot integrations, two-way Gmail/Outlook sync, telephony, mobile applications, sophisticated marketing attribution, CMS/landing-page builder, comprehensive service SLA escalation, AI agents, the full HubSpot automation designer, Stripe customer portal, MFA/SSO, detailed RBAC policies, or enterprise-scale load/security audit. Some entities such as saved views, segments and custom-field configuration currently offer basic forms/logic, not full HubSpot feature parity. Production readiness requires end-to-end test fixes, consent/compliance review, billing webhook idempotency, queue reliability, rate-limiting review, backups/restore drills and a pentest. Do not handle production data before completing these steps.

## Layout

`app/` controllers, domain services, middleware and models; `config/crm.php` entity schemas; `database/migrations` platform, domain and operations; `resources/views` full Blade UI; `resources/css/fida-admin.css` original Fida Admin v2 styles; `resources/css/crm.css` institutional navy/amber layers; `resources/js/crm.js` navigation/board/sales interactions; `tests/Feature` integration tests; `routes/` web, API, background jobs. `vendor/` and `node_modules/` are intentionally excluded as standard dependency artifacts.

© Fida Technologies. Proprietary project source.


## v0.10.0 UI/Blade redesign

- All Blade templates were normalized to prevent inline PHP directive collisions. A Laravel-compiled-view regression test is included but **must be run after installing Composer dependencies**.
- The previous admin-style sidebar was replaced with a CRM navigation rail and a fully independent, auto-scrolling menu.
- Records use searchable tables, status filters and 3-area detail pages (properties, activity, related records). Create/edit forms have contextual fields and selectable related objects.
- Fida amber remains the accent alongside deep navy and neutral work surfaces.
- `npm install` now installs **jQuery, Select2 and SweetAlert2**; `npm run build` must complete before trying the new UI. Buttons show progress on submission; destructive actions use confirmations.
- A small rules-based queue highlights overdue follow-ups, deals whose close dates have passed and leads with a manually assigned score >=70. This does not make AI forecasts.
- See `RELEASE_NOTES.md`, `BUILD_VERIFICATION.md` and `PRODUCT_ACCEPTANCE.md` for current scope, limitations and staging tests.

## v0.11.0 workspace management

- **Roles & permissions:** Settings → Roles & permissions. Owners manage custom roles, using granular module and action checkboxes. Built-in role templates cannot be edited; team assignments are under Settings → Organization & team.
- **Currency management:** Settings → Currencies & rates. Add supported currencies, set manual rate-to-base values, see historical rates and archive currencies. Amounts on new deals and sales documents snapshot their rates. Keep a backup before changing finance settings.
- **Prospect discovery:** Sales → Prospect discovery. Connect a tenant-specific Apollo key, save filters by industry/location/headcount, run a search or schedule a daily/weekly run, review each organization and approve it into a company and a business lead. The API does not provide personal emails from organization search.
- **Related navigation:** Select a workspace area on the left primary rail; the secondary menu shows only that area's relevant functions.

Review `CHANGELOG_v0.11.md` before migrating a v0.10 installation.
