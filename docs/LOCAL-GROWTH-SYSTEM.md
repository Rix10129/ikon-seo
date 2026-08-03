# Local Growth System

Ikon SEO v0.17.0 adds an evidence and workflow layer for local-business and hybrid websites. It does not promise local rankings and it does not change public business information automatically.

## Evidence model

The system combines:

- Website Profile and Website Strategy
- Local storefront, hybrid and service-area records
- Business Profile account and location matches
- NAP audit results
- Citation records and maintenance dates
- Review status and response workflow timing
- Existing service and location landing pages
- Optional Analytics key events
- Optional Business Profile performance metrics
- Stored competitor-prominence observations
- Imported referring-domain and competitor-gap evidence

## Local readiness

Local readiness measures whether the required evidence and workflows are present. It is not a Google ranking score.

Checks include:

1. Local Business or Hybrid operating mode
2. Real local records
3. Website-versus-profile alignment
4. Service-area policy
5. Important offering-to-page coverage
6. Citation maintenance
7. Review response workflow
8. Local conversion measurement
9. Current competitor-prominence evidence

## Business-profile alignment

The report compares linked Business Profile locations with the website's master local records. Confirmed mismatches are separated from warnings and unavailable evidence.

Public Business Profile changes are never performed by this module. Existing Business Profile drafts still require exact administrator approval before they are sent.

## Review workflow

The Local Growth module stores only:

- A one-way review identifier hash and remote reference
- Star rating
- Whether a comment is present
- Whether a reply is present
- First and latest observation time
- Workflow status, owner, due date and notes

It does not permanently store review text. Preparing or sending a reply remains a separate approval-controlled Business Profile workflow.

## Citations

Citation health reports:

- Consistency percentage
- Correction requirements
- Duplicate warnings
- Pending or unverified records
- Stale review dates

A high citation count is not treated as a substitute for accurate, relevant and maintained business information.

## Service areas and local pages

The system checks:

- Service-area or hybrid records without verified areas
- Service-area-only records incorrectly marked as customer-facing
- The same area assigned to several local records
- Missing Website Strategy service-area policy
- Important services without a clear page match
- Local records without an assigned landing page

An area name alone is not sufficient reason to create a page. Every proposed page requires a distinct user need, genuine service evidence and enough unique value to avoid doorway-style duplication.

## Conversion evidence

Optional local conversion snapshots include:

- Analytics sessions, users, engagement, views and key events
- Business Profile website clicks
- Call clicks
- Direction requests
- Conversations
- Bookings
- Search and Maps impressions

Traffic is not treated as the final outcome. The Website Strategy should identify the real lead or revenue actions that matter.

## Competitor prominence

Agency administrators can store concise, dated observations from:

- Local-pack research
- Organic results
- Reviews
- Citations
- Backlinks
- Brand mentions
- Directories
- Other manual research

Every observation records its source type, query, evidence, confidence and date. Old observations are marked stale according to the configured freshness window.

## Scheduled refreshes

The weekly task can refresh connected review and conversion evidence using read-only requests. It never changes a public profile, sends a review reply, edits a page, creates a redirect or publishes content.

## Private workspace action

`syncIkonSEOLocalGrowth` supports:

- `read`
- `refresh`
- `sync_reviews`
- `sync_conversions`
- `save_prominence`
- `update_review_task`

Review workflow updates store internal task state only. Competitor observations store research evidence only.

## Current API limitation

Google discontinued Business Profile Questions and Answers API support and its related notifications in 2025. Ikon SEO therefore does not present an unavailable Q&A automation feature.
