# Controlled Publishing Readiness & Post-Launch Verification

Ikon SEO v1.11.0 adds a guarded handoff between final editorial sign-off and a separately authorised WordPress publishing decision.

## Workflow

1. A Content Workbench draft completes Editorial Review and receives final sign-off.
2. An administrator or user with publishing capability creates an immutable release candidate.
3. Ikon SEO runs a bounded preflight against the signed-off draft and its prepared metadata.
4. A human approves the release as **Ready for manual publishing**.
5. An authorised WordPress user publishes or merges the content manually.
6. Ikon SEO records the public post and URL.
7. Read-only public checks run at launch, approximately 24 hours, 7 days and 28 days.
8. A human closes monitoring after all checkpoints are reviewed or the monitoring window has elapsed.

## Release-candidate lock

A release candidate stores:

- Editorial review and brief IDs
- Controlled draft and source-page IDs
- Publication mode
- Target URL and proposed slug
- Signed-off snapshot hash
- Current controlled-draft hash
- Preflight and public-verification results
- Readiness approver and timestamps
- Manual-publication record
- Monitoring schedule and events

Any change to the signed-off editorial snapshot or controlled draft invalidates preflight and readiness. The content must return to Editorial Review before a fresh release candidate is prepared.

## Preflight checks

The bounded preflight checks include:

- Draft matches the final editorial snapshot
- Item remains unpublished
- Page title and proposed slug
- Finished-content depth and drafting placeholders
- SEO title and meta description
- No prepared `noindex` directive
- Same-site canonical target
- Relevant internal links
- Conversion action where appropriate
- Featured media where appropriate
- Existing live source page for separate revision workflows

Blockers prevent readiness approval. Warnings remain visible for a human decision.

## Manual publishing boundary

Readiness approval only writes release metadata. It explicitly records:

```text
publishes_automatically = false
requires_manual_wordpress_action = true
```

This component contains no action for publishing, scheduling, merging, redirecting, deleting, changing canonicals or changing indexability.

For new pages, WordPress publication of the controlled draft can be detected. For an existing-page revision, the authorised user must merge or apply the revision manually and then identify the published source post and URL.

## Post-launch verification

Public verification is read-only, restricted to the current WordPress host, uses WordPress safe HTTP validation, and is bounded to a 10-second request, up to three redirects and a maximum 1 MB response body. It checks:

- HTTP response status
- `noindex` in HTML or headers
- Rendered canonical
- Rendered title and description
- H1 count
- Structured data presence
- Measurement-code presence
- Conversion-action presence

Results are observations, not automatic fixes. Blockers create an issues-found state for human review.

## Monitoring checkpoints

The release stores public snapshots for:

- Launch
- Approximately 24 hours
- Approximately 7 days
- Approximately 28 days

The daily scheduler processes no more than three due releases in one run. Monitoring cannot be completed while public blockers remain. It normally requires four post-launch snapshots, but may be closed after the 28-day monitoring window has elapsed and a human reviews the available evidence.

## Workspace endpoint

```text
GET  /wp-json/ikon-seo/v1/publishing-readiness
POST /wp-json/ikon-seo/v1/publishing-readiness
```

Supported commands:

```text
read
create_release
run_preflight
mark_ready
record_manual_publication
verify_launch
complete_monitoring
block
unblock
compare
```

Existing connection-key authentication, scopes, rate limiting, payload limits and Project History logging apply. Preparation and read-only verification commands require the `draft` scope. `mark_ready`, `record_manual_publication`, `complete_monitoring`, `block` and `unblock` require the explicitly enabled `approve` scope. Governed workspace actions are attributed to the administrator who created the active connection key; upgraded connections fall back only to the configured WordPress administrator when that account still has administrator capability.

## Database tables

Database component version: `30.0`

```text
wp_ikon_seo_publishing_releases
wp_ikon_seo_publishing_checks
wp_ikon_seo_publishing_snapshots
wp_ikon_seo_publishing_events
```

## Safety boundary

Ikon SEO v1.11.0 does not automatically:

- Publish, privately publish or schedule posts
- Merge revisions into live pages
- Delete or noindex content
- Create redirects
- Change canonical settings
- Submit URLs for indexing
- Change Google Business Profile
- Contact reviewers, prospects or backlink targets
