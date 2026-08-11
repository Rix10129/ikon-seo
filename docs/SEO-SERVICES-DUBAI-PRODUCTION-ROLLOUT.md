# SEO Services Dubai — Production Rollout

This rollout is intentionally isolated from the frozen Ikon Digitals homepage, global theme foundation and signed Ikon SEO core release.

## Delivery model

The page ships as a small standalone WordPress rollout plugin rather than modifying the signed `Ikon SEO 2.0.1` package. This keeps the current SEO platform and release manifest untouched while giving the service page its own preview, apply and restore controls.

Package directory:

`production/seo-services-dubai/`

Main plugin file:

`ikon-seo-services-dubai-rollout.php`

## What the rollout contains

- Full `/seo-services-dubai/` commercial page body.
- Page-scoped responsive stylesheet under `.ikon-sd`.
- Verified FWF Safe Driver and ZeroSync proof summaries with source context.
- Google Search, Google Maps and AI Search workstreams.
- Dubai-specific search strategy, process, reporting, customer-fit and commercial FAQ sections.
- A WordPress admin control under **Ikon SEO → Production Page** when Ikon SEO is active.
- A no-write **Preview New Layout** action for the existing page.
- Automatic pre-change backup of the existing page content and relevant SEO/builder metadata before Apply.
- One-click restore to the captured pre-rollout page body/status/meta.
- Domain guard: Apply and Restore only run on `ikondigitals.com`.

## Safe apply flow

1. Install and activate **Ikon SEO Services Dubai Rollout**.
2. Open **Ikon SEO → Production Page**.
3. Confirm the target page and site guard.
4. Click **Preview New Layout**. Preview is administrator-only and does not write the new page body to the database.
5. Check the preview at desktop, tablet and mobile widths.
6. Return to **Production Page** and click **Apply Production Page** only after the preview is acceptable.
7. Open the public page in an incognito/private browser and complete final QA.

The controller finds the existing page by slug. If the page exists, its current published/draft status is preserved. If the page does not exist, Apply creates a draft instead of auto-publishing a new URL.

## Rollback

If the live layout does not behave correctly with the theme:

1. Open **Ikon SEO → Production Page**.
2. Click **Restore Previous Version**.
3. The saved page content, title, excerpt, status, Rank Math fields, Elementor data and page-template data are restored.

The backup remains stored in page meta until Restore is used. Re-applying does not overwrite the original pre-rollout snapshot.

## SEO ownership

- `/seo-services-dubai/` owns the primary intent **SEO services Dubai**.
- The homepage remains responsible for the broader **SEO agency Dubai** positioning.
- Local SEO, Technical SEO and AI Search pages keep their own narrower commercial intent.

## Metadata installed when Rank Math is active

**SEO title:** SEO Services in Dubai | Ikon Digitals

**Meta description:** SEO services in Dubai covering technical SEO, commercial pages, local search, content, authority and AI visibility, backed by documented results.

**Focus keyword:** SEO services Dubai

**Canonical:** `https://ikondigitals.com/seo-services-dubai/`

## Evidence-link rule

The production page does not invent or guess Proof Library permalinks. Result CTAs use the verified Results hub until a specific public evidence URL is confirmed by the proof system.

## What this rollout does not touch

- Homepage content or V7/V9 foundation.
- Header, navigation or footer.
- Global Customizer CSS.
- Existing Proof Library records.
- Existing Results Hub records.
- Ikon SEO core plugin files or signed release manifest.
- Other WordPress pages.

## QA breakpoints

Check at minimum:

- 1440px desktop.
- 1024px tablet landscape.
- 768px tablet.
- 390px mobile.

Confirm that headings do not clip under the sticky header, metric cards do not overflow, FAQ rows remain readable, the two proof rows remain legible, buttons resolve to the intended live pages, and floating WhatsApp controls do not cover important links or buttons.
