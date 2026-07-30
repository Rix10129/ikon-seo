# Ikon SEO v0.6 Security Model

## Defaults

- Draft-only creation
- Separate improvement drafts
- Remote merge disabled
- Read and draft key scopes only
- Profile-bound writes enabled
- Entity schema disabled until explicitly allowed
- Semantic FAQ schema disabled
- Request size and hourly rate limits
- Idempotency protection for writes
- Read-only Google Search Console scope
- One-hour page-plan claim locks
- Recommendation-only refresh monitoring
- Profile-bound local records
- Verified-address gating for location entities
- No remote Business Profile approval route
- Exact-text administrator approval for review replies and Google Posts

## Connection keys

Keys are generated inside WordPress, stored only as password hashes and shown once. Generating a replacement invalidates the old key. Profile identity changes, profile imports and domain migrations revoke the current key.

The key supports independent scopes:

- `read`: profiles, pages, inventory, media, Search Console, Local SEO and Business Profile reports, comparisons and logs
- `draft`: create or update managed drafts, approved media, page-plan actions and Business Profile drafts
- `approve`: remote merge and rollback, only when Remote approval is also enabled

## Cross-site protection

The active profile creates a fingerprint from the site URL, business URL, business name, industry, entity type and profile version. Page writes must include this `profile_id`. A stale or foreign profile ID is rejected.

## Schema protection

- Arbitrary JSON-LD is not accepted.
- The business entity type comes from the profile, not the request.
- Industry policy limits valid entity types.
- Local-business subtypes require verified address data.
- Review and aggregate-rating schema are rejected.
- Rank Math nodes are merged by stable IDs and duplicate types are skipped.
- With Yoast, overlapping core page nodes are omitted from the fallback graph.

## Media protection

- Remote imports require HTTPS.
- Hosts must be explicitly approved.
- Redirected destinations are checked.
- Private and reserved IP destinations are rejected.
- Attachment MIME type, size and image status are validated.

## Logs

Activity logs store action, result, page IDs, request ID, timestamp and a payload hash. Page content and connection keys are not stored.

## Google Search Console

- OAuth client secrets and refresh tokens are encrypted with AES-256-GCM.
- The encryption key is derived from WordPress authentication salts and the site URL.
- Access tokens are short-lived cached values.
- Tokens and client secrets are excluded from REST responses, profile exports and logs.
- OAuth state and PKCE protect the administrator callback.
- Only `webmasters.readonly` is requested.
- URL inspection accepts only URLs on the current WordPress hostname.
- Domain migration clears the site-bound encrypted Search Console credentials.

## Page-plan queue

- Imported rows are bound to the current Website Profile fingerprint.
- Only approved CSV columns are accepted.
- Imports are limited to 2 MB and 500 valid rows.
- Active keyword/location duplicates are skipped.
- Claims use a one-time random token stored as a SHA-256 hash in the queue table. A successful idempotent claim response may be cached server-side only until the one-hour claim expires.
- Claims expire after one hour.
- Completing a plan still runs normal profile, content, media, schema, duplicate and quality validation.
- The queue has no model credentials and cannot generate content independently.

## Scheduled monitoring

The daily event reads stored review dates and cached or read-only Search Console performance. It creates recommendations only. It cannot edit, regenerate, merge or publish content.

## Local SEO

- Every location, citation, rank observation and Business Profile draft stores the active Website Profile ID.
- A profile identity or domain change rebinds local records, rejects pending Business Profile drafts and revokes stale remote access.
- Service-area and online-only records cannot emit a postal address or location entity.
- A LocalBusiness subtype requires an active verified storefront or hybrid record.
- Local pages require meaningful services and at least three genuine local details.
- The quality report treats NAP, verified-address and doorway-page failures as critical.
- UTM generation is restricted to URLs on the current WordPress website.
- Imported citation and rank CSV files have row, type and size limits.

## Google Business Profile

- OAuth client secrets and refresh tokens use the same site-bound AES-256-GCM protection as Search Console credentials.
- OAuth state and PKCE are bound to the current WordPress administrator.
- Google provides the broad `business.manage` scope; Ikon SEO enforces read-only behavior by policy until an administrator approves an exact draft.
- There is no REST or connected-workflow endpoint that approves or sends a review reply or Google Post.
- Review content is held in a short-lived WordPress transient for display and is not copied into plugin tables or logs.
- Daily review checks store only counts and timestamps.
- Remote drafts require the current Website Profile ID and a linked active location.
- Review replies require a specific review resource returned by Google.
- Google Posts allow only approved standard, event and offer structures and same-site links.
- Every send is recorded in the activity log without secrets or third-party review text.
- Automatic replies, automatic posts, review gating and fabricated reviews are prohibited by design.

## Emergency stop

Turn off **Enable authenticated remote actions** to pause the connection without deleting the key. Revoke the key when access should be permanently invalidated.
