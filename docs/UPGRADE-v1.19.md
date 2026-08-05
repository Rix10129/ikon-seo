# Upgrade to Ikon SEO v1.19.0

## Before upgrading

1. Use a staging website first.
2. Create and verify a v1.18.0 Platform Health configuration recovery archive.
3. Export any critical WordPress and database backups through your hosting provider.
4. Record the current plugin version and database component version.
5. Confirm `WP_ENVIRONMENT_TYPE` is correctly defined on staging and production.

## Upgrade

Upload and activate `ikon-seo-v1.19.0.zip` through the normal WordPress plugin workflow. Ikon SEO does not install its own update.

The database component moves from `37.0` to `38.0` and adds:

- `wp_ikon_seo_license_entitlements`
- `wp_ikon_seo_release_catalog`
- `wp_ikon_seo_deployment_plans`
- `wp_ikon_seo_deployment_events`

The upgrade also creates a persistent `ikon_seo_installation_id` when one does not exist.

## After upgrading

1. Open **Ikon SEO → Platform Health**.
2. run full hardening checks;
3. verify the signed release manifest;
4. confirm database component `38.0`;
5. confirm the Deployment Control daily cron is scheduled;
6. open **Deployment Control**;
7. register the installed signed release;
8. on staging only, create a local evaluation entitlement if no signed entitlement is available;
9. create a new configuration recovery archive;
10. confirm the public website and existing Ikon SEO records remain unchanged.

## Focused workspace change

The focused OpenAPI workspace remains at exactly 30 operations. Deployment Control replaces the read-only Site Inventory operation in the focused schema. Site Inventory remains available in WordPress and through its direct REST route.

## Rollback

Ikon SEO does not perform automatic code rollback. Restore the previous plugin package and database backup manually through WordPress or the hosting platform. Configuration archives are same-version only and do not restore plugin code, credentials, posts, pages or media.
