# Agency Service Levels, Capacity & Client Reporting

Ikon SEO v1.15.0 adds an internal agency operations layer above Portfolio Governance and the read-only Agency Command Centre.

## Workflow

```text
Approved service plan
→ Managed-site assignment
→ Team capacity allocation
→ Capacity-controlled work items
→ Evidence-based report draft
→ Separate human approval
→ Manual client delivery record
```

## Service plans

Each versioned plan records:

- Included deliverables and excluded services.
- Monthly capacity units.
- Maximum concurrent work items.
- Response target hours.
- Review and reporting cadence.
- Evidence required for client reports.
- Currency and optional monthly fee for internal portfolio use.

Every normalised plan permanently records that client approval is required, report delivery is manual, live-site writes are unavailable and rankings are not guaranteed.

## Capacity controls

A managed site can have one active or paused assignment. New work is blocked when it would exceed the plan's concurrent-item limit or monthly capacity allocation.

Team members can receive capacity records for a date range. The dashboard compares open assigned units with available units and reports utilisation without automatically reassigning work.

## Client reports

Reports use stored evidence from the Agency Command Centre and service work records. They include:

- Website and strategy summary.
- Connected-evidence coverage.
- Work recorded for the report period.
- Service-level status and capacity use.
- Human-written executive summary and next actions.
- Explicit limitations and non-guarantee language.

A report becomes `review_ready`, then requires a different WordPress user to approve it. If the managed-site snapshot or any included work item changes, the stored evidence fingerprint becomes stale and approval is blocked.

Ikon SEO does not send the report. An administrator can only record a supported manual delivery method after approval.

## Safety

The module cannot:

- Email a client or publish a client portal.
- Publish, edit or merge WordPress content.
- Promise rankings, leads or revenue.
- Change redirects, canonicals, robots directives or external profiles.
- Increase plan capacity automatically.
- Assign work automatically to a team member.
