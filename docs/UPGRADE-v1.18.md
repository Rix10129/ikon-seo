# Upgrade to Ikon SEO v1.18.0

## Release

**Ikon SEO v1.18.0 — Secure Read-Only Client Portal**

## Before upgrading

1. Install and test on staging first.
2. Create a v1.17.0 configuration recovery archive.
3. Verify the v1.17.0 signed package state.
4. Confirm WordPress users intended for portal access already exist.
5. Confirm Agency Command Centre and Service Levels contain the correct managed-site assignments.

## Database upgrade

Install v1.18.0 over the existing plugin. The database component should migrate to:

```text
37.0
```

New tables:

```text
wp_ikon_seo_client_portal_access
wp_ikon_seo_client_portal_snapshots
wp_ikon_seo_client_portal_events
```

New scheduled task:

```text
ikon_seo_client_portal_maintenance
```

The daily maintenance task expires time-limited access, removes old client-view events according to retention settings and removes portal snapshots older than 90 days. It does not send messages or modify public content.

## Initial configuration

1. Open **Ikon SEO → Client Portal**.
2. Select an existing WordPress user.
3. Select one managed website.
4. Choose the client-visible sections.
5. Add an optional expiry.
6. Create the pending access grant.
7. Review the complete SHA-256 access fingerprint.
8. Activate the exact pending grant.
9. Add `[ikon_seo_client_portal]` to a staging page manually.
10. Sign in using the assigned test account.

## Required staging tests

- A user assigned to website A cannot see website B.
- An unassigned logged-in user sees no records.
- A logged-out visitor sees only the sign-in prompt.
- A pending assignment cannot be used.
- A revoked assignment stops working immediately.
- An expired assignment is excluded.
- Draft and review-ready reports do not appear.
- Internal notes, fees, staff capacity and credentials do not appear.
- Unacknowledged Search Impact studies do not appear.
- The portal REST response uses private no-store caching.
- No WordPress post is created or published automatically.

## Rollback

The v1.18.0 database tables can remain unused if the plugin is rolled back on staging. Do not delete the tables until access records and event retention requirements have been reviewed.

A v1.17.0 configuration archive does not contain v1.18.0 client portal access grants. Access grants should be recreated after a rollback and re-upgrade rather than copied manually.
