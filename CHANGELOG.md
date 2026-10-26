# Changelog

All notable changes to Bookingistic are documented here (mirroring the WordPress.org `readme.txt` changelog). Releases are tagged `vX.Y.Z` on GitHub with downloadable zips; version snapshots live on the `release/X.Y.Z` branches.

## 2.5.6 — 2026-10-26

* Security: every custom-table SQL query now goes through $wpdb->prepare() with placeholders; table and column identifiers are bound with %i (whitelisted columns only), optional filters are value-bound instead of concatenated, and ORDER BY uses a strict column whitelist. No phpcs:ignore remains on any SQL-preparation (PreparedSQL / PreparedSQLPlaceholders / InterpolatedNotPrepared) sniff.
* Performance: reads from the plugin's own tables are now object-cached (wp_cache_get/wp_cache_set, one cache group per table) and invalidated through a per-table last_changed key on every write (create, update, delete, status change, cancel, payment updates, schema install/upgrade, uninstall, and at the end of every booking transaction). Availability, overlap/double-booking, daily-limit, slot-lock, slug-uniqueness and reminder checks always read live data. The remaining Plugin Check DirectQuery notices carry narrow line-level ignores with the reason stated. No functional changes.

## 2.5.5 — 2026-10-23

* Fixed: REST requests from the booking form and the admin calendar now work on sites using plain permalinks (the WordPress default on a fresh install) — endpoint URLs are built with a proper query-string join instead of plain concatenation, which previously produced "No route was found matching the URL and request method" on every booking-form and calendar request

## 2.5.4 — 2026-10-23

* Hardening: numeric superglobal reads now go through absint()/floatval()/sanitize_key() instead of raw casts, satisfying the stricter WordPress Coding Standards rulesets used by the latest Plugin Check and the WordPress.org review Playground — the plugin reports 0 errors and 0 warnings under it

## 2.5.3 — 2026-10-23

* Hardening: every superglobal read is now unslashed and sanitized (or explicitly cast) — admin pages, list filters, and the public cancel/reschedule/pay links
* Hardening: all admin templates now use plugin-prefixed variable names; read-only display filters are annotated for the WordPress coding standards
* Fixed: removed a leftover duplicate "Footer text" field on the Settings screen that referenced the removed 2.5.1 branding gate
* Dev: plugin now reports 0 errors and 0 warnings under the Plugin Check (PCP) static analysis suite

## 2.5.2 — 2026-10-23

* Fixed: the Google Calendar sync cron event is now scheduled correctly on a fresh activation — the custom "every 15 minutes" interval is registered before scheduling (previously the interval was only registered after activation, so the event was silently skipped on new installs)
* Security: every admin save and action handler (settings, services, staff, bookings, email automations, integrations, appearance) now verifies a capability before processing, in addition to the existing nonce checks — users without the matching Bookingistic capability get a 403

## 2.5.1 — 2026-10-21

* Removed the email footer branding and its license gate — every shipped feature is now free and fully enabled (WordPress.org guidelines)
* Security: staff email addresses are no longer exposed by the public REST endpoints
* Security: client IP detection for rate limiting now uses only the direct connection address unless the site explicitly opts in to trusted proxy headers, and all header input is sanitized
* Security: legacy form nonce is now sanitized before verification; admin error notices sanitize input before escaping
* Privacy: email .ics attachments are written to the system temp directory with random names and deleted after sending — no booking data in the public uploads folder
* Fixed: appearance screen styles load through the standard enqueue system
* Fixed: removed the redundant translation loader (WordPress.org loads translations automatically)
* Brand: plugin links now point to bookingistic.com

## 2.5.0 — 2026-10-21

