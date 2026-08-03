# Workflow Automation Fixtures

## Template coverage

- Local strategy recommends `local_growth`.
- Editorial strategy recommends `editorial_growth`.
- Ecommerce strategy recommends `ecommerce_growth`.
- Hybrid strategy recommends `hybrid_growth`.
- Every template has unique task keys and at least one approval stage.

## Dependency behavior

- The first task is ready immediately.
- Dependent tasks remain pending until all dependency tasks are completed or skipped.
- Approval-required tasks become pending approval after dependencies complete.
- A dependent task cannot be marked completed before its dependencies.

## Safety behavior

- Only tasks with `safe_level=read` and a known automation action can run unattended.
- Draft, live, outreach, redirect and business-profile tasks never execute through the safe runner.
- Missing Search Console or Analytics connections complete with a not-connected note rather than changing the site.

## Retry behavior

- Failed read-only tasks return to ready until the configured maximum attempt count.
- Retry delay increases exponentially and is capped at 24 hours.
- Final failures remain visible with the latest sanitized error.

## Continuity

- Creating a workflow writes a Project History event.
- Approving or completing a task writes a Project History event.
- Daily and weekly briefings are stored in Project History.
- The private workflow sync reads the same stored state in a new conversation.
