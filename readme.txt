=== Bookingistic ===
Contributors: developershuvo
Tags: booking, appointment, appointments, calendar, scheduling
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 2.4.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced Booking, Calendar & Email Automation for WordPress. 4 form designs, 3 calendar views, industry presets, Elementor widget, Google Calendar two-way sync, staff scheduling, and automated emails — all native to WordPress.

== Description ==

Bookingistic turns your WordPress site into a premium booking system — for strategy calls, salons, clinics, tutors, fitness trainers, consultants, and any appointment-based business.

**Booking form designs (pick per site or per shortcode)**

* **Classic** — two-panel layout with service details
* **Modern Card** — elevated card with soft shadows, perfect for coaches
* **Split Hero** — bold gradient header, great for salons and clinics
* **Minimal Inline** — borderless, blends into any theme

Switch templates instantly under Bookingistic → Appearance, or per form: `[bookingistic service="consult" template="modern"]`.

**Three calendar views**

* Month grid — the classic date picker
* Pills — horizontal next-14-days scroller (mobile-first)
* List — upcoming dates with Today / Tomorrow labels

**One-click industry presets**

Salon/Spa, Clinic/Medical, Fitness/Yoga, Tutoring, Consulting, Home Services — each applies a tuned design, brand color, and scheduling defaults (slot interval, notice window, booking horizon). Everything stays editable.

**Elementor widget**

Drag-and-drop the Bookingistic Form widget with live controls for service, template, and calendar view. (Appears automatically when Elementor is active.)

**Real scheduling engine**

* Services with duration, buffers, price, category, manual/auto confirmation
* Staff with per-day working hours, days off, holidays, daily booking limits
* Double-booking guard, min-notice, max-advance window, slot interval
* Timezone-aware: slots render in the visitor's timezone
* Cancel & reschedule via signed, expiring links — no account needed

**Google Calendar**

* Free on every site: .ics attachments + one-click "Add to Google Calendar" links
* Two-way sync (bring your own free Google OAuth client): confirmed bookings push to each staff member's calendar, and their external events automatically block booking slots. Refreshed every 15 minutes.

**Email automation**

Branded HTML confirmations, reminders (hourly cron), cancellation and reschedule notices. Automation rules fire on booking events; every send is logged. Free tier adds a small branding footer — removable with a filter.

**Payments (bring your own gateway)**

* Paid services collect payment through a WooCommerce order per booking — customers pay with any payment method WooCommerce already supports, and payment status flows back to the booking automatically (paid → booking confirmed).
* Payment ledger: every attempt (pending, paid, refunded, failed) is logged under Bookingistic → Payments.
* Developer gateways: plug additional providers into the `bookingistic_payment_gateways` filter — no core billing decision is hard-coded.

**Developer-friendly**

* REST API at `/wp-json/bookingistic/v1/` (services, staff, availability, bookings)
* Actions & filters: `bookingistic_booking_confirmed`, `bookingistic_available_slots`, `bookingistic_external_busy_intervals`, `bookingistic_can_remove_branding`, and more
* Clean custom tables; opt-in data removal on uninstall (`BOOKINGISTIC_DELETE_DATA`)
* Backward compatible with the v1 `wpistic_booking` CPT and legacy REST route

= Shortcode =

`[bookingistic service="strategy-call"]`
`[bookingistic service_id="1"]`
`[bookingistic template="split" calendar_view="pills"]`

= REST API =

`GET  /wp-json/bookingistic/v1/services`
`GET  /wp-json/bookingistic/v1/availability?service_id=N&date=YYYY-MM-DD`
`POST /wp-json/bookingistic/v1/bookings`
`POST /wp-json/bookingistic/v1/bookings/{id}/reschedule`
`GET  /wp-json/bookingistic/v1/payments`
`GET  /wp-json/bookingistic/v1/payments/gateways`

== Installation ==

1. Upload the `bookingistic` folder to `/wp-content/plugins/`, or install via Plugins → Add New.
2. Activate the plugin.
3. Open **Bookingistic → Appearance** and pick an industry preset (or your own design).
4. Add your services under **Bookingistic → Services**.
5. Drop `[bookingistic service="your-service"]` on any page — or use the Elementor widget.

