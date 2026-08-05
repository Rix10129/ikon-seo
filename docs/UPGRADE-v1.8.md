# Upgrade to Ikon SEO v1.8.0

## Release

**v1.8.0 — Evidence Intelligence & Opportunity Engine**

## Upgrade process

1. Back up the WordPress database and plugin directory.
2. Install `ikon-seo-v1.8.0.zip` over the existing Ikon SEO plugin.
3. Confirm the plugin reports version 1.8.0.
4. Allow the database upgrade to create the keyword-evidence and opportunity tables.
5. Open **Ikon SEO → Opportunity Engine**.
6. Rebuild the queue from current first-party evidence.
7. Optionally import an approved Semrush, Ahrefs or licensed-provider CSV export.
8. Review high-priority items and mark them reviewed, planned, completed or dismissed. Only planned items are eligible for Operating Plan handoff.
9. Re-import the bundled focused OpenAPI schema when workspace access is required.

## Data migration

No existing Website Profile, Website Strategy, discovery decisions, workflow, Page Plan, Operating Plan or Project History data is removed. The two new tables start empty and are populated only after a rebuild or approved import.

The database component version is 27.0. Existing v1.7.1 fact approvals and Guided Launch state remain unchanged.

## Focused workspace schema

The focused schema remains at exactly 30 operations. The new Opportunity Engine action replaces the standalone Search Intelligence, Analytics report and refresh-monitoring actions in the focused schema. Those modules remain available in WordPress and at their direct authenticated REST routes.

## Staging checks

- Confirm both new database tables exist.
- Confirm a queue rebuild completes without changing public content.
- Confirm Search Console opportunities show only when connected evidence is available.
- Confirm CSV imports reject unreadable files and imports above 5,000 rows.
- Confirm stale provider evidence is visibly identified.
- Confirm status updates create Project History records.
- Confirm invalid keys return HTTP 401 and read-only keys cannot perform draft-scoped writes.
- Confirm repeated writes with the same idempotency key return the stored result.
- Confirm the focused OpenAPI document contains exactly 30 paths.
- Confirm no publish, redirect, canonical, noindex, deletion, outreach or backlink action is available in the engine.
