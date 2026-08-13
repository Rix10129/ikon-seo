# v4.4 Managed Update Groundwork

Status: development validation record. Do not install this development branch on a production site yet.

## Why this exists

The agency-scale goal is to stop visiting every client WordPress admin to upload another ZIP. v4.3.0 already has Deployment Control, recovery, production-health and Hub/Site Agent primitives. v4.4 extends those primitives rather than creating a second updater.

## Fleet and operator layer

`Ikon_SEO_Managed_Update_Coordinator` gives Agency Hub one fleet report with:

- Site Agent version from authenticated health data;
- Hub version;
- connection/last-seen state;
- compatibility: current / update available / review / blocked / unknown;
- signed release-catalogue state;
- per-site managed-update credential/enablement state;
- one managed-update action when the site and release are eligible.

The Hub Command view also has the Portfolio Priority Queue: profile state, campaign state, exception count, installed version and one next safe action per website.

## Separate deployment authority

Managed updates do **not** reuse the normal Site Agent workflow key.

v4.4 creates a separate per-site deployment credential. The Site Agent stores only its password hash; the Hub stores the encrypted corresponding deployment key. Revoking the normal connection also clears the deployment authority and disables managed updates on that Site Agent.

Managed updates are locally disabled by default and must be explicitly enabled on the Site Agent.

## Independently signed release envelope

The Hub is not the only trust check.

For `preflight` and `apply`, the Hub sends a signed release envelope. The Site Agent independently verifies that envelope using its packaged release public key before any package download is considered.

The signed payload contains release/version/database/channel/environment, package URL, exact package SHA-256, manifest SHA-256, WordPress/PHP requirements and whether remote download is allowed.

This prevents a compromised or buggy normal workflow from silently substituting arbitrary release metadata.

## Site Agent preflight

The managed-update transport blocks when material conditions include:

- local managed updates disabled;
- no separate deployment credential;
- release signature not independently verified;
- release identity/version/database incomplete;
- missing/fake package SHA-256;
- remote download not permitted by the signed envelope;
- non-HTTPS or non-allowlisted package host;
- same-version overwrite/downgrade;
- major-version transition;
- target database older than installed schema;
- PHP/WordPress below release requirements;
- non-stable release on production;
- another update lock active;
- WordPress file modifications disabled;
- host requires interactive filesystem credentials.

## Package verification and WordPress install path

When preflight is ready, the Site Agent:

1. acquires a bounded update lock;
2. downloads the signed package URL to a temporary file;
3. enforces the package size ceiling;
4. verifies the exact package SHA-256;
5. unpacks to a temporary verification directory;
6. verifies `ikon-seo/ikon-seo.php`, plugin identity and exact target version;
7. verifies packaged release manifest version/database and optional manifest hash;
8. creates a local Platform Hardening recovery archive;
9. exposes the already-verified local package to WordPress's normal `Plugin_Upgrader` path;
10. confirms the installed plugin header reports the approved target version;
11. records a pending post-reboot verification checkpoint.

The Hub coordinator itself contains no filesystem/install primitive. Filesystem replacement happens locally on the Site Agent only.

## Post-reboot verification

On the next Site Agent request, the transport confirms target Core version + target DB version and runs Production Health. A critical health result marks the update verification failed; otherwise the pending update is marked verified after reboot with health status/counts retained.

A failed site must be held from further cohort rollout until reviewed.

## Public-content safety

The update transport contains no page publish/update/delete/trash path and does not modify redirects, robots/indexability, GBP data or website content.

Managed plugin deployment and website production remain separate authorities.

## Release tooling

The development tree now has CLI helpers to:

- build the deterministic release manifest;
- sign/verify that manifest with a private key supplied by path at release time;
- generate normalized release metadata from an exact package URL/hash;
- sign a release envelope;
- verify a release envelope;
- export a public key from a private release key.

Private signing keys are never copied into the plugin tree.

## Trust-root transition

The recovered v4.3.0 package does not include the private key corresponding to its old signing material. Therefore v4.4 is intentionally the one manual trust-transition release.

Before v4.4 can be called installable:

1. create a new dedicated release-signing key in secure operator storage;
2. package only the public release key in v4.4;
3. produce/verify the signed v4.4 manifest;
4. perform the exact v4.3.0 -> v4.4 staging upgrade and data-preservation tests;
5. perform rollback/recovery rehearsal;
6. then install v4.4 manually on the Agency Hub/pilot Site Agent.

Future compatible releases can then use the managed-update path instead of repeated ZIP uploads.

## Current validation baseline

Current local recovered v4.4 development baseline:

- plugin version: **4.4.0**;
- database component: **57.0**;
- current release gate: **41/41 blocking tests pass**;
- full PHP syntax pass: **197 PHP files, zero syntax failures**;
- bundled JSON/OpenAPI/current-test files parse successfully;
- current generated release manifest: **406 tracked files**.

Historical tests pinned to superseded exact versions remain historical evidence and are not used as current-release blockers.
