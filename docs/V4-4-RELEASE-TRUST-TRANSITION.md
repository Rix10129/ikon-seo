# v4.4 Release Trust Transition

## Problem

The recovered v4.3.0 source includes `release/manifest.json`, `release/manifest.sig`, `release/public-key.pem` and the separate licensing public key. The private key needed to sign a modified v4.4 release is not part of the recovered package and should never have been committed to the plugin.

A modified v4.4 ZIP must not reuse the old v4.3.0 signature. Platform Hardening should reject that state.

## Required transition

Before the first consolidated v4.4 package is offered for installation:

1. Finish Core consolidation and current-release tests.
2. Establish a new release-signing key in an offline/secure agency-controlled location.
3. Put only the corresponding public verification key in the plugin.
4. Rebuild the v4.4 release manifest from the final source tree.
5. Sign the exact manifest with the new private release key.
6. Build the exact ZIP and record its SHA-256 in signed release metadata.
7. Perform a one-time manual/staging trust transition from signed v4.3.0 to signed v4.4.
8. Verify Platform Hardening accepts the new manifest/key.
9. Preserve the v4.3.0 recovery ZIP and trust root for rollback/audit.
10. Only after that enable managed-update transport for later releases.

## Security rule

The private release-signing key must not be committed to GitHub, stored in Project Brain exports, shipped inside the WordPress plugin or copied to every client Site Agent.

Agency Hub/Site Agents need only the public verification material and signed release envelope/package metadata.
