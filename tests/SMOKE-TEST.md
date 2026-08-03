# Ikon SEO v0.9.1 Smoke Test

## Upgrade

- Existing settings and logs remain.
- Version shows `0.9.1`.
- A recognized ZeroSync installation becomes an accounting profile.
- A fresh installation contains site-local, non-accounting defaults.
- No connection key appears in a profile export.
- Existing profile ID and connection key remain valid after the plugin-only upgrade.
- Queue, location, citation, local-rank and Business Profile draft tables exist.
- One daily monitor event and one daily evidence-crawl event are scheduled.
- Evidence and Analytics snapshot tables exist after upgrade.

## Simple connection

- Connection screen hides the OpenAPI URL and developer key until Advanced settings is opened.
- Connect Ikon SEO creates an eight-character code with no ambiguous letters or numbers.
- The code expires after ten minutes and can be exchanged only once.
- Starting a new pairing invalidates the previous connection key.
- `/pair` requires no key but is rate-limited and returns a no-store response.
- The returned key authenticates Bearer requests; the legacy `X-Ikon-SEO-Key` header remains accepted.
- The dynamic OpenAPI schema uses a Bearer security scheme and does not expose unsupported header parameters.
- A successful exchange changes the dashboard state to Connected.
- Disconnect immediately rejects the old key.
- Test website API reports REST or security-plugin failures without exposing credentials.

## Website Profile

- Saving a valid profile produces `configured: true`.
- Changing business identity changes `profile_id`, pauses remote actions and revokes the key.
- Changing design colours alone does not change `profile_id`.
- Import rejects invalid or oversized JSON.
- Import pauses remote actions and revokes the key.
- Industry/entity mismatches are rejected.

## Profile-bound writes

- Missing `profile_id` is rejected while the safety setting is enabled.
- A foreign or stale profile ID is rejected.
- A current profile ID creates a draft.
- A draft created under an old profile cannot be merged.

## Builders and SEO plugins

- Elementor profile creates editable Elementor data.
- Gutenberg profile creates WordPress content without Elementor ownership metadata.
- Rank Math metadata is saved and schema is merged without duplicate core nodes.
- Yoast title, description, focus keyword, canonical and robots values are saved.
- Yoast fallback schema omits overlapping core page nodes.

## Schema

- Schema preview creates no posts or media.
- AccountingService is rejected outside an accounting profile.
- Local-business subtypes require verified address data.
- Request data cannot override the profile entity type.
- Review and aggregate-rating schema are rejected.
- FAQ markup is emitted only when enabled and visible.

## Workflow

- New pages remain drafts.
- Improve mode creates a separate review draft.
- Comparison includes content, metadata, links, schema and image changes.
- Merge preserves source page ID and URL.
- Merge saves a rollback snapshot.
- Rollback restores managed content, builder data, SEO metadata, schema and image.

## Domain tools

- Preview changes nothing.
- Invalid or identical URL pairs are rejected.
- Apply updates only reported references.
- Apply stores per-page snapshots.
- Apply clears Elementor CSS, pauses remote actions and revokes the key.
- Apply clears encrypted Search Console credentials.

## Search Console

- Invalid client IDs and missing secrets are rejected.
- The redirect URI exactly matches the Google OAuth client configuration.
- OAuth state is user-bound, expires, and cannot be reused.
- Connected status never exposes client secrets, refresh tokens or access tokens.
- Only the read-only webmasters scope is requested.
- Property selection accepts only properties returned for the connected account.
- Performance compares equal current and previous periods.
- URL inspection rejects another hostname.
- Sitemap reads do not submit, delete or change a sitemap.

## Page plans

- CSV without `keyword` is rejected.
- Unsupported headers, page types and profile languages are rejected or skipped.
- Import stops at 500 valid rows and skips active duplicates.
- Queue rows contain the active profile ID.
- Claim returns a one-time token and prevents a second active claim.
- Expired and incorrect claim tokens are rejected.
- Completion enforces the planned focus keyword, language and page type.
- Completion creates a normal WordPress draft and records its page ID.
- Failed completion stores the error and can be reset by an administrator.

## Refresh monitor

