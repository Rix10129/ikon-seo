# Closed-Loop Test Fixtures

## Improvement fixture

Baseline:

- Search clicks: 100
- Search impressions: 1,000
- Average position: 12
- Sessions: 200
- Key events: 10
- Diagnostic priority: 80
- Finding count: 8

Current:

- Search clicks: 140
- Search impressions: 1,250
- Average position: 8
- Sessions: 230
- Key events: 14
- Diagnostic priority: 55
- Finding count: 4

Expected result: `succeeded` with several comparable signals.

## Decline fixture

Baseline:

- Search clicks: 100
- Search impressions: 1,000
- Average position: 8
- Sessions: 220
- Key events: 12

Current:

- Search clicks: 60
- Search impressions: 650
- Average position: 15
- Sessions: 140
- Key events: 5

Expected result: `declined`.

## Insufficient evidence fixture

Baseline and current contain only one meaningful comparable metric.

Expected result: `inconclusive` with low confidence.

## Safety fixture

- Approving a recommendation does not edit a page.
- Starting work stores a baseline.
- Completing work schedules measurement windows.
- Restoring a checkpoint is unavailable through the focused private-workspace action.
- Checkpoint payloads exclude workflow keys, OAuth tokens, client secrets and external-service keys.
