# Local Growth Fixtures

## 1. Storefront with aligned profile

Expected:

- Profile alignment contains no critical mismatch.
- Linked-location count increases.
- Public profile remains unchanged.

## 2. Service-area-only record with customer-facing address flag

Expected:

- Critical service-area issue.
- Recommendation to remove the public-address claim unless customers are genuinely served there.
- No schema or profile change is applied.

## 3. Duplicate service-area ownership

Expected:

- Medium review issue.
- Recommendation to confirm ownership and avoid competing area pages.

## 4. Missing offering page

Expected:

- Offering is marked uncovered only when no sufficiently similar page exists.
- Recommendation asks for commercial and intent review rather than automatic page creation.

## 5. Citation correction and duplicate

Expected:

- Consistency percentage decreases.
- Correction and duplicate counters increase.
- No directory account is changed.

## 6. New review without reply

Expected:

- Review workflow item is created.
- Due date uses the configured response target.
- Review text is not persisted in the Local Growth table.

## 7. Existing review with reply

Expected:

- Workflow status becomes responded.
- Reply state and response time are stored.
- Reply content is not persisted in the Local Growth table.

## 8. Connected conversion evidence

Expected:

- Analytics and Business Profile metrics are stored as separate source records.
- Reporting period is preserved.
- No Analytics or Business Profile setting is changed.

## 9. Stale competitor observation

Expected:

- Observation is marked stale after the configured number of days.
- Recommendation requests refreshed research.

## 10. Non-local website mode

Expected:

- Scheduled Local Growth refresh exits without remote requests.
- Workflow task reports not applicable.

## 11. Private workspace action limit

Expected:

- Focused schema has exactly 30 unique operations.
- `syncIkonSEOLocalGrowth` is present.
- The general Local Summary operation is omitted only from the focused schema and remains available in WordPress.
