# Agency Command Centre Fixtures

## Connection fixture

1. Generate a read-only site key on a managed website.
2. Add its HTTPS URL and full key to the command centre.
3. Confirm the initial snapshot is stored and the key is not displayed again.
4. Revoke the key on the managed website and confirm the next refresh creates a connection alert.
5. Replace the key and confirm the alert resolves after a successful refresh.

## Snapshot privacy fixture

Confirm the response contains summary evidence only and does not contain:

- Private workflow keys
- OAuth client secrets or tokens
- Complete page content
- Customer review text
- Stored external-service keys
- WordPress password hashes

## Approval fixture

Create a managed-site workflow task that requires approval and a review draft awaiting review. Refresh the site and confirm both appear in the central approval queue. Confirm the central website cannot approve or execute the remote action.

## Alert fixture

Test snapshots containing:

- Critical diagnostic findings
- Failed URLs
- Overdue workflow tasks
- Stale evidence
- Missing Search Console or Analytics connections

Confirm active alerts are updated rather than duplicated and can be manually resolved.

## Budget fixture

Set a monthly budget, record several usage items and confirm the percentage and risk alert are calculated from the current calendar month only.

## Duplication fixture

Connect two editorial websites with overlapping privacy signatures. Confirm an overlap review appears without exposing full article text. Confirm same-site signatures do not create a cross-site warning.

## Reporting fixture

Download one client report and the portfolio CSV. Confirm branding is applied, secrets are excluded and the report states that rankings are not guaranteed.
