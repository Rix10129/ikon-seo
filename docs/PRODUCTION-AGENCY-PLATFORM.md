# Ikon SEO v2.0.0 — Production Agency Platform

Ikon SEO v2.0.0 adds a final production-certification layer above Platform Health and Deployment Control.

## Purpose

The module records whether a specific Ikon SEO release and database version have completed the agency's production-readiness process. It does not make the software production-ready by assertion; the administrator must record real staging and compatibility evidence.

## Controlled workflow

1. Create a versioned production support contract.
2. Have a different administrator approve it.
3. Create a certification run for production, staging, development or local.
4. Record each compatibility, recovery and security check.
5. Refresh the evidence gate.
6. Have a different administrator approve the exact evidence fingerprint.
7. Create a bounded rollout wave for managed-site IDs.
8. Have a different administrator approve the rollout.
9. Install the plugin manually on each website.
10. Record each manual deployment result and close the wave.

## Mandatory critical checks

- Signed package integrity
- Database migration and upgrade journal
- Platform Health readiness
- Configuration recovery restore drill
- WP-Cron reliability and backlog
- REST authentication, rate limiting and replay controls
- Writer, reviewer and approver separation
- Tenant isolation
- Privacy, retention and secret redaction
- Administrator runbook and incident procedure

Critical checks cannot be waived.

## Advisory compatibility checks

- Shared-hosting performance
- Caching and object-cache compatibility
- Elementor controlled-draft compatibility
- Rank Math or Yoast compatibility
- WordPress multisite review

These checks may be documented as exceptions, but unresolved failures remain visible as warnings.

## Safety contract

The module permanently declares:

- `manual_distribution_only = true`
- `automatic_installation = false`
- `automatic_rollback = false`
- `remote_publish_disabled = true`
- `client_data_isolated = true`

It does not download packages, install or activate plugins, roll back code, publish content, update live pages, send client emails or disable the public website.

## Evidence invalidation

The certification stores an evidence fingerprint derived from:

- The approved support contract
- The individual certification checks
- Platform Health and package-integrity evidence
- The verified deployment record
- The tested recovery archive

Any change to those inputs invalidates the previous approval and requires a new review.

## Real production limitation

A code-level test suite cannot replace staging evidence. Before approving a production certification, test the exact package on representative hosting, theme, page-builder, SEO-plugin, caching, user-role and security-plugin configurations.
