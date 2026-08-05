# Guided Launch fixtures

## Fixture 1 — Fresh installation

- Auto Discovery has not run.
- Guided Launch score remains below activation.
- The first action links to Auto Discovery.
- Activation returns a discovery-required error.

## Fixture 2 — Discovery complete, strategy incomplete

- Auto Discovery report exists.
- Strategy readiness is below 70/100.
- Activation returns a strategy-confirmation error.
- No workflow or Operating Plan change occurs.

## Fixture 3 — Current unresolved conflicts

- Discovery contains multiple phone numbers or another conflict.
- The conflict acknowledgement is absent or belongs to an older discovery timestamp.
- Activation returns a conflict-review error.

## Fixture 4 — Confirmed local-business strategy

- Discovery exists.
- Strategy is configured at 70/100 or higher.
- Current conflicts are resolved or acknowledged.
- No workflow exists.
- Guided Launch creates the recommended local-growth workflow.
- The selected one-to-five read-only tasks run.
- The Operating Plan refreshes without external-source refresh.

## Fixture 5 — Existing workflow

- An active workflow already exists.
- Guided Launch does not create a duplicate workflow.
- Safe tasks and the Operating Plan can still run.

## Fixture 6 — Safety

- No published post content changes.
- No redirect, canonical, noindex or deletion action occurs.
- No Google Business Profile, review, outreach or backlink action occurs.
- Project History records the activation result and any module errors.
