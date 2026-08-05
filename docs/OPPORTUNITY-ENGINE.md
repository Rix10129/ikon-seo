# Evidence Intelligence & Opportunity Engine

Ikon SEO v1.8.0 adds a unified, approval-first work queue for SEO evidence already available to the website.

## Evidence sources

The engine can combine:

- Google Search Console striking-distance, decay and overlap evidence;
- stored Google Analytics landing-page and conversion evidence;
- technical crawl, internal-link, redirect and PageSpeed findings;
- URL Inspection and indexation recommendations;
- approved competitor-page observations and content-gap briefs;
- imported backlink and authority evidence;
- approved Semrush, Ahrefs, licensed-provider or manual CSV exports.

External provider data is never treated as guaranteed traffic, rankings or revenue. Every row retains its source and observation date.

## Opportunity queue

Each current opportunity stores:

- type and category;
- primary evidence source;
- target page and optional keyword;
- search intent when known;
- impact and confidence;
- estimated implementation effort;
- change risk;
- transparent priority score;
- supporting evidence and recommended actions;
- human review status and notes.

Review states are `open`, `reviewed`, `planned`, `completed` and `dismissed`. Only items explicitly marked `planned` can be added to a refreshed Closed-Loop Operating Plan, and duplicate page/category recommendations are skipped.

## Priority method

Priority is an internal work-order score. It combines estimated impact, evidence confidence, freshness, implementation effort and change risk. It is not a Google ranking score, traffic forecast or guarantee.

Higher-risk actions such as consolidation, redirects or canonical review receive a scoring reduction and remain recommendations only.

## CSV imports

The importer accepts up to 5,000 rows per upload. Common Semrush and Ahrefs headings are normalised where possible. Recommended fields are:

- `keyword`
- `url`
- `domain`
- `position`
- `previous_position`
- `search_volume`
- `difficulty`
- `traffic`
- `cpc`
- `intent`
- `country`
- `competitor_url`
- `observed_at`

Imported evidence can be marked stale after a configurable number of days. A source export must be lawfully obtained and approved for the client website.

## Workspace endpoint

Authenticated workspaces can use:

- `GET /wp-json/ikon-seo/v1/opportunity-engine`
- `POST /wp-json/ikon-seo/v1/opportunity-engine`

Supported commands are `read`, `rebuild`, `import`, `update_status` and `archive_evidence`. Read requests require read scope; writes require draft scope and use existing rate limiting and idempotency protection.

The focused OpenAPI schema contains exactly 30 operations. The Opportunity Engine replaces the standalone Search Intelligence, Analytics report and refresh-monitoring actions in that focused schema. Those modules remain available in WordPress and through their direct REST routes.

## Safety boundary

The engine cannot:

- publish or edit a public page;
- create a redirect;
- delete or noindex content;
- change a canonical;
- update a Business Profile;
- answer reviews;
- contact prospects or publishers;
- build backlinks.

It only collects evidence, calculates a review priority and records human decisions.
