# Upgrade to Ikon SEO v0.8.0

## What changed

- Added a client-safe interface for normal administrators.
- Added explicit Agency Access so only approved Ikon users can see workflow credentials, profile import/export, Search Console credentials, domain tools, settings and activity logs.
- Added a Rank Math compatibility dashboard with metadata, canonical, indexing and schema ownership checks.
- Added a read-only image SEO audit for missing, filename-based and duplicate ALT text, captions and descriptions.
- Added redirect opportunities using unresolved internal links and Rank Math 404 logs when available.
- Added read-only workspace endpoints for SEO health, image audits and redirect opportunities.
- Changed the workspace schema to Bearer authentication and removed unsupported header parameters from the action schema.

## Upgrade steps

1. Upload the v0.8.0 ZIP and choose **Replace current with uploaded**.
2. Open **Ikon SEO → Agency Access** and confirm which administrator accounts should have agency access.
3. Open **SEO Health**, **Image Audit** and **Redirect Opportunities** and run the first scans.
4. In the private workspace action, re-import the OpenAPI schema URL so the new endpoints and cleaner Bearer authentication are loaded.
5. Test the connection and Website Profile before testing any write action.

## Safety

- No redirects or image metadata are changed automatically.
- Client Mode does not expose workflow credentials.
- Draft-only and profile-bound write protections remain enabled.
- Rank Math remains the technical renderer; Ikon SEO audits compatibility and manages controlled additions.
