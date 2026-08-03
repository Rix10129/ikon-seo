# Ikon SEO Installation

## Install or upgrade

1. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
2. Upload the Ikon SEO ZIP.
3. When upgrading, choose **Replace current with uploaded**.
4. Open **Ikon SEO → Website Profile** and confirm the business details.
5. Open **Site Inventory** to scan the website.
6. Continue with **Local SEO** and **Page Plans**.

Google Business Profile, Search Console and Workflow are optional. They do not block the local website audit and planning tools.

## Optional workflow developer integration

Ikon SEO does not include a built-in content generation service. Use **Workflow → Workflow access settings** only when a compatible external workflow has already been configured.

The advanced section provides:

- A dynamic OpenAPI schema URL
- Short-lived one-time pairing codes
- Developer keys stored as password hashes
- Read and draft scopes

Creating a pairing code alone does not connect the private operator workspace. The external workflow must exchange the code and then call the health endpoint.

## Safe defaults

- Draft-only writes are enabled.
- Profile matching prevents cross-site writes.
- Live publishing and remote merge are disabled by default.
- Improvement drafts remain separate until administrator approval.
- Rollback snapshots are retained before an approved merge.
