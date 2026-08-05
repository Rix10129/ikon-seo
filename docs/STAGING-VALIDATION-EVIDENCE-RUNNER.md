# Ikon SEO v2.0.1 Staging Validation & Evidence Runner

## Purpose

The staging runner collects non-destructive evidence inside a real WordPress staging, development or local environment. It does not certify a release by itself. It creates an evidence-locked run that a different WordPress administrator can approve and hand to the existing Production Certification workflow.

## Safety boundary

The runner may write only Ikon SEO validation rows, short-lived cache values, and temporary self-test files. Temporary artifacts are removed after each check. It does not publish, update or delete WordPress posts; install or activate plugins; change redirects, canonicals or robots directives; send email; or contact external hosts other than a safe request to the current WordPress site.

Automated runs are blocked when `wp_get_environment_type()` reports `production`.

## Critical checks

All critical checks must return `passed`. A warning, skipped, failed or pending critical check blocks approval.

1. WordPress and PHP runtime compatibility
2. Database migration and required tables
3. Ikon SEO database CRUD self-test
4. Signed package and file integrity
5. Platform Health readiness
6. Required WP-Cron schedules
7. Same-site WP-Cron loopback
8. Required REST route registration
9. Connection-key scopes, payload limits and rate limits
10. Two-administrator approval capability
11. Client and managed-site tenant isolation contract
12. Recovery and support-bundle secret redaction
13. Temporary recovery-directory write and cleanup
14. Safe same-site HTTP request
15. No automatic publishing or plugin-installation primitives

## Advisory checks

Advisory checks document the site environment without weakening critical gates:

- Object-cache round trip
- Elementor controlled-draft compatibility
- Rank Math or Yoast compatibility
- Cache-plugin review
- Security-plugin review
- Multisite review
- Shared-hosting workload limits

## Approval workflow

1. Install v2.0.1 on a staging, development or local WordPress site.
2. Open **Ikon SEO → Staging Validation**.
3. Start a validation run.
4. Resolve every critical block and rerun the affected checks.
5. Review the evidence fingerprint.
6. Sign in as a different administrator.
7. Approve the exact fingerprint.
8. Export/copy the privacy-minimised evidence pack.
9. Record the evidence in **Production Certification**.

Approved evidence is immutable. A new environment or configuration change requires a new run.

## Workspace commands

`POST /wp-json/ikon-seo/v1/staging-validation`

- `read`
- `start_run`
- `run_checks`
- `approve_run`
- `evidence_pack`

Preparation and evidence collection require the `draft` key scope. `approve_run` requires `approve` scope.

## Evidence limitations

The package-level tests performed outside WordPress do not replace this runner. Hosting-specific conclusions require execution on the actual staging stack, including its database, theme, caching, security plugins, object cache, user roles and WP-Cron configuration.
