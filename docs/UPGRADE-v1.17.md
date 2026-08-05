# Upgrade to Ikon SEO v1.17.0

## Before upgrading

1. Use a staging copy of the website.
2. Create a full hosting-level files and database backup.
3. Confirm WordPress 6.4 or newer and PHP 7.4 or newer.
4. Record the current Ikon SEO plugin and database component versions.
5. Confirm the active connection key belongs to an active administrator.

## Upgrade

Install v1.17.0 over the existing plugin. The database component should migrate to:

```text
36.0
```

New tables:

```text
wp_ikon_seo_release_integrity_runs
wp_ikon_seo_recovery_archives
wp_ikon_seo_upgrade_journal
```

New daily hook:

```text
ikon_seo_platform_hardening_daily
```

## After upgrading

1. Open **Ikon SEO → Platform Health**.
2. Run the full hardening checks.
3. Verify the detached release manifest.
4. Review compatibility and security findings.
5. Create a configuration recovery point.
6. Confirm the upgrade journal shows the previous and current versions.
7. Test scheduler repair on staging.
8. Create a sanitized support bundle and confirm that it contains no credentials or page content.
9. Restore a staging configuration archive using its exact payload hash.
10. Confirm public posts and pages remain unchanged.

## Rollback policy

Ikon SEO does not automatically roll back a plugin or database migration. Use a verified hosting-level backup for full rollback. The built-in recovery archive restores only credential-free Ikon SEO configuration from the same plugin and database component version.
