# Ikon SEO

Ikon SEO is an approval-first WordPress SEO operating system for auditing, planning and improving websites without automatically changing live pages.

## Current release

**v1.5.0 — Portfolio Quality & Footprint Guard** adds:

1. **Privacy-preserving local page signatures** using bounded hashes, topic terms and structural counts instead of complete article text.
2. **Cross-site content similarity review** using bundles exported from other managed websites.
3. **Repeated template and heading-pattern detection** for portfolio-wide editorial review.
4. **Topic-map overlap and differentiation evidence** without treating normal subject overlap as an automatic failure.
5. **Hashed author and optional media-reuse observations** without exporting author names or image files.
6. **Publishing-pattern observations** used only as contextual evidence.
7. **Thin programmatic cluster detection** for repeated local pages below configurable content thresholds.
8. **Publisher Intelligence review gates** for high-risk findings.
9. **Closed-Loop Operating Plan and Agency Command Centre integration** with approval-required recommendations.
10. **Weekly bounded evaluation, retention cleanup and Project History records** while preserving exactly 30 focused workspace operations.

All v1.4.0 International & Server Intelligence and every earlier experiment, claim, revenue, governance, indexation, Operating Plan, agency, local, publisher, workflow, strategy, search, technical, competitor, authority, analytics, Project History and draft-protection system remain available.

Portfolio findings are review signals. They do not prove copying, spam, a search-policy violation or ranking impact.

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
zip -r ikon-seo-v1.5.0.zip ikon-seo -x 'ikon-seo/.git/*'
```

## Project continuity

Project History stores durable research, recommendations, page-plan decisions, drafts and next steps in WordPress. A private workspace can call `syncIkonSEOProjectHistory` with an empty object to load the latest state without relying on conversation history.

Agency users can download a no-secret transfer guide from **Project History**. When moving to another private-workspace account, import the same schema, generate a fresh workflow key, test the connection and history action, then revoke the old key.
