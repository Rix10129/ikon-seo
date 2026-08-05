# Fact-Level Strategy Review

Ikon SEO v1.7.1 adds a durable approval layer between Auto Discovery and Guided Launch.

## Decision states

Each detected fact is stored with one of these states:

- `detected` — technical or public evidence was found, but no explicit business decision was recorded.
- `confirmed` — the suggested value was explicitly accepted.
- `edited` — the user supplied a corrected value.
- `rejected` — the suggestion must not be used.
- `conflict` — related evidence contains multiple incompatible values.
- `needs_confirmation` — the website alone cannot establish the business decision safely.
- `outdated` — a later scan changed or removed evidence that had previously been confirmed.

Confirmed and edited values are stored separately from the latest detected value. A rescan therefore cannot silently replace an earlier business decision.

## Rescan comparison

After every Auto Discovery run the review layer records:

- new facts;
- changed facts;
- unchanged facts;
- outdated or removed facts.

When evidence changes, the prior approved value is retained and the fact becomes `outdated` until it is reviewed again.

## Conflict resolution

Phone, email, language and currency conflicts can be resolved by:

- choosing one detected value;
- entering the correct value;
- marking multiple values as valid.

Conflict decisions are tied to the current discovery evidence. New or changed conflict evidence must be reviewed again.

## Workspace endpoints

Authenticated private workspaces can use:

- `GET /wp-json/ikon-seo/v1/discovery-review`
- `POST /wp-json/ikon-seo/v1/discovery-review`
- `GET /wp-json/ikon-seo/v1/guided-launch`
- `POST /wp-json/ikon-seo/v1/guided-launch`

Supported review commands:

- `read`
- `accept_high_confidence`
- `update_fact`
- `resolve_conflict`
- `apply_confirmed`

Write requests use the existing revocable connection key, scope checks, payload limits and hourly rate limits. `generated_at` acts as an optimistic lock, and `X-Idempotency-Key` prevents duplicate write processing.

## Safety boundary

Fact review and Guided Launch do not publish content, change live pages, create redirects, modify canonicals or indexation settings, update public profiles, send review responses, perform outreach or create backlinks.
