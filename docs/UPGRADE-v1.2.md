# Upgrade to Ikon SEO v1.2.0

1. Create a complete hosting and database backup.
2. Test the upgrade on staging before production.
3. Upload the v1.2.0 ZIP and choose **Replace current with uploaded**.
4. Confirm Agency Access still identifies the correct administrator.
5. Open **Structured Data & Media**.
6. Save conservative governance settings.
7. Review three pages and ten images.
8. Save a source record for one test image.
9. Confirm no public markup, media file or published page changed.
10. Re-import the focused OpenAPI schema in the private workspace.

## Database upgrade

Database component version 22.0 adds:

- `ikon_seo_schema_audits`
- `ikon_seo_media_assets`
- `ikon_seo_governance_runs`

Existing profiles, strategy, integrations, Operating Plan, Project History, workflows, research, Page Plans and drafts are preserved.

## Focused action change

The focused schema adds `syncIkonSEOStructuredMediaGovernance`. Schema Preview remains available in WordPress and the complete REST interface but is omitted from the focused 30-operation schema.
