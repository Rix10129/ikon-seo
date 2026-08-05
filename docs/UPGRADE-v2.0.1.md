# Upgrade to Ikon SEO v2.0.1

## Summary

v2.0.1 is a certification-runner release. It adds non-destructive staging evidence collection and does not add automatic publishing, installation or rollback.

## Database migration

The Ikon SEO database component advances from `39.0` to `40.0` and adds:

- `wp_ikon_seo_staging_runs`
- `wp_ikon_seo_staging_checks`
- `wp_ikon_seo_staging_events`

The normal plugin upgrade path uses `dbDelta()` and records the transition in the existing upgrade journal.

## Before upgrading

1. Create a Platform Health configuration recovery archive.
2. Confirm the v2.0.0 package is healthy.
3. Install v2.0.1 on staging before production.
4. Keep at least two administrator accounts available for approval separation.
5. Confirm the Ikon SEO connection key has read, draft and approve scopes where the private workspace will be used.

## After upgrading

1. Confirm plugin version `2.0.1` and database version `40.0`.
2. Verify the signed release manifest.
3. Open **Staging Validation** and start a run.
4. Resolve critical failures rather than waiving them.
5. Approve the exact evidence fingerprint with another administrator.
6. Store the evidence pack with the production certification record.

## Rollback

The staging runner does not perform automatic rollback. Restore through the hosting or WordPress deployment process and use the Platform Health recovery archive for supported configuration restoration. Database rollback should be performed only from a verified backup and controlled maintenance window.