- Disabled monitoring performs no scheduled work.
- Manual run remains available.
- Review dates use `YYYY-MM-DD`.
- An administrator can add an existing page or post to the default review schedule.
- Overdue and upcoming pages appear at the configured window.
- Low-volume Search Console declines are excluded.
- Mark reviewed advances the next review date by the configured interval.
- No monitoring path edits or publishes page content.

## Local records

- Storefront, hybrid, service-area and online-only records save under the active profile.
- A verified-address checkbox is ignored for service-area and online-only records.
- Deleting an assigned or GBP-linked location is rejected.
- A profile identity change preserves local records under the new profile ID and rejects pending Business Profile drafts.
- A domain migration updates matched same-site URLs but no third-party citation URL.

## Local pages and quality

- A verified-location page requires an active verified storefront or hybrid record.
- A service-area page rejects a storefront-only location.
- A local page requires at least one service and three genuine local details.
- Service-area schema emits no postal address, coordinates or location entity.
- A verified location can emit only its profile-approved LocalBusiness subtype.
- Visible NAP mismatches and missing verified-page requirements appear in the quality report.
- A highly similar city page receives a critical doorway-risk failure.
- A draft with a critical local failure cannot be merged.
- Publishing or merging a verified-location page assigns the landing page to that record.

## Local operations

- UTM generation rejects another hostname and retains only controlled UTM fields.
- Citation import rejects invalid or oversized CSV and remains bound to the active profile.
- Citation export contains no credentials.
- Local-rank observations are manual/imported records and do not scrape Google.
- Rank CSV import rejects unsupported engines, devices and malformed dates.

## Google Business Profile

- OAuth state and PKCE are administrator-bound and cannot be reused.
- Status never exposes the client secret, refresh token or access token.
- The plugin identifies its policy mode as read-only with administrator-approved mutations.
- Accounts and remote locations load only after the administrator connects Google.
- A local record must be explicitly linked to a remote location.
- Comparison reports show website/GBP mismatches without changing either source.
- A Google-supplied review-request URL is escaped, displayed and copyable; no review gating or incentive language is added.
- Review content is cached temporarily and is absent from plugin tables and logs.
- Performance reads do not alter Business Profile data.
- Monthly search keywords preserve Google privacy-threshold rows and handle pagination.
- Accounts, remote locations and reviews handle paginated responses within configured safety caps.
- A connected workflow can stage only a draft bound to the current profile.
- No REST route can approve, reject or send a Business Profile draft.
- Only a WordPress administrator can send the exact stored draft.
- Rejected drafts cannot be sent.
- Changing the selected Google account removes stale remote links and rejects pending drafts.
- Disconnecting Google rejects pending drafts.
- A core Website Profile identity change clears Business Profile authorization and remote links.
- Daily review checks store counts/timestamps only and never auto-reply.

## Evidence Foundation

- Page Diagnostics lists published pages and posts without editing them.
- Manual crawl batches respect the selected limit and use same-site URLs only.
- Remote crawl requests use safe WordPress HTTP requests, response-size limits and timeouts.
- Saving a published page or post marks its stored evidence stale.
- Evidence stores response status, indexability, robots, canonical, rendered title and description, H1 count, words, links and image ALT gaps.
- robots.txt and the active sitemap endpoint are stored as status snapshots.
- Per-page reports separate `direct` evidence from `inferred` hypotheses.
- Fix priority is labelled as workflow prioritisation and never as a Google ranking score.
- A selected-page refresh can add read-only URL Inspection evidence when Search Console is connected.
- The crawler never changes content, settings, redirects or indexing directives.

## Google Analytics 4

- Analytics credentials are visible only to approved agency administrators.
- The OAuth flow requests only `analytics.readonly`, uses state and PKCE, and stores the refresh token encrypted.
- Search Console OAuth client credentials can be reused without copying the decrypted client secret into the browser.
- Property selection accepts only GA4 properties returned by the connected account.
- Reports compare equal current and previous periods.
- Totals and landing pages include sessions, active users, engagement, views and key events.
- Reports are cached for six hours and never change Analytics configuration.
- Analytics is optional; diagnostics continue without it and expose a limitation note.

## v0.9.0 private workflow

