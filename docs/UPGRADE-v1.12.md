# Upgrade to Ikon SEO v1.12.0

## Before upgrading

1. Create a complete WordPress database and files backup.
2. Test the package on staging.
3. Confirm v1.11.0 Publishing Readiness records are healthy.
4. Confirm Search Console, Analytics and revenue evidence imports use the expected website URL format.

## Upgrade changes

- Plugin version: 1.12.0
- Database component version: 31.0
- New tables:
  - `wp_ikon_seo_impact_studies`
  - `wp_ikon_seo_impact_measurements`
  - `wp_ikon_seo_impact_events`
- New daily cron hook: `ikon_seo_search_impact_monitoring`
- New REST workspace: `/wp-json/ikon-seo/v1/search-impact`
- The focused OpenAPI schema remains limited to 30 operations.

## Staging validation

1. Update and reactivate Ikon SEO if the database upgrade does not run automatically.
2. Confirm database component version 31.0.
3. Open **Ikon SEO → Search Impact**.
4. Select a manually published release and create a study.
5. Confirm the baseline period ends before the recorded publication date.
6. Confirm an external or identical comparison URL is rejected.
7. Capture an eligible checkpoint.
8. Record a confounder and review its effect on confidence.
9. Assess and acknowledge the outcome using an administrator account.
10. Refresh the checkpoint and confirm the old assessment is invalidated.
11. Confirm the public page remains unchanged throughout the workflow.

## Connection scopes

The `draft` scope permits reading, study creation, baseline/checkpoint capture and confounder recording. The `approve` scope is required for assessment, acknowledgement, blocking and unblocking.
