# Competitor & Content Intelligence

Ikon SEO v0.12.0 stores short, auditable competitor observations and compares them with WordPress pages. It is designed to answer:

- What search intent appears dominant for the target query?
- Which page formats recur among reviewed results?
- Which topics and entities recur across competitors but are not present on the connected page?
- Which proof and conversion patterns deserve factual review?
- What must the page do differently instead of copying competitors?

## Evidence sources

Accepted evidence sources are:

- Current manual web research
- A compatible connected research workflow
- A licensed search-result or competitor-data provider
- A controlled import prepared by an administrator

The plugin does **not** automate Google Search queries and does not download or copy competitor page content.

## Stored competitor observations

Each observation can include:

- Search query
- Observed date
- Search intent
- Result type
- Page URL, title, description and main heading
- Section headings
- Topics and entities
- Proof and trust patterns
- Conversion patterns
- Search-result features
- Factual evidence notes
- Differentiation notes

Only short observations should be stored. Do not paste complete competitor articles, proprietary text or unsupported claims.

## Page briefs

A page brief compares one WordPress page with stored observations for one query. It records:

- Target and inferred page intent
- Dominant stored result type
- Intent alignment
- Competitor evidence count
- Evidence confidence
- Recurring-topic coverage
- Missing recurring topics and entities
- Proof and conversion patterns
- Direct evidence
- Inferred hypotheses
- Differentiation requirements
- Limitations

A recurring topic is not automatically required. It must be factually relevant, useful to the visitor and consistent with the business.

## Topical coverage map

The topic map combines:

- Search Console query clusters
- Their leading pages and visibility
- Stored page briefs
- Gap priority and intent

The map helps identify unsupported topics and weak page coverage. It is not a command to create a page for every keyword.

## Connected workflow

The focused schema exposes `syncIkonSEOCompetitorContentIntelligence`.

Call it with an empty object to read the current state:

```json
{}
```

Store reviewed observations:

```json
{
  "research": [
    {
      "query": "office cleaning services doha",
      "url": "https://example-competitor.com/office-cleaning/",
      "intent": "local_service",
      "result_type": "service_page",
      "title": "Office Cleaning Services",
      "headings": ["What is included", "Our cleaning process"],
      "topics": ["daily office cleaning", "deep cleaning", "custom schedule"],
      "trust_elements": ["real project photos", "staff training details"],
      "conversion_elements": ["request a quote"],
      "source": "connected_research",
      "observed_at": "2026-08-02"
    }
  ]
}
```

Create a stored page brief:

```json
{
  "analyse": {
    "post_id": 123,
    "target_query": "office cleaning services doha",
    "intent": "local_service"
  }
}
```

Research writes require the draft scope. The action does not modify page content.

## Safety requirements

- Never represent competitor claims as facts about the connected business.
- Never copy competitor paragraphs or distinctive wording.
- Never invent reviews, credentials, service areas, prices or guarantees.
- Refresh research before major rewriting, merging or redirecting decisions.
- Treat frequency as evidence of possible user expectations, not a ranking factor.
- Separate direct observations from hypotheses.
