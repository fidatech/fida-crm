# Approved feature roadmap / outstanding work

The intended product is a HubSpot-inspired US-market SaaS, with original amber design and lower page weight. **Current release implements core CRM and multiple working workflows, not feature parity with HubSpot.**

Priority 0 before public subscriptions: Composer installation, run migrations/tests and fix runtime failures; hardened tenant boundaries for every endpoint; end-to-end browser tests; backup/restore process; Stripe webhook idempotency/seat enforcement; consent and CAN-SPAM review; error monitoring; brute-force protection; MFA; passwordless/SSO planning; uptime checks and measured performance targets. Add a second reviewer for all payment changes.

Priority 1 product features: production Gmail/Outlook OAuth sync, outbound email activities and threads, calendar availability and meeting booking, email open/click tracking, visual workflow UI, contact list and merge, lead scoring, custom fields and true custom objects, bulk actions, saved report builder, invoice delivery, refund/credit notes, integrated accounting APIs, better helpdesk SLA timing, knowledge-base public portal, inbound help desk channels.

Priority 2 growth/US differentiation: integrated calling/SMS with US A2P registration, pipeline forecasting and targets, multi-step sequences with opt-out enforcement, campaign attribution and segments, richer AI-assisted drafts (approval required), webhooks and marketplace integrations, dashboard builder, mobile app, accessibility WCAG audit, data residency options and analytics benchmarks. CMS/social publishing and full project management remain optional add-ons, not core ERP.

Performance budget proposal to validate: paginate all core lists, constrain query count with instrumentation, test at realistic scales (e.g. 100k contacts / 10k deals and 50 concurrent sessions), focus on interaction latency and server p95, add caching only after measuring. These are **proposed tests**, not measured performance claims.
