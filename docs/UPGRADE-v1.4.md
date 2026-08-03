# Upgrade to Ikon SEO v1.4.0

## Before upgrading

1. Create a complete hosting and database backup.
2. Test the upgrade on staging.
3. Confirm Ikon SEO v1.3.0 is operating normally.
4. Export any server-log sample separately; logs are not required for the upgrade.

## Upgrade

1. Upload `ikon-seo-v1.4.0.zip` in WordPress.
2. Choose **Replace current with uploaded**.
3. Confirm Agency Access still contains the approved administrator.
4. Open **International & Server**.
5. Confirm database component version `24.0` in Production Health.

## First test

1. Save one locale-map line that matches the current website.
2. Audit no more than three pages.
3. Confirm no public page changes.
4. Import a short sanitized log sample.
5. Confirm privacy and crawler reports.
6. Run cleanup on staging.
7. Re-import the focused workspace schema and confirm 30 operations.

## Rollback

Use the hosting backup for a complete rollback. Recovery checkpoints do not include plugin files, database tables, server logs or credentials.
