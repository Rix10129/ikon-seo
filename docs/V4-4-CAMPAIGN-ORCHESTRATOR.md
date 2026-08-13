# v4.4 Campaign Orchestrator

Status: implemented and passing the current local release gate against the exact recovered v4.3.0 Core development tree.

## Why this exists

The agency operator should not manually repeat the sequence of research setup, blueprint creation, page selection, batch creation, draft payload storage, dispatch and QA for every page.

The Campaign Orchestrator is a thin coordinator over the **existing v4 Production Core**. It does not replace Production Core and it does not generate content inside WordPress.

Its job is to turn one researched architecture into one resumable coordinated campaign.

## Normal flow

`prepare -> approve_blueprint -> build -> dispatch -> review_exceptions`

A connected private assistant can provide the research/architecture and generated draft payloads. WordPress remains the source of truth for:

- website/profile identity
- blueprint IDs
- page ownership
- target resolution
- batch IDs/items
- architecture approval
- unpublished draft dispatch
- replacement review queues
- Launch Readiness

## Commands

### `prepare`

Requires:

- stable `campaign_key`
- researched page architecture
- selected site ID where required

Creates one Growth Blueprint through Production Core.

The campaign stores only IDs, fingerprints and workflow state. Repeating the same campaign key with the same plan reuses the same Blueprint rather than creating another one.

If the researched plan materially changes, a replacement Blueprint is prepared and the prior Blueprint ID remains traceable in campaign state.

### `approve_blueprint`

Requires the `approve` connection-key scope.

Approves the architecture once through Production Core. This deliberately does **not** depend on the legacy Remote merge setting because architecture approval is not a live-page merge.

No public page changes.

### `build`

Requires an approved Blueprint.

- creates one Production Core batch if needed
- otherwise resumes the existing batch
- accepts explicit page IDs / target resolutions
- stores generated draft payloads through Production Core
- reuses identical already-stored content payloads instead of restorage
- preserves a content-ready batch state when the campaign resumes
- returns Launch Readiness and the next action

If no generated payload exists, the orchestrator does not invent one.

### `build_and_dispatch`

Builds and then dispatches only when the batch actually contains dispatchable generated content.

A previously prepared content-ready batch may be resumed and dispatched without restating all payloads.

### `dispatch`

Delegates to Production Core `dispatch_batch()`.

Production Core uses the existing Site Agent draft endpoints, including idempotent remote draft keys and replacement-review proposals.

This step may create **unpublished WordPress drafts** or **local review proposals**. It does not publish a page.

### `read` / `status`

Returns resumable campaign state, Blueprint/Batch status and exception-first Launch Readiness without exposing stored page bodies.

## Operator surface

The orchestrator is embedded at the top of the existing **Agency Hub -> Production** tab.

It does not add another normal top-level menu.

The production screen describes the intended path:

`research/prepare once -> approve architecture once -> build/store page payloads in one batch -> dispatch unpublished drafts -> review exceptions`

## REST

New route:

`/campaign-orchestrator`

GET:

- read one campaign or recent campaigns
- `read` scope

POST scope rules:

- `read`, `status` -> `read`
- `approve_blueprint` -> `approve`
- `prepare`, `build`, `dispatch`, `build_and_dispatch` -> `draft`

The specialized architecture-approval scope deliberately does not reuse `can_approve()`, because that older method also checks the Remote merge switch intended for live replacement workflows.

## Safety contract

The orchestrator declares and tests:

- automatic publish: false
- automatic live-page update: false
- automatic delete: false
- automatic redirect: false
- automatic indexing change: false
- architecture approval required: true
- existing-page review required: true
- unpublished draft dispatch allowed: true

The class contains no direct `wp_insert_post`, `wp_update_post`, `wp_delete_post`, `wp_trash_post`, redirect or indexing mutation primitive.

Existing-page changes remain governed by Controlled Existing Page Updates / local replacement review.

## Resumability and idempotency

Two layers are used:

1. Stable `campaign_key` + plan/content fingerprints inside the coordinator.
2. Existing REST idempotency plus Production Core's idempotent per-batch/per-item remote dispatch keys.

This prevents routine retries from recreating Blueprints, batches or identical content payloads.

## Validation

New tests:

- `v440-campaign-orchestrator-runtime-test.php`
- `v440-campaign-orchestrator-static-test.php`

They verify:

- idempotent prepare
- architecture approval requirement
- explicit page selection
- one-batch creation/reuse
- identical content payload reuse
- content-ready state preservation across resume
- no fabrication during build-and-dispatch
- draft-only dispatch path
- changed-plan traceability
- REST/auth/OpenAPI integration
- no prohibited public-write primitives

Current local release gate after integration:

**28 / 28 blocking tests passed.**

All **193 PHP files** in the development tree pass syntax validation.

## Operator impact

Once the full v4.4 milestone is packaged and deployed, the goal is to replace repeated page-by-page production setup with one campaign operation. Human attention moves to architecture approval and exceptions rather than every successful page action.
