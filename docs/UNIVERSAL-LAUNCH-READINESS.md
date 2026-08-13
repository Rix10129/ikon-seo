# Universal Launch Readiness

Goal: replace scattered page/plugin checks with one campaign-level readiness result.

## States

- `ready` — no blocking issue; campaign item may proceed to human approval.
- `review` — human judgment required but no automatic destructive action is authorised.
- `blocked` — unsafe or materially incomplete; do not apply/publish.
- `held` — intentionally excluded from normal production, for example legal content awaiting legal QA.

## Check families

### Identity and facts

- business/profile match
- verified vs unreviewed business facts
- stale facts
- contact/CTA destination validation
- fake office/location protection

### Architecture and search intent

- primary intent ownership
- duplicate/cannibalising keyword ownership
- URL/title/slug conflict
- page-role classification
- CREATE/IMPROVE/KEEP/MERGE/HUB/CLEANUP action consistency

### Public-surface integrity

- placeholder/demo/default WordPress content
- internal notes/roadmap/future-system language
- lorem/sample/test content
- fabricated or unsupported claims
- unapproved proof/evidence

### Content and conversion

- visible H1 uniqueness
- intent/content alignment
- CTA presence and verified destinations
- required service/product/location detail
- customer-facing language rather than system/debug language

### Internal links

- destination exists
- destination is published when required
- anchor/destination intent compatibility
- orphaned commercial pages
- circular or duplicate generated link blocks

### Metadata and schema

- title/meta readiness
- canonical consistency
- robots/indexability conflicts
- Rank Math/Yoast schema collision
- schema-visible content alignment
- entity/location schema evidence requirements

### Existing-page update safety

- immutable source snapshot exists
- source has not materially changed since snapshot
- review draft is separate
- diff available
- rollback snapshot available
- local human approval required

### Visual/runtime readiness

- renderer completed
- builder payload valid
- desktop/tablet/mobile checks
- overflow/wrapping/layout distortion
- missing media/fallback handling
- header/footer/global-kit isolation

### Evidence freshness

- research source date
- stale SERP/competitor evidence
- performance evidence freshness
- source attribution retained

### Legal-policy hold

Legal pages can be `held` even when technically renderable. The content engine must not fabricate legal wording to clear the gate.

## Campaign output

The primary operator view should summarize instead of exposing every passing check.

Example:

`26 processed | 22 ready | 3 review | 1 blocked`

Clicking a non-ready count opens only the exceptions with:

- page
- severity/state
- root cause
- evidence/source
- recommended action
- whether the system can repair safely in bulk
- whether human confirmation is required

## Bulk repair policy

The system may auto-fix only deterministic, reversible and site-scoped issues where the source of truth is clear.

Examples that can qualify after implementation/testing:

- remove exact default WordPress sample content
- repair a known generated internal roadmap sentence
- normalize duplicate generated prefixes
- re-run metadata from an approved batch payload
- update a review draft from an approved renderer payload

Examples that remain human-controlled:

- legal wording
- business/legal identity
- address/office claims
- destructive redirects/deletions/noindex
- merge decisions between competing live pages
- unverified pricing/review/credential claims
- publication/live apply

## Exception-first UX

The dashboard should not force the operator to open every page.

Default campaign screen:

1. readiness summary
2. blockers
3. review-needed exceptions
4. changes that can be bulk repaired
5. final human approval queue

Passing details remain available for audit/history but collapsed by default.
