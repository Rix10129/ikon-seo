# Indexation and Hardening Fixtures

## URL safety

- Same-host HTTPS URL is accepted.
- External host is rejected.
- Invalid URL is rejected.
- A changed published page is queued only when the policy is enabled.

## Quota behaviour

- Local budget cannot exceed 2,000.
- Batch size cannot exceed the remaining local daily budget.
- A quota response stops the current batch.
- Read-only inspection does not call an indexing-submission endpoint.

## Result mapping

- Passing verdict maps to indexed.
- Failing or neutral verdict maps to not indexed.
- Robots block maps to `robots_blocked`.
- Meta noindex maps to `meta_noindex`.
- Failed fetch maps to `fetch_failed`.
- Different Google and user canonicals map to `canonical_mismatch`.

## Production health

- Missing database table produces a critical check.
- Missing scheduled event produces a warning.
- Stale scheduler heartbeat produces a warning.
- Rank Math and Yoast active together produces a critical check.
- REST loopback failure produces a warning.

## Safety assertions

- No page is published or edited.
- No redirect is created.
- No canonical or robots directive is changed.
- No indexing request is sent.
- No service credential is returned in reports.
