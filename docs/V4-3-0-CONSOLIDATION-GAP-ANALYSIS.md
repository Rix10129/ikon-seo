# v4.3.0 Consolidation Gap Analysis

Purpose: avoid rebuilding capabilities that already exist in Core, and focus engineering only on the gaps that reduce operator time.

## Executive conclusion

The recovered v4.3.0 platform already contains most of the difficult safety and production primitives needed for an agency-scale system.

The main problem is **orchestration and operator surface fragmentation**, not absence of intelligence.

The correct approach is therefore consolidation, aggregation and central control — not another set of standalone plugins.

---

## 1. Project Brain / fact verification

### Already present

`Ikon_SEO_Discovery_Review` already implements a durable fact-review model with states:

- `detected`
- `confirmed`
- `edited`
- `rejected`
- `conflict`
- `needs_confirmation`
- `outdated`

It also supports:

- stale-report protection
- optimistic locking
- source fingerprints
- conflict resolution
- bulk confirmation of high-confidence non-sensitive facts
- preservation of previously confirmed values when new evidence changes
- controlled application of confirmed facts to profile/strategy

### Implication

The standalone Intelligence & Integrity plugin should **not** become a second Project Brain fact database.

Its useful fact-state concepts should be mapped into Discovery Review / Website Profile instead.

### Gap

The Core needs a concise portfolio-facing fact summary and clearer separation between:

- safe technical facts,
- identity-sensitive business facts,
- legal/contact/location facts,
- stale facts requiring re-confirmation.

---

## 2. Public-surface integrity

### Already present

Core already detects placeholders in several controlled-production paths:

- Recovery Section Classifier
- Recovery Quality Gates
- Publishing Readiness
- Renderer/contact guardrails

Core also blocks placeholder contact values and validates direct-contact CTA routes in the adaptive renderer.

### Missing / fragmented

There is no one reusable root-cause public-surface integrity service that scans existing published pages for issues such as:

- default WordPress sample content
- demo/template text
- public internal roadmap/system notes
- unsupported ranking-guarantee language
- client-facing copy accidentally containing operational instructions
- cross-page duplication of the same generated internal message

### Consolidation action

Move the useful scanner logic from the Intelligence & Integrity prototype into one **internal Core integrity service** and let other components call it.

Do not expose it as another separately installed plugin.

---

## 3. Launch Readiness

### Already present

`Ikon_SEO_Publishing_Readiness` is substantial. It already checks a controlled release for:

- final editorial snapshot consistency
- unpublished state
- title and slug
- content depth
- placeholders
- SEO title
- meta description
- noindex
- canonical host
- internal links
- conversion action
- featured media
- original source page existence for revision workflows

Post-launch it checks:

- HTTP status
- indexability
- rendered canonical
- rendered title/description
- H1 count
- structured data
- measurement code
- conversion action

It stores immutable release/check/snapshot/event records and monitors launch checkpoints.

### Gap

This is primarily **release/page-oriented**, while the agency operator needs a **campaign/batch/site-level exception view**.

It also does not currently aggregate all relevant signals from:

- Discovery Review facts
- public-surface integrity
- Production Core page ownership
- controlled-update staleness
- portfolio-quality findings
- visual/template certification
- legal-policy hold state

### Consolidation action

Do not replace Publishing Readiness. Add a campaign-level **Launch Readiness Aggregator** that consumes existing subsystem reports and normalizes them to:

- ready
- review
- blocked
- held

Primary UX target:

`26 processed | 22 ready | 3 review | 1 blocked`

Passing details remain auditable but collapsed by default.

---

## 4. Agency Hub / Site Agent

### Already present

`Ikon_SEO_Production_Core` already has explicit roles:

- Agency Hub
- Site Agent

Agency Hub already supports:

- connected sites
- Growth Blueprints
- production batches
- batch content storage
- remote draft dispatch
- review queue
- reports
- template intelligence
- assistant connection

Site Agent already supports:

- overview
- managed drafts
- controlled updates
- local review
- templates
- corrections
- results
- connection/settings

The UI copy already describes the intended operator model: one connected assistant, central blueprint/batch production, and unpublished drafts.

### Gap

The current operator experience still exposes too many adjacent modules and does not provide one central **portfolio priority / next action / exception queue**.

### Consolidation action

Keep the existing roles and underlying tables. Simplify the operator surface around:

1. Sites
2. Opportunities / Architecture
3. Campaigns
4. Exceptions / Review
5. Results
6. System / Updates

Advanced legacy modules can remain available under Advanced Tools.

---

## 5. Batch-first production

### Already present

