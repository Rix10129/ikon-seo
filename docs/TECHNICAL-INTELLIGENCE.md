# Technical Intelligence

Ikon SEO 0.11.0 adds a read-only technical evidence layer that joins URL discovery, sitemap membership, internal links, HTTP responses and performance data.

## URL sources

The discovery process combines:

- published public WordPress content types
- XML sitemap indexes and child sitemaps
- links found by the same-site evidence crawler
- stored Search Console page URLs
- stored Analytics landing-page paths

Only URLs on the active website hostname are stored or checked.

## Internal-link graph

For each stored internal link, Ikon SEO can retain:

- source and destination URL
- anchor text
- followed or nofollow status
- approximate placement such as navigation, header, footer, main content or article
- first and latest observation

The graph calculates inbound links, outbound links and followed-link depth from the homepage. An orphan classification means the page was not reachable in the stored graph; it should be manually verified when links are produced only after scripts run.

## Sitemap and URL checks

Reports include:

- published content missing from discovered sitemaps
- sitemap-only URLs not found in WordPress or the stored link graph
- failed URLs
- redirects and their targets
- internal links pointing to failed or redirected destinations
- weak, empty and nofollow internal anchors
- groups of pages declaring the same stored canonical URL

Checks use bounded batches and do not modify redirects, canonicals, content or sitemap settings.

## Performance evidence

PageSpeed batches store mobile or desktop laboratory evidence:

- performance, SEO, accessibility and best-practice scores
- LCP, FCP, CLS and total blocking time
- leading performance opportunities

An optional encrypted Google Cloud API key enables direct Chrome User Experience Report requests when sufficient real-user data exists. Field evidence can include LCP, INP, CLS and TTFB.

## Limitations

- The same-site crawler analyses server-returned HTML and does not execute JavaScript.
- Field metrics may be unavailable for low-traffic URLs.
- A graph result is evidence, not proof that a search engine cannot discover the page.
- Performance scores are diagnostic evidence, not ranking guarantees.
