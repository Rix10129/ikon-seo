# Visibility & Brand Intelligence

Ikon SEO v0.19.0 adds a customer-safe evidence layer for understanding how a brand appears across organic search, local search, editorial coverage, directories, video, social sources and sampled answer-engine responses.

## What it stores

### Visibility observations

Each reviewed observation may include:

- Environment or source type
- Query or question
- Connected brand, competitor or neutral-source role
- Mentioned, cited, absent or unclear status
- Cited URL
- Supporting source URL
- Sentiment
- Observed prominence
- Evidence note
- Confidence
- Observation date

### Brand mentions

Each reviewed mention may include:

- External mention URL
- Source domain
- Mention type
- Linked or unlinked status
- Linked target URL
- Sentiment
- Relevance
- Source-strength context
- Workflow status
- Review notes

## Important limitations

- Sampled answer-engine observations do not represent every generated answer, account, location or time.
- An unlinked mention is a research lead, not automatic permission to contact the publisher.
- Sentiment and source strength require human verification.
- Evidence coverage is a data-completeness measure, not a ranking or market-share score.
- Ikon SEO does not scrape search engines automatically.
- Ikon SEO does not publish, build links, send outreach or post reputation responses automatically.

## Recommended workflow

1. Configure the primary brand name and verified aliases.
2. Add the competitors that matter to the website strategy.
3. Store representative observations for important commercial and informational queries.
4. Store verified external brand mentions.
5. Review unlinked mention and reputation opportunities.
6. Compare competitor mentions and citations by source and query.
7. Refresh a combined snapshot.
8. Add approved relationship, correction, content or promotion tasks to Workflow Automation.
9. Recheck outcomes on a defined schedule.

## Private workspace action

The focused action is:

`syncIkonSEOVisibilityBrandIntelligence`

Supported commands:

- `read`
- `save_observations`
- `save_mentions`
- `update_mention`
- `refresh_snapshot`

All stored evidence should be concise and traceable to a reviewed source.
