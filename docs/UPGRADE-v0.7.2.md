# Ikon SEO v0.7.2

This release makes the workspace schema compatible with the private workspace action editor by limiting the public OpenAPI document to 29 focused operations.

Excluded from the workspace schema: pairing, profile export, domain migration, media import, UTM builder, and Google Business Profile operations. The WordPress features remain available in the plugin; they are simply not exposed in this single action schema.

Use the same schema URL after upgrading:

`https://YOUR-DOMAIN/wp-json/ikon-seo/v1/openapi`
