# Search Impact Monitoring & Outcome Attribution

Ikon SEO v1.12.0 adds an approval-first measurement layer after a release has been manually published and verified.

## Workflow

```text
Recorded manual publication
→ Pre-launch baseline from stored evidence
→ 7, 28, 56 and 90-day checkpoints
→ Optional same-site comparison page adjustment
→ Evidence-quality and confounder review
→ Human outcome assessment
→ Human decision acknowledgement
```

## Evidence sources

The engine reads evidence already stored by Ikon SEO:

- Google Search Console page evidence
- Google Analytics landing-page evidence
- Privacy-preserving qualified-lead, customer and revenue events
- Manual confounders such as seasonality, algorithm updates, campaigns, tracking changes, pricing or availability changes

It never scrapes search engines, invents targets, or treats unavailable evidence as zero-confidence proof.

## Measurement model

Each study selects one primary metric: clicks, impressions, CTR, average position, sessions, active users, views, key events, qualified leads, customers or revenue. Revenue is measured only in the configured default currency so values in unlike currencies are not summed.

The baseline window defaults to 28 days immediately before publication. Checkpoints are captured at 7, 28, 56 and 90 days after publication. A same-site comparison URL may be added to help distinguish page-specific movement from wider site or seasonal movement. The comparison URL must be different from the target page.

Evidence quality accounts for source availability, date coverage, freshness, observation volume, comparison coverage and recorded confounders. Results are labelled high, medium or low confidence.

## Outcome language

The engine can classify an assessed result as:

- Positive signal
- Negative signal
- Neutral signal
- Inconclusive

These labels describe an association after a release. They do not prove that the release caused the observed change. A human approver must assess the evidence and record a decision such as retain, expand carefully, continue monitoring, investigate, consider revision or no action.

## Stale-evidence protection

Refreshing the baseline or capturing a newer or refreshed checkpoint invalidates the previous assessment and acknowledgement. The study returns to monitoring or ready-for-assessment status and requires a new human decision.

## Safety

Search Impact cannot publish, edit, merge, redirect, delete, noindex or change canonical settings. Automated monitoring is bounded to three due studies per daily run and performs only stored-evidence reads and measurement-record writes.
