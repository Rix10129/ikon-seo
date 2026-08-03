# Indexation Intelligence

## Purpose

Indexation Intelligence stores quota-aware evidence from Google Search Console for important URLs on the connected website. It helps an agency review indexing state, canonical selection, crawl recency, mobile usability and rich-result evidence without changing the website or requesting indexing.

## Evidence boundary

The URL Inspection service reports the version currently known to Google. It is not a live-page test and it does not guarantee future indexing.

Ikon SEO does not use this module to:

- request indexing;
- submit general website pages through a separate indexing service;
- change robots directives;
- remove noindex settings;
- change canonical tags;
- publish or edit content;
- treat a neutral result as proof that a page can never be indexed.

## Queue sources

URLs may be prepared from:

- published WordPress inventory;
- the homepage and high-value pages;
- post-change events when enabled;
- approved manual URL queues;
- the private workspace action.

Only same-host website URLs are accepted.

## Priority and staleness

The queue prioritises the homepage, pages, changed published content and unresolved issues. Stored evidence can be marked stale after a configurable number of days and rechecked within the local inspection budget.

## Stored fields

For every inspected URL, the module may store:

- verdict and coverage state;
- indexing and page-fetch states;
- robots state;
- last crawl time;
- Google-selected canonical;
- user-declared canonical;
- canonical disagreement flag;
- local noindex evidence;
- sitemap-discovery evidence;
- mobile-usability verdict;
- rich-results verdict and bounded item types;
- issue code and last error;
- request and inspection dates.

## Quota controls

The local daily budget is configurable from 1 to 2,000 inspections. A smaller default protects shared quotas and encourages prioritisation. The batch runner also stops when a quota or rate-limit response is observed.

## Recommended first test

1. Connect Search Console and select the correct property.
2. Prepare no more than 20 important URLs.
3. Run a batch of 1 to 3 inspections.
4. Confirm the stored canonical and coverage evidence.
5. Review any noindex, robots or fetch issue manually.
6. Confirm that no indexing request or live-page edit occurred.

## Private workspace command

The focused action is `syncIkonSEOIndexationIntelligence`.

Supported commands:

- `read`
- `seed`
- `queue`
- `inspect_batch`
- `inspect_url`
- `run_health`
- `save_settings`
- `cleanup`
