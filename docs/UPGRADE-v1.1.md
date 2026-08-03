# Upgrade to Ikon SEO v1.1.0

## Release focus

Production Hardening and Indexation Intelligence.

## Before upgrading

- Create a complete hosting backup.
- Use a staging copy for the first installation.
- Record the current Ikon SEO version and connected Search Console property.
- Do not share workflow keys or service credentials in screenshots.

## Upgrade steps

1. Upload `ikon-seo-v1.1.0.zip` from WordPress Plugins.
2. Choose to replace the installed version.
3. Open **Ikon SEO → Agency Access** and confirm the agency administrator.
4. Open **Ikon SEO → Indexation Intelligence**.
5. Run **Production health checks**.
6. Confirm database component `21.0` and the new tables.
7. Prepare a small URL queue.
8. Run one to three read-only inspections.
9. Confirm Project History records the actions.
10. Re-import the focused private-workspace schema.

## New database tables

- `ikon_seo_indexation_urls`
- `ikon_seo_indexation_runs`
- `ikon_seo_system_health_runs`

Existing profiles, strategy, Search Console, Analytics, workflows, content research, local data, Operating Plan, Project History, Page Plans and drafts are preserved.

## Focused schema change

The focused schema remains at 30 operations.

Added:

- `syncIkonSEOIndexationIntelligence`

Removed only from the focused schema:

- `inspectGoogleIndexStatus`

The original single-URL inspection endpoint remains available in WordPress and the complete REST interface.

## Rollback

A plugin downgrade should be tested on staging. The three v1.1 tables may remain in the database after a file rollback; this protects stored inspection history. Restore a verified hosting backup when a full database rollback is required.
