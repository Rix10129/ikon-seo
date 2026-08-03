# Structured Data & Media Governance

## Purpose

This module records structured-data and media evidence without changing rendered markup, image files, alternative text or published pages.

## Structured-data review

The auditor retrieves same-site public pages with WordPress safe requests and examines rendered JSON-LD. It records:

- Parsed schema nodes and detected types
- Duplicate identifiers and repeated primary entity types
- Selected required-property gaps
- Invalid JSON-LD and missing context or type
- Visible-content consistency evidence
- Provider-output hints for Rank Math, Yoast SEO, themes and extensions
- Candidate search-feature families for editorial review

The audit does not promise that a search feature will appear.

## Media review

The media auditor records:

- File type, dimensions and size
- Alternative-text evidence
- Website usage and featured/social use
- Optional identical-file hashes
- Unused-image observations
- Page-level featured image, social preview, width, height and alternative-text attribute gaps
- Source, creator, license and rights notes

Missing alternative text is not automatically an error for a decorative image. Duplicate bytes do not prove an editorial or licensing violation.

## Source record types

- Original
- Licensed
- Client supplied
- Generated
- Public domain
- Unknown

Rights records are stored as attachment metadata. They do not replace legal review or the original license document.

## Scheduling and retention

A weekly bounded task can review stale pages and images. Agency administrators control batch sizes, stale-evidence periods, image-size warnings, source-record policy, file hashing and retention.

## Safety boundary

The module does not:

- Add or remove schema markup
- Change Rank Math, Yoast SEO or theme settings
- Rewrite alternative text
- Compress, replace or delete images
- Change published pages
- Claim guaranteed search enhancements
