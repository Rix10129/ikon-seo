# IkonDigitals Legacy Helper Retirement Plan

Purpose: reduce the live production stack after the consolidated Core milestone without deleting rollback/recovery history.

No plugin in this document should be auto-deactivated or deleted. Retirement happens only after the equivalent v4.4 Core capability is installed and verified.

## Canonical Core

### Ikon SEO 4.3.0

Status: **KEEP / RECOVERY BASELINE**

Validated uploaded package SHA-256:

`ed972ca478d8d34a30dd7a784aa6855a111496a95adab60cbdb38a626caf2a56`

Multiple recovered copies (`ikon-seo.zip`, `ikon-seo(1).zip`) hash to the same package and are duplicates of the same canonical candidate.

## Migrate into Core, then retire from normal operation

### Ikon SEO Production Orchestrator (through 0.6.2)

Status: **MIGRATE -> CORE CAMPAIGN ORCHESTRATOR -> RETIRE**

Reason: campaign/batch coordination now belongs inside v4 Core over Growth Blueprints, Production Core, Controlled Existing Page Updates and Launch Readiness. A normal future site should not need a second orchestrator plugin.

### Intelligence & Integrity (through 0.2.4)

Status: **MIGRATE -> CORE INTEGRITY/LAUNCH READINESS -> RETIRE**

Reason: useful public-surface scanner/root-cause QA behavior is being merged into Core. Discovery Review remains the fact source of truth; there should not be a second Project Brain fact database.

### SEO Services Dubai rollout 1.1.0
### Local SEO Dubai rollout 1.1.0
### Google Maps SEO Dubai rollout 1.0.0-1.4.0
### Google Business Profile Optimization Dubai rollout 1.0.0
### Technical SEO Dubai rollout 1.0.0-1.0.1

Status: **ARCHIVE / RECOVERY ONLY AFTER CORE VALIDATION**

Reason: these are page/site-specific rollout helpers created during the pilot. Their normal function should be replaced by Website Profile + researched architecture + Campaign Orchestrator + Controlled Existing Page Updates. They remain useful only as historical/recovery evidence until the resulting pages and rollback history are verified under v4.4.

## Retain as optional until separate migration is proven

### Ikon SEO Growth Tools 0.9.0

Status: **RETAIN TEMPORARILY / OPTIONAL PROOF LAYER**

Reason: this companion contains Proof Library/V7 proof preparation, permission-state handling, Results/Case Study presentation and proof rollout behavior. Do not retire it merely because production orchestration moved into Core. First prove that approved/permissioned proof records, case-study drafts and existing V7 evidence output remain intact under the consolidated system.

Long-term decision: either migrate durable proof/provenance behavior into Core/optional evidence module, or keep Growth Tools as a genuinely optional domain-specific extension. It must not become required for ordinary SEO production on every client.

## Theme archives

Ikon Digitals V7 theme packages are not Core plugins. Keep the current approved theme and archive older versions for rollback; do not treat theme version history as a production-module dependency.

## Retirement acceptance

Before deactivating a helper on IkonDigitals:

1. Export/store Project Brain and relevant recovery snapshot.
2. Confirm v4.4 Core owns the equivalent workflow.
3. Confirm existing pages/drafts remain editable and unchanged publicly.
4. Confirm Launch Readiness and Controlled Existing Page Updates still work.
5. Confirm proof permission/output where Growth Tools is involved.
6. Deactivate one helper at a time on staging or a controlled window.
7. Re-run public/rendered integrity and conversion/contact checks.
8. Only then remove the helper from normal operation; preserve its ZIP/checksum offline.

## Universal rule

No new commercial page or client website should require a site-specific rollout plugin. If the Core cannot express the requirement through Website Profile, architecture, campaign payload, renderer/pattern intelligence or controlled update governance, treat that as a Core capability gap to solve generically.
