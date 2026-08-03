# Evidence Foundation

Ikon SEO 0.9.0 stores page-level evidence before making recommendations. The goal is to explain what is confirmed, what is inferred and what information is still unavailable.

## Direct facts

Examples include:

- A page returns a non-200 HTTP response.
- A page contains `noindex`.
- A canonical points to another URL.
- A page has no rendered title, description or H1.
- A published URL is blocked by robots.txt.
- A stored internal link returns an error.
- Google URL Inspection reports a canonical or indexing conflict.

## Inferred hypotheses

Examples include:

- High impressions and low CTR suggest a search-snippet opportunity.
- A page ranking near page one may benefit from stronger relevance or internal links.
- Several pages receiving visibility for the same query may indicate cannibalisation.
- Declining organic clicks may indicate decay, competition or changed demand.
- Weak engagement or no key events may indicate intent or conversion problems.

Inferred findings include confidence and supporting evidence. They are not presented as known facts about Google's private systems.

## Crawler behaviour

- Crawls only URLs belonging to the current WordPress site.
- Starts from published pages and posts.
- Uses safe WordPress HTTP requests with time and response-size limits.
- Does not submit forms, execute changes or follow off-site links.
- Stores response, indexability, metadata, heading, word-count, link and image evidence.
- Supports manual batches and a daily scheduled refresh.
- Marks evidence stale when a page is saved.

## Per-page report

Each report can include:

- Fix priority from 0 to 100
- Direct blockers
- Inferred opportunities
- Evidence source and confidence
- Recommended action
- Search Console metrics and query overlap
- Analytics landing-page metrics and trends
- Optional URL Inspection result

The fix priority is an internal work-order score, not a Google ranking score and not a ranking guarantee.
