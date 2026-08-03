# Search Intelligence fixture expectations

A deterministic fixture should verify:

- Two pages sharing at least 25% of a query's impressions with positions within eight places are classified as strong overlap.
- A change in the leading URL between periods is classified as URL switching when both URLs retain meaningful visibility.
- Query-page pairs between positions 8 and 20 with sufficient impressions become striking-distance opportunities.
- A page whose impressions decline beyond the configured threshold becomes a decay signal only when the previous period has sufficient evidence.
- “office cleaning services doha” and “office cleaners in doha” normalize into the same practical cluster.
- Branded and non-branded totals remain separated.