Production Core already supports:

- site-scoped Growth Blueprints
- approved blueprint pages
- explicit batch creation
- up to 100 approved blueprint pages in a batch
- stored briefs/draft payloads
- new-draft vs replacement-target resolution
- Hub-to-Agent draft dispatch
- replacement review queue
- idempotency
- exact batch-item identification

### Gap

The workflow still assumes too much manual handoff between research, payload storage, dispatch and review.

### Consolidation action

Build one orchestrated campaign command that can:

1. read the current Website Profile and inventory,
2. load/update researched architecture,
3. create/update the batch,
4. store generated payloads,
5. dispatch eligible new drafts,
6. queue replacement drafts for review,
7. run consolidated Launch Readiness,
8. return exceptions only.

Publication remains human/local.

---

## 6. Existing-page updates

### Already present

v4.3.0 contains Controlled Existing Page Updates and its current runtime/static tests pass.

The platform also has older recovery/snapshot/diff/rollback components.

### Consolidation action

Use the v4.3 controlled-update path as the default IMPROVE/REPLACE workflow. Do not build site-specific rollout plugins for commercial pages.

The agency system should only require operator attention when:

- the source changed after snapshot,
- a merge/ownership decision is ambiguous,
- the review draft is blocked,
- the final local apply requires approval.

---

## 7. Central updates

### Already present

`Ikon_SEO_Deployment_Control` already has important release-management primitives:

- signed entitlement validation
- signed release-envelope validation
- release catalog
- deployment plans
- readiness gates
- manual deployment recording
- post-deployment verification
- environment/channel logic
- recovery/archive awareness

Current safeguards explicitly state:

- automatic plugin updates: false
- remote package download: false
- filesystem installation: false
- automatic rollback: false
- manual WordPress update required: true

### Gap

This is exactly the source of the operator pain seen during the IkonDigitals pilot: every release still requires visiting WordPress and replacing a ZIP manually.

### Consolidation action

**Extend Deployment Control**, do not create a new updater.

A later v4.4 milestone should add a safe managed-update transport with:

- signed release metadata/package verification
- Hub/Agent compatibility matrix
- preflight
- local backup/recovery reference
- controlled filesystem install/update
- migration probe
- post-update verification
- automatic rollback only if it can be proven safe and bounded; otherwise guided local rollback
- agency-wide version dashboard
- staged rollout cohorts

No update should change live site content.

---

## 8. Pattern Library / portfolio intelligence

### Already present

Pattern Library and Portfolio Governance already include privacy, threshold and cross-site controls. Existing tests for Pattern Library privacy/threshold behavior and Portfolio Governance isolation pass in the recovered package.

### Consolidation action

Use these existing modules as the portfolio-learning substrate rather than inventing a new cross-client memory store.

The public operator experience should expose validated pattern recommendations only after sufficient evidence, while raw client data remains isolated.

---

## 9. Test/release management

### Already present

The recovered package contains 101 PHP tests plus fixture documents spanning many historical releases.

### Gap

Historical version-pinned tests are mixed with current release-gate tests, so running every test file produces many expected failures after later versions advance.

### Consolidation action

Add a release test manifest/runner that classifies:

- current blocking release tests
- current non-blocking regression tests
- historical frozen assertions

The central updater must use the current blocking set only when deciding release eligibility.

---

# v4.4 engineering priorities

## Milestone A — Recovery and source control

- preserve exact v4.3.0 package
- import extracted source as canonical baseline
- add a current release-test manifest

## Milestone B — Core integrity consolidation

- internal public-surface integrity service
- reuse Discovery Review fact state instead of separate fact storage
- root-cause finding deduplication
- site/campaign summary endpoint

## Milestone C — One Launch Readiness experience

- aggregate facts + integrity + ownership + controlled-update status + publishing checks + visual/runtime checks
- ready/review/blocked/held normalization
- exception-first UI

## Milestone D — One orchestrated campaign command

- architecture -> batch -> payloads -> dispatch/review -> readiness
- idempotent/resumable
- no publish

## Milestone E — Managed updates

- extend Deployment Control with signed package transport and staged update flow
- eliminate repeated ZIP upload in routine agency work

## Milestone F — Universal proof

- IkonDigitals end-to-end
- one materially different client end-to-end
- no site-specific rollout plugin or code change

# Decision

The last two weeks did not prove that the overall project was useless. They exposed that we were working **around** the mature v4 Core instead of consolidating **inside** it.

The recovered source shows that most hard primitives already exist. The productivity gain now comes from reducing operator surfaces and connecting those primitives into one resumable agency workflow.
