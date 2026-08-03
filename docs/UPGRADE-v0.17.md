# Upgrade to Ikon SEO v0.17.0

1. Back up WordPress files and the database.
2. Upload the v0.17.0 ZIP and choose **Replace current with uploaded**.
3. Confirm the agency administrator under **Ikon SEO → Agency Access**.
4. Open **Ikon SEO → Website Strategy** and confirm Local Business or Hybrid mode where appropriate.
5. Open **Ikon SEO → Local SEO** and verify every real location or service-area record.
6. Open **Ikon SEO → Local Growth**.
7. Run the first refresh without requesting fresh connected data.
8. Review service-area, citation, page-coverage and conversion gaps.
9. When Business Profile and Analytics connections are ready, run a fresh connected-data refresh.
10. Re-import the private workspace schema and test `syncIkonSEOLocalGrowth` with `{"command":"read"}`.

## Database upgrade

Database component 17.0 adds:

- `ikon_seo_local_review_tasks`
- `ikon_seo_local_prominence`
- `ikon_seo_local_conversions`

Existing profiles, strategies, workflows, Page Plans, Project History, Search Console, Analytics, drafts and research evidence are preserved.

## Safety

- Review text is not stored by the Local Growth module.
- Review replies are not sent by Local Growth.
- Public profile changes remain separately staged and administrator-approved.
- Citation corrections are recommendations only.
- Location pages are not created automatically.
- Readiness is not a ranking score.
