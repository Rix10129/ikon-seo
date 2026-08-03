# Upgrade to Ikon SEO v1.5.0

## Before upgrading

1. Create a complete hosting and database backup.
2. Test the upgrade on staging.
3. Confirm that Ikon SEO v1.4.0 or a compatible earlier release is active.
4. Record the current Website Profile and Agency Access state.

## Upgrade procedure

1. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
2. Upload `ikon-seo-v1.5.0.zip`.
3. Choose **Replace current with uploaded**.
4. Confirm that the plugin remains active.
5. Open **Ikon SEO → Agency Access** and confirm the approved administrator.
6. Open **Ikon SEO → Production Health**.
7. Confirm database component version `25.0` and the new portfolio tables.
8. Open **Ikon SEO → Portfolio Quality**.

## New database tables

- `ikon_seo_portfolio_quality_profiles`
- `ikon_seo_portfolio_quality_findings`
- `ikon_seo_portfolio_quality_imports`

Existing profiles, connections, strategies, workflows, evidence, experiments, claims, attribution records, Project History, Page Plans and drafts are preserved.

## Safe first test

1. Keep the review-ready block enabled.
2. Set the scan batch to 10.
3. Create local signatures.
4. Export the bundle.
5. Import it only into a different staging website.
6. Evaluate no more than ten local pages.
7. Confirm that findings are evidence signals and no page changes occur.
8. Confirm Project History records the scan, import and evaluation.
9. Confirm the Operating Plan receives high-priority findings.
10. Run the weekly task once on staging.

## Private workspace schema

Re-import the focused schema from:

`https://YOUR-WEBSITE.example/wp-json/ikon-seo/v1/openapi`

The focused schema contains exactly 30 operations and adds:

`syncIkonSEOPortfolioQualityGuard`

International & Server Intelligence remains available in WordPress and the complete REST interface but is omitted from the focused schema to retain the 30-operation limit.

## Rollback

A plugin rollback does not remove the new database tables. Restore the complete hosting backup if a full database rollback is required.

Do not use a recovery checkpoint as a replacement for a hosting backup.
