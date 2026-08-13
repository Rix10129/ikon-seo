# Universal Client Onboarding Contract

The goal of onboarding is to collect only information the system cannot safely derive, then reuse it everywhere.

## Target operator flow

`Install once -> Connect once -> Confirm facts -> Scan -> Review architecture -> Build batch`

No repeated plugin installation should be required for normal site production.

## Automatically derived where reliable

- canonical site/home URL
- WordPress locale
- CMS/runtime version
- active theme
- page builder(s)
- SEO plugin(s)
- published/draft URL inventory
- post types
- existing titles/slugs/H1 where readable
- existing metadata/schema where readable
- internal-link graph
- known forms/contact destinations where technically discoverable
- Search Console/Analytics connection state
- local/GBP connection state
- existing Ikon-managed drafts/proposals/history

Derived facts must record source and timestamp.

## Confirm once / manual-sensitive facts

- public brand name
- industry/business model when ambiguous
- primary market(s)
- service geography
- public phone
- public email
- WhatsApp destination
- legal entity name
- legal jurisdiction
- verified office/address status
- approved credentials/certifications
- approved pricing claims
- approved review/testimonial use
- proof permission policy
- preferred conversion routes

Unknown is valid and must not be replaced with an invented default.

## Initial scan output

The first scan should produce one concise dashboard:

- Website Profile completeness
- site inventory count
- page-role distribution
- commercial/revenue-page opportunities
- duplicate/cannibalisation risks
- cleanup/demo/placeholder risks
- local/GBP state
- analytics/search evidence state
- production readiness
- recommended first campaign

## Architecture approval

Before generation, Agency Hub presents a page ownership plan with:

- URL
- page role
- primary intent
- secondary cluster
- action
- reason
- existing source page where applicable
- priority
- risk/uncertainty

Only exceptions and high-impact conflicts should require operator decisions.

## Ongoing updates

When facts or runtime configuration changes, the Site Agent records the change and marks dependent plans/drafts stale when appropriate.

Examples:

- phone/WhatsApp changed -> affected CTAs require refresh
- SEO plugin changed -> schema/metadata compatibility review
- theme/builder changed -> visual/runtime recheck
- source live page changed after snapshot -> controlled update blocked until resnapshot
- service area changed -> local plans reassessed

## Multi-client isolation

Every plan, fact, research packet, proof item and production payload is tied to a website/profile identity. Cross-site portfolio learning may use anonymised validated patterns, but raw client data is not reused on another site.

## Success criterion

A normal new-client onboarding should require one short fact-confirmation pass and one architecture approval pass, not page-by-page setup.
