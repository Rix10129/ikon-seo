# Upgrade to Ikon SEO v1.11.0

## Before upgrading

1. Back up the WordPress database and plugin files.
2. Use a staging website with the same theme, Elementor version, SEO plugin, security plugins and user-role configuration as production.
3. Confirm v1.10.0 Editorial Review is working and at least one controlled draft can reach final sign-off.

## Upgrade

1. Install `ikon-seo-v1.11.0.zip` over the existing Ikon SEO installation.
2. Activate or reactivate the plugin if WordPress requests it.
3. Confirm the plugin version is `1.11.0`.
4. Confirm the database component version is `30.0`.
5. Confirm the four `ikon_seo_publishing_*` tables exist.
6. Regenerate the connection key on staging so governed workspace actions have an explicit administrator owner.
7. Confirm the daily `ikon_seo_publishing_verification` event is scheduled.

## Staging acceptance test

1. Confirm the publishing target and verification URL are on the current WordPress host.
1. Open a signed-off Editorial Review.
1. Create a release candidate.
1. Edit the controlled draft and confirm preflight is blocked as stale.
1. Return through Editorial Review, sign off the current snapshot and create a fresh candidate.
1. Run preflight and review every blocker and warning.
1. Approve **Ready for manual publishing**.
1. Confirm the WordPress post remains a draft.
1. Publish a new-page draft manually, or merge an existing-page revision manually.
1. Record or confirm the public URL.
1. Run launch verification.
1. Confirm HTTP, canonical and indexability results match the rendered page.
1. Confirm public issues are reported but not automatically changed.
1. Exercise the 24-hour, 7-day and 28-day monitoring process in staging or with controlled test records.
1. Confirm monitoring cannot close early while fewer than four checkpoints exist and the window has not elapsed.


## Rollback

If staging validation fails:

1. Deactivate v1.11.0.
2. Restore the plugin files and database backup taken before the upgrade.
3. Do not delete the new publishing tables individually unless the full rollback plan requires it.
4. Record the failing step, WordPress/PHP versions, active theme, Elementor/SEO-plugin versions and relevant error logs.

## Production caution

Do not deploy to a live client website until staging confirms database migration, user permissions, manual-publishing detection, rendered canonical/indexability checks and scheduled verification behavior.
