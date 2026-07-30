# Ikon SEO

Ikon SEO is an approval-first WordPress SEO workflow plugin for creating, auditing and improving structured pages without directly changing live content.

## Current release

**v0.6.0** introduces a simplified connection experience:

1. Complete the Website Profile.
2. Click **Connect Ikon SEO**.
3. Enter the short-lived pairing code in the approved workflow.
4. Scan the website.
5. Create and review drafts.

Permanent credentials and OpenAPI details remain available only under **Advanced connection settings**.

## Safety defaults

- External writes are saved as drafts.
- Improvement mode creates a separate review copy.
- Profile IDs prevent cross-client writes.
- Pairing codes expire after ten minutes and work once.
- Permanent connection keys are stored as password hashes.
- Live page publishing and remote merge remain disabled by default.

## Development

The plugin supports WordPress 6.4+ and PHP 7.4+.

Run PHP syntax checks from the repository root:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Build an installable archive from the parent directory:

```bash
zip -r ikon-seo-v0.6.0.zip ikon-seo -x 'ikon-seo/.git/*'
```
