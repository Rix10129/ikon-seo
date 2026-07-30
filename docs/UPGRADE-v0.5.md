# Upgrade to Ikon SEO v0.5

1. Back up the website and database.
2. Upload `ikon-seo-v0.5.0.zip`.
3. Choose **Replace current with uploaded**.
4. Confirm the dashboard shows `0.5.0`.
5. Recheck the Website Profile and connection health.
6. Keep draft-only and profile-bound writes enabled.
7. Open **Local SEO**, add one genuine location or service-area record and run the NAP audit.
8. Refresh the connected Action/OpenAPI schema.
9. Test one local draft and its quality report.
10. Connect Google Business Profile only after the core local test passes.
11. Stage one review reply or Google Post, reject it, then repeat and approve an exact harmless test draft when appropriate.
12. Complete `tests/SMOKE-TEST.md` on staging before any client rollout.

The upgrade preserves v0.4 profiles, keys, settings, pages, queues, Search Console configuration, monitoring and rollback data. The new local tables start empty. No Google account is connected and no Business Profile mutation is enabled automatically.
