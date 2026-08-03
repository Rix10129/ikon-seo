# Search Intelligence

Ikon SEO 0.10.0 adds a persistent Search Console page-query evidence database.

## What is stored

For each stored period, Ikon SEO saves final Search Console rows using these dimensions:

- Query
- Page
- Country
- Device

Each row includes clicks, impressions, CTR and average position. The current comparison period and the immediately preceding period are saved separately. Older snapshots are retained for future trend analysis.

Search Console may omit anonymized and low-volume rows. The plugin reports this limitation and never presents the stored database as a complete record of every search.

## Query clusters

Related queries are grouped locally using normalized meaningful tokens. The clustering system removes common filler words and applies lightweight term normalization. It does not call a third-party language service.

Clusters help identify:

- Commercial topic demand
- Supporting query variations
- Leading pages by topic
- Fragmented visibility across several URLs

Clustering is a planning aid, not proof that every query has identical intent.

## Cannibalisation evidence

A query is flagged only when more than one internal URL receives meaningful impressions and the secondary URL has a material share of visibility, close average positions or a change in the leading URL between periods.

Classifications include:

- Partial overlap
- Strong cannibalisation
- URL switching

Ikon SEO does not automatically merge or redirect pages. Search intent, current results, content differences, backlinks and business goals must be reviewed first.

## Striking-distance opportunities

Query-page pairs with meaningful impressions and an average position between 8 and 20 are prioritised for focused research. Recommendations emphasise intent comparison, differentiated evidence and contextual internal links rather than arbitrary word-count increases.

## Content decay

Pages are compared with the immediately preceding period. A decay signal is created when impressions decline beyond the configured threshold and the previous period contains enough evidence.

A decline may reflect seasonality, SERP changes, technical changes, competition, demand or internal overlap. It is a review trigger, not a confirmed diagnosis.

## Data handling

- OAuth access remains read-only.
- Stored rows remain in the WordPress database.
- Writes use batched database operations.
- Existing period data is replaced inside a transaction where supported.
- A local maximum-row setting prevents unlimited imports.
- Old snapshots are pruned to a bounded history.
