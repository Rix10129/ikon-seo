# Guided Launch & Strategy Activation

Ikon SEO v1.7.0 connects first-install discovery to the existing workflow and Closed-Loop Operating Plan.

## Purpose

Auto Discovery identifies likely website facts and strategy values, but it intentionally does not execute business decisions. Guided Launch begins only after the administrator reviews those suggestions.

The launch sequence is:

```text
Auto Discovery
→ Business confirmation
→ Conflict review
→ Recommended workflow
→ Bounded read-only audit tasks
→ Initial Operating Plan
→ Next five actions
```

## Activation stages

1. **Website discovery** — an Auto Discovery report must exist.
2. **Business confirmation** — the Website Strategy must be configured and reach at least 70/100 readiness.
3. **Conflict review** — detected conflicts must be resolved or explicitly acknowledged as reviewed.
4. **Recommended workflow** — Ikon SEO creates the mode-specific workflow only when no workflow exists.
5. **Initial safe audits** — one to five read-only workflow tasks can run in the activation batch.
6. **Operating Plan** — current evidence is consolidated into approval-controlled recommendations.

The activation score is an onboarding-completeness measure. It is not a ranking score.

## Safety boundaries

Guided Launch can:

- create an internal workflow;
- run bounded read-only evidence tasks;
- refresh the internal Operating Plan;
- save Project History continuity;
- show the next five actions.

Guided Launch cannot:

- publish or edit live pages;
- create or apply redirects;
- change canonicals or indexation;
- update Google Business Profile;
- respond to reviews;
- perform outreach or backlink actions;
- approve recommendations on behalf of an administrator.

## Recommended first test

1. Back up WordPress and the database.
2. Run Auto Discovery with a 25-page limit.
3. Apply only confirmed values.
4. Open **Ikon SEO → Guided Launch**.
5. Review conflicts and strategy readiness.
6. Run three safe tasks and build the Operating Plan.
7. Confirm that no published content, redirects, indexation or external profiles changed.
