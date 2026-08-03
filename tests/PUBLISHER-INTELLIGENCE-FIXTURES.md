# Publisher Intelligence fixtures

## Opportunity priority

- High demand, medium difficulty, business value 90 should receive a higher priority than low demand, high difficulty, business value 30.
- Unknown demand and difficulty must remain explicitly labelled rather than invented.

## Quality gate

- A linked draft with a complete contributor profile, sources, contextual internal links and low duplication should be eligible to pass the configured threshold.
- A draft with very high same-site similarity must be blocked.
- An affiliate or advertising item requiring disclosure must be blocked when no disclosure is detected.
- Passing the quality gate must not publish the draft.

## Portfolio signatures

- Exported bundles must not include full post content.
- Invalid JSON or an unsupported format must be rejected.
- Imports must be limited to 1,000 signatures per bundle.
- High signature overlap should create a warning, not delete or rewrite content.

## Lifecycle review

- Stored search decay should recommend an update.
- Very high same-site similarity should recommend consolidation review.
- Old content with little evidence should only receive a low-confidence retirement review.
- No lifecycle recommendation may change a live URL automatically.
