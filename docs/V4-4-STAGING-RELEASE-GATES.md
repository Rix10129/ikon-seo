# v4.4 Staging Release Gates

Status: go/no-go list before asking an operator to manually install v4.4.

## Completed in development

- Core development line is 4.4.0.
- DB component is 57.0.
- Integrity/Launch Readiness consolidated inside Core.
- Campaign Orchestrator is resumable and batch-first.
- Fleet version/compatibility visibility exists.
- Separate managed-update credential exists; normal workflow key is insufficient.
- Site Agent managed update is locally disabled by default.
- Site Agent independently verifies signed release envelope.
- Remote download requires signed permission, HTTPS allowlisted host and exact package SHA-256.
- Same-version overwrite, downgrade and major-version transition are blocked.
- Local recovery checkpoint is required before replacement.
- Package replacement uses the Site Agent's WordPress upgrader path.
- Post-reboot Core/DB/Production Health verification is recorded.
- Managed update transport has no public page publish/update/delete/trash path.
- Release CLI can build manifest, generate normalized release metadata, sign/verify release envelopes and export a public key.
- Current release gate: 41/41 blocking tests pass.
- Full syntax pass: 197 PHP files, zero failures.
- JSON/OpenAPI/current test files parse successfully.

## Source-control gate

- [ ] Commit the exact extracted recovered v4.3.0 source tree to the canonical recovery branch.
- [ ] Preserve the original v4.3.0 ZIP checksum and recovery manifest.
- [ ] Review the v4.3.0 -> v4.4 diff on top of the canonical recovered tree.

No code merge into legacy `main` is allowed before this is complete.

## Release trust gate

- [ ] Establish a dedicated v4.4+ release-signing trust root outside the plugin repository.
- [ ] Package the corresponding public release key only.
- [ ] Confirm no private signing material exists in plugin/repository/logs.
- [ ] Build final v4.4 manifest and verify its signature.
- [ ] Build final v4.4 ZIP and record exact SHA-256.
- [ ] Create and verify the signed release envelope for that exact package.

## v4.3.0 -> v4.4 staging migration

- [ ] Full staging database/files backup.
- [ ] Record v4.3.0 / DB 56.0 baseline.
- [ ] Manual upgrade to the final v4.4 candidate.
- [ ] Activation completes with no fatal error.
- [ ] DB reaches 57.0.
- [ ] Website Profile / confirmed facts preserved.
- [ ] Project History / Project Brain preserved.
- [ ] Growth Blueprints / page ownership preserved.
- [ ] Campaigns, batches and draft payloads preserved.
- [ ] Review/managed drafts preserved.
- [ ] Search Console/Analytics/local connection state preserved.
- [ ] Production Health completes.
- [ ] Launch Readiness loads.
- [ ] One existing campaign resumes without duplicate batch/drafts.
- [ ] One Controlled Existing Page Update snapshot/diff/review flow passes.

## Managed-update proof on staging

Prove the transport with a later signed patch after v4.4 is installed.

- [ ] Generate/rotate the separate staging deployment credential.
- [ ] Enable managed updates locally on the staging Site Agent.
- [ ] Hub sees installed version + eligibility.
- [ ] Signed release imports as verified.
- [ ] Remote preflight returns ready.
- [ ] Exact package hash verified before install.
- [ ] Recovery checkpoint recorded.
- [ ] WordPress updater completes replacement.
- [ ] Next request confirms Core/DB/Production Health.
- [ ] Hub sees the new verified version.
- [ ] Audit/history event is retained.

## Failure rehearsal

- [ ] Invalid deployment credential rejected.
- [ ] Invalid release signature rejected.
- [ ] Wrong package hash rejected.
- [ ] Non-allowlisted host rejected.
- [ ] Candidate release rejected on production.
- [ ] Same-version/downgrade rejected.
- [ ] File-mod-disabled/non-direct filesystem host rejected.
- [ ] Critical post-update health stops the rollout cohort.
- [ ] Recovery/rollback procedure is reproducible.

## Exit criterion

Only after these gates pass should the user receive the consolidated v4.4 production ZIP. After that one manual trust-transition install, compatible later releases should be managed from Agency Hub instead of repeated ZIP replacement on every client site.
