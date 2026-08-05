# Editorial Review, Collaboration & Revision Control

Ikon SEO v1.10.0 adds a human editorial governance layer around v1.9.0 controlled drafts.

## Workflow

```text
Controlled unpublished draft
→ Writer and reviewer assignment
→ Review snapshot
→ Source and claim verification
→ Structured comments or revision request
→ New revision round and version comparison
→ Editorial round approval
→ Publisher Intelligence readiness
→ Final human sign-off
→ Separate WordPress publishing decision
```

## Collaboration records

Each editorial review stores:

- Writer and reviewer assignments.
- Writing and review due dates.
- Current review round and workflow state.
- Structured comments with anchors, assignees and resolution records.
- Source, claim and quality verification checklists.
- Immutable draft snapshots for each review request or revision submission.
- Version comparison summaries.
- Blocked reasons, overdue status and decision history.
- Final human sign-off details.

## Approval gates

Editorial approval is blocked when:

- Opportunity evidence or the approved content brief is stale.
- The draft changed after the latest review snapshot.
- Required source or claim checks remain pending.
- A verification check failed.
- Editorial comments remain open.
- The current user is not the assigned reviewer or an administrator.

Final sign-off additionally requires the Content Workbench draft to pass Publisher Intelligence and be marked `ready`.

## Publishing boundary

Final sign-off updates internal workflow metadata only. It does not publish, schedule, replace or merge a WordPress post. The controlled item remains a WordPress draft until a separate authorised publishing decision occurs.

## Workspace operations

`POST /wp-json/ikon-seo/v1/editorial-review` supports:

- `read`
- `start_review`
- `assign`
- `request_review`
- `add_comment`
- `resolve_comment`
- `update_check`
- `request_changes`
- `submit_revision`
- `approve_round`
- `sign_off`
- `block`
- `unblock`
- `compare`

The route uses the existing revocable connection key, read/draft scopes, payload controls, rate limits, idempotency protection, Project History and activity logs.
