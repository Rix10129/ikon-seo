# Upgrade to Ikon SEO v1.15.0

1. Back up the WordPress database and plugin directory.
2. Install v1.15.0 over v1.14.0 on staging.
3. Confirm `ikon_seo_db_version` is `34.0`.
4. Confirm the six new service-level tables exist.
5. Open **Ikon SEO → Service Levels**.
6. Create and approve one test service plan.
7. Assign it to a staging website already registered in Agency Command Centre.
8. Set capacity for two different WordPress users.
9. Create a bounded work item and move it through the allowed statuses.
10. Generate a report, then change one included work item and confirm approval is blocked as stale.
11. Regenerate the report and approve it with a different user from the preparer.
12. Mark it manually delivered and confirm no email or public-site action occurs.

New scheduled hook:

```text
ikon_seo_agency_service_level_monitor
```

New tables:

```text
wp_ikon_seo_service_plans
wp_ikon_seo_service_assignments
wp_ikon_seo_team_capacity
wp_ikon_seo_service_work_items
wp_ikon_seo_client_reports
wp_ikon_seo_service_events
```
