# Secure Read-Only Client Portal

Ikon SEO v1.18.0 adds a client-facing portal that displays approved, client-safe SEO operations evidence without exposing the agency workspace or permitting live website changes.

## Access model

The portal uses existing WordPress user accounts. Ikon SEO does not create public magic links, passwordless share URLs or anonymous report pages.

Each access grant is restricted to:

- One WordPress user.
- One managed website.
- An explicit section allowlist.
- An optional expiry date.
- A deterministic SHA-256 access fingerprint.

A new grant is stored as `pending`. It cannot be used until a local WordPress administrator activates the exact stored fingerprint. Access can be revoked immediately.

## Front-end setup

Add this shortcode to a WordPress page chosen by the administrator:

```text
[ikon_seo_client_portal]
```

The plugin does not automatically create or publish a page. The administrator controls the page, theme, navigation and membership rules.

A visitor must be signed into WordPress and have an active, unexpired assignment. Users without an assignment receive no portal records.

## Client-visible sections

An administrator can allow any combination of:

```text
Overview
Service scope
Approved reports
Completed work
Planned work
Acknowledged Search Impact observations
Items requiring client attention
```

Only reports with `approved` or `delivered` status are visible. Search Impact records must have a human-acknowledged outcome.

## Excluded information

The client-safe snapshot excludes:

- Internal agency notes.
- Report preparation and decision notes.
- Service-plan prices and agency margins.
- Team capacity and individual staff workload.
- Connection keys, token hashes and OAuth credentials.
- WordPress administrator URLs.
- Draft or review-ready reports.
- Unacknowledged impact studies.
- Pattern-library portfolio evidence.
- Data from any other managed website.

## Snapshot model

The portal reads a sanitised, allowlisted snapshot instead of exposing raw agency tables. Snapshots have a bounded lifetime and are regenerated from stored evidence when expired.

Each snapshot stores:

- Assignment ID.
- Managed-site ID.
- Sanitised payload.
- SHA-256 payload hash.
- Source evidence timestamp.
- Generation timestamp.
- Expiry timestamp.

The client sees a stale-evidence warning when the managed-site evidence is older than seven days.

## REST routes

Authenticated client route:

```text
GET /wp-json/ikon-seo/v1/client-portal
```

This route requires the logged-in WordPress user to have an active assignment. The response is private and sent with no-store cache headers.

Administrator/workspace route:

```text
GET  /wp-json/ikon-seo/v1/client-portal-admin
POST /wp-json/ikon-seo/v1/client-portal-admin
```

The administrator route requires the connection key's `approve` scope because access records can contain user identity and tenant assignments.

Supported administrator commands:

```text
read
preview_user
create_access
activate_access
revoke_access
refresh_snapshot
```

## Audit and privacy

Portal access events store irreversible salted hashes of the viewer's IP address and user-agent string. Raw network identifiers are not stored by this module.

Access creation, activation and revocation are recorded in Project History and the client portal event log. Routine client views are recorded only in the bounded event log.

## Safety boundaries

The portal cannot:

- Publish, schedule, edit, merge or delete WordPress content.
- Approve briefs, editorial reviews or publishing releases.
- Send emails, Slack messages or client notifications.
- Change redirects, canonicals, robots settings or external profiles.
- Create a WordPress page automatically.
- Expose another client's website data.
- Present SEO observations as guaranteed rankings, leads or revenue.
