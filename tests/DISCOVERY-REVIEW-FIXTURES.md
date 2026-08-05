# Discovery Review Fixtures

## Required tests

1. A high-confidence technical fact starts as `detected`.
2. An inferred business fact starts as `needs_confirmation`.
3. A conflict starts unresolved and blocks review readiness.
4. A suggested value can be marked `confirmed`.
5. A corrected value can be marked `edited`.
6. A suggestion can be marked `rejected` and is excluded from application.
7. A changed confirmed fact becomes `outdated` after rescan.
8. The prior approved value remains stored after evidence changes.
9. A removed confirmed fact is retained in the archive as outdated.
10. A stale `generated_at` write returns a conflict error.
11. A conflict can use a detected value, custom value or multiple-valid decision.
12. Only confirmed and edited values are applied.
13. Applying identity-sensitive profile changes revokes the old remote key.
14. Guided Launch blocks while uncertain, outdated or conflicting facts remain.
15. Guided Launch succeeds after fact review and strategy readiness are complete.
16. No fact-review operation publishes, redirects, changes indexation or writes to an external profile.

Executable local stub tests:

```bash
php tests/discovery-review-test.php
php tests/guided-launch-v171-test.php
```
