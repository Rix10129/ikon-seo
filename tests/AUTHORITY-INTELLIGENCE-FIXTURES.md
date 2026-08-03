# Authority Intelligence fixtures

## Generic website backlink

- Source: `https://publisher.example/article`
- Target: connected-domain service page
- Expected: active website backlink, one referring domain, descriptive anchor.

## Competitor source gap

- Import one competitor backlink from `publisher.example` to `competitor.example`.
- Do not import a website backlink from the same source domain.
- Expected: `publisher.example` appears as a competitor source-domain gap.

## Shared source

- Import a website backlink and competitor backlink from the same source domain.
- Expected: the source is excluded from competitor gaps.

## Lost link

- Import a website backlink with `status=lost`.
- Expected: appears in lost-link and recovery evidence.

## Redirected or failed target

- Refresh Technical Intelligence so the target has a stored 3xx or 4xx status.
- Expected: target appears in recovery evidence with high confidence.

## Domain protection

- Import a `site_backlink` whose target is not the connected domain.
- Expected: record is skipped with a target-domain validation error.

## File limits

- Upload a file larger than 10 MB.
- Expected: administrator error; no import.
- Parse more than 20,000 rows.
- Expected: parsing stops at the bounded limit.

## Workspace schema

- Confirm exactly 30 unique operations.
- Confirm `syncIkonSEOAuthorityIntelligence` is present.
- Confirm the focused `/media` path is omitted.
