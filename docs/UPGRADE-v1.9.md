# Upgrade to Ikon SEO v1.9.0

1. Back up the WordPress database and plugin directory.
2. Install `ikon-seo-v1.9.0.zip` over the existing Ikon SEO plugin on staging.
3. Reactivate the plugin if WordPress requests it so database version 28.0 adds the Content Workbench columns.
4. Confirm the plugin reports version 1.9.0.
5. Open **Opportunity Engine** and mark one genuine content opportunity **Planned**.
6. Open **Content Workbench**, build and review the proposed brief.
7. Approve the current evidence snapshot and create one unpublished scaffold.
8. Edit the draft, run Publisher Intelligence quality evaluation and confirm it cannot be marked ready until the gate passes.
9. Change or rebuild the source opportunity and confirm the prior brief becomes outdated before further readiness decisions.
10. Confirm no public page, redirect, canonical, noindex or deletion action occurred.

Do not deploy to a live client website until the upgrade, permission, REST, Elementor/Gutenberg and rollback checks pass on staging.
