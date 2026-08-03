# Upgrade to Ikon SEO 0.9.0

1. Upload the 0.9.0 ZIP in **Plugins → Add New Plugin → Upload Plugin**.
2. Choose **Replace current with uploaded**.
3. Open **Ikon SEO → Agency Access** and confirm your administrator remains approved.
4. Open **Ikon SEO → Page Diagnostics**.
5. Run the evidence crawl in batches until the pending count reaches zero.
6. Connect Search Console if it is not already connected.
7. Optionally open **Ikon SEO → Analytics**, enable the two Google Analytics APIs, add the displayed OAuth callback and connect a GA4 property.
8. Re-import the private workflow schema from:

   `https://YOUR-DOMAIN/wp-json/ikon-seo/v1/openapi`

9. Keep Bearer authentication and the existing workflow key. Generate a fresh key only when transferring accounts or when the old key may have been exposed.
10. Test `diagnoseIkonSEORankingEvidence` with a read-only request before using any draft action.

The schema contains 30 operations. SEO Health, Image Audit and Redirect Opportunities remain available in WordPress and through their direct REST routes but are not included in the private workflow schema to remain within the editor limit.

Existing profiles, Page Plans, Project History, drafts and connection settings are retained during the database upgrade.
