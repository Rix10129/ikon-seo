# Upgrade to v0.13.0

1. Back up the WordPress database and files.
2. Upload the v0.13.0 ZIP and choose **Replace current with uploaded**.
3. Open **Ikon SEO → Authority Intelligence**.
4. Confirm that the database is ready.
5. Download the generic CSV template and test with a small website-backlink import.
6. Review referring domains, target pages and recovery evidence.
7. Import competitor evidence separately and specify the competitor domain.
8. Re-import the private workspace schema from `/wp-json/ikon-seo/v1/openapi`.
9. Keep Bearer authentication and the existing workflow key.
10. Test `syncIkonSEOAuthorityIntelligence` with an empty JSON object before storing observations.

The update does not change live content, redirects, canonicals or links. Existing data and workflow keys remain in place.
