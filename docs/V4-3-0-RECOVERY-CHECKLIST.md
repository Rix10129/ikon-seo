# Ikon SEO v4.3.0 — Canonical Source Recovery Checklist

Purpose: restore the exact recovered/live v4.3.0 Core into source control before further feature development.

## Source-of-truth rule

Do not build the universal consolidation on top of the older repository main branch while the live/recovered production system identifies itself as v4.3.0 / DB 56.0.

The exact installed/recovered v4.3.0 package must become the canonical code base first.

## Recovery inputs

Expected Core package characteristics from the recovery manifest:

- Plugin: Ikon SEO
- Version: 4.3.0
- Role: Agency Hub + Site Agent
- Package root: `ikon-seo/`
- Approximate recovered scope: 396 files / 185 PHP files
- DB component: 56.0 in the exported Project Brain

## One-time recovery procedure

1. Preserve the original v4.3.0 ZIP byte-for-byte.
2. Create a checksum before extraction.
3. Extract into a clean working directory.
4. Confirm plugin header reports 4.3.0.
5. Confirm expected v4 modules are present, especially Agency Hub/Site Agent, Website Profile, Production, Review, Elementor renderer, Search Console/Analytics, Local/GBP and Controlled Existing Page Updates.
6. Run PHP syntax validation across all PHP files.
7. Parse bundled JSON/OpenAPI assets.
8. Scan for secrets/private keys/tokens before commit.
9. Compare against repository main and record why the repository line diverged.
10. Replace the recovery branch contents with extracted source while preserving `.git` metadata.
11. Commit the complete extracted source, not only the ZIP.
12. Tag the recovered baseline after review.
13. Run a WordPress staging activation/upgrade test.
14. Verify database migration/version state.
15. Verify Agency Hub role.
16. Verify Site Agent role.
17. Verify one new unpublished draft workflow.
18. Verify one Controlled Existing Page Update end-to-end.
19. Verify rollback.
20. Only then start consolidation feature work.

## Required runtime acceptance

### Core safety

- No remote auto-publish by default.
- Existing-page update remains review/snapshot based.
- Draft status is preserved until local human approval.
- Delete/redirect/noindex operations are not silently enabled.

### Site isolation

- Website Profile/profile ID remains site scoped.
- Contact details and local records cannot leak between managed sites.
- Agency Hub cannot complete a plan against the wrong client profile.

### Builder/SEO compatibility

- Elementor managed drafts remain editable/renderable.
- Rank Math/Yoast handling does not duplicate schema.
- Theme header/footer/global kit are not overwritten by page production.

### Evidence/integrity

- Project Brain export works.
- Source/evidence records survive upgrade.
- Stale-source protection works for controlled existing-page updates.

## Freeze during recovery

Until the exact v4.3.0 source is canonical:

- do not add another page rollout plugin
- do not create another standalone Integrity plugin release
- do not make new universal architecture decisions based on repository v0.6.0 internals
- do not delete historical recovery branches or package records

Documentation-only consolidation work may continue safely.
