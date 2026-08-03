# Page-Plan Queue

The page-plan queue separates planning from content generation. It does not contain an workflow/model API and cannot write pages while the private operator workspace is closed.

## CSV columns

| Column | Required | Purpose |
|---|---:|---|
| `keyword` | Yes | Primary focus keyword |
| `service` | No | Service or topic context |
| `location` | No | Genuine target location |
| `page_type` | No | `service`, `location`, `article`, `collection`, `tool`, `profile`, `about`, `contact`, or `howto` |
| `language` | No | One of the active profile languages |
| `template_hint` | No | Suggested component/layout direction |
| `desired_slug` | No | Proposed WordPress slug |
| `source_page_id` | No | Existing page ID for improvement mode |
| `priority` | No | `0`–`100`; defaults to `50` |

## Connected workflow

1. List `planned` items.
2. Claim one item.
3. Generate a complete page within the one-hour claim. Expired claims return to `planned` automatically.
4. Return the claim token and page payload.
5. Ikon SEO enforces the planned profile, focus keyword, language, page type, slug and improvement target.
6. The normal duplicate, media, schema and quality checks run.
7. A successful item receives the created draft ID.

Failed items retain a sanitized error and can be reset by an administrator. Completed and failed items can be archived without deleting their audit trail.

## Quality rule

A row is a plan, not permission to create a thin doorway page. Location and service pages must have distinct search intent, genuine business relevance, accurate claims, useful page-specific information and human review.
