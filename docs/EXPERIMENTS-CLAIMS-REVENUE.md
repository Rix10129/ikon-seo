# Experiments, Claims & Revenue

Ikon SEO v1.3.0 adds an approval-first evidence layer for controlled SEO tests, content-claim verification, and privacy-preserving lead or revenue attribution.

## Controlled experiments

An experiment records:

- a title and hypothesis;
- the proposed change type;
- one or more same-site test URLs;
- an optional comparison group;
- the primary and secondary metrics;
- a minimum observation period;
- baseline, checkpoint, and outcome measurements;
- data-quality findings, outcome, and confidence.

A URL cannot be assigned to both groups. URLs already used in an active experiment are blocked from a second active experiment so several changes are not measured against the same page at once.

The module can use available Search Intelligence, Analytics, and stored attribution evidence. It does not prove that a recorded change caused every measured movement. Seasonality, competitors, tracking changes, campaigns, and unrelated website work can influence the result.

## Measurement safeguards

Outcome measurements review:

- minimum experiment duration;
- minimum observation count;
- comparison-group availability;
- availability of usable search, behaviour, conversion, or value metrics;
- the configured material-change threshold.

Supported outcome labels are:

- Improved
- Declined
- Neutral
- Inconclusive

Average position is interpreted in the correct direction: a lower number is better. A low-confidence measurement is classified as inconclusive.

## Content claim ledger

A claim record may include:

- the related WordPress post;
- claim text and claim type;
- standard, sensitive, or high risk;
- source URL, title, type, and publication date;
- verification state;
- reviewer;
- verification and review-due dates;
- notes.

High-risk claims use the shorter configured review period. Overdue records are marked by the weekly maintenance task.

The ledger documents editorial review. It does not replace legal, medical, financial, or other professional review where required.

## Lead and revenue attribution

The attribution foundation accepts bounded records such as:

- lead;
- qualified lead;
- appointment;
- proposal;
- sale;
- refund;
- affiliate value;
- advertising value.

Records may include source, medium, campaign, landing URL, CRM stage, value, currency, and non-identifying metadata.

Customer names, email addresses, phone numbers, addresses, messages, and similar contact fields are not stored by this module. Internal event references are converted into one-way hashes. The module does not change CRM records or contact leads.

## Approval boundary

This module does not automatically:

- edit or publish pages;
- apply experiment changes;
- change metadata, canonicals, or redirects;
- contact leads;
- change CRM records;
- create invoices or financial records;
- declare causation from observed movement.

## Scheduled maintenance

The weekly task:

- marks overdue claims;
- moves ended running experiments into monitoring;
- applies configured retention cleanup.

Scheduled maintenance is bounded and does not retrieve or modify public content.

## Focused workspace action

The focused schema exposes `syncIkonSEOExperimentsClaimsRevenue` with these commands:

- `read`
- `save_experiment`
- `update_experiment`
- `capture_measurement`
- `save_claims`
- `update_claim`
- `import_revenue_events`
- `save_settings`
- `cleanup`

Structured Data & Media Governance remains available in WordPress and the complete REST interface but is omitted from the focused schema to retain the 30-operation compatibility limit.
