# Visibility & Brand Intelligence fixtures

## Fixture 1 — own-brand sampled citation

Input:

- Type: answer engine
- Query: office cleaning services in Doha
- Brand role: connected brand
- Status: cited
- Valid HTTPS source URL
- Valid cited URL on the connected website
- Confidence: medium

Expected:

- Observation is stored.
- Connected-brand citation count increases.
- The observation is labelled as sampled evidence, not complete coverage.

## Fixture 2 — competitor citation gap

Input:

- Same commercial query
- Connected brand observation: absent
- Competitor observation: cited
- Competitor domain supplied

Expected:

- Absence gap is reported.
- Competitor comparison includes the competitor.
- No page or outreach task is created automatically.

## Fixture 3 — external unlinked mention

Input:

- Valid external mention URL
- Linked: false
- Relevance: 80
- Sentiment: positive

Expected:

- Mention is stored as an unlinked opportunity.
- Opportunity priority is elevated.
- No outreach is sent.

## Fixture 4 — linked mention

Input:

- Valid external mention URL
- Target URL belongs to the connected website

Expected:

- Mention is classified as linked.
- It is excluded from the unlinked-opportunity queue.

## Fixture 5 — self-mention rejection

Input:

- Mention URL belongs to the connected website

Expected:

- Record is rejected as not being an external brand mention.

## Fixture 6 — invalid competitor observation

Input:

- Brand role: competitor
- No competitor name or domain

Expected:

- Record is rejected.

## Fixture 7 — evidence coverage

Input:

- Brand name configured
- Aliases and competitors configured
- Search data present
- Local data present
- Authority data present
- Five observations and three mentions stored

Expected:

- Evidence coverage reports strong completeness.
- The interface explicitly states that coverage is not a ranking score.

## Fixture 8 — command-centre snapshot

Expected:

- Read-only agency snapshot includes counts for observations, mentions, unlinked mentions and competitors.
- Snapshot contains no full article content or credentials.
- Unlinked mentions can generate a portfolio alert.
