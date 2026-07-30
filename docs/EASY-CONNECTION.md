# Easy connection in Ikon SEO v0.6

The normal setup no longer requires a website owner to copy an OpenAPI URL, choose an authentication header or expose a permanent API key.

## Website-owner flow

1. Complete the Website Profile.
2. Open **Ikon SEO → Connection**.
3. Click **Connect Ikon SEO**.
4. Copy the temporary code and enter it in the approved Ikon SEO workflow with the website address.
5. The workflow exchanges the code once and then verifies the connection through `/health`.
6. Return to WordPress and continue to **Scan website**.

The code contains eight unambiguous letters and numbers, is valid for ten minutes and is deleted after one successful exchange.

## Pairing endpoint

`POST /wp-json/ikon-seo/v1/pair`

Example body:

```json
{
  "code": "ABCD-2345"
}
```

A successful response returns the dynamic schema URL, the hidden connection key, the required header name and the allowed scopes. The response must not be logged or cached by the client.

## Connection states

- **Not connected:** no active key exists.
- **Waiting for pairing:** a key exists but no authenticated request has verified the workflow.
- **Connected:** the plugin has received a valid authenticated request.

## Advanced mode

Developers can still generate a traditional key and use `X-Ikon-SEO-Key`. Replacing or revoking a key immediately invalidates the previous connection.
