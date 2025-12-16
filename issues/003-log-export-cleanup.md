# Exported log files are never cleaned up

## Type
Improvement

## Problem
`LdgLogger::exportLogs()` writes JSON files to the WordPress uploads directory but does not register any cleanup. Over time each export leaves another file containing potentially sensitive data. The files use predictable names and remain accessible if the uploads directory is web-readable.

## Where
- `includes/class-ldg-logger.php`: `exportLogs()` creates a timestamped file in `wp_upload_dir()/livedg-logs` without retention or access controls beyond file permissions.

## Suggested Fix
Add retention limits (e.g., delete files older than N days on export), or store exports in a protected location and generate short-lived signed URLs. Provide an admin UI entry to purge exported logs.
