=== Ikon SEO ===
Contributors: ikondigitals
Tags: seo, local-seo, google-business-profile, elementor, schema, workflow
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 0.6.0
License: GPLv2 or later

Universal SEO publishing, Local SEO controls, Google Business Profile reporting, structured drafts and approval-first improvements.

== Description ==

Ikon SEO provides a secure connected workflow for creating and improving WordPress pages.

Features include:

* Universal website setup with no accounting or location defaults.
* Industry-aware business entity and schema policies.
* Profile fingerprints that prevent cross-client page writes.
* Profile import and export without connection secrets.
* Elementor and Gutenberg page support.
* Rank Math and Yoast metadata support.
* Allow-listed Service, page, article, person, collection, application, video, breadcrumb and profile-controlled business schema.
* Website inventory, orphan detection, keyword-overlap checks and internal-link discovery.
* SEO quality and high-trust content review reports.
* Media Library search and protected remote image imports.
* Separate improvement drafts, comparisons, safe merge and rollback snapshots.
* Read-only domain-migration preview and administrator-only application.
* One-time pairing codes, hidden connection keys, scoped access, rate limits and idempotency.
* Read-only Google Search Console performance, sitemap and URL inspection.
* Encrypted OAuth credentials with state and PKCE protection.
* CSV page-plan queue with profile binding and expiring claim locks.
* Scheduled content review dates and recommendation-only performance monitoring.
* Multi-location records for storefront, hybrid, service-area and online-only businesses.
* NAP consistency reports across assigned pages, visible content and generated schema.
* Verified-location and service-area page rules with doorway-page similarity protection.
* Location-aware LocalBusiness schema, service-area safeguards and local quality scoring.
* Same-site UTM builder, citation register and manual/imported local-rank observations.
* Optional Google Business Profile accounts, locations, reviews and performance reports.
* Draft-only review replies and Google Posts with exact administrator approval.

== Installation ==

1. Upload and activate the plugin.
2. Complete Ikon SEO → Website Profile.
3. Keep draft-only and profile-bound writes enabled.
4. Open Connection and click Connect Ikon SEO.
5. Enter the temporary pairing code in the approved Ikon SEO workflow.
6. Scan the website, then create and review drafts.

== Frequently Asked Questions ==

= Is AccountingService used on every website? =

No. It is available only when the active profile is an accounting business. Other industries receive their own curated schema policy.

= Can I move the plugin to another website? =

Yes. Install the same plugin and create or import a separate profile. Connection keys are never exported.

= Does it publish automatically? =

Draft-only mode is enabled by default. Improvement mode always creates a separate review draft.

= Does the CSV queue write hundreds of pages automatically? =

No. It stores approved page plans. A connected workflow must research and return each complete page while ChatGPT/Codex is active. Every page still passes the normal checks and becomes a draft.

= Can Ikon SEO request Google indexing? =

No. Search Console access is read-only. URL Inspection reports the indexed version known to Google; it does not request indexing or guarantee inclusion.

= Can it update a live page safely? =

An administrator can compare and merge an approved improvement into the original page while preserving its page ID and URL. A rollback snapshot is created first.

= Does a service-area page receive a LocalBusiness address? =

No. Only an active, verified storefront or hybrid location can generate a location entity and address. A service area records the places served without pretending that an office exists there.

= Can it post or answer reviews automatically? =

No. The connected workflow can prepare a draft, but only a WordPress administrator can approve the exact saved text. Remote approval, automatic replies and unattended Google Posts are not provided.

= Is Google Business Profile access truly read-only? =

Google provides the broad `business.manage` OAuth permission. Ikon SEO enforces a read-only operating mode inside the plugin and uses that permission for a write only after a WordPress administrator approves an exact saved draft.

== Changelog ==

= 0.6.0 =

* Added a simple Connect Ikon SEO workflow with short-lived one-time pairing codes.
* Hidden permanent API keys and OpenAPI details under Advanced connection settings.
* Added real connection verification based on successful authenticated activity.
* Added Test website API, Reconnect, Disconnect and Scan website actions.
* Added pairing expiry countdowns and clearer setup status messaging.
* Added connection, pairing and API-test activity logs.
* Improved Settings and Activity feedback and made the tab navigation wrap reliably.
* Preserved read-and-draft default scopes, profile binding and live-page protections.

= 0.5.0 =

* Added profile-bound multi-location and service-area records.
* Added NAP consistency checks and location-to-landing-page assignments.
* Added verified-location and service-area page validation.
* Added doorway-page similarity protection and local quality blocking.
* Added profile-controlled local schema with verified-address safeguards.
* Added citation management, CSV import/export and correction tracking.
* Added same-site UTM generation and manual/CSV local ranking observations.
* Added optional Google Business Profile OAuth, account/location linking and comparison reports.
* Added cached reviews and Business Profile performance reporting.
* Added exact-text review-reply and Google Post drafts with administrator-only approval.
* Added review alerts without permanently storing third-party review content.
* Preserved universal profiles, Search Console, queues, monitoring, builders, SEO integrations and rollback safeguards.

= 0.4.0 =

* Added encrypted read-only Google Search Console OAuth.
* Added equal-period performance comparisons, top queries and top pages.
* Added indexed-version URL inspection and sitemap status reads.
* Added CSV page-plan import with profile binding and duplicate protection.
* Added one-hour claim tokens and validated queue completion.
* Added scheduled review dates and recommendation-only refresh monitoring.
* Added monitoring thresholds, manual refresh controls and new connected endpoints.
* Preserved v0.3 universal profiles, schema policy, builders, SEO integrations and approval safeguards.

= 0.3.0 =

* Added universal Website Profiles and profile-bound writes.
* Removed fresh-install ZeroSync, Dubai, UAE and accounting defaults.
* Added industry-aware dynamic business entity schema.
* Added profile import/export and domain migration safeguards.
* Added Gutenberg and Yoast support.
* Added schema preview and active-profile conflict warnings.
* Preserved v0.2 audit, media, comparison, merge, rollback and security features.

= 0.2.0 =

* Added schema policy, inventory, quality reports, media support, safe merge and rollback.

= 0.1.0 =

* Initial secure draft creation and improvement-copy release.
