# International & Server Intelligence

Ikon SEO v1.4.0 adds approval-first international-targeting governance and optional privacy-preserving server-log evidence.

## International page evidence

The module retrieves public pages from the connected website and records:

- HTML language value
- Content-language response or meta evidence
- Canonical URL
- Hreflang language and language-region alternates
- Self-reference evidence
- Duplicate and invalid locale declarations
- x-default evidence
- Canonical and alternate conflicts
- Configured URL-prefix and page-language consistency
- Country, currency and phone-prefix signals configured for the locale
- Reciprocal links between alternate pages that have both been audited

The audit is read-only. It does not add, remove or rewrite public language tags, canonicals or translated content.

## Locale map

Use one line per locale:

```text
locale|path prefix|country|currency|phone prefix
```

Example:

```text
en-QA|en|Qatar|QAR|+974
ar-QA|ar|Qatar|QAR|+974
```

A locale map describes the website's actual structure. Do not add a language or region that the website does not genuinely serve.

## Server-log evidence

Agency administrators may import:

- Apache combined-log files
- Generic CSV files
- Bounded structured event batches through the private workspace

Supported evidence includes timestamp, request method, request path, status, response bytes, response time, crawler declaration and optional network address for verification.

## Privacy controls

Before storage:

- Network addresses are replaced with one-way hashes.
- User-agent strings are replaced with one-way hashes.
- Query values are discarded.
- Query-key names are optional and bounded.
- External website URLs are not accepted for page audits.
- Imports are limited by file size, row count and retention period.

## Crawler verification

Declared Googlebot, Bingbot and Applebot activity may be checked with reverse and forward DNS when verification is enabled and a valid network address is present. DNS verification may be unavailable or slow on some hosts. A declared crawler is not labelled verified unless the checks pass.

## Reports

The module reports:

- Crawler requests and verification states
- 4xx and 5xx paths
- Redirect requests
- Parameterized paths
- Admin, API and feed paths
- Static resources
- Slow responses
- Important pages without recent crawler evidence

These records show requests contained in the imported logs. They do not prove indexing, ranking impact or search-engine intent.

## Safety boundary

The module does not automatically:

- Edit or publish pages
- Add or remove hreflang
- Change canonicals
- Translate content
- Change country or language targeting
- Request indexing
- Alter server configuration
- Block crawlers
