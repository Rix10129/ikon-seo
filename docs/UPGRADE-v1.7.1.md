# Upgrade to Ikon SEO v1.7.1

## Release

**v1.7.1 — Workspace Integration & Fact-Level Approval**

## Upgrade process

1. Back up the WordPress database and plugin directory.
2. Install `ikon-seo-v1.7.1.zip` over the existing Ikon SEO plugin.
3. Confirm the plugin reports version 1.7.1.
4. Open **Ikon SEO → Fact Review**.
5. Review uncertain or conflicting facts from the latest Auto Discovery report.
6. Apply confirmed values, then continue to Guided Launch.
7. Re-import the bundled OpenAPI schema in the approved private workspace when the new endpoints are required.

## Data migration

Existing v1.6.0 and v1.7.0 Auto Discovery reports are migrated lazily into fact-level decision records. Previously applied discovery fields are treated as confirmed where that information is available. No existing Website Profile, Website Strategy, workflow, Operating Plan or Project History data is deleted.

## New activation gate

Guided Launch now requires:

- a current Auto Discovery report;
- no uncertain or outdated business facts;
- resolved conflicts, or the existing current-scan acknowledgement fallback;
- a configured Website Strategy with at least 70/100 readiness.

## Staging checks

- Confirm a previous applied discovery value appears as confirmed.
- Confirm a changed detected value becomes outdated after a rescan.
- Confirm a stale workspace update returns HTTP 409.
- Confirm invalid connection keys return HTTP 401.
- Confirm keys without draft scope return HTTP 403 for review writes.
- Confirm repeated writes with the same idempotency key return the stored result.
- Confirm Guided Launch remains unable to publish or change live SEO controls.