* Fixed: `/wp-json/bookingistic/v1/payments/status` now accepts the signed link's `t` parameter (previously only read `token`, so plugin-issued links always returned 410)
* Security: pay-now links now expire after 90 days — the token embeds its expiry under an HMAC; previously a leaked pay link stayed valid forever
* Security: Google Calendar OAuth tokens are now encrypted at rest (authenticated secretbox keyed from the site salt); existing tokens migrate transparently on the next save
* New: race-safe booking — concurrent submissions for the same staff member are serialized with a row lock and the availability re-check runs inside the transaction, preventing double-booking under simultaneous submits (InnoDB; silently degrades to check-then-insert on MyISAM)
* Dev: standalone test suite under `tests/` (run `php tests/run-all.php`) covering payment token expiry, crypto round-trip, and deposit math

## 2.4.0 — 2026-10-13

* New: premium admin UI — every Bookingistic screen restyled with a consistent design system (page headers with taglines, card-based panels, refined tables, status pills, sticky save bar)
* New: Integrations page rebuilt as an animated card grid — icon, description, Connected/Not connected pill, hover lift, and staggered fade-in (pure CSS, respects `prefers-reduced-motion`)
* New: dashboard, bookings, services, staff, customers, and customer profiles now show live stat cards (real counts from your data)
* New: Reports screen with bookings-by-status summary and top services (replaces the Phase 2 placeholder)
* Tweak: legacy "Bookings (v1)" menu removed from the admin sidebar (data and the legacy REST endpoint are untouched; developers can restore it with the `bookingistic_legacy_bookings_show_ui` filter)
* Tweak: admin stylesheet rebuilt around CSS variables matching the WordPress admin palette; no external fonts or CDNs

## 2.3.0 — 2026-10-13

* New: Payments module — WooCommerce Orders gateway with enable/disable switch under Bookingistic → Payments
* New: payment ledger — every payment attempt (pending/paid/refunded/failed) recorded per booking, with admin screen and REST endpoints (`/payments`, `/payments/gateways`, `/payments/status`)
* New: `bookingistic_payment_gateways` (alias `bookingistic_gateways`), `bookingistic_payment_recorded`, `bookingistic_payment_currency` filters — gateway registry is fully pluggable
* New: deposits — per-service deposit percentage; only the deposit is charged upfront (`deposit_paid` status), supported across forms, admin and REST
* New: signed pay-now links (`?bookingistic_action=pay`, HMAC token, 410 on bad token) + `[bookingistic_payment id email]` shortcode with Pay now button
* New: booking form success screen shows a Pay now button when a gateway is connected, or pay-later instructions otherwise
* New: `{payment_link}` email merge tag now carries the booking's signed pay link
* New: admin manual actions — Mark paid / Refund on payment records (nonce + capability checked) for offline payments
* New: paid bookings that receive payment are confirmed automatically (consistent with the WooCommerce integration flow)

## 2.2.0 — 2026-10-10

* New: 4 booking form templates — Classic, Modern Card, Split Hero, Minimal Inline (Appearance page + shortcode/Elementor override)
* New: 3 calendar views — month grid, 14-day pills, upcoming list
* New: one-click industry presets with live preview (salon, clinic, fitness, tutoring, consulting, home services)
* New: brand accent color + corner radius controls
* New: Elementor widget with live template/calendar controls
* New: Google Calendar two-way sync — per-staff OAuth connect, booking push, freebusy blocking, 15-min refresh
* New: Appearance admin page with live preview
* New: `bookingistic_gcal_sync` 15-minute cron; `bookingistic_external_busy_intervals` filter
* Improved: refreshed readme, i18n strings, POT file
* Fixed: staff REST responses no longer expose calendar token metadata

## 2.1.0 — 2026-10-10

* Version alignment release (matches the distributed 2.1.0 package).

## 2.0.0

* Phase 1 rebuild: custom tables, modular OOP architecture, availability engine, frontend booking form with side calendar, full REST namespace, email automations with branding helper, admin UI, signed cancel & reschedule links.

## 1.0.0

* Initial v1 Strategy Call lead-capture plugin.
