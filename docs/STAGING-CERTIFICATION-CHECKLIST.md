# Ikon SEO v2.0.1 Staging Certification Checklist

Use one copy per staging website.

## A. Environment identity

- [ ] Environment type is `staging`, `development`, or `local`
- [ ] WordPress version recorded
- [ ] PHP version recorded
- [ ] Database server/version recorded
- [ ] Active theme recorded
- [ ] Elementor version/status recorded
- [ ] Rank Math or Yoast version/status recorded
- [ ] Cache and security plugins recorded
- [ ] Object-cache status recorded
- [ ] Multisite status recorded

## B. Upgrade preparation

- [ ] Current plugin version documented
- [ ] Platform Health configuration archive created
- [ ] Database backup confirmed outside Ikon SEO
- [ ] Second administrator account confirmed
- [ ] Connection key scopes reviewed
- [ ] Maintenance/rollback owner identified

## C. Installation and migration

- [ ] v2.0.1 installed manually
- [ ] Plugin version is `2.0.1`
- [ ] Database component version is `40.0`
- [ ] Upgrade journal contains the transition
- [ ] Three staging-validation tables exist
- [ ] No unexpected activation error was logged

## D. Automated staging run

- [ ] Staging Validation run created
- [ ] Every critical check passed
- [ ] Advisory warnings reviewed
- [ ] Package signature verified
- [ ] Platform Health is ready
- [ ] Cron loopback passed
- [ ] Same-site HTTP passed
- [ ] REST and connection security passed
- [ ] Tenant isolation passed
- [ ] Secret redaction passed
- [ ] Temporary artifacts were removed

## E. Manual compatibility exercises

- [ ] Auto Discovery completed on a bounded sample
- [ ] Fact Review approval/rejection tested
- [ ] Guided Launch safe batch tested
- [ ] Opportunity Engine queue refreshed
- [ ] Content Workbench created an unpublished draft
- [ ] Elementor draft opened without corrupt metadata
- [ ] Rank Math or Yoast metadata remained intact
- [ ] Editorial writer/reviewer separation tested
- [ ] Publishing Readiness kept the item as Draft
- [ ] Client Portal tenant isolation tested with two users/sites
- [ ] Recovery archive preview and restore tested
- [ ] No public content changed during validation

## F. Performance and hosting observation

- [ ] PHP memory peak recorded
- [ ] Longest request duration recorded
- [ ] Cron backlog reviewed after 24 hours
- [ ] Error log reviewed after 24 hours
- [ ] Cache purge behavior reviewed
- [ ] Security plugin did not block expected same-site routes
- [ ] Shared-hosting limits remained within acceptable bounds

## G. Evidence approval

- [ ] Evidence fingerprint copied
- [ ] Prepared-by administrator identified
- [ ] Different approving administrator identified
- [ ] Approver reviewed critical evidence
- [ ] Exact fingerprint approved
- [ ] Privacy-minimised evidence pack exported
- [ ] Evidence pack attached to Production Certification
- [ ] Any exception includes owner, reason, and follow-up date

## H. Outcome

- [ ] Approved for pilot rollout
- [ ] Blocked pending remediation
- [ ] Deferred for additional observation

Decision notes:

```text

```
