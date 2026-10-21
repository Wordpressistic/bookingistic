# Security policy

Bookingistic stores customer personal data (names, emails, phone numbers, booking notes) and takes payments, so security reports are our highest-priority work. Bookingistic is maintained by WordPressistic LLC.

## Supported versions

| Version | Supported |
| --- | --- |
| latest release (2.5.x) | ✅ |
| older releases | upgrade first, then re-test |

Older versions do not receive fixes; please upgrade to the latest release before reporting.

## Reporting a vulnerability

**Please do not open a public issue, pull request or discussion for a security problem.**

- Email **[hello@insightistic.com](mailto:hello@insightistic.com)** with the subject `SECURITY: <short summary>`.
- Or use GitHub's **Report a vulnerability** button on this repository's *Security* tab (private vulnerability reporting).

Include the affected component and version, reproduction steps or a proof of concept, the impact you believe it has, and whether you want credit.

We aim to acknowledge reports within **3 business days**, give an initial assessment within **10 business days**, and ship a fix or mitigation for confirmed high-severity issues as quickly as we can. We will keep you updated along the way and credit you in the changelog if you wish.

## Scope notes

The following areas carry the most security-relevant behavior and are reviewed on every release:

- **Public REST endpoints** (`/wp-json/bookingistic/v1/`) — public booking creation is honeypot- and rate-limit-protected; staff email addresses are never exposed; client IP detection for rate limiting uses the direct connection address unless the site explicitly opts in to trusted proxy headers.
- **Signed links** — cancel, reschedule and pay-now links are HMAC-signed; pay links expire after 90 days and bad tokens return `410 Gone`.
- **Google Calendar OAuth tokens** — stored encrypted at rest (authenticated secretbox keyed from the site salt).
- **Payments** — ledger mutations and admin actions (Mark paid / Refund) are capability- and nonce-checked; WooCommerce order creation is the only billing path in the free plugin.
- **Uninstall** — data removal is opt-in via `BOOKINGISTIC_DELETE_DATA`; nothing is deleted unless the site owner asks for it.
