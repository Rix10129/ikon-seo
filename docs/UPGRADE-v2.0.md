# Upgrade to Ikon SEO v2.0.0

## Before upgrading

1. Install v1.19.0 on staging first.
2. Open **Platform Health** and verify the signed v1.19.0 package.
3. Create a configuration recovery archive.
4. Export any operational records required by your agency.
5. Confirm WordPress 6.4+ and PHP 7.4+.

## Upgrade result

The database component moves from `38.0` to `39.0` and creates:

- `wp_ikon_seo_support_contracts`
- `wp_ikon_seo_production_certifications`
- `wp_ikon_seo_certification_checks`
- `wp_ikon_seo_rollout_waves`
- `wp_ikon_seo_certification_events`

A daily bounded hook is registered:

- `ikon_seo_production_certification_daily`

## After upgrading

1. Verify the v2.0.0 signed package in **Platform Health**.
2. Confirm database version `39.0` in the upgrade journal.
3. Run all Platform Health checks.
4. Complete a recovery restore drill on staging.
5. Open **Production Certification**.
6. Create and approve a support contract with two different administrators.
7. Complete the certification checks using real evidence.
8. Approve the exact evidence fingerprint with a different administrator.
9. Prepare a small pilot rollout wave.
10. Install v2.0.0 manually and record the results.

## Rollback

Ikon SEO does not perform automatic rollback. Use the hosting provider or WordPress deployment process to restore the previous plugin package and database backup. Configuration recovery archives restore Ikon SEO settings only; they are not database or plugin-code backups.
