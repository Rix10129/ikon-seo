# Upgrade to v0.12.0

1. Back up WordPress and the database.
2. Upload the v0.12.0 ZIP and choose **Replace current with uploaded**.
3. Open **Ikon SEO → Content Intelligence**. This triggers the normal database upgrade if it has not already run.
4. Confirm the dashboard shows the competitor-research and content-brief databases as ready.
5. Re-import the private workspace schema from `/wp-json/ikon-seo/v1/openapi`.
6. Keep the existing Bearer workflow key. A new key is not required unless it was exposed.
7. Test the action with an empty object before storing research.
8. Store a small set of current competitor observations for one priority query.
9. Build one page brief and review the evidence, hypotheses and limitations.
10. Do not change live content until the brief has been reviewed by a person.

The upgrade preserves existing profiles, Project History, Search Console, Analytics, Page Plans, technical evidence and draft protections.
