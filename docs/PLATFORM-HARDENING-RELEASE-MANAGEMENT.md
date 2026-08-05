# Platform Hardening & Release Management

Ikon SEO v1.17.0 adds a local, approval-first production-readiness layer. It is designed to detect deployment problems and preserve recoverable configuration before a live upgrade. It does not perform automatic plugin updates, automatic rollback or live content changes.

## Release integrity

The packaged release includes:

```text
release/manifest.json
release/manifest.sig
release/public-key.pem
```

The manifest records SHA-256 hashes for packaged PHP, JavaScript, JSON, CSS, Markdown, text and CSV files. The detached signature is verified with OpenSSL and the bundled public key. The runtime check also reports missing, changed and unexpected executable files.

This check detects corruption and unexpected executable files inside the plugin package. It does not replace trusted update transport, hosting malware protection, server file monitoring or independent verification of the official public key.

## Platform readiness gate

The release gate combines:

- Production health checks.
- Database component and table verification.
- WordPress, PHP and database compatibility.
- Required PHP extensions.
- HTTPS, filesystem and scheduler health.
- Workspace connection ownership and scopes.
- Release manifest verification.
- Availability and age of a configuration recovery archive.

A blocked gate does not deactivate the plugin. It tells the administrator not to approve the release for production until the blocking evidence has been resolved.

## Recovery archives

Two archive types are supported:

### Configuration archive

Stores credential-free Ikon SEO settings for the same plugin and database version. It does not include posts, pages, media, OAuth secrets, connection keys, API keys or passwords.

Restoration requires:

1. A local administrator.
2. A valid configuration archive.
3. A matching plugin and database version.
4. The exact stored SHA-256 payload hash.
5. An automatic pre-restore recovery point.

### Support bundle

Stores sanitized environment and health evidence for manual troubleshooting. It contains hashed host identity rather than the website URL and does not transmit itself anywhere.

## Upgrade journal

Every installation, upgrade, reactivation or scheduler repair records:

- Previous and current plugin versions.
- Previous and current database component versions.
- Migration completion state.
- Whether automatic rollback was used.
- Timestamp and immutable journal UUID.

## Scheduler repair

The explicit repair command recreates expected Ikon SEO cron events and reruns production health checks. It does not execute the scheduled workloads during repair.

## Safety boundaries

The module cannot:

- Publish, schedule, update, merge or delete WordPress content.
- Restore connection keys or third-party credentials.
- Send a support bundle externally.
- Automatically roll back the database.
- Install or update plugin packages.
- Modify client websites remotely.
