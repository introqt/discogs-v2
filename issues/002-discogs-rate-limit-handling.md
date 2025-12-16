# Discogs rate limiting relies on fixed sleeps instead of API guidance

## Type
Improvement

## Problem
`LdgDiscogsClient::makeRequest()` sleeps for 60 seconds whenever it sees HTTP 429 or when the local transient counter reaches 60 requests. The code does not read Discogs `Retry-After`/`X-Discogs-Ratelimit-Remaining` headers, so it may over-throttle or misalign with the official rate window. The blocking `sleep()` calls inside the AJAX-backed admin requests can also freeze the UI and PHP workers for up to a minute.

## Where
- `includes/class-ldg-discogs-client.php`: rate limit enforcement uses transients and hardcoded `sleep(60)` instead of the server-provided retry window.

## Suggested Fix
Parse Discogs rate limit headers to calculate a dynamic retry delay (respect `Retry-After` when provided) and fail fast with a helpful message instead of blocking the admin request for a full minute. Consider tracking remaining requests per user-agent/token to avoid cross-user contention.
