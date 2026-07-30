# Ikon SEO v0.6 Smoke Test

## Upgrade

- Existing settings and logs remain.
- Version shows `0.6.0`.
- A recognized ZeroSync installation becomes an accounting profile.
- A fresh installation contains site-local, non-accounting defaults.
- No connection key appears in a profile export.
- Existing profile ID and connection key remain valid after the plugin-only upgrade.
- Queue, location, citation, local-rank and Business Profile draft tables exist.
- One daily monitor event is scheduled.

## Simple connection

- Connection screen hides the OpenAPI URL and developer key until Advanced settings is opened.
- Connect Ikon SEO creates an eight-character code with no ambiguous letters or numbers.
- The code expires after ten minutes and can be exchanged only once.
- Starting a new pairing invalidates the previous connection key.
- `/pair` requires no key but is rate-limited and returns a no-store response.
- The returned key authenticates `X-Ikon-SEO-Key` requests.
- The dynamic OpenAPI schema exposes the key as a private header parameter after pairing.
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

## Package

- All PHP files parse under PHP 7.4-compatible syntax.
- JavaScript parses.
- JSON and OpenAPI references validate.
- ZIP contains one top-level `ikon-seo/` folder.
- No customer-facing dashboard label exposes the connected generation method.
