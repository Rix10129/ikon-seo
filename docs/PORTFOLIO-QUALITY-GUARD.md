# Portfolio Quality & Footprint Guard

## Purpose

The Portfolio Quality & Footprint Guard helps an agency review whether websites in a managed portfolio are becoming too similar in content, structure, authorship presentation, media use or publishing patterns.

The module is designed for agencies operating many local-business, editorial, affiliate or lead-generation websites. Its findings are editorial review signals. They are not declarations of copying, spam, policy violations or ranking outcomes.

## Evidence model

A local page is converted into a bounded profile containing:

- Page URL and title
- Word, heading, paragraph and internal-link counts
- Hashed content shingles
- A bounded set of short topic terms
- Hashed heading and template patterns
- Hashed title pattern
- Hashed author presentation
- Optional media file hashes
- A coarse publishing day-and-hour pattern

Complete article text, author names and image file contents are not included in exported bundles.

## Cross-site review

Export a signature bundle from one managed website and import it into another. The receiving website can then compare its own pages against the imported evidence.

Review categories include:

- Cross-site content similarity
- Repeated template and heading footprint
- Topic-map overlap
- Reused media files
- Reused author presentation with similar content
- Synchronized publishing patterns
- Thin programmatic page clusters on the local website

Only the strongest matching imported page is used for each local page during a bounded evaluation. This reduces duplicated findings while keeping the review manageable.

## Review gates

High-risk findings can block a Publisher Intelligence item from reaching review-ready status. The block does not change or unpublish the page.

An agency administrator can mark a finding as:

- Open
- Reviewed
- Accepted
- Resolved
- Dismissed

Resolving or dismissing the relevant finding removes the portfolio block during the next evaluation.

## Operating Plan integration

Critical and high findings are copied into the Closed-Loop Operating Plan as approval-required recommendations. No recommendation performs a public change automatically.

## Privacy and safety

The module:

- Accepts bundles of at most 5 MB
- Imports at most 2,000 page profiles per bundle
- Rejects a bundle from the current website
- Does not store complete imported page content
- Does not store imported author names
- Does not store imported image files
- Does not edit or publish pages
- Does not contact website owners
- Does not create links or redirects
- Does not claim that a detected pattern caused a ranking result

Page titles, URLs and bounded topic terms are included because they are needed for a useful editorial comparison. Treat exported bundles as internal agency records.

## Recommended first test

1. Create signatures for ten pages on a staging website.
2. Export its signature bundle.
3. Import the bundle into a different staging website.
4. Create signatures for ten local pages.
5. Evaluate the portfolio.
6. Review every blocking finding manually.
7. Confirm that no public content changed.
8. Mark one test finding reviewed or dismissed.
9. Re-run the evaluation and review the Publisher Intelligence gate.

## Limitations

- Similarity can be legitimate when websites serve different audiences or use common factual terminology.
- A matching file hash indicates identical bytes, not unlawful reuse.
- A matching author hash indicates the same normalized author presentation, not the person’s verified identity.
- Publishing-time patterns are contextual evidence, not a ranking factor.
- Short or templated local pages require business and editorial review before any conclusion.
