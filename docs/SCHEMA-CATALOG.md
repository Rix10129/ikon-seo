# Ikon SEO v0.6 Schema Policy

Ikon SEO uses an allow-listed schema graph. The active Website Profile—not the page request—controls the business entity.

## Page-level types

| Page purpose | Types |
|---|---|
| Standard page | `WebPage` |
| About page | `AboutPage` |
| Contact page | `ContactPage` |
| Service page | `WebPage`, `Service` |
| Hub page | `CollectionPage`, `ItemList`, optional `OfferCatalog` |
| Article | `WebPage`, `Article` or `BlogPosting` |
| Expert profile | `ProfilePage`, `Person` |
| Calculator or tool | `WebPage`, `WebApplication` |
| Guide | `WebPage`, semantic `HowTo` |
| Original video | `VideoObject` |
| Visible hierarchy | `BreadcrumbList` |
| Visible FAQs | Optional semantic `FAQPage` |

`FAQPage` and `HowTo` are semantic options, not promises of Google rich results.

## Profile entity types

The setup screen provides curated policies for accounting, finance, legal, real estate, therapy and healthcare, dental, driver and transport, home services, automotive, travel, retail, ecommerce, restaurant, hotel, education, nonprofit, recruitment, marketing and software websites.

Examples:

- Accounting profile: `Organization`, `LocalBusiness`, `AccountingService`, or `FinancialService`
- Therapy and healthcare: `Organization`, `LocalBusiness`, `MedicalClinic`, or `HealthAndBeautyBusiness`
- Driver and transport: `Organization` or verified `LocalBusiness`
- Real estate: `Organization`, `LocalBusiness`, or `RealEstateAgent`
- Ecommerce: `Organization`, `OnlineStore`, or `Store`

An accounting request is rejected when the active profile is not an accounting business.

## Entity rules

- The entity node must be enabled in the Website Profile.
- A local-business subtype requires a real, verified physical address.
- Service-area pages do not become office locations.
- The request may supply entity description and area served, but cannot change its type.
- Rank Math global organization data is reused by stable ID.
- Yoast core page/entity nodes are not duplicated by the fallback graph.
- Self-controlled review stars are not generated.

## Location entity rules

An individual landing page can receive a location entity only when all of the following are true:

- the Local SEO module is enabled;
- the request uses `local.page_kind: verified_location`;
- `local.location_id` belongs to the active Website Profile;
- the record is active and classified as `storefront` or `hybrid`;
- its address is verified and complete; and
- its configured entity type is allowed by the Website Profile industry policy.

The location node can include `PostalAddress`, `GeoCoordinates`, opening hours, phone, map URL, image, price range, social profiles and appointment action when those fields are verified and visible. The page `Service` provider points to the location node, and the location node can point to the parent organization.

Service-area and online-only records may contribute `areaServed` to a page or service, but they never produce a fake address, coordinates or location entity. Rank Math stable IDs and type checks continue to prevent duplicated entity nodes.

## Request shape

```json
{
  "schema": {
    "page_type": "service",
    "service": {
      "name": "Example Service",
      "service_type": "Example Service",
      "area_served": ["Verified Area"]
    },
    "business_entity": {
      "name": "Business Name",
      "area_served": ["Verified Area"]
    }
  },
  "local": {
    "location_id": 12,
    "page_kind": "verified_location",
    "services": ["Example Service"],
    "unique_local_details": [
      "Customer entrance is on Example Street",
      "Appointments are available on weekday mornings",
      "The nearest public landmark is Example Station"
    ],
    "map_url": "https://www.google.com/maps/place/VERIFIED-PLACE"
  }
}
```

`business_entity.type` is unnecessary and cannot override the active profile.
