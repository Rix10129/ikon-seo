# Portfolio Quality Guard Fixtures

## Content signatures

- Identical hash sets return 100% Jaccard similarity.
- Disjoint hash sets return 0% similarity.
- Partial overlap is calculated against the complete union.
- Invalid or short hash values are discarded during import.

## Title and structure patterns

- Location endings are normalized to a location placeholder.
- Numbers are normalized to a number placeholder.
- Matching template and heading hashes with meaningful content overlap produce a repeated-template finding.
- Template equality alone does not prove a quality problem.

## Cross-site findings

- Content overlap above the configured threshold produces a blocking finding.
- Topic overlap without high content overlap produces a non-blocking differentiation review.
- Strong identical media evidence can block review.
- A repeated author hash requires meaningful content overlap before a finding is created.
- Publishing cadence is only a low-severity contextual signal.

## Local scaled-page review

- A group smaller than the configured cluster minimum does not create a finding.
- A group meeting the cluster minimum creates findings only when enough pages are below the configured word threshold.
- Each affected page receives its own review record.

## Privacy

- Export bundles exclude complete content.
- Export bundles exclude author names.
- Export bundles exclude image files.
- Raw media content is represented only by optional file hashes.
- Imports from the current website are rejected.
- Bundles above 5 MB are rejected by the administration upload handler.
- Imports are bounded to 2,000 page profiles.

## Approval and safety

- Findings never edit or publish a page.
- Findings never create redirects or external links.
- High findings can set `blocked_portfolio` on related Publisher Intelligence items.
- Dismissed and resolved findings do not remain open.
- Operating Plan recommendations require approval.
