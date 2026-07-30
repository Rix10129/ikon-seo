# Ikon SEO v0.6 — Connected Workflow Rules

Use these rules with any approved connected content workflow.

## Pairing before authentication

1. Receive the website URL and one-time code from the WordPress administrator.
2. POST the code to `/pair` without an API key.
3. Store the returned connection key securely and do not display or log it.
4. Send the key in `X-Ikon-SEO-Key` for subsequent requests.
5. Call `/health` immediately so WordPress can verify that pairing completed.

The pairing code expires after ten minutes and can be exchanged only once.

## Required sequence

1. Read `/health`.
2. Read `/profile`.
3. Stop if `configured` is false.
4. Use the returned business facts, target locations, languages, contact details, builder and SEO plugin.
5. Use only schema types listed in `allowed_schema_types`.
6. Search `/pages` and `/inventory` before proposing a new URL.
7. Use `/internal-links` to confirm destinations.
8. Preview schema with `/schema/preview`.
9. Include the current `profile_id` in every page write.
10. Create drafts by default.

Search Console is optional. When connected, use `/search-console/performance`, `/search-console/inspect` and `/search-console/sitemaps` as evidence and diagnostics. Never claim that Search Console data guarantees ranking or indexing.

## Local SEO sequence

1. Read `/local` and `/local/locations`.
2. Use only an active record in the current Website Profile.
3. Distinguish `verified_location` from `service_area`.
4. Never infer a physical office, address or coordinates from an area served.
5. Include `local.location_id`, `local.page_kind`, genuine services and at least three genuine local details.
6. Read the assigned landing page and NAP report before proposing a replacement.
7. Use the same-site UTM builder for Business Profile links.
8. Treat manual rank observations and citations as operational records, not guarantees of Google visibility.

Location-page content must include facts unique to that location. Service-area content must explain genuine availability and area knowledge without presenting a fake office. Do not generate city-name-swapped pages.

## Business Profile sequence

1. Read `/local/gbp/status`; stop if Google is not connected or the record is not linked.
2. Use comparison, review, daily performance and monthly search-keyword reads as evidence.
3. Do not expose OAuth credentials, access tokens or raw connection errors to page content.
4. A connected workflow may stage a review reply or Google Post draft using the current `profile_id`.
5. Never state or imply that staging sends anything to Google.
6. Never ask for or attempt remote approval. A WordPress administrator must review the exact saved text in the dashboard.
7. Do not fabricate reviews, incentivize only positive reviews, hide negative-review requests or mass-produce generic replies.

## Page-plan batches

1. Read `/queue?status=planned`.
2. Select one plan and call `/queue/{id}/claim`.
3. Retain the returned one-time `claim_token`; it expires after one hour.
4. Research and generate one complete page using the plan, active profile, site inventory and confirmed internal links.
5. Submit `{claim_token, page}` to `/queue/{id}/complete`.
6. Do not reuse a claim token or complete a plan on another website.
7. If a plan conflicts with an existing page, recommend improvement mode rather than forcing a duplicate.

CSV import does not authorize low-value location-page duplication. Each page must have distinct intent, useful local or service information, and a full quality review.

## Identity protection

- Never reuse a profile ID from another website.
- Never override the active `business_entity_type`.
- Do not infer a physical office from a service area.
- Do not request a local-business subtype without a verified address.
- Do not invent phone numbers, addresses, credentials, prices, reviews, guarantees or regulatory claims.
- Use `Organization` when no accurate subtype is configured.

## Page creation

- Search for the title, slug, primary keyword and close variants first.
- Prefer improvement mode when a relevant URL already exists.
- Use one primary search intent and one visible H1.
- Use the active profile language only.
- Use the approved business terminology and CTA rules.
- Confirm internal links exist and are published.
- Use authoritative sources for financial, legal, medical, tax and other high-trust topics.
- Keep schema aligned with visible content.

## Improvement mode

- Read the source page before writing.
- Keep its public URL and page purpose unless the administrator approves a migration.
- Create a separate review draft.
- Compare content, headings, metadata, internal links, schema and featured image.
- Do not merge when quality status is `needs_changes`.
- Do not merge if the Website Profile changed after the draft was created.

## Publishing

Direct publishing and remote merge are disabled by default. Administrator review remains the preferred approval route.

The refresh monitor is recommendation-only. A performance decline or overdue review date may justify analysis, but never authorizes an automatic rewrite or publication.
