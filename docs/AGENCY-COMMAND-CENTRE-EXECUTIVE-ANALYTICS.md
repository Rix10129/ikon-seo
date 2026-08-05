# Agency Command Centre & Executive Portfolio Analytics

Ikon SEO v1.16.0 consolidates the existing approval-first workflows into one agency operations view. It extends the earlier read-only Agency Command Centre rather than creating a second portfolio system.

## Workflow

```text
Managed-site snapshots
→ Normalised portfolio operations
→ Transparent health components
→ Unified approval inbox
→ Persistent risk register
→ Capacity forecast
→ Internal notifications
→ Executive operational analytics
```

## Portfolio scorecards

Each managed website receives a transparent operational health score out of 100. The visible components are:

- Strategy readiness: 20 points.
- Evidence freshness and connected sources: 15 points.
- Technical blockers and failed URLs: 15 points.
- Workflow timeliness and unresolved workflow risks: 15 points.
- Publishing readiness and verification issues: 10 points.
- Search Impact measurement coverage: 10 points.
- Service-level compliance: 10 points.
- Connection health: 5 points.

This is not a Google ranking score and does not predict traffic, leads or revenue. Every deduction is derived from stored evidence and shown in the website scorecard.

## Unified approval inbox

The command centre normalises local approval requirements reported by managed websites, including:

- Fact Review decisions.
- Content briefs.
- Editorial reviews.
- Publishing readiness and manual publication actions.
- Search Impact assessments.
- Pattern validation and revalidation.
- Governance proposals.
- Client reports.

Approvals remain inside the originating website or workflow. The command centre provides a review link and never approves records automatically.

## Portfolio risk register

Risks are generated from evidence such as stale snapshots, unresolved conflicts, workflow delays, technical blockers, failed publishing verification, missing Search Impact checkpoints, overdue reports, capacity pressure and connection errors.

Agency administrators can assign an owner and due date, resolve a risk with a required note, or reopen it. If the underlying evidence still exists after refresh, a resolved risk can return to the open state.

## Capacity forecasting

The forecast uses approved Service Level capacity records and open work items to report:

- Total and committed capacity units.
- Remaining capacity.
- Utilisation by team member.
- Work expected within 30 days.
- Unassigned and overdue items.
- People or portfolios at capacity risk.

The forecast does not automatically reassign users or alter a client commitment.

## Executive analytics

Aggregated operational counts cover portfolio health, opportunities, content production, editorial review, publishing readiness, Search Impact and client reporting. They describe stored workflow activity only and are not presented as business outcomes.

## Internal notifications

The module stores bounded WordPress-admin notifications for high-value conditions. Administrators can acknowledge or dismiss them. v1.16.0 does not send email, Slack or external notifications.

## Client-portal preparation

The REST workflow includes a read-only `client_portal_preview` command. Its response is intentionally limited to client-safe service-plan, completed-work and approved-report fields. It does not create a public route, expose agency notes, or publish a client portal.

## Safety

The Executive Command Centre cannot:

- Publish, update, merge, schedule or delete WordPress content.
- Approve a local business, editorial or publishing decision automatically.
- Send client messages.
- Reassign work automatically.
- Change redirects, canonicals, robots directives or external profiles.
- Treat an operational health score as a ranking, lead or revenue prediction.
