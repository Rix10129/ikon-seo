# Google Search Console Setup

Ikon SEO v0.6 uses OAuth 2.0 with this read-only scope:

`https://www.googleapis.com/auth/webmasters.readonly`

## Google Cloud configuration

1. Open Google Cloud Console and select or create a project.
2. Enable the **Google Search Console API**.
3. Configure the OAuth consent screen for the account or organization that will use the plugin.
4. Create an **OAuth client ID** with application type **Web application**.
5. In WordPress, open **Ikon SEO → Search Console**.
6. Copy the exact **Authorized redirect URI** displayed there.
7. Add that URI to the Google OAuth client.
8. Copy the client ID and client secret into Ikon SEO and save them.
9. Click **Connect Google Search Console**.
10. Approve read-only access and select the correct property.

Domain properties use a value such as `sc-domain:example.com`. URL-prefix properties use the full prefix, including scheme and trailing slash.

## Data behavior

- Performance compares equal periods and ends three days ago to reduce partial-data effects.
- Search Console can omit anonymized and low-volume query rows.
- URL Inspection reports the version currently in Google's index; it is not a live URL test.
- Sitemap access is read-only.
- Ikon SEO does not use Google's Indexing API.

## Credential behavior

Client secrets and refresh tokens are encrypted on the WordPress site. They are excluded from profile exports, connected responses and logs. If WordPress authentication salts or the website domain change, reconnect Search Console.
