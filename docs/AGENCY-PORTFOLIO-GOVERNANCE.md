# Agency Portfolio Synchronisation & Governance

Ikon SEO v1.14.0 adds an approval-first governance layer for agencies managing multiple connected WordPress websites.

## Purpose

The governance layer lets an agency define a consistent operating policy once, assign it to managed websites and verify whether each website follows the required safety and review controls.

It does not provide remote administrative control over live content. Policy delivery is proposal-only, and activation always belongs to a local WordPress administrator on the managed website.

## Governance workflow

```text
Create versioned policy
→ Approve policy centrally
→ Configure a proposal-only key on the managed website
→ Assign the approved policy to that website
→ Deliver a signed policy proposal
→ Store it in the local approval inbox
→ Local administrator accepts or rejects it
→ Apply bounded workflow limits
→ Monitor compliance and sync health
```

## Policy rules

A policy can define:

- Minimum Website Strategy readiness from 70 to 100.
- Maximum Guided Launch safe-task batch from one to five.
- Required Fact Review.
- Required Guided Launch.
- Required content-brief approval.
- Required Editorial Review.
- Required Publishing Readiness preflight.
- Optional Search Impact study requirement.
- Data-retention guidance from 90 to 1,095 days.
- Permitted evidence-source categories.

The following safety rules are permanently locked:

```text
manual_publish_only = true
pattern_use = advisory_only
portfolio_evidence = anonymised_only
external_live_writes = disabled
```

A policy payload cannot weaken these locked rules.

## Versioning

Policies use a stable policy key and an integer version. A new policy version creates a separate immutable proposal fingerprint. An older accepted policy remains active until a local administrator accepts a newer proposal.

Retiring a central policy stops its assignments from being treated as active proposals. It does not silently remove a policy already accepted on a managed website.

## Proposal-only connection

Each managed website generates a dedicated governance proposal key. This credential is separate from the normal Ikon SEO workspace key.

The proposal endpoint is:

```text
POST /wp-json/ikon-seo/v1/agency-governance-agent
```

The endpoint can only:

- Return proposal-connection status.
- Receive an integrity-checked policy proposal.
- Return a proposal receipt.

It cannot activate a policy, publish content, change live pages, create redirects, update external profiles or perform outreach.

The key is revocable, stored as a password hash on the managed website, rate limited and bounded by payload size.

## Local approval inbox

A received proposal is stored as:

```text
pending_local_approval
```

A local WordPress administrator must choose one of the following:

- Accept and activate the proposal.
- Reject it with a required explanation.

Only one accepted policy is active at a time. Accepting a newer proposal supersedes the previous local acceptance record while retaining the audit trail.

The active policy stores the source fingerprint, policy key, version, policy fingerprint, accepting administrator and acceptance date. It explicitly records that it does not publish automatically.

## Compliance reporting

The compliance report checks the active website against the policy and built-in safeguards, including:

- Draft-only content workflow.
- Direct live updates disabled.
- Manual publishing boundary.
- Fact Review requirement.
- Brief approval requirement.
- Editorial Review requirement.
- Publishing preflight requirement.
- Advisory-only Pattern Library use.
- Anonymised portfolio evidence.
- Disabled external live writes.

The report provides a score, blocking count and effective Guided Launch limits. It is an operational compliance report, not a Google ranking score or a legal certification.

## Guided Launch enforcement

When a local policy is active:

- The policy can raise the minimum strategy-readiness requirement above the normal 70/100 threshold.
- The policy can reduce the maximum number of safe audit tasks launched in one batch.
- It cannot lower the built-in readiness floor or raise the batch beyond five tasks.

## Central workspace

The approval-controlled workspace endpoint is:

```text
GET  /wp-json/ikon-seo/v1/portfolio-governance
POST /wp-json/ikon-seo/v1/portfolio-governance
```

Supported commands are:

```text
read
create_policy
approve_policy
retire_policy
save_site_key
assign_policy
sync_assignment
accept_proposal
reject_proposal
```

Central policy approval, retirement and local proposal decisions require the approval scope. Preparation and synchronisation actions use the draft scope.

## Synchronisation boundaries

Synchronisation is bounded to ten assignments per scheduled run. Safe HTTP requests are used, redirects are limited, fingerprints are verified and errors are stored per assignment and managed website.

A successful remote receipt means only that the proposal reached the local inbox. It does not mean the policy was accepted.

## Audit trail

Governance events record:

- Proposal-key generation and revocation.
- Policy creation, approval and retirement.
- Managed-site key configuration.
- Policy assignment.
- Synchronisation success or failure.
- Proposal receipt.
- Local acceptance or rejection.

## Safety boundary

Portfolio Governance cannot:

- Publish, schedule or merge content.
- Edit or replace a live page.
- Delete, redirect or noindex content.
- Change canonical settings.
- Submit URLs for indexing.
- Update Google Business Profile.
- Answer reviews.
- Perform outreach or backlink actions.
- Remotely activate a policy without local approval.
