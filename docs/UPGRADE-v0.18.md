# Upgrade to Ikon SEO v0.18.0

1. Back up the WordPress files and database.
2. Upload the v0.18.0 ZIP and choose **Replace current with uploaded**.
3. Open **Ikon SEO → Agency Access** and confirm your administrator account.
4. Open **Ikon SEO → Agency Command Centre**.
5. Save the command-centre settings.
6. On one test website, generate a read-only site key.
7. Add that website to the command centre and verify the first snapshot.
8. Review alerts, approvals, benchmarks and the client report before connecting the full portfolio.
9. Re-import the focused private-workspace schema if used.

The upgrade adds four database tables for managed websites, snapshots, portfolio alerts and tracked usage. Existing Website Strategy, Workflow Automation, Publisher Intelligence, Local Growth, Project History, Search Console, Analytics and drafts are preserved.

The focused action schema remains at 30 operations. Local Rank reading remains available in WordPress and the complete REST interface but is omitted from the focused private-workspace schema.
