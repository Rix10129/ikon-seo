# Ikon SEO v0.6 — Installation and Upgrade

## Requirements

- WordPress 6.4 or newer
- PHP 7.4 or newer
- HTTPS for connected workflows
- Elementor or the WordPress block editor
- Rank Math or Yoast is recommended

## Install or upgrade

1. Back up the website and database.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Upload `ikon-seo-v0.6.0.zip`.
4. Choose **Replace current with uploaded** when upgrading.
5. Activate Ikon SEO.
6. Open **Ikon SEO → Website Profile**.
7. Complete the business identity, industry, entity, locations, language, currency, builder, SEO plugin and design rules.
8. Save the profile.
9. Keep draft-only mode and profile-bound writes enabled.
10. Generate a new connection key after the profile is complete.

Upgrading from v0.5 keeps the active Website Profile, connection settings, logs, drafts, queue, Search Console configuration and rollback history. v0.6 keeps the v0.5 profile-bound location, citation, local-rank and Google Business Profile draft tables and adds one-time pairing state. It does not create locations or connect Google automatically.

## Profile-first connection

Use the dynamic schema URL shown under **Ikon SEO → Connection**. It uses the installed website URL and plugin version automatically.

Every connected workflow must:

1. Call `checkIkonSEOConnection`.
2. Call `readIkonSEOWebsiteProfile`.
3. Retain the returned `profile_id`.
4. Inspect existing pages and inventory.
5. Include that exact `profile_id` with page or schema-preview requests.

If the business name, business URL, industry or entity type changes, the profile ID changes, remote actions pause and the old key is revoked.

## First safe test

1. Run a read-only inventory.
2. Preview schema for one planned service page.
3. Create one new draft.
4. Confirm the selected builder can edit it.
5. Confirm Rank Math or Yoast metadata.
6. Confirm the schema graph uses the active profile entity.
7. Create one improvement draft.
8. Compare it with the original.
9. Merge only after review and keep the rollback snapshot.

## Local SEO setup

1. Open **Ikon SEO → Settings** and enable the Local SEO module.
2. Open **Ikon SEO → Local SEO**.
3. Create one record for each genuine storefront, hybrid location, service-area operation or online-only business unit.
4. Mark a physical address as verified only after the business has confirmed it.
5. Assign a published landing page only when that page represents the same location.
6. Run the NAP audit.
7. Import known citations or add them manually.
8. Use the UTM builder for the Business Profile website, appointment and post links.

Service-area and online-only records never generate a physical-address entity. A page payload using `local` must identify at least one genuine service and three genuine local details.

## Optional Search Console setup

1. Open **Ikon SEO → Search Console**.
2. Copy the displayed redirect URI.
3. In Google Cloud, enable the Search Console API and create an OAuth Web application.
4. Add the exact redirect URI to the OAuth client.
5. Save the client ID and secret in Ikon SEO.
6. Connect Google and select the exact domain or URL-prefix property.
7. Refresh the first 28-day performance report.

Ikon SEO requests only `webmasters.readonly`. It does not use the general Indexing API or promise indexing.

## Optional page-plan queue

Open **Ikon SEO → Page Plans** and upload a CSV using:

`keyword,service,location,page_type,language,template_hint,desired_slug,source_page_id,priority`

The queue stores approved plans only. A connected ChatGPT/Codex workflow must claim a plan, generate a complete page, and return it within one hour. The normal profile, duplicate, schema and quality checks still run before a draft is created.

## Domain changes

Use **Ikon SEO → Domain Tools**. Preview the old and new URL before applying anything. Applying a confirmed migration:

- updates only matched stored references;
- saves up to three domain-migration snapshots per affected page;
- clears generated Elementor CSS;
- updates the stored website URL;
- pauses remote actions; and
- revokes the connection key; and
- clears encrypted Search Console credentials because their encryption is site-URL bound.

No domain migration runs automatically.

## Optional Google Business Profile setup

See `docs/GOOGLE-BUSINESS-PROFILE.md`. Access requires a Google Cloud project approved for the Business Profile APIs. Connect one Google account, select an account, then explicitly link each remote location to the correct Ikon SEO location record.

The plugin starts in a policy-enforced read-only mode. Connected workflows can read reports and stage drafts. Only a WordPress administrator can approve the exact stored review reply or Google Post.
