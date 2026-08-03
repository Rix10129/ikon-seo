# Connected Workflow (Developer Integration)

> **Optional:** Ikon SEO works locally without this integration. The plugin does not include a built-in content generation service, and a pairing code alone does not connect the private operator workspace.

Use these rules with any approved connected content workflow.

## Pairing before authentication

1. Receive the website URL and one-time code from the WordPress administrator.
2. POST the code to `/pair` without an API key.
3. Store the returned connection key securely and do not display or log it.
4. Use the key as a Bearer API key for subsequent requests. The legacy `X-Ikon-SEO-Key` header remains accepted for compatible private integrations.
5. Call `/health` immediately so WordPress can verify that pairing completed.

The pairing code expires after ten minutes and can be exchanged only once.

## Required sequence

1. Read `/health`.
2. Read `/profile`.
3. Call `/strategy` with `{ "save": false }` and use the returned operating mode, goals, quality gate and automation policy.
4. Stop if the Website Profile is not configured. Treat incomplete strategy readiness as a setup limitation and do not invent the missing decisions.
5. Use the returned business facts, target locations, languages, contact details, builder and SEO plugin.
6. Use only schema types listed in `allowed_schema_types`.
7. Read `/workspace-state` to restore durable project context.
8. Read `/inventory` and `/evidence` before diagnosing a page or proposing a new URL.
9. Search `/pages` before proposing a new URL.
10. Use `/internal-links` to confirm destinations.
11. Preview schema with `/schema/preview`.
12. Include the current `profile_id` in every page write.
13. Create drafts by default.

Search Console and Google Analytics are optional read-only evidence sources. Use `/search-console/performance`, `/search-console/inspect`, `/search-console/sitemaps`, `/analytics/status` and `/analytics/report` when available. Never claim that Search Console or Analytics data guarantees ranking, indexing or conversions.

## Ranking evidence sequence

1. Read `/evidence` for the whole website or pass one `post_id` for a page-level report.
2. Treat `direct` evidence as confirmed observations from the crawler, WordPress or URL Inspection.
3. Treat `inferred` evidence as a hypothesis that requires search-intent, competitor or user-experience review.
4. Use fix priority only to order work; never describe it as a Google ranking score.
5. State limitations when Search Console, Analytics, backlink or competitor data is unavailable.
6. Do not run a rewrite merely because a page is short. Compare intent and coverage and avoid filler.
7. Save meaningful findings and next actions to Project History.

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
## Competitor and content research

Version 0.12.0 exposes `syncIkonSEOCompetitorContentIntelligence` in the focused workspace schema.

- Call with `{}` to read stored research, page briefs and the topic map.
- Add `research` to store short, current competitor observations.
- Add `analyse` to create one page-level brief.
- Research and analysis writes require the draft scope but never modify page content.
- The workflow must use current web research or a licensed provider; the plugin does not automate Google Search queries.
- Never copy competitor paragraphs or use competitor claims as facts about the connected business.


## Authority Intelligence

Version 0.13.0 exposes `syncIkonSEOAuthorityIntelligence` in the focused workspace schema. Call it with an empty object to read the imported off-site evidence report. Storing link observations requires draft scope. The focused schema omits Media Library search to remain within the 30-operation editor limit; the WordPress Media Library and full REST route remain available.


## Strategy and operating mode

Version 0.14.0 exposes `syncIkonSEOWebsiteStrategy`. Call it with `{ "save": false }` to read the active strategy. Save only an explicitly approved update with `{ "save": true, "strategy": { ... } }`. Strategy changes guide future audits and drafts but do not publish, redirect, delete or alter live content.


## Workflow Automation action

Version 0.15.0 exposes `syncIkonSEOWorkflowAutomation`.

Read current state:

```json
{"command":"read"}
```

Create the strategy-recommended workflow only after approval:

```json
{"command":"create_workflow","template":"local_growth","name":"Q3 SEO Growth Cycle"}
```

Run only due read-only tasks:

```json
{"command":"run_safe_tasks","limit":3}
```

The action never publishes, redirects, edits live pages, sends outreach or changes an external business profile.


## Publisher Intelligence action

Version 0.16.0 exposes `syncIkonSEOPublisherIntelligence` for approved opportunity, hub, pipeline, contributor, quality-gate and lifecycle operations.

Read current publisher state:

```json
{"command":"read","limit":100}
```

Save one researched opportunity only after review:

```json
{"command":"save_keyword","keyword":{"keyword":"example topic","intent":"informational","page_type":"article","demand_band":"medium","difficulty_band":"medium","business_value":70}}
```

Run a quality gate on a linked draft:

```json
{"command":"evaluate_post","post_id":123,"item_id":45}
```

A passed quality gate means ready for human review. The action never publishes, consolidates, retires or redirects content automatically. Search Console sitemaps remain available in WordPress and Technical Intelligence but are omitted from the focused 30-operation workspace schema.


## Local Growth System

Version 0.17.0 exposes `syncIkonSEOLocalGrowth` for reading local readiness, refreshing read-only review and conversion evidence, updating internal review workflow ownership and storing dated competitor-prominence observations. The action cannot publish pages, send replies or change public business information.


## Agency Command Centre action

Version 0.18.0 exposes `syncIkonSEOAgencyCommandCentre` for reading the portfolio, refreshing read-only snapshots, recording usage and resolving portfolio alerts. Website connections and key changes remain restricted to the WordPress Agency Command Centre screen.

Read portfolio state:

```json
{"command":"read","limit":100}
```

Refresh one connected website:

```json
{"command":"refresh_site","site_id":12}
```

The action does not publish, edit, redirect, merge, delete, send outreach, answer reviews or change public business information. Local Rank reading remains available in WordPress but is omitted from the focused 30-operation schema.


## Visibility and Brand Intelligence

Version 0.19.0 exposes `syncIkonSEOVisibilityBrandIntelligence` for reading combined organic, local, authority, mention and sampled citation evidence. It can store concise reviewed observations and brand mentions, update mention workflow state and refresh a combined snapshot.

The action does not scrape search engines, send outreach, create backlinks, publish content or post reputation responses. Use source URLs and concise evidence notes so every stored observation can be reviewed later.
