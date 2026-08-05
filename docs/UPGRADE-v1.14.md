# Upgrade to Ikon SEO v1.14.0

## Before upgrading

1. Create a complete WordPress files and database backup.
2. Test the package on staging before a live agency or client website.
3. Confirm v1.13.0 Pattern Library tables and current Search Impact records are healthy.
4. Record the current workspace connection scopes and active agency-site registrations.
5. Confirm WordPress cron and safe outbound HTTPS requests work in staging.

## Upgrade changes

- Plugin version: 1.14.0
- Database component version: 33.0
- New managed-site fields:
  - `encrypted_governance_key`
  - `governance_status`
  - `governance_last_sync_at`
  - `governance_last_error`
- New tables:
  - `wp_ikon_seo_governance_policies`
  - `wp_ikon_seo_governance_assignments`
  - `wp_ikon_seo_governance_inbox`
  - `wp_ikon_seo_governance_events`
- New scheduled hook: `ikon_seo_portfolio_governance_sync`
- New local proposal endpoint: `/wp-json/ikon-seo/v1/agency-governance-agent`
- New workspace endpoint: `/wp-json/ikon-seo/v1/portfolio-governance`
- The focused OpenAPI schema remains limited to 30 unique operations.

## Staging validation

1. Install v1.14.0 over v1.13.0.
2. Confirm the database component upgrades to 33.0.
3. Open **Ikon SEO → Agency Governance**.
4. Generate a proposal-only governance key on a managed staging website.
5. Copy the key once and save it against that website in the agency command centre.
6. Create a draft policy with a readiness requirement above 70 and a safe batch below five.
7. Approve the policy centrally.
8. Assign it to the managed staging website and run synchronisation.
9. Confirm the remote website shows a pending local proposal, not an active policy.
10. Reject one proposal and confirm a decision note is required.
11. Deliver a new version and accept it with a local administrator account.
12. Confirm Guided Launch uses the policy readiness threshold and batch limit.
13. Confirm the compliance report identifies all locked safeguards.
14. Revoke the proposal key and confirm later synchronisation fails safely.
15. Confirm no page, post, redirect, canonical, indexation setting, Business Profile record, review or outreach action changes.

## Connection scopes

The normal workspace key remains responsible for central governance commands.

The `draft` scope permits:

- Reading the governance report.
- Creating draft policies.
- Saving proposal keys for managed sites.
- Assigning approved policies.
- Delivering proposal-only synchronisation.

The `approve` scope is required for:

- Approving or retiring a central policy.
- Accepting or rejecting a local proposal through the authenticated workspace.

The dedicated governance proposal key can only deliver policy proposals to the local inbox. It cannot perform workspace actions or activate a policy.

## Rollback

If rollback is necessary:

1. Deactivate v1.14.0.
2. Restore the v1.13.0 plugin files.
3. Keep the database backup available; v1.13.0 ignores the new governance tables and fields but does not manage them.
4. Reinstall v1.14.0 before using governance records again.

Do not delete governance tables during a routine rollback because they contain policy decisions, proposal receipts and audit history.
