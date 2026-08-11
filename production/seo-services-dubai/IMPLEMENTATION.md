# SEO Services Dubai — Safe WordPress Rollout

This implementation is intentionally isolated from the frozen homepage, global header, global footer and Proof Library.

## Before any live merge

1. Resolve the existing production page by slug `seo-services-dubai` and update that page rather than creating a second indexable URL.
2. Create a WordPress revision / rollback snapshot before changing page content.
3. Prepare the replacement as a review draft first.
4. Load `page.css` only for the SEO Services Dubai page. Do not add these rules globally.
5. Use the active Website Profile for the real WhatsApp/contact destination. The preview uses `/contact/` as a placeholder and that URL must be checked against the live menu before deployment.
6. Keep `/results/` as the generic proof destination only until the Proof Library returns a verified public evidence URL for a specific approved record.
7. When a public approved evidence asset exists, wire the result CTA to that asset or case-study detail. Never generate an evidence URL from a title or guess a permalink.
8. Preserve the live page's existing canonical URL and SEO ownership: `SEO services Dubai`.
9. Keep broad `SEO agency Dubai` intent on the homepage.
10. Do not publish the old `[Production Review] SEO Services in Dubai` draft as a separate page.

## Data rules

- FWF Search Console metrics: 92 → 221 clicks; 18.9K → 33.9K impressions.
- FWF `#4 safe driver dubai` is Semrush ranking evidence and must remain labelled as such.
- ZeroSync percentage changes must remain attached to the selected comparison period from the approved record.
- Semrush traffic/ranking data is third-party estimated/ranking evidence, not first-party analytics.
- Do not add fabricated leads, revenue, ROI, client totals, awards, years in business or ranking guarantees.

## Intended WordPress flow

`existing live page` → `rollback snapshot` → `review draft` → `desktop/mobile QA` → `SEO/meta/schema QA` → `approve` → `merge into existing live page` → `purge cache` → `verify canonical + links + structured data`.

## Visual QA

Check at minimum:
- 1440px desktop
- 1024px tablet landscape
- 768px tablet
- 390px mobile

No heading may clip under the sticky header. Proof metrics may not overflow their columns. FAQ text must remain readable without horizontal scrolling. The floating WhatsApp control must not cover primary CTAs or FAQ controls.
