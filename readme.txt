=== Bookingistic ===
Contributors: wordpressistic
Tags: booking, appointment, appointments, calendar, scheduling
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 2.2.0
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

== Installation ==

1. Upload the `bookingistic` folder to `/wp-content/plugins/`, or install via Plugins → Add New.
2. Activate the plugin.
3. Open **Bookingistic → Appearance** and pick an industry preset (or your own design).
4. Add your services under **Bookingistic → Services**.
5. Drop `[bookingistic service="your-service"]` on any page — or use the Elementor widget.

Upgrading from 1.x? Your existing bookings and the legacy form keep working.

== Frequently Asked Questions ==

= Does the free version include payments? =

Payments via WooCommerce and Stripe/PayPal checkout are on the roadmap. Currently services can be priced (shown on the form and in emails); payment collection lands in an upcoming release.

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

= 2.2.0 =
New designs, calendar views, industry presets and Google Calendar sync. Database upgrades run automatically — back up before updating as with any release.
