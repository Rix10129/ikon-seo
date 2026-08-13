# v4.4 Managed Update Security Model

Status: security design for the consolidated Agency Hub / Site Agent update path.

## Security objective

Allow an Agency Hub administrator to update compatible Site Agents without visiting every WordPress admin, while keeping code deployment separate from SEO/content production authority.

## Trust layers

A managed update is accepted only when all required layers agree:

1. local Site Agent enablement;
2. separate per-site deployment credential;
3. independently verified release signature on the Site Agent;
4. signed release metadata allows remote download;
5. HTTPS package host is locally allowlisted;
6. exact package SHA-256 matches;
7. package plugin identity/version matches;
8. packaged manifest version/database/hash matches;
9. WordPress/PHP/DB/environment compatibility preflight passes;
10. local recovery checkpoint exists;
11. WordPress's plugin upgrader completes;
12. post-reboot Core/DB/Production Health verification passes.

No single normal workflow credential is sufficient to replace plugin code.

## Threat: compromised normal SEO workflow key

Risk: an attacker with the normal Site Agent workflow key attempts code deployment.

Mitigation:

- managed update endpoint uses `X-Ikon-Deployment-Key`, not the normal workflow key;
- deployment credential is generated separately;
- Site Agent stores only the deployment credential hash;
- Hub stores the encrypted deployment key;
- managed updates are disabled locally by default.

## Threat: Hub sends substituted package URL/hash

Risk: a compromised/buggy Hub tries to point a Site Agent at arbitrary code.

Mitigation:

- Hub sends the original signed release envelope;
- Site Agent independently verifies the envelope against its packaged release public key;
- URL/hash/version/database requirements are part of the signed payload;
- package SHA-256 is checked after download;
- plugin identity/version are checked after extraction.

## Threat: man-in-the-middle / package-host compromise

Risk: downloaded bytes differ from approved release.

Mitigation:

- HTTPS required;
- host allowlist required;
- exact signed SHA-256 required;
- mismatch blocks installation before WordPress upgrader runs.

The allowlist alone is not the trust root; the signed hash is required.

## Threat: downgrade / replay

Risk: a valid old signed release is replayed.

Mitigation:

- target must be strictly newer than installed version;
- same-version overwrite is blocked;
- downgrade is blocked;
- major-version transitions use a separate manual migration path;
- production accepts stable channel only;
- target database version cannot be older than installed schema.

## Threat: update on incompatible host

Risk: remote deployment fails because host requires credentials or disallows file changes.

Mitigation:

- checks `DISALLOW_FILE_MODS` / WordPress file-mod policy;
- remote managed update requires direct filesystem method;
- interactive filesystem credentials block remote update;
- PHP/WordPress requirements are checked before package replacement.

## Threat: partial/failed plugin replacement

Risk: plugin files are replaced but the new release does not boot or migrate cleanly.

Mitigation:

- recovery archive created before replacement;
- verified local package is handed to WordPress `Plugin_Upgrader`, not copied ad hoc;
- installed header version checked immediately;
- update is recorded as `installed_pending_reboot`, not immediately trusted;
- next request checks target Core version, DB component and Production Health;
- critical health state becomes `verification_failed` and blocks cohort continuation.

A production rollout should proceed in cohorts rather than updating all sites at once.

## Threat: updater edits website/public SEO state

Risk: code deployment authority becomes a way to modify pages, redirects, indexability or GBP data.

Mitigation:

The managed-update transport is constrained to plugin deployment. Static release tests reject public-content write primitives such as page publish/update/delete/trash from the transport. Normal SEO production remains governed by Production Core / Controlled Existing Page Updates / Launch Readiness.

## Threat: private signing key leakage

Risk: an attacker signs malicious release metadata.

Mitigation:

- private release key is never stored in the plugin source tree;
- plugin contains public verification key only;
- release CLI accepts the private key by external path at release time;
- repository scans must reject `PRIVATE KEY` material under release/source paths;
- production private key should be stored in dedicated operator/CI secret storage with restricted access and rotation procedures.

## Trust-root transition

v4.3.0's old private signing material is not available in the recovered source. Do not pretend it is recoverable.

v4.4 must establish a new release-signing trust root during the one manual upgrade transition. The corresponding private key must be secured outside WordPress and outside the repository before v4.4 is released.

## Remaining release gates

Before enabling the transport on real client sites:

- exact v4.3.0 -> v4.4 staging upgrade;
- DB 56.0 -> 57.0 migration verification;
- Website Profile / Project Brain / campaigns / drafts preservation;
- Controlled Existing Page Update smoke test;
- managed update preflight with a signed test release;
- successful staged v4.4 -> next-patch test on staging;
- failure/rollback rehearsal;
- separate deployment credential revoke/rotate test;
- cross-site credential/isolation test;
- audit-log verification.

## Rollout policy

Recommended production progression:

`development -> staging -> Ikon internal pilot -> one low-risk Site Agent -> remaining compatible Site Agents`

One failed verification pauses the cohort. No silent "update everything" behavior is allowed.
