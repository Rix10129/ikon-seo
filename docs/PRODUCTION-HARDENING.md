# Production Hardening

## Purpose

Production Health provides repeatable checks before Ikon SEO is treated as stable on a live website. It records evidence rather than silently changing hosting, plugins or schedules.

## Checks included

- PHP and WordPress minimum versions
- Ikon SEO database component version
- expected database tables
- PHP memory limit
- expected scheduled events
- scheduler heartbeat
- simultaneous Rank Math and Yoast activation
- common cache-layer presence
- common security-layer presence
- local REST loopback
- WordPress environment type

## Scheduler limitation

WordPress scheduled events normally run when the website receives a request after an event becomes due. Low-traffic websites or websites with page-load cron disabled may need a real hosting scheduler. Ikon SEO records a heartbeat so delayed execution is visible.

## Compatibility rule

Rank Math and Yoast should not normally remain active together because duplicate metadata, sitemap and schema ownership can become ambiguous. Ikon SEO reports this as a critical compatibility issue but does not deactivate either plugin.

## Safe upgrade sequence

1. Create a hosting backup.
2. Test the upgrade on staging.
3. Confirm the database component version.
4. Run Production Health.
5. Confirm all expected tables exist.
6. Confirm scheduled events and the heartbeat.
7. Test the REST schema endpoint.
8. Test one small indexation batch.
9. Review logs and Project History.
10. Promote the release only after the test passes.

## Retention

Production-health run history is retained for a configurable period. Cleanup removes old run records only; it does not delete website content or integration credentials.
