# Structured Data & Media Governance Fixtures

1. Upgrade staging and confirm database component version `22.0`.
2. Confirm all three governance tables exist.
3. Open **Structured Data & Media** as an agency administrator.
4. Open the same screen in Client Mode and confirm settings and write controls are hidden.
5. Review a page with valid JSON-LD and confirm types are recorded.
6. Review a page with invalid JSON-LD and confirm an error is recorded.
7. Test duplicate `@id` and repeated primary entity evidence.
8. Test a same-site URL and reject an external-host URL.
9. Review an image with missing alternative text.
10. Review an oversized image and an unused image.
11. Enable file hashes and confirm identical local files form a duplicate group.
12. Save a licensed source record and confirm it is retained after re-audit.
13. Confirm the module does not change markup, image files, alternative text or published content.
14. Confirm Project History records administrator runs and source-record updates.
15. Re-import the focused schema and confirm exactly 30 unique operations with `syncIkonSEOStructuredMediaGovernance` present.
16. Confirm `/schema/preview` remains in the complete REST interface but is omitted from the focused schema.
