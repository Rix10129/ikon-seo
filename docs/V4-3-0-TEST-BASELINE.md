# Ikon SEO v4.3.0 — Test Baseline

Date: 2026-08-13
Package: recovered v4.3.0 / DB 56.0

## Raw standalone run

All `tests/*.php` files were executed independently with PHP.

- Total: 101
- Passed: 46
- Failed: 55

This raw number is intentionally **not** treated as a release score because the directory mixes current tests with historical, version-pinned release assertions.

## Current/high-value tests that passed

The following categories passed and are relevant to the agency-scale consolidation:

### Agency and tenant safety

- Agency Service Levels
- Client Portal tenant isolation
- Portfolio Governance auth and flow
- Portfolio Governance no-live-change policy
- Executive Command Centre

### Production and content control

- Content Workbench
- Content Workbench approval/stale-draft flow
- Editorial Review flow
- Batch Recovery no-live-write behavior
- Opportunity Engine and Operating Plan handoff

### Publishing and release safety

- Publishing Readiness auth
- Publishing Readiness manual-publish flow
- Deployment Control entitlement/signature/readiness/no-auto-update behavior
- Platform Hardening
- Production Certification
- Staging Validation

### Intelligence / planning

- Discovery Review fact/conflict lifecycle
- Pattern Library privacy/threshold/no-live-change behavior
- Smart Autopilot runtime planning
- Page Role & Opportunity Intelligence runtime/static
- Decision Validation & URL Normalization runtime/static
- WordPress State Reconciliation runtime

### Rendering / conversion

- Native Elementor renderer runtime
- responsive conversion runtime
- verified contact & CTA runtime
- service semantic guardrails runtime

### v4.3 release

- `v430-controlled-existing-page-updates-runtime-test.php`
- `v430-controlled-existing-page-updates-static-test.php`

Both v4.3 controlled-existing-page-update tests passed.

## Why many tests fail

Most failures are old release-contract tests that assert exact historical values such as:

- plugin version `4.0.4`, `4.0.11`, `4.1.x`, etc.
- older database versions such as `35.0`, `37.0`, `39.0`, `53.0`
- exact historical OpenAPI operation counts
- historical renderer version constants
- historical readme release text

Those assertions correctly fail when run against 4.3.0, but they do not necessarily indicate a 4.3.0 regression.

Examples include many `v113-...` through `v421-...` static release tests.

## Failures that deserve separate review

A few failures are not explained solely by the top-level plugin version and should be reviewed during source consolidation:

1. `openapi-v190-test.php`
   - expects an old exact dynamic operation count and reports Pattern Library mismatch.
   - likely historical-schema drift, but should be replaced with capability-based OpenAPI tests rather than exact counts.

2. `release-manifest-test.php`
   - reports `.gitignore` missing from the recovered ZIP.
   - this is a packaging/source-control artifact issue, not a WordPress runtime defect.

3. some renderer/static tests report old design/version constants in addition to version checks.
   - current runtime tests for the later renderer features pass, so these should be classified as historical assertions until proven otherwise.

## Required test-system change

Before v4.4 release work, add a machine-readable test manifest, for example:

```json
{
  "current_blocking": [],
  "current_regression": [],
  "historical_frozen": []
}
```

The release pipeline and managed updater should execute **current blocking tests** as the release gate and preserve historical tests for archaeology/regression reference without allowing them to create false release failures.

## Rule going forward

No future release should rely on `run every PHP file in tests/ and count failures` as a production-readiness metric.
