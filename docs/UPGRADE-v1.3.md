# Upgrade to Ikon SEO v1.3.0

## Before upgrading

1. Create a complete hosting and database backup.
2. Test the update on a staging copy first.
3. Confirm v1.2.0 opens without a fatal error.
4. Record the current plugin and database component versions.

## Upgrade steps

1. Upload `ikon-seo-v1.3.0.zip` through WordPress.
2. Choose **Replace current with uploaded**.
3. Open **Ikon SEO → Agency Access** and confirm the approved administrator.
4. Open **Ikon SEO → Experiments, Claims & Revenue**.
5. Confirm database component version `23.0` through Production Health.
6. Confirm the four new tables exist.
7. Re-import the focused OpenAPI schema in the private workspace.

## First staging test

1. Create one draft experiment using a staging or low-risk test URL.
2. Add one comparable same-site URL, when available.
3. Capture a baseline.
4. Save one source-backed claim record.
5. Add one test lead event using a non-identifying internal reference.
6. Confirm no published page, CRM record, or customer communication changes.
7. Run the weekly maintenance task once.
8. Confirm Project History records the changes.

## New tables

- `ikon_seo_experiments`
- `ikon_seo_experiment_measurements`
- `ikon_seo_claims`
- `ikon_seo_revenue_events`

Existing profiles, credentials, strategy, research, Project History, workflows, Page Plans, drafts, Indexation Intelligence, and Structured Data & Media Governance records are preserved.

## Rollback

A plugin rollback does not remove the new tables. Use a tested hosting backup to restore the complete pre-upgrade state. Recovery checkpoints do not include external-service credentials and do not replace a hosting backup.
