# Experiments, Claims & Revenue Fixtures

## Experiment URL controls

1. A same-site HTTPS page is accepted as a test URL.
2. An external-host URL is rejected.
3. A URL present in both test and comparison groups is rejected.
4. A URL used by an approved, running, or monitoring experiment is rejected from another active experiment.

## Outcome classification

1. Clicks changing from 100 to 120 with a 10% threshold classify as improved when confidence is sufficient.
2. Clicks changing from 100 to 80 classify as declined.
3. Clicks changing from 100 to 105 classify as neutral.
4. Average position changing from 10 to 8 classifies as improved.
5. Low-confidence evidence classifies as inconclusive.
6. Zero-before and zero-after evidence classifies as inconclusive.

## Data quality

1. A period shorter than the minimum receives a warning.
2. A sample below the minimum observation count receives a warning.
3. A missing comparison group receives a warning.
4. A record with no usable search, behaviour, conversion, or value metric receives a warning.

## Claim ledger

1. Empty claim text is skipped.
2. Repeated post-and-claim hashes update the existing record.
3. High-risk claims receive the shorter configured review period.
4. Due verified or review-needed claims become overdue.

## Revenue privacy

1. Internal event references are stored only as one-way hashes.
2. Name, email, phone, address, message, and customer-contact metadata are removed.
3. External landing-page URLs are rejected.
4. Invalid currencies fall back to the configured three-letter currency.
5. Duplicate event keys are skipped.
6. Reports exclude reference hashes and plain contact details.

## Safety

1. No experiment change is applied automatically.
2. No published page is edited.
3. No CRM record is written.
4. No lead is contacted.
5. No outcome is presented as proof of causation.
