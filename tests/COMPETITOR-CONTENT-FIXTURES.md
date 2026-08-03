# Competitor & Content Intelligence fixtures

## Storage validation

- Reject an observation without a query.
- Reject a URL without HTTP or HTTPS.
- Limit one sync request to 50 observations.
- Update the same query, URL and observed date rather than creating a duplicate.
- Preserve separate observations for the same page on different dates.
- Archive rather than permanently delete an observation from the dashboard.

## Intent validation

- `how to clean an office` should infer informational intent.
- `best cleaning companies in doha` should infer commercial investigation or local service depending on explicit selection.
- `office cleaning services doha` should infer local service intent.
- `book sofa cleaning` should infer transactional intent.

## Brief validation

- A brief with no stored competitor pages must return low confidence and a research requirement.
- Three matching competitor pages should produce medium confidence.
- Five or more matching competitor pages should produce high confidence.
- A WordPress post compared with dominant service pages should show a possible intent mismatch.
- Missing recurring topics must be hypotheses, not confirmed ranking blockers.
- Competitor proof patterns must be labelled as observations requiring factual verification.
- Creating a brief must not change the post.
- Creating a brief must create a Project History research item.

## Safety validation

- No Google Search scraping code is present.
- No competitor HTML or full article body is stored.
- No competitor claim is automatically copied into WordPress content.
- No page, redirect, schema or metadata is changed by the research action.
- The focused OpenAPI schema remains at 30 operations.
