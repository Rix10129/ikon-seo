# Project History and account continuity

Ikon SEO 0.8.1 stores project continuity inside WordPress rather than relying on a conversation history.

## What is stored

- Audits and research summaries
- Recommendations and open next steps
- Page-plan decisions
- Drafts created or updated
- Approvals, changes and manual notes
- Related WordPress page IDs

## Private workspace action

Use `syncIkonSEOProjectHistory`:

- Send `{}` to load the current state.
- Include `event` to save one project event and receive the refreshed state.
- Include `status_update` to mark an existing item open, completed or dismissed.

Recommended startup sequence:

1. Read Website Profile.
2. Read Site Inventory.
3. Call `syncIkonSEOProjectHistory` with `{}`.
4. Read open Page Plans.
5. Continue from the last completed step and pending items.

## Moving to another account

The project history remains on the website. In the new account:

1. Create a private workspace.
2. Import the same OpenAPI schema URL.
3. Generate a fresh workflow key in WordPress.
4. Configure Bearer authentication with the new key.
5. Test connection, Website Profile and Project History.
6. Revoke the old key after the new connection is confirmed.

The downloadable transfer guide never contains the workflow key.
