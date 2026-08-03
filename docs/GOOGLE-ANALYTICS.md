# Google Analytics 4 integration

Ikon SEO can connect to a Google Analytics 4 property in read-only mode. Analytics is optional and supports behavioural and conversion evidence; it is not treated as a direct Google ranking signal.

## Data used

The integration can read:

- Sessions
- Active users
- Engaged sessions and engagement rate
- Views
- Key events
- Average session duration
- Landing page plus query string
- Current-period and previous-period comparisons

Reports are cached for six hours to reduce API usage. Ikon SEO makes four report requests for a normal refresh: current totals, previous totals, current landing pages and previous landing pages.

## Google Cloud setup

1. Use the same Google Cloud project as Search Console, or create a dedicated project.
2. Enable **Google Analytics Data API**.
3. Enable **Google Analytics Admin API**.
4. Create an OAuth 2.0 Client ID of type **Web application**.
5. Copy the callback URL shown in **Ikon SEO → Analytics** into the client's authorised redirect URIs.
6. Paste the Client ID and Client Secret into Ikon SEO, or select the option to reuse the saved Search Console OAuth client.
7. Connect Google Analytics, choose the correct GA4 property and refresh the report.

The Google account used for connection must have access to the selected Analytics property.

## Privacy and safety

- OAuth refresh tokens are encrypted using the plugin's credential storage.
- The integration requests the read-only Analytics scope.
- No Analytics settings, events or property configuration are changed.
- No secret is included in profile exports or workspace transfer guides.
- Client administrators cannot view credentials unless Agency Access permits it.

## Limitations

Analytics data can show traffic, engagement and conversion patterns, but it cannot prove why Google ranks a page. Ikon SEO combines Analytics with crawl evidence, Search Console and WordPress data and labels conclusions as direct facts or inferred hypotheses.

Official references:

- https://developers.google.com/analytics/devguides/reporting/data/v1
- https://developers.google.com/analytics/devguides/config/admin/v1/rest/v1beta/accountSummaries/list
