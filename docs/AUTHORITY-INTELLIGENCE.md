# Authority & Off-Site Evidence

Ikon SEO v0.13.0 stores auditable link observations from administrator-approved CSV files or a connected private workflow. It does not crawl the wider web, sell links, automate outreach, or claim access to Google's private ranking logic.

## Supported evidence

- Generic CSV files using the included template
- Common Ahrefs, Semrush and Majestic export headings
- Manually reviewed observations
- Licensed-provider or connected-workflow records
- Website backlinks and competitor backlinks are stored separately

## Core reports

1. Active and lost website backlink records
2. Unique referring domains and linked target pages
3. Follow, nofollow, sponsored, UGC and unknown link evidence
4. Branded, URL, generic, descriptive and empty anchor categories
5. Failed, redirected, unpublished and lost target recovery
6. Competitor source domains absent from the website dataset
7. Important published pages with no active backlink record
8. Page-level authority evidence used by Ranking Diagnostics

## Evidence limitations

Third-party domain metrics are provider estimates. Ikon SEO stores them as supplied and does not normalize them into a new score. The imported file may be incomplete, delayed or sampled. Every link opportunity requires review for topical relevance, editorial legitimacy, traffic quality, spam risk and feasibility.

## Import safety

- Import controls are shown only to agency administrators.
- Files are read from the temporary upload location and are not added to the Media Library.
- Maximum file size is 10 MB.
- Maximum parsed rows per file is 20,000.
- Source and target must be different HTTP or HTTPS domains.
- Website backlink records must target the connected website.
- Competitor records must target the specified competitor domain.
- Imports never create, remove or alter a backlink.

## Connected action

`syncIkonSEOAuthorityIntelligence` accepts an empty object to read the current report. It can optionally store up to 1,000 approved link observations per request. Any write requires the draft scope.
