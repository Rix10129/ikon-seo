# Upgrade to Ikon SEO 0.8.1

1. Upload the 0.8.1 ZIP and choose **Replace current with uploaded**.
2. Open **Ikon SEO → Project History**. The database upgrade runs automatically.
3. Re-import the OpenAPI schema in the private workspace so `syncIkonSEOProjectHistory` appears.
4. Add this startup rule to the private workspace instructions:
   - Read Website Profile, Site Inventory, Project History and Page Plans before analysis.
   - Save meaningful research, recommendations, drafts and next steps to Project History.
5. Test the history action with an empty object.

Existing profiles, page plans, settings and workflow keys are retained.
