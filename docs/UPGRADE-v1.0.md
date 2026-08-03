# Upgrade to Ikon SEO v1.0.0

## Before upgrading

1. Back up the WordPress database and website files.
2. Confirm that the current Ikon SEO connection is healthy.
3. Record any drafts or approvals currently in progress.
4. Keep the existing workflow key private.

## Installation

1. Open WordPress **Plugins → Add New Plugin → Upload Plugin**.
2. Upload `ikon-seo-v1.0.0.zip`.
3. Select **Replace current with uploaded**.
4. Confirm the correct administrator under **Ikon SEO → Agency Access**.
5. Open **Ikon SEO → Operating Plan**.

## Database upgrade

The database component advances to version 20.0 and creates four new tables:

- Recommendations
- Outcome snapshots
- Scheduled outcomes
- Recovery checkpoints

Existing profiles, strategies, workflows, research, Search Console, Analytics, Project History, Page Plans and drafts are preserved.

## First checks

1. Open the System Health section.
2. Create a recovery checkpoint.
3. Refresh the Operating Plan.
4. Confirm recommendations show evidence, priority, effort and approval state.
5. Approve only one low-risk test recommendation.
6. Confirm no published page changes automatically.

## Private workspace schema

Re-import the existing schema URL from the connected website. Keep Bearer authentication and the current workflow key.

The focused schema still contains exactly 30 operations. Visibility and Brand Intelligence remains available in WordPress and the complete REST interface but is omitted from the focused schema to make room for the Closed-Loop action.

## Recovery

An Ikon recovery checkpoint restores supported non-secret plugin settings only. It is not a replacement for a hosting backup and cannot restore WordPress content, database tables or files.
