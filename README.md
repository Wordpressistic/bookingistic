# Bookingistic

**Advanced Booking, Calendar & Email Automation for WordPress.**
Part of the WordPressistic / WPistic suite. Build Your Business Automation with AI — [wordpressistic.com](https://www.wordpressistic.com).

> v2.2 — four form designs, three calendar views, industry presets, Elementor widget, Google Calendar two-way sync, staff scheduling, email automation.

## What ships in 2.2

- **4 booking form templates** — Classic (two-panel), Modern Card, Split Hero, Minimal Inline. Pick globally under *Bookingistic → Appearance* or per form:
  ```text
  [bookingistic service="strategy-call" template="modern" calendar_view="pills"]
  ```
  Every template is theme-overridable at `{theme}/bookingistic/form-{key}.php` and implements the same DOM contract, so one JS controller drives all designs.
- **3 calendar views** — month grid, 14-day pills scroller (mobile-first), upcoming list with Today/Tomorrow labels.
- **One-click industry presets** — Salon/Spa, Clinic/Medical, Fitness, Tutoring, Consulting, Home Services: applies design + brand color + scheduling defaults with a live preview (Appearance page).
- **Brand controls** — accent color + corner radius, applied via CSS variables.
- **Elementor widget** — Bookingistic Form, with live controls for service/template/calendar (auto-registers when Elementor is active).
- **Google Calendar two-way sync** — bring your own free OAuth client; per-staff connect from the staff editor:
  - confirmed bookings push to the staff member's calendar (create/update/cancel),
  - their external events block booking slots via the freebusy API (15-min cron),
  - free tier keeps ICS attachments + "Add to Google Calendar" deep links on every site.
- **Custom database tables** — services, staff, customers, bookings, booking meta, email automations, email logs, calendar events, payment records (`dbDelta`, auto-upgrade from 2.0/2.1).
- **Modular OOP** under `Bookingistic\`, PSR-4-style autoload from `includes/`.
- **Backward compatibility** — v1 `wpistic_booking` CPT remains registered, legacy `POST /wpistic/v1/bookingistic/request` still works.
- **Availability engine** — duration + buffers, slot interval, working hours (business + per-staff per-day), days off, holidays, daily staff limits, min notice, max advance window, double-booking guard; server-side re-validated on submit. Timezone-aware end to end.
- **REST API** at `/wp-json/bookingistic/v1/` — services (CRUD), staff (read/public-trimmed), availability, bookings (public POST with honeypot + rate limit, signed-token reschedule).
- **Email subsystem** — branded HTML templates, `{var}` renderer, event-driven automations, hourly reminder cron, email logs, free-tier footer removable via `bookingistic_can_remove_branding`.
- **Capabilities** — `manage_bookingistic`, `view_bookingistic`, per-area caps; clean opt-in uninstall via `BOOKINGISTIC_DELETE_DATA`.

## Requirements

- WordPress 6.4+, PHP 8.0+
- Elementor (optional) — for the drag-and-drop widget
- Google OAuth Client (optional) — for two-way calendar sync

## Install

1. Upload `bookingistic` to `/wp-content/plugins/` or install from the WordPress.org directory.
2. Activate, then open **Bookingistic → Appearance** → pick an industry preset.
3. Add services under **Bookingistic → Services**.
4. Place `[bookingistic service="your-service"]` — or use the Elementor widget.

## For developers

Key hooks:

```php
// Filter available slots before render
add_filter( 'bookingistic_available_slots', fn( $slots, $service, $date, $staff_id ) => $slots, 10, 4 );

// Inject external busy intervals (Google freebusy uses this)
add_filter( 'bookingistic_external_busy_intervals', fn( $intervals, $staff_id ) => $intervals, 10, 2 );

// Integrate with the booking lifecycle
add_action( 'bookingistic_booking_confirmed', function ( $booking, $customer, $service ) { /* ... */ }, 10, 3 );

// Premium white-label
add_filter( 'bookingistic_can_remove_branding', fn() => true );
```

## Roadmap

- Native payments (Stripe/PayPal checkout, deposits) — `payment_records` schema is already in place
- Gutenberg block companion to the Elementor widget
- More industry kits + multi-service cart booking

## License

GPL-2.0-or-later. Part of the WordPressistic ecosystem — [wordpressistic.com](https://www.wordpressistic.com).
