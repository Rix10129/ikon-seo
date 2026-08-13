# Ikon SEO v4.3.0 — Recovery Audit

Date: 2026-08-13
Status: exact package recovered and locally validated; full extracted-tree import to GitHub still pending

## Recovered package identity

The recovered ZIP supplied from the live/installed plugin contains:

- Plugin: `Ikon SEO`
- Plugin version: `4.3.0`
- Core constant: `IKON_SEO_VERSION = 4.3.0`
- Database version: `Ikon_SEO_Plugin::DB_VERSION = 56.0`
- WordPress requirement: 6.4+
- PHP requirement: 7.4+
- Package root: `ikon-seo/`
- ZIP SHA-256: `ed972ca478d8d34a30dd7a784aa6855a111496a95adab60cbdb38a626caf2a56`

## Package inventory

- 396 files total
- 185 PHP files
- 186 Markdown files
- 9 CSV files
- 5 JSON files
- 5 CSS files
- 2 JavaScript files
- 2 PEM public-key files
- 1 signature file
- 1 WordPress readme
- approximately 5.67 MB extracted text/source
- 302 PHP class declarations detected
- approximately 4,142 PHP method declarations detected
- 34 `register_rest_route` calls in the main REST controller
- about 150 Ikon SEO database-table tokens referenced by the plugin core

Directory composition includes:

- `includes/`
- `docs/`
- `tests/`
- `assets/`
- `openapi/`
- `release/`

## Confirmed architectural components

The package is not the old single-site v0.6 line. It contains the expected agency-scale modules, including:

- safe bootstrap / guarded migration
- Agency Hub and Site Agent production roles
- production core and Growth Blueprints
- connected website inventory
- batch production and remote draft dispatch
- local review queue
- controlled existing-page updates
- workspace/project history
- auto discovery and discovery review
- Search Console and Analytics
- technical and indexation intelligence
- competitor/content intelligence
- local/GBP systems
- authority and conversion operations
- publisher intelligence
- content workbench and editorial review
- publishing readiness
- search impact/outcome measurement
- pattern library
- portfolio governance and quality guard
- production health
- platform hardening
- deployment control
- production certification
- staging validation
- Agency Autopilot and command centre

## Existing safety architecture verified in source

### Safe bootstrap

v4.3.0 loads through `Ikon_SEO_Safe_Bootstrap` rather than immediately loading every module. It provides:

- compatibility checks
- guarded database migration
- one-request bootstrap probe
- explicit full-platform enablement
- safe-mode fallback
- emergency `IKON_SEO_SAFE_MODE`

The bootstrap module requires PHP extensions used by the platform and blocks full loading when critical compatibility checks fail.

### Existing-page safety

Controlled Existing Page Updates are present as a Core module. The current production system already supports source-safe review rather than blind overwrite.

### Publishing safety

Publishing Readiness explicitly does not publish, schedule, merge, redirect, delete or change canonical/indexing settings automatically. It produces release checks and expects a human/manual publication action.

### Deployment safety

Deployment Control already contains signed entitlement/release validation, preflight checks and post-deployment verification. Its current safeguards explicitly keep:

- automatic plugin updates off
- remote package download off
- filesystem installation off
- automatic rollback off
- manual WordPress update required

This is important for the consolidation plan: central update management should extend Deployment Control rather than create another independent update subsystem.

## Source-control divergence confirmed

Repository `main` currently reports Ikon SEO version `0.6.0` and eagerly requires a small set of early modules.

Recovered v4.3.0 reports version `4.3.0`, database version `56.0`, uses Safe Bootstrap, and contains the complete v4 production platform.

Therefore repository `main` is not a safe base for new universal code work. The recovered v4.3.0 package is the canonical recovery candidate.

## Validation performed

### PHP syntax

All 185 PHP files were checked with `php -l` during recovery validation and passed syntax validation.

### Structured files

Recovered JSON files parse successfully.

### Secret hygiene

A basic static scan found no obvious embedded private API-token/private-key material. The PEM files in `release/` are public verification keys, consistent with the signed release/entitlement design.

## Test baseline

All 101 standalone PHP test files were executed against the recovered package.

Result:

- 46 passed
- 55 failed

This raw result is **not** interpreted as 55 current defects. Most failures are historical release assertions intentionally pinned to older version numbers, database versions, renderer versions or exact OpenAPI-operation counts. For example, many old release tests expect `4.0.4`, older DB versions or older exact schema-operation counts, which cannot be true for a recovered 4.3.0 package.

Important current/regression tests that passed include:

- deployment control safety
- platform hardening
- production certification
- staging validation
- discovery review fact/conflict workflow
- portfolio governance and isolation
- publishing readiness flow and auth
- pattern-library privacy and threshold checks
- content workbench/editorial flow
- batch recovery no-live-write behavior
- Smart Autopilot runtime planning
- page-role intelligence runtime/static checks
- decision validation runtime/static checks
- WordPress target reconciliation runtime checks
- native Elementor renderer runtime
- responsive conversion runtime
- verified contact/CTA runtime
- service semantic guardrails runtime
- **v4.3.0 Controlled Existing Page Updates runtime and static tests**

## Test-suite debt discovered

The package contains a valuable historical regression archive, but the suite has not been separated into:

1. current release gate tests,
2. compatibility/regression tests, and
3. historical frozen release assertions.

This makes a naïve `run every PHP file` result noisy and unsuitable as an agency release gate.

A consolidation milestone should add a current-release test runner/manifest so the agency updater can distinguish real current failures from intentionally obsolete historical assertions.

## Recovery conclusion

The supplied ZIP is the correct v4.3.0 Core recovery candidate.

Next engineering work should use this codebase and should not continue feature development against repository v0.6.0.