- The dynamic and packaged schemas use Bearer authentication with no explicit authentication header parameters.
- The packaged schema has exactly 30 operations.
- `readGoogleAnalyticsStatus`, `readGoogleAnalyticsReport` and `diagnoseIkonSEORankingEvidence` are available.
- The evidence crawl write route is intentionally excluded from the private workflow schema.
- Direct SEO Health, Image Audit and Redirect Opportunity routes remain available in WordPress but are excluded from the private workflow schema.

## Package

- All PHP files parse under PHP 7.4-compatible syntax.
- JavaScript parses.
- JSON and OpenAPI references validate.
- ZIP contains one top-level `ikon-seo/` folder.
- No customer-facing dashboard label exposes the connected generation method.

## Google Business Profile optional setup

- Open **Business Profile** on a fresh installation.
- Confirm the screen asks whether a Google Business Profile exists before showing OAuth fields.
- Select **No, not yet** and confirm OAuth credentials, approval queue and connection controls are hidden.
- Confirm links to Site Inventory, Local SEO and Page Plans remain available.
- Select **Yes, I have one** and confirm the existing Google OAuth setup is displayed.

## v0.7.2 workflow checks

- Overview sends a configured website to **Scan Website** before Workflow.
- The top tab reads **Workflow**.
- Workflow says it is optional and does not show a pairing code in the normal view.
- Pairing controls appear only inside **Workflow access settings**.
- Site Inventory says the scan runs locally without an external workflow or GBP.
- Skipping Google Business Profile does not create a warning on Overview.


## Diagnostic Accuracy v0.9.1

- A draft with a present SEO title does not fail because of a fixed character range.
- A present meta description does not fail because of a fixed character range.
- Content is not penalised for being below an arbitrary service-page or article word count.
- Multiple H1 elements are retained as context and are not automatically scored as a ranking failure.
- A missing H1 is reported as a direct content-structure finding.
- Stored focus topics use semantic token overlap rather than exact-phrase matching.
- Related findings sharing a root cause are deduplicated.
- Page reports contain `priorities`, `data_sufficiency`, `business_value`, `ranking_blockers`, `search_opportunities`, `conversion_issues` and `measurement_issues`.
- Conversion and Analytics measurement findings do not appear as confirmed ranking blockers.

## Search Intelligence 0.10.0

1. Confirm Search Console is connected to the correct property.
2. Open **Ikon SEO → Search Intelligence**.
3. Refresh 28 days with a 50,000-row limit.
4. Confirm current and previous periods are stored without exposing OAuth credentials.
5. Confirm query clusters appear and leading pages open on the same website.
6. Confirm every overlap result lists at least two URLs and states that review is required.
7. Confirm striking-distance rows have positions between 8 and 20.
8. Confirm decay rows compare current and previous impressions.
9. Open Page Diagnostics and confirm stored query-level evidence appears on affected pages.
10. Re-import the OpenAPI schema and verify exactly 30 operations with `readIkonSEOSearchIntelligence` present.


## Technical Intelligence 0.11.0

1. Open **Technical Intelligence** and refresh URL discovery.
2. Confirm WordPress, sitemap, crawler, Search Console and Analytics source flags appear where available.
3. Confirm the homepage has depth 0 and followed internal pages receive increasing depths.
4. Run a 10-URL status batch and verify redirects and failed destinations are stored without changing the site.
5. Run a one-page mobile PageSpeed batch.
6. Configure a valid Google Cloud API key and confirm field data is shown only when available.
7. Open a page diagnosis and confirm graph, sitemap or performance evidence appears when applicable.
8. Re-import the focused schema and confirm exactly 30 operations with `readIkonSEOTechnicalIntelligence`.
9. Verify a client administrator can view reports but cannot view or change the stored performance-data key.


## Competitor & Content Intelligence 0.12.0

1. Open **Content Intelligence** and confirm the customer-safe screen contains no workflow credentials.
2. Add one competitor observation with a query, valid HTTPS URL, intent, result type and observed date.
3. Add the same query, URL and date again and confirm it updates rather than duplicates.
4. Confirm invalid URLs and records without a query are rejected.
5. Store at least three competitor pages for one query and create a page brief.
6. Confirm the brief shows target intent, inferred page intent, dominant result type, confidence, topic coverage, direct evidence, hypotheses and limitations.
7. Confirm recurring competitor proof patterns are labelled for factual review and are not copied into page content.
8. Confirm creating a brief does not change the selected WordPress post.
9. Confirm Project History receives a research item for the brief.
10. Confirm the topic map combines stored briefs with Search Console clusters when available.
11. Re-import the focused schema and confirm exactly 30 operations with `syncIkonSEOCompetitorContentIntelligence` present.
12. Call the action with `{}` and confirm it reads the current state without requiring draft scope.
13. Store research through the action and confirm draft scope is required.
14. Confirm `/local/citations` remains available in WordPress but is omitted from the focused 30-operation schema.
15. Search customer-facing PHP, JavaScript and CSS for prohibited provider terminology and confirm none is present.


