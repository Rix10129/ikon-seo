# v4.4 Automatic Upgrade Preservation Verification

Purpose: reduce manual checking during the v4.3.0 / DB56 to v4.4 / DB57 staging upgrade.

## Before migration

When v4.4 first detects a v4.3.x or DB56 installation, Core records a minimal preservation checkpoint before the v4.4 schema installers run.

The checkpoint records version information, a one-way installation identity fingerprint, whether the main Ikon settings record existed, and row counts for critical existing project tables that are present.

It intentionally avoids copying full settings values into the checkpoint.

Critical table counts cover the existing workspace history, Agency Hub/Site Agent production architecture, blueprints/pages/batches/items, publishing releases, editorial reviews, patterns, recovery snapshots, opportunities and content briefs where those tables exist.

## After migration

After Core/DB version state is updated, v4.4 verifies:

- installation identity is unchanged;
- the main settings record still exists when it existed before;
- every checkpointed critical table still exists;
- checkpointed critical row counts have not decreased during migration;
- Production Core schema reached 1.2.

The result is stored as passed or failed with only check status/count information.

## Launch Readiness integration

The consolidated Launch Readiness gate consumes this preservation result on the Site Agent.

- no v4.3 to v4.4 checkpoint: not applicable;
- checkpoint exists but verification has not completed: review;
- verification passed: ready;
- verification failed: blocked with the failed check identifiers.

A staging upgrade therefore produces one visible preservation exception instead of forcing the operator to inspect many subsystems manually.

## Current test coverage

A runtime fixture verifies baseline capture, successful additive preservation, failure when a critical table count decreases, and inclusion in Launch Readiness.

## Current development baseline

- current release gate: **44/44 blocking tests pass**;
- full PHP syntax pass: **201 PHP files, zero syntax failures**;
- bundled JSON files: **6 parsed, zero failures**;
- deterministic development manifest: **410 tracked files**;
- Core: **4.4.0**;
- database component: **57.0**.

This checkpoint is an additional guard. It does not replace the real staging backup, migration smoke test or recovery rehearsal required before production release.
