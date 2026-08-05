# Ikon SEO Deployment Control, Licensing & Managed Updates

Ikon SEO v1.19.0 adds a governance layer around plugin releases. It records trusted release metadata, verifies licence entitlements, prepares deployment preflights, requires separate approval, and verifies an update after a WordPress administrator installs it manually.

## Safety boundary

Deployment Control does not:

- download a plugin package;
- write plugin files;
- invoke the WordPress upgrader;
- activate or deactivate plugins;
- perform a rollback;
- publish or edit website content;
- disable the public website when a licence expires;
- delete data when a licence expires.

The public website, WordPress administration, exports, recovery archives and existing records remain available even if an entitlement expires or is revoked. Entitlement state only governs new agency-orchestration and managed-deployment actions.

## Environment model

The deployment environment comes from WordPress `WP_ENVIRONMENT_TYPE` through `wp_get_environment_type()`:

- `production`
- `staging`
- `development`
- `local`

Production is permanently restricted to the `stable` release channel. Candidate and internal channels are only available outside production.

## Persistent installation identity

Each WordPress installation receives a local `ikon_seo_installation_id`. The entitlement site fingerprint combines the normalised website host and this persistent identifier. It is not a secret and it survives normal WordPress salt rotation.

## Entitlements

Signed production entitlements use an RSA-SHA256 envelope containing:

- licence ID;
- organisation;
- edition;
- site fingerprint;
- allowed feature set;
- allowed environment scope;
- issue, activation and expiry dates;
- maximum covered websites.

Only allowlisted features are accepted. A signed entitlement issued for another installation is rejected.

A local evaluation entitlement can be created for up to 30 days only when WordPress reports a non-production environment. It cannot be created on production.

## Release catalogue

The catalogue stores approved metadata, not executable packages. Each future release record should contain:

- release ID;
- plugin version;
- database component version;
- channel and environment;
- exact ZIP SHA-256;
- signed manifest SHA-256;
- minimum PHP and WordPress versions;
- publication date and release notes.

A release without a real ZIP SHA-256 cannot pass deployment readiness. Registering the currently installed package is useful for audit history, but WordPress cannot reconstruct the original uploaded ZIP hash from installed files.

## Deployment lifecycle

1. Register or import trusted release metadata.
2. Confirm an active entitlement covers the current environment.
3. Run Platform Health and verify package integrity.
4. Create a credential-free recovery archive.
5. Prepare a deployment plan and store its preflight fingerprint.
6. A different administrator approves the exact fingerprint.
7. A WordPress administrator updates the plugin manually.
8. Record the manual update only after WordPress reports the approved plugin and database versions.
9. Run post-deployment Platform Health and signed-manifest verification.
10. Close the deployment record with human notes.

Prepared plans become stale when left untouched for 30 days and must be prepared again.

## Workspace scopes

The private endpoint is:

`/wp-json/ikon-seo/v1/deployment-control`

Draft scope permits read-only evidence collection, release registration, deployment preparation and verification. Approve scope is required for entitlement changes, deployment approval, recording manual installation and closing a verified record.

## Data tables

- `wp_ikon_seo_license_entitlements`
- `wp_ikon_seo_release_catalog`
- `wp_ikon_seo_deployment_plans`
- `wp_ikon_seo_deployment_events`

## Scheduled monitoring

The daily `ikon_seo_deployment_control_daily` task updates entitlement lifecycle states and marks abandoned prepared deployments stale. It never downloads or installs updates.
