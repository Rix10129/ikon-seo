# Agency Command Centre

Ikon SEO v0.18.0 adds an agency-only operating layer for monitoring many connected WordPress websites without granting the central site permission to publish or edit remote content.

## Architecture

Each managed website generates a separate read-only site key. The command-centre website stores that key encrypted and requests a bounded snapshot over HTTPS from:

`/wp-json/ikon-seo/v1/agency-snapshot`

The snapshot excludes workflow keys, OAuth credentials, full content, customer review text and other secrets.

## Portfolio information

The command centre can show:

- Website and strategy status
- Connection availability
- Page-diagnostic summaries
- Technical failures and orphan-page counts
- Search and Analytics connection state
- Workflow progress, overdue tasks and approval items
- Publisher and Local Growth readiness
- Drafts awaiting review
- Monitoring and queue summaries
- Privacy-preserving publisher signatures
- Research and service budget usage

## Safety boundary

The central command centre does not publish, edit, redirect, merge, delete, send outreach, answer reviews or change a public business profile. Managed-site connections and key replacement are performed only by an Agency administrator in WordPress. Approval-controlled work remains on the individual managed website.

## Connection process

1. Install v0.18.0 on the command-centre website and on the managed website.
2. On the managed website, open **Ikon SEO → Agency Command Centre**.
3. Generate a read-only site key and copy it immediately.
4. On the command-centre website, add the managed website URL and complete site key.
5. The connection is verified before it is stored.
6. Revoke and replace the site key whenever access changes.

## Portfolio safeguards

- HTTPS-only managed websites
- WordPress safe remote requests
- Encrypted stored site keys
- Hourly snapshot rate limit
- Bounded response size
- Batched scheduled refreshes
- Retained snapshot history
- No central remote-write endpoints
- Agency-only settings and connection management
