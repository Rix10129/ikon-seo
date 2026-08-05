# Upgrade to Ikon SEO v1.10.0

1. Back up the staging database and `wp-content/plugins/ikon-seo` directory.
2. Install `ikon-seo-v1.10.0.zip` over v1.9.0 on staging.
3. Reactivate the plugin if WordPress does not run the database upgrade automatically.
4. Confirm database version `29.0` and plugin version `1.10.0`.
5. Create or open one controlled Content Workbench draft.
6. Start Editorial Review and assign different writer and reviewer accounts.
7. Set writing and review due dates.
8. Submit the draft for review and confirm an immutable snapshot is stored.
9. Complete source and claim verification checks.
10. Add one structured revision request and confirm approval is blocked while it remains open.
11. Submit a revision and compare the latest two snapshots.
12. Run Publisher Intelligence and mark the controlled draft Ready.
13. Approve the editorial round and record final sign-off.
14. Confirm the WordPress post remains `draft` and no live page changes.
15. Test `POST /wp-json/ikon-seo/v1/editorial-review` with a new idempotency key.

## New tables

- `wp_ikon_seo_editorial_reviews`
- `wp_ikon_seo_editorial_comments`
- `wp_ikon_seo_editorial_checks`
- `wp_ikon_seo_editorial_snapshots`
- `wp_ikon_seo_editorial_events`
