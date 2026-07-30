# Local SEO Operations

## Location types

| Type | Physical address entity | Typical use |
|---|---:|---|
| Storefront | Only when verified | Customers visit the business |
| Hybrid | Only when verified | Customers visit and the business also travels |
| Service area | Never | The business travels to customers |
| Online only | Never | No eligible in-person location |

Create a separate record for each genuine operational location. Do not create records for target cities that are only marketing areas.

## Location-page payload

Add a `local` object to the normal page payload:

```json
{
  "local": {
    "location_id": 12,
    "page_kind": "service_area",
    "target_area": "Example City",
    "services": ["Genuine Service"],
    "unique_local_details": [
      "Verified service availability fact",
      "Verified travel or appointment fact",
      "Useful area-specific customer information"
    ],
    "directions": "",
    "parking": "",
    "landmarks": [],
    "map_url": ""
  }
}
```

Use `verified_location` only for an active verified storefront or hybrid location. Use `service_area` only for a service-area or hybrid record, without an invented office.

## Approval blockers

The local quality report can block a merge when:

- the location is missing, inactive, foreign to the active profile or not suitable for the selected page kind;
- a verified landing page lacks a matching name, phone, address or map;
- services or three genuine local details are missing;
- schema claims a location that is not verified; or
- the page is too similar to another managed local page.

Similarity is a warning signal, not a substitute for human review. Editors should confirm that every location page solves a distinct customer need.

## NAP, citations and UTMs

The NAP audit compares each location’s master record with its assigned landing page and stored schema. It does not crawl every external directory.

The citation register tracks known directory URLs, the NAP used, status, owner, correction work and review date. Import/export is administrative; the plugin does not submit citations automatically.

The UTM builder permits only the current WordPress hostname. Recommended conventions:

- Business Profile website: `utm_source=google&utm_medium=organic&utm_campaign=google-business-profile`
- Google Post: add a short `utm_content` identifying the approved post
- Appointment link: use a distinct campaign or content value

## Local rank observations

Record or import legitimate observations from an approved rank provider. The plugin does not scrape Google and does not claim that Search Console or Business Profile APIs provide geo-grid local-pack rankings.
