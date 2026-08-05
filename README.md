# Ikon SEO

Ikon SEO is an approval-first WordPress SEO operating system for auditing, planning and improving websites without automatically changing live pages.

## Current release

**v2.0.1 — Staging Validation & Evidence Runner**

The plugin now adds versioned production support contracts, release-specific certification checklists, exact evidence fingerprints, two-person certification approval and bounded controlled rollout waves. It records production-readiness and manual deployment evidence but never downloads, installs, activates, publishes or rolls back anything automatically.

Production certification does not claim that a site is safe without real staging evidence. Critical package, migration, recovery, cron, REST, role-separation, tenant-isolation, privacy and runbook checks must pass, and any evidence change invalidates the previous approval.

## Evidence sources

- Rendered page crawl and response evidence
- WordPress content, status and internal links
- Rank Math, Yoast or stored metadata
- Google Search Console performance and optional URL Inspection
- Optional Google Analytics 4 landing-page reports
- Sitemaps, robots.txt snapshots and multi-source URL discovery
- Internal-link graph, HTTP redirects and optional PageSpeed/CrUX evidence
- Stored competitor observations, page briefs and topical coverage maps
- Imported backlink, referring-domain, anchor and competitor source-gap evidence
- Existing Project History and Page Plans

A fix priority is not a Google ranking score. Diagnostics identify confirmed blockers and probable opportunities; they do not claim access to Google's private ranking logic or guarantee rankings.

## What works without an external workflow

- Page-level technical and content diagnostics
- Website inventory and metadata audits
- Orphan-page and keyword-overlap checks
- SEO Health, Image Audit and Redirect Opportunities
- Local SEO records, service areas, NAP and citations
- Website and business profiles
- Page Plans, Project History and refresh monitoring
- Optional Search Console, Analytics and Google Business Profile integrations

## Safety defaults

- External writes are saved as drafts.
- Improvement mode creates a separate review copy.
- Profile IDs prevent cross-client writes.
- Permanent connection keys are stored as password hashes.
- Analytics and Search Console connections are read-only.
- Live page publishing and remote merge remain disabled by default.

## Development

The plugin supports WordPress 6.4+ and PHP 7.4+.

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

```bash
zip -r ikon-seo-v2.0.1.zip ikon-seo -x 'ikon-seo/.git/*'
```

## Project continuity

Project History stores durable research, recommendations, page-plan decisions, drafts and next steps in WordPress. A private workspace can call `syncIkonSEOProjectHistory` with an empty object to load the latest state without relying on conversation history.

Agency users can download a no-secret transfer guide from **Project History**. When moving to another private-workspace account, import the same schema, generate a fresh workflow key, test the connection and history action, then revoke the old key.

## Staging certification

v2.0.1 adds a non-destructive validation runner for real WordPress staging environments. It collects evidence, blocks on every non-passing critical check, locks results to a SHA-256 fingerprint, and requires a different administrator to approve the handoff. See `docs/STAGING-VALIDATION-EVIDENCE-RUNNER.md`.
