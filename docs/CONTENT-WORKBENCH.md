# Content Workbench

Ikon SEO v1.9.0 adds an approval-first bridge from the Opportunity Engine to Publisher Intelligence and WordPress drafts.

## Workflow

```text
Planned opportunity
→ Proposed evidence-locked brief
→ Human brief approval
→ Separate unpublished scaffold or controlled draft
→ Publisher Intelligence quality gate
→ Ready for human review
→ Separate WordPress publishing decision
```

## Brief contents

Each brief records the source opportunity, evidence hash, version, target query, intent, page type, audience, confirmed offerings, section plan, internal-link candidates, conversion requirements, trust and source requirements, unsupported claims, differentiation requirements and known hypotheses.

## Safety gates

- Only an Opportunity Engine item in `planned` state may create a brief.
- Technical, authority, indexation and PageSpeed work cannot be routed into content drafting.
- A current evidence hash is required for approval and draft creation.
- A source-opportunity change marks the brief outdated.
- Stale briefs cannot be evaluated or marked ready.
- One active controlled draft is allowed per approved brief.
- Existing pages receive separate revision drafts; the live page is not edited.
- All generated posts use WordPress `draft` status.
- Publishing, redirects, deletion, canonicals and noindex changes are outside this workflow.

## Workspace operations

`POST /wp-json/ikon-seo/v1/content-workbench` supports:

- `read`
- `create_brief`
- `approve_brief`
- `reject_brief`
- `create_scaffold`
- `submit_draft`
- `evaluate_draft`
- `mark_ready`

Write operations require the existing revocable connection key, the draft scope, rate limits and idempotency controls.

## Controlled draft payload

A submitted draft is limited to 512 KB, 40 sections and 30 FAQs. The renderer creates Gutenberg-compatible content and optional Elementor data, but the post remains unpublished.
