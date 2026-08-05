# Upgrade to Ikon SEO v1.13.0

## Before upgrading

1. Create a complete WordPress database and files backup.
2. Test the package on staging.
3. Confirm v1.12.0 Search Impact studies and acknowledgements are healthy.
4. Confirm the active connection key has the scopes needed for the intended workspace actions.

## Upgrade changes

- Plugin version: 1.13.0
- Database component version: 32.0
- New tables:
  - `wp_ikon_seo_patterns`
  - `wp_ikon_seo_pattern_evidence`
  - `wp_ikon_seo_pattern_events`
- New weekly cron hook: `ikon_seo_pattern_library_refresh`
- New REST workspace: `/wp-json/ikon-seo/v1/pattern-library`
- The focused OpenAPI schema remains limited to 30 unique operations.

## Staging validation

1. Install v1.13.0 over v1.12.0.
2. Confirm database component version 32.0.
3. Open **Ikon SEO → Pattern Library**.
4. Refresh pattern candidates from acknowledged Search Impact studies.
5. Confirm one website alone cannot meet the cross-site validation threshold.
6. Export the anonymised evidence bundle and confirm it contains no URLs, names, queries, content or contact data.
7. Import an approved anonymised bundle from another staging website.
8. Confirm a pattern becomes Review ready only after five usable studies across three usable site fingerprints with at least 65% consistency.
9. Validate one eligible pattern with an approver account.
10. Change or refresh supporting evidence and confirm the pattern becomes Revalidation required.
11. Confirm no page, redirect, canonical, indexation setting or external profile changes.

## Connection scopes

The `draft` scope permits reading, candidate refresh and approved anonymised evidence import. The `approve` scope is required for validation, limited-use, rejection, retirement and restoration decisions.