## Authority & Off-Site Evidence 0.13.0

1. Upgrade and confirm both authority tables exist.
2. Open Authority Intelligence in Client Mode and confirm credentials and technical settings are hidden.
3. Confirm only an Agency administrator sees the CSV import form.
4. Import the generic website-backlink fixture and confirm one referring domain and one target page.
5. Import a competitor fixture and confirm a source-domain gap is created.
6. Import a shared source and confirm it is excluded from gaps.
7. Import a lost link and confirm recovery evidence.
8. Refresh Technical Intelligence with a 3xx/4xx target and confirm recovery confidence is high.
9. Re-import the workspace schema and confirm exactly 30 operations.
10. Call `syncIkonSEOAuthorityIntelligence` with `{}` and confirm no write occurs.
11. Confirm no live page, redirect, canonical or external link is changed.


## Agency Command Centre 0.18.0

1. Upgrade and confirm the four agency command tables exist.
2. Confirm only an Agency administrator sees the Agency Command Centre tab.
3. Generate a read-only site key and confirm it is displayed only once.
4. Add one HTTPS test website and confirm its initial snapshot is stored.
5. Confirm the stored site key is encrypted and excluded from reports and exports.
6. Refresh the test website and confirm snapshot history remains bounded.
7. Revoke the remote key and confirm a connection alert appears.
8. Replace the key and confirm a successful refresh resolves the connection alert.
9. Confirm remote approval tasks appear centrally but cannot be executed from the command centre.
10. Record usage and confirm the monthly budget percentage and alert.
11. Export a white-labelled client HTML report and portfolio CSV.
12. Connect two Publisher Intelligence sites and test privacy-signature overlap review.
13. Re-import the focused schema and confirm exactly 30 operations with `syncIkonSEOAgencyCommandCentre` present.
14. Confirm `/local/ranks` remains available in WordPress but is omitted from the focused schema.
15. Confirm no central action publishes, edits, redirects, deletes, sends outreach or changes public profile data.


## Visibility & Brand Intelligence 0.19.0

1. Open **Ikon SEO → Visibility & Brand**.
2. Save a primary brand name, one verified alias and one competitor.
3. Store an own-brand observation with a valid source URL.
4. Store a competitor citation observation for the same query.
5. Store an external unlinked brand mention.
6. Confirm the mention appears in the unlinked-opportunity queue.
7. Change its workflow state to Reviewed.
8. Refresh the combined snapshot.
9. Confirm Project History records the update.
10. Confirm no outreach, publication, link creation or public response occurs.
11. On an Agency Command Centre test site, refresh the managed snapshot and confirm visibility counts appear.
12. Re-import the focused action schema and confirm exactly 30 actions are available.

## v1.1.0 Indexation Intelligence and Production Health

1. Upgrade a staging copy from v1.0.0.
2. Confirm database component version `21.0`.
3. Open **Indexation Intelligence** without a fatal error.
4. Run Production Health and review every critical or warning item.
5. Confirm the three v1.1 tables exist.
6. Connect Search Console and select the correct property.
7. Prepare no more than 20 URLs.
8. Run a batch of one to three inspections.
9. Confirm index state, canonical and crawl evidence are stored.
10. Confirm external-host URLs are rejected.
11. Confirm no indexing request, live test or page edit occurs.
12. Confirm indexation findings can appear in the Operating Plan.
13. Confirm the Agency Command Centre snapshot includes bounded indexation and system-health summaries.
14. Re-import the focused schema and confirm 30 unique operations.
15. Deactivate and reactivate on staging; confirm scheduled events are not duplicated.


## v1.2.0 Structured Data & Media Governance

