# v4.4 Fail-Closed Release Readiness Gate

The development tree now contains a separate CLI release-readiness checker. Its job is to make it difficult to accidentally label a development ZIP as production-ready.

## Checks

The gate requires all of the following before returning `RELEASE READY`:

- current blocking release test gate passes;
- no private signing key material exists anywhere in the plugin tree;
- release manifest exists and parses;
- every file named in the manifest exists and matches its SHA-256;
- manifest public key is present and parseable;
- release-envelope public key is present and parseable;
- manifest signature verifies;
- signed release envelope verifies;
- manifest release/database values exactly match the signed release envelope;
- signed `manifest_sha256` matches the exact manifest bytes;
- supplied package SHA-256 matches the signed release envelope;
- signed release policy explicitly permits HTTPS managed deployment.

The checker never creates signing keys. A production private key must remain outside WordPress and outside the repository.

## Fail-closed behavior

The current v4.4 **development** tree intentionally returns `RELEASE NOT READY` because it still contains the recovered v4.3 manifest/trust material and does not contain the final production v4.4 trust root/package/envelope.

That is the expected result. Development passing unit/static tests is not sufficient to create an installable production release.

## Test coverage

A controlled signed fixture proves the readiness checker can reach `RELEASE READY` when all artifacts agree.

The same fixture then changes the package bytes after signing; the readiness checker rejects it. This protects the package-hash binding between the signed release envelope and the actual ZIP.

A separate v4.4 migration-safety test verifies the intended DB 56 -> 57 changes remain additive and that managed-update authority is cleared/disabled on disconnect and domain migration.

## Current development validation

- current release gate: **43/43 blocking tests pass**;
- full PHP syntax pass: **200 PHP files, zero syntax failures**;
- bundled JSON files: **6 parsed, zero failures**;
- current deterministic development manifest: **409 tracked files**;
- plugin version: **4.4.0**;
- database component: **57.0**.

These numbers describe the development source tree only. They do not replace the real WordPress staging migration and trust-transition gates.

## Remaining production gates

1. Import the exact extracted v4.3.0 source into canonical GitHub source control.
2. Establish a dedicated production release-signing trust root outside the repository.
3. Build the exact final v4.4 manifest, signature, package and signed release envelope.
4. Run the fail-closed release readiness checker against those exact artifacts.
5. Run the real v4.3.0 / DB 56.0 -> v4.4 / DB 57.0 WordPress staging migration and data-preservation tests.
6. Rehearse recovery/rollback.
7. Only then provide the v4.4 production ZIP to the operator for the one manual trust-transition install.
8. Prove central managed updates using a later signed staging patch before enabling client rollout cohorts.
