# Strategy & Website Modes

Ikon SEO v0.14.0 adds a site-wide strategy layer so audits, priorities, research and drafts are guided by the website's real business model rather than a universal checklist.

## Operating modes

### Local Business

Prioritises services, genuine locations and service areas, verified business facts, local proof, reviews, citations and measurable lead actions.

### Editorial / Blog

Prioritises a defined audience, topic hubs, useful original information, authorship, sourcing, content freshness, internal links and responsible monetisation.

### Ecommerce

Provides the strategy foundation for products, categories, commercial intent, conversion events, trust policies, product data and revenue measurement.

### Hybrid Business + Publisher

Connects commercial pages with supporting editorial hubs. Articles must have a defined audience purpose and a logical relationship to the website's offers.

## Strategy readiness

Readiness identifies missing inputs across:

- Website identity
- Audience and positioning
- Priority services, products or topics
- Conversions and success metrics
- Editorial and evidence standards
- Ownership and capacity
- Mode-specific requirements
- Search Console and Analytics evidence

Readiness is not a ranking score. It describes whether the system has enough strategic context to make responsible recommendations.

## Quality gate

Every mode shares core rules:

- Match the correct user need and search intent.
- Add material value beyond existing website content.
- Do not invent business facts, reviews, credentials, prices or outcomes.
- Support claims according to the active evidence policy.
- Use a relevant conversion path and measurement plan.
- Require human review before publication or destructive changes.

Mode-specific requirements are added for local proof, editorial authorship and disclosures, or ecommerce product and policy accuracy.

## Automation policy

Ikon SEO can automate evidence collection, diagnosis, research summaries, briefs, suggestions, draft creation, reports and alerts according to the selected level.

The following always require approval:

- Publishing or direct live edits
- Redirects, canonicals, noindex or deletion
- Business and schema claims
- Business Profile changes or review replies
- Outreach and external link actions

## Private workspace action

`syncIkonSEOWebsiteStrategy` accepts an empty object or `{ "save": false }` to read the current strategy.

A strategy is updated only when `save` is true and a `strategy` object is supplied. The connection key must include draft access.
