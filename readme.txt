=== Bookingistic ===
Contributors: wordpressistic
Tags: booking, appointment, calendar, scheduling, automation
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced Booking, Calendar & Email Automation for WordPress. Strategy calls, service appointments, staff-based bookings, and automated email workflows — all native to WordPress.

== Description ==

Bookingistic turns your WordPress website into a premium booking system. Phase 1 ships:

* Custom database tables (services, staff, customers, bookings, automations, email logs)
* Multi-step frontend booking form with side calendar (shortcode: `[bookingistic service="slug"]`)
* REST API at `/wp-json/bookingistic/v1/`
* Availability engine with buffers, min notice, max advance window, slot interval, working hours, and double-booking guard
* Customer + admin confirmation emails, reminder cron, cancel & reschedule via signed links
* Branded free-tier emails; premium-ready white-label hook (`bookingistic_can_remove_branding`)
* Admin dashboard, bookings list, services CRUD, settings
* Backward compatibility with the v1 `wpistic_booking` CPT and `/wpistic/v1/bookingistic/request` REST route

Roadmap:

* Phase 2 — Full month/week/day calendar, manual booking, staff CRUD, customer profile drilldown
* Phase 3 — Email automation visual builder, white-label controls, test-send
* Phase 4 — Staff working hours / days off / holidays / per-staff overrides
* Phase 5 — Integrations: WooCommerce, Google Calendar, Outlook, Messageistic SMS, CRMistic
* Phase 6 — Premium: payments, group bookings, advanced reports, licensing

== Installation ==

1. Upload the `bookingistic` folder to `/wp-content/plugins/`.
2. Activate the plugin in the WordPress admin.
3. Open **Bookingistic → Settings** and configure your business + email sender.
4. Drop `[bookingistic service="strategy-call"]` on any page.

== REST API ==

`GET  /wp-json/bookingistic/v1/services`
`GET  /wp-json/bookingistic/v1/availability?service_id=N&date=YYYY-MM-DD`
`POST /wp-json/bookingistic/v1/bookings`
`POST /wp-json/bookingistic/v1/bookings/{id}/reschedule`

== Backward compatibility ==

* The v1 `wpistic_booking` CPT remains registered (read-only).
* The legacy `POST /wpistic/v1/bookingistic/request` endpoint still accepts strategy-call leads and creates them as pending bookings on the seeded "Strategy Call" service.

== Changelog ==

= 2.0.0 — 2026-05-11 =
* Phase 1 rebuild. Custom tables, modular OOP architecture, availability engine,
  frontend booking form with side calendar, full REST namespace, email automations
  with branding helper, admin UI, signed cancel & reschedule links.

= 1.0.0 — 2026-05-04 =
* Initial v1 Strategy Call lead-capture plugin.
