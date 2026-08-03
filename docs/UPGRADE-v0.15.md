# Upgrade to Ikon SEO v0.15.0

1. Back up WordPress files and the database.
2. Upload the v0.15.0 ZIP and choose **Replace current with uploaded**.
3. Open **Ikon SEO → Agency Access** and confirm the correct administrators retain agency access.
4. Open **Ikon SEO → Workflow Automation**.
5. Create the workflow recommended for the active Website Strategy mode.
6. Keep automatic execution limited to read-only tasks.
7. Test **Run safe tasks now** with a small crawler and URL-check batch.
8. Confirm the new briefing appears in Project History.
9. Re-import the OpenAPI schema in the private workspace.
10. Test `syncIkonSEOWorkflowAutomation` with `{ "command": "read" }` before issuing a write command.

Existing profiles, strategy settings, connection keys, research records, Search Console, Analytics, Project History, drafts and Page Plans are preserved.
