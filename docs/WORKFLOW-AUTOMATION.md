# Workflow Automation

Ikon SEO v0.15.0 turns the Website Strategy into a durable, approval-first task system.

## Templates

- Local Business Growth
- Editorial / Blog Growth
- Ecommerce Growth
- Hybrid Growth

Each template creates a dependency-aware sequence of strategy, evidence, research, planning, approval, publication and measurement tasks.

## Safe unattended actions

The scheduler may run only read-only work:

- Strategy status checks
- Website inventory refreshes
- Evidence crawl batches
- Technical URL and internal-link refreshes
- Search Intelligence refreshes when Search Console is connected
- Analytics refreshes when GA4 is connected
- Page Diagnostics refreshes
- Content refresh monitoring
- Daily and weekly briefings

Missing optional Google connections are recorded as skipped evidence rather than causing a live website change.

## Approval-required actions

The workflow engine never automatically:

- Publishes or edits live content
- Creates redirects
- Changes canonicals, noindex or robots directives
- Sends outreach
- Builds external links
- Replies to reviews or posts to business profiles
- Deletes or merges pages

These tasks remain approval-controlled even when the Website Strategy uses the controlled-changes automation level.

## Scheduling

WordPress schedules an hourly safe-task runner, a daily briefing and a weekly briefing. WP-Cron depends on website traffic unless the host connects it to a real server scheduler.

## Retries

Read-only failures are retried with bounded exponential backoff. Each task stores attempts, latest error, next run and a run-history record. The default maximum is three attempts.

## Project continuity

Workflow creation, approvals, task status changes, automated completions and briefings are written to Project History. This state remains available across separate conversations and account changes.