1. Upgrade a staging copy from v1.1.0.
2. Confirm database component version `22.0`.
3. Confirm the three governance tables exist.
4. Open **Structured Data & Media** without a fatal error.
5. Run a structured-data batch of three pages.
6. Run a media batch of ten images.
7. Confirm external-host structured-data URLs are rejected.
8. Confirm invalid JSON-LD, missing types and duplicate entity evidence.
9. Confirm image size, alternative text, usage and duplicate-file evidence.
10. Save one source and rights record.
11. Confirm no markup, image file, alternative text or published page changes.
12. Confirm governance activity appears in Project History.
13. Re-import the focused schema and confirm 30 unique operations.
14. Confirm `syncIkonSEOStructuredMediaGovernance` is present.
15. Confirm Schema Preview remains available outside the focused schema.


## v1.3.0 Experiments, Claims & Revenue

1. Upgrade a staging copy from v1.2.0.
2. Confirm database component version `23.0`.
3. Confirm the four v1.3 tables exist.
4. Open **Experiments, Claims & Revenue** without a fatal error.
5. Create a draft experiment with one same-site test URL.
6. Add a comparison URL and confirm test/comparison overlap is rejected.
7. Attempt to reuse an active experiment URL and confirm it is blocked.
8. Capture a baseline and an outcome fixture.
9. Confirm low-quality evidence becomes inconclusive.
10. Save a standard and a high-risk claim; confirm the high-risk review date is sooner.
11. Record one lead and one sale with non-identifying internal references.
12. Confirm event references are hashed and contact metadata is excluded.
13. Confirm an external landing-page URL is rejected.
14. Confirm no public page, CRM record or customer communication changes.
15. Re-import the focused schema and confirm 30 unique operations.
16. Confirm `syncIkonSEOExperimentsClaimsRevenue` is present.
17. Confirm Structured Data & Media Governance remains available outside the focused schema.
18. Run weekly maintenance and confirm overdue and retention processing.


## v1.4.0 International & Server Intelligence

1. Upgrade a staging copy from v1.3.0.
2. Confirm database component version `24.0`.
3. Confirm the three v1.4 tables exist.
4. Open **International & Server** without a fatal error.
5. Save one valid locale-map line and audit three same-site pages.
6. Confirm external-host audit URLs are rejected.
7. Confirm HTML language, canonical and hreflang evidence is stored.
8. Confirm duplicate locales, missing self-reference, x-default and canonical conflicts are detected.
9. Audit corresponding alternate pages and confirm reciprocal-link evidence refreshes.
10. Import a small Apache combined-log fixture.
11. Confirm raw IP addresses, raw user-agent strings and query values are absent from stored records.
12. Confirm query-key storage can be disabled.
13. Enable crawler verification on staging and confirm unverified declarations are not reported as verified.
14. Review crawler errors, waste paths, slow paths and important uncrawled pages.
15. Confirm no translated page, canonical, hreflang tag or published content changes.
16. Re-import the focused schema and confirm 30 unique operations.
17. Confirm `syncIkonSEOInternationalServerIntelligence` is present.
18. Confirm Experiments, Claims & Revenue remains available outside the focused schema.
19. Run weekly maintenance and retention cleanup on staging.

## v1.5.0 Portfolio Quality & Footprint Guard

1. Upgrade on staging and confirm database component version 25.0.
2. Confirm the Portfolio Quality tab is visible to approved agency administrators and read-only clients.
3. Create signatures for ten local pages.
4. Confirm the exported JSON bundle excludes complete page content, author names and image files.
5. Attempt to import the bundle back into the same site and confirm it is rejected.
6. Import a different managed-site bundle and confirm the bounded page count.
7. Evaluate the portfolio and review content, template, topic, author, media and publishing-pattern findings.
8. Create four thin pages with the same title and template pattern on staging and confirm cluster findings.
9. Confirm high findings can set Publisher Intelligence to `blocked_portfolio` without changing a page.
10. Mark a test finding dismissed, evaluate again and confirm the gate is recalculated.
11. Confirm high findings appear as approval-required Operating Plan recommendations.
12. Confirm Agency Command Centre snapshots and alerts include portfolio-quality counts.
13. Run the weekly task once and confirm Project History records it.
14. Confirm no page, redirect, external link, author profile or media file is changed.
15. Re-import the focused schema and confirm exactly 30 unique operations.
