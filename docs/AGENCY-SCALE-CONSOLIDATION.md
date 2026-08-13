# Ikon SEO — Agency-Scale Consolidation Architecture

Status: architecture lock for the next engineering phase

## Problem being solved

The pilot workflow exposed too much operator friction: repeated ZIP upgrades, companion plugins, page-specific rollout plugins, page-by-page QA, repeated site setup and duplicate intelligence capture. That workflow does not scale to an agency portfolio.

The target is not more automation screens. The target is a smaller operator surface with stronger internal orchestration.

## Product architecture

### 1. One Ikon SEO Core

Ikon SEO Core is the normal installable product. The same core can operate as:

- **Agency Hub** — portfolio intelligence, research packets, architecture, batch planning, campaign orchestration, consolidated QA, release governance and cross-site learning.
- **Site Agent** — website inventory, Website Profile, verified facts, local rendering, builder/SEO-plugin integration, review drafts, snapshot/diff/apply/rollback and runtime checks.

Companion rollout plugins are migration artifacts or site-specific extensions, not the normal production workflow.

### 2. Intelligence & Integrity becomes a Core module

The useful parts of the prototype are merged behind Project Brain / Launch Guard:

- fact state: verified / reviewed / unknown / stale
- source attribution
- no-fabrication controls
- public-surface placeholder/internal-note detection
- duplicate-intent and ownership checks
- stale-source protection
- consolidated launch readiness

No separately installed Integrity plugin should be required in the final architecture.

### 3. Batch-first production

Normal workflow:

1. Connect site once.
2. Scan site inventory and runtime environment.
3. Build/update Website Profile.
4. Research market, SERPs, competitors, keyword/page ownership and content gaps.
5. Produce one site architecture with actions such as CREATE, IMPROVE, KEEP, MERGE, HUB IMPROVEMENT, INTERNAL-LINK ONLY, CLEANUP REVIEW or IGNORE.
6. Generate one coordinated campaign.
7. Build/update all campaign drafts in bulk.
8. Run one consolidated QA gate.
9. Show operator exceptions only.
10. Use local human approval for live-page replacement.

The operator should not repeat setup for every page.

## Operator experience target

### New client

Install once -> connect once -> confirm non-inferable business facts -> scan -> review architecture -> build batch -> review exceptions -> approve.

### Existing client

Agency Hub -> priority queue -> recommended campaign -> build/update drafts -> consolidated QA -> exception review -> controlled apply.

The normal experience should be portfolio-first, not plugin-maintenance-first.

## Research layer

Research is website/market scoped, not page isolated. A research packet should contain:

- current site inventory and existing page ownership
- commercial services/products and target locations
- SERP intent and competitor page patterns
- keyword clusters and cannibalisation risk
- existing-page performance/evidence where available
- content and architecture gaps
- local/GBP opportunities where relevant
- source freshness and confidence
- recommended page action and rationale

Research can be performed by the connected private assistant. No paid model/API key is required inside each client WordPress installation. If unattended model execution is added later, it should be an agency-level service rather than one billing/key setup per client site.

## Website Profile and facts

Facts are divided into two classes.

### Safe technical facts

Examples: canonical home URL, WordPress locale, active builder, SEO plugin, published URL inventory, post status and runtime capabilities. These can be derived automatically when confidence is high.

### Sensitive business facts

Examples: public brand name, phone, WhatsApp, address, legal entity, jurisdiction, price claims, credentials, guarantees and office/location assertions. These require confirmation or trusted evidence.

Unknown is a valid state. The system must not invent a value to make a profile look complete.

Once confirmed, a business fact is reused across research, drafts, CTAs, schema and QA until changed or marked stale.

## Controlled existing-page updates

Established pages are never blindly overwritten in the normal workflow.

Required path:

1. Analyze and snapshot source.
2. Create a separate review draft.
3. Compare content, metadata, internal links, schema and design payload.
4. Block if source changed after snapshot.
5. Human review.
6. Controlled local apply.
7. Preserve rollback snapshot/history.

No remote auto-publish, delete, redirect, noindex or live merge by default.

## Consolidated Launch Readiness gate

One campaign-level gate should combine:

- placeholder/demo/default WordPress content
- internal roadmap/admin notes exposed publicly
- duplicate primary intent / cannibalisation
- missing or conflicting page ownership
- metadata readiness
- internal-link destination validity
- schema collision/visible-content alignment
- proof/claim permission and source context
- contact/CTA validity
- stale research/evidence
- responsive/visual readiness
- builder/rendering compatibility
- legal-policy hold states

Output should be exception-oriented, for example:

`26 processed | 22 ready | 3 review | 1 blocked`

The operator reviews the exceptions, not every successful check.

## Central update and compatibility management

The finished system needs a controlled update channel so operators do not repeatedly upload ZIP files.

Required capabilities:

- signed release metadata
- Core version compatibility matrix
- Hub/Site Agent compatibility status
- safe staged update flow
- database migration preflight
- rollback package/version reference
- release notes and blocker visibility
- ability to hold one client on an older compatible agent temporarily

No automatic update should bypass compatibility or backup safeguards.

## Portfolio intelligence

Cross-site learning is advisory and privacy-safe.

A validated pattern may learn from anonymised outcomes such as:

- page role
- market/industry fingerprint
- layout family
- content module combination
- intent class
- measurable search/conversion outcome

It must not copy client content, URLs, contact data, credentials, private analytics or unverified claims between websites.

A single site's result must not become a universal rule.

## Universal acceptance test

The architecture is not considered agency-ready until the same Core package can complete the workflow on two materially different websites without code changes or site-specific rollout plugins.

Pilot sequence:

1. IkonDigitals — agency/SEO service website.
2. A materially different client website — different industry, page architecture and conversion model.

Acceptance means the system adapts through Website Profile, inventory, research, templates/patterns and governance rather than hardcoded site logic.

## Engineering order

1. Recover exact v4.3.0 Core source as canonical source of truth.
2. Preserve current live/recovered package and database expectations.
3. Merge Intelligence & Integrity logic into Core.
4. Build consolidated Launch Readiness.
5. Build batch-wide controlled cleanup/existing-page campaign support.
6. Add central version/update compatibility management.
7. Run IkonDigitals end-to-end.
8. Run one different client end-to-end without code changes.
9. Only then resume feature expansion.

## Non-goals

- no default remote auto-publish
- no automatic deletion
- no automatic redirect/noindex
- no invented business/legal/contact facts
- no fake locations/offices
- no rank guarantees
- no site-specific hardcoded templates presented as universal intelligence
- no paid model/API key required per client WordPress site
