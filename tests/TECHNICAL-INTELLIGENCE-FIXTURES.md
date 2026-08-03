# Technical Intelligence deterministic fixtures

Use these fixtures during a live test.

## Discovery and sitemap parity

- A published page present in WordPress and a sitemap should show both source flags.
- A published page intentionally excluded from the sitemap should appear in sitemap gaps.
- A legacy URL left only in the sitemap should appear in sitemap-only evidence.

## Link graph

- A page linked from the homepage should have depth 1.
- A page linked only from a depth-1 page should have depth 2.
- A published page with no followed path from the homepage should be classified as outside the stored graph.
- An empty anchor and a generic “read more” anchor should appear in weak-anchor evidence.
- An internal nofollow link should appear in nofollow evidence.

## HTTP evidence

- A 404 destination should appear in failed URLs and broken internal links.
- A 301 destination should preserve its Location target.
- A method-not-allowed response to HEAD should be retried with a bounded GET request.

## PageSpeed and field data

- A successful PageSpeed response should store one mobile or desktop row per URL.
- A missing CrUX record should not cause the lab report to fail.
- A low laboratory score should be presented as evidence, not a confirmed ranking cause.
- A field LCP over 2500 ms, INP over 200 ms or CLS over 0.1 should create an experience finding.

## Safety

- No action changes content, canonicals, redirects, robots.txt or XML sitemaps.
- API credentials never appear in reports, logs or profile exports.
- Only same-host URLs are discovered and checked.
