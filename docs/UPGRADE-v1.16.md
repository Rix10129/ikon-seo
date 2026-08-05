# Upgrade to Ikon SEO v1.16.0

1. Back up the WordPress database and plugin directory.
2. Install v1.16.0 over v1.15.0 on staging.
3. Confirm `ikon_seo_db_version` is `35.0`.
4. Confirm the two new Executive Command Centre tables exist.
5. Open **Ikon SEO → Agency Command Centre**.
6. Refresh connected staging websites and confirm their newer workflow counts appear.
7. Review the transparent portfolio health components for at least two sites.
8. Test website, severity, approval-type, owner and text filters.
9. Assign an owner and due date to one generated risk.
10. Resolve the risk with a note, refresh the evidence and confirm it reopens only when the condition still exists.
11. Acknowledge and dismiss test internal notifications.
12. Confirm capacity totals match Service Levels records and no automatic assignment occurs.
13. Use the REST `client_portal_preview` command on a staging site and inspect that only client-safe fields are returned.
14. Confirm all local approvals still occur in their originating modules.
15. Confirm no managed website content, external profile or client communication changes during refresh.

New scheduled hook:

```text
ikon_seo_executive_command_refresh
```

New tables:

```text
wp_ikon_seo_command_risks
wp_ikon_seo_command_notifications
```

The release preserves the focused private-workspace schema at exactly 30 operations.