Upgrading from 1.x? Your existing bookings and the legacy form keep working.

== Frequently Asked Questions ==

= Does the free version include payments? =

Yes — via WooCommerce. Turn on "WooCommerce Orders" under Bookingistic → Payments and every paid booking creates a WooCommerce order the customer can check out with (cards, PayPal, bank transfer — whatever your store supports). Payment status syncs back to the booking, and confirmed payment auto-confirms the booking. Direct Stripe/PayPal gateways without WooCommerce are on the roadmap.

= Does Google Calendar sync need a paid service? =

No. The deep-link + .ics features work everywhere. Two-way sync uses your own free Google OAuth client credentials (setup guide under Bookingistic → Integrations → Google Calendar).

= Can each staff member have their own calendar? =

Yes — per-day working hours, days off, daily limits, and individual Google Calendar connections.

= Will it slow my site down? =

No external CDN calls, no jQuery dependency, one small CSS + one JS file, loaded only where the form appears.

== Screenshots ==

1. Booking form — Modern Card template with month calendar
2. Appearance — one-click industry presets with live preview
3. Calendar view — pills scroller for mobile-first booking
4. Admin dashboard — bookings, services, staff, email automations

== Changelog ==

= 2.4.0 =
* New: premium admin UI — every Bookingistic screen restyled with a consistent design system (page headers with taglines, card-based panels, refined tables, status pills, sticky save bar)
* New: Integrations page rebuilt as an animated card grid — icon, description, Connected/Not connected pill, hover lift, and staggered fade-in (pure CSS, respects prefers-reduced-motion)
* New: dashboard, bookings, services, staff, customers, and customer profiles now show live stat cards (real counts from your data)
* New: Reports screen with bookings-by-status summary and top services (replaces the Phase 2 placeholder)
* Tweak: legacy "Bookings (v1)" menu removed from the admin sidebar (data and the legacy REST endpoint are untouched; developers can restore it with the `bookingistic_legacy_bookings_show_ui` filter)
* Tweak: admin stylesheet rebuilt around CSS variables matching the WordPress admin palette; no external fonts or CDNs

= 2.3.0 =
* New: Payments module — WooCommerce Orders gateway with enable/disable switch under Bookingistic → Payments
* New: payment ledger — every payment attempt (pending/paid/refunded/failed) recorded per booking, with admin screen and REST endpoints (`/payments`, `/payments/gateways`, `/payments/status`)
* New: `bookingistic_payment_gateways` (alias `bookingistic_gateways`), `bookingistic_payment_recorded`, `bookingistic_payment_currency` filters — gateway registry is fully pluggable
* New: deposits — per-service deposit percentage; only the deposit is charged upfront (`deposit_paid` status), supported across forms, admin and REST
* New: signed pay-now links (`?bookingistic_action=pay`, HMAC token, 410 on bad token) + `[bookingistic_payment id email]` shortcode with Pay now button
* New: booking form success screen shows a Pay now button when a gateway is connected, or pay-later instructions otherwise
* New: `{payment_link}` email merge tag now carries the booking's signed pay link
* New: admin manual actions — Mark paid / Refund on payment records (nonce + capability checked) for offline payments
* New: paid bookings that receive payment are confirmed automatically (consistent with the WooCommerce integration flow)

= 2.2.0 =
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

= 2.1.0 =
* Version alignment release (matches the distributed 2.1.0 package).

= 2.0.0 =
* Phase 1 rebuild: custom tables, modular OOP architecture, availability engine,
  frontend booking form with side calendar, full REST namespace, email automations
  with branding helper, admin UI, signed cancel & reschedule links.

= 1.0.0 =
* Initial v1 Strategy Call lead-capture plugin.

== Upgrade Notice ==

= 2.3.0 =
Adds payments: WooCommerce Orders gateway, payment ledger, deposits, signed pay links and the [bookingistic_payment] shortcode. A small automatic database upgrade (service deposit column) runs on update.

= 2.2.0 =
New designs, calendar views, industry presets and Google Calendar sync. Database upgrades run automatically — back up before updating as with any release.
