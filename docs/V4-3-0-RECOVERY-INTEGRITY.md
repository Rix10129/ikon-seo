# Ikon SEO v4.3.0 Recovery Integrity Fingerprint

This record identifies the exact recovered source candidate used as the base for v4.4 development.

## Original uploaded archive

- plugin: Ikon SEO
- plugin version: 4.3.0
- database component: 56.0
- archive SHA-256: `ed972ca478d8d34a30dd7a784aa6855a111496a95adab60cbdb38a626caf2a56`
- archive entries: 396
- PHP files: 185
- package root: `ikon-seo/`

## Extracted source fingerprint

For the clean extracted `ikon-seo/` directory, every file was sorted by relative path and represented as:

`relative-path<TAB>sha256<TAB>byte-size<NEWLINE>`

The SHA-256 of that canonical source list is:

`205e0d96c89999913bacfe0511d45c81812b2456f41157aaa4564885cd4c278f`

- extracted files: 396
- extracted total bytes: 5,670,409

This lets us distinguish the validated recovered v4.3.0 source from the older repository `main` tree even before the full extracted tree is imported into the canonical recovery branch.

## Validation already completed

- plugin header reports 4.3.0;
- `IKON_SEO_VERSION` reports 4.3.0;
- release manifest reports DB 56.0;
- Agency Hub references present;
- Site Agent references present;
- Controlled Existing Page Updates present;
- 185 PHP files syntax-clean in the recovered package;
- bundled JSON files parsed successfully;
- common embedded secret-pattern scan returned no hits.

## Source-control rule

The older v0.6.0-era repository `main` must not be treated as the code base for v4.4. The full extracted v4.3.0 tree still needs to be imported into the dedicated canonical recovery branch before a production code PR is merged.
