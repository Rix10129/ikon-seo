# v4.4 Managed Update Groundwork

Status: development design/validation record. Do not deploy this branch as an installable release yet.

## Why this exists

The agency-scale goal is to stop visiting every client WordPress admin to upload another ZIP. v4.3.0 already has Deployment Control with signed entitlement/release metadata, preflight, recovery references, manual approval and post-deployment verification. v4.4 should extend that system instead of inventing a second updater.

## Phase 1 implemented in recovered v4.3.0 development tree

A read-only `Ikon_SEO_Managed_Update_Coordinator` now provides fleet version visibility without installing code.

It reports per managed site:

- Site Agent version from the existing authenticated `/health` response;
- Agency Hub version;
- connection state and last-seen time;
- compatibility state: current / update available / review / blocked / unknown;
- latest release-catalogue metadata when available;
- explicit transport state.

The Agency Hub Command view consumes this report, so version/update status is part of the daily operator surface rather than another control room.

The Hub Command view also now has a portfolio priority queue that condenses each managed site to profile state, campaign state, exception count, installed version and one next safe action.

## Safety state in Phase 1

Central package installation remains explicitly disabled.

The coordinator contains no:

- `Plugin_Upgrader` invocation;
- package download;
- filesystem write;
- plugin activation/deactivation;
- public-content modification.

This is intentional until release signing, recovery preflight and post-install verification are ready.

## Update transport design for the next gate

The later transport should run locally on each Site Agent and be initiated from Agency Hub only after explicit deployment authorization.

Required boundaries:

1. Dedicated deployment authority; do not silently reuse a normal draft-production key.
2. Signed release envelope and exact package checksum before download/install.
3. WordPress/PHP/DB compatibility preflight.
4. Local recovery reference before replacement.
5. Use WordPress upgrader/filesystem mechanisms, not ad-hoc file copying.
6. Verify activation, Core version, DB version and REST/connection health after install.
7. Mark failed site blocked from cohort continuation.
8. Staged cohorts: internal/staging -> pilot -> remaining compatible sites.
9. Audit every release/site/operator/preflight/install/verification/recovery decision.
10. No updater action changes page content, redirects, robots/indexability, GBP data or publication state.

## Trust-root prerequisite

The recovered v4.3.0 package contains signed release-integrity files, but the private release-signing key is not present in the recovered source package. The v4.4 installable milestone must therefore complete an explicit release-signing/trust-root transition rather than shipping a modified package with the old v4.3.0 manifest/signature.

Until that is solved, development packages are not operator-installable releases.

A CLI release helper has now been added in the development tree to build a deterministic manifest and sign/verify it with a private key supplied by path at release time. The private signing key is never stored in the plugin tree.

## Current validation baseline

The recovered v4.4 development tree has a `tests/current-release-tests.json` manifest and CLI `tests/current-release-gate.php` runner so historical exact-version tests are not treated as current blockers.

Current release gate after fleet-version, portfolio-queue and release-tooling groundwork: **40/40 blocking tests pass**.

A full PHP syntax pass currently covers **195 PHP files with zero syntax failures**, and the bundled OpenAPI/current-test JSON files parse successfully.

Historical static tests pinned to old release numbers remain historical evidence and are intentionally not used as v4.4 release blockers.
