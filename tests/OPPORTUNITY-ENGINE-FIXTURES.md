# Opportunity Engine fixtures

Validate these cases on staging after upgrading to v1.8.0.

1. **No connected evidence** — the dashboard reports source limitations and does not invent opportunities.
2. **Search Console striking distance** — a query between positions 8 and 20 becomes a Search Growth opportunity.
3. **Content decay** — declining impressions become a Performance Recovery review, not an automatic rewrite.
4. **Analytics measurement gap** — a landing page with meaningful sessions and no key events is flagged for tracking and conversion review.
5. **Competitor content gap** — only stored, reviewed competitor observations contribute to a brief.
6. **Semrush/Ahrefs CSV import** — common headers map to keyword, URL, volume, difficulty, position and date.
7. **Stale import** — old provider evidence receives reduced freshness and confidence.
8. **Indexation conflict** — canonical and noindex findings remain high-risk and approval-only.
9. **Technical redirect chain** — the engine proposes review but never writes a redirect.
10. **Status persistence** — completed or dismissed opportunities retain their human status after a rebuild.
11. **Workspace idempotency** — replaying the same write request does not duplicate imports or status updates.
12. **Safety boundary** — no Opportunity Engine command can publish, delete, redirect, noindex or change canonicals.
