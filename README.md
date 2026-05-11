# Bookingistic

**Advanced Booking, Calendar & Email Automation for WordPress.**
Part of the WordPressistic / WPistic suite. Build Your Business Automation with AI — [wordpressistic.com](https://www.wordpressistic.com).

> v2 (Phase 1) — clean modular rewrite of the v1 Strategy Call lead-capture plugin into a full booking engine.

## What ships in Phase 1

- **Custom database tables** — services, staff, customers, bookings, booking meta, email automations, email logs, calendar events, payment records (created with `dbDelta` on activation).
- **Modular OOP** under `Bookingistic\` namespace, PSR-4-style autoload from `includes/`.
- **Backward compatibility** — v1 `wpistic_booking` CPT remains registered, and the legacy REST route `POST /wpistic/v1/bookingistic/request` still works (now creates a pending booking on the seeded *Strategy Call* service).
- **Availability engine** — service duration + buffers, slot interval, working hours, min notice, max advance window, double-booking guard. Server-side re-validated on submit.
- **Frontend booking form** via `[bookingistic service="strategy-call"]` — multi-step UX with side calendar, time-slot picker, customer details, confirmation. No deps, vanilla JS, mobile responsive.
- **REST API** at `/wp-json/bookingistic/v1/` — services (CRUD), staff (read), availability (read), bookings (public POST with honeypot + rate-limit; admin GET/PATCH; signed-token reschedule).
- **Email subsystem** — branded HTML templates, `{var}` parser (safe by default), reminder cron, signed-link cancel & reschedule, automation rules driven by `bookingistic_booking_*` action hooks, free-tier branding footer with premium-ready `Branding_Manager::can_remove_branding()` hook.
- **Admin UI** — Dashboard, Bookings, Services (CRUD), Customers, Email automations (read-only), Settings. Staff / Calendar / Reports pages are placeholder pages flagged for the matching phase.
- **Capabilities** — `manage_bookingistic`, `view_bookingistic`, `create/edit/delete_bookingistic_bookings`, plus per-area caps.
- **Uninstall** — opt-in via `define( 'BOOKINGISTIC_DELETE_DATA', true );` in `wp-config.php`.

## Shortcode

```text
[bookingistic service="strategy-call"]
[bookingistic service_id="1"]
[bookingistic type="services"]      (filter — Phase 2)
[bookingistic staff="john"]         (filter — Phase 2)
[bookingistic category="consultation"] (filter — Phase 2)
```

## REST endpoints

| Method | Route | Auth |
|---|---|---|
| `GET`    | `/services`                       | Public (active only) / Admin (all) |
| `POST`   | `/services`                       | `manage_bookingistic` |
| `GET`    | `/services/{id}`                  | Public (if active) / Admin |
| `PATCH`  | `/services/{id}`                  | `manage_bookingistic` |
| `DELETE` | `/services/{id}`                  | `manage_bookingistic` |
| `GET`    | `/staff`                          | Public (trimmed) / Admin (full) |
| `GET`    | `/availability?service_id&date`   | Public (cached 30s) |
| `POST`   | `/bookings`                       | Public (honeypot + 5/hr per IP) |
| `GET`    | `/bookings`                       | `manage_bookingistic` |
| `GET`    | `/bookings/{id}`                  | `manage_bookingistic` |
| `PATCH`  | `/bookings/{id}`                  | `manage_bookingistic` |
| `POST`   | `/bookings/{id}/reschedule`       | Public + valid `reschedule_token` |

Legacy alias: `POST /wpistic/v1/bookingistic/request` still works.

## Action hooks

- `bookingistic_booking_submitted` — fired after every new booking.
- `bookingistic_booking_confirmed` — when status transitions to confirmed.
- `bookingistic_booking_cancelled`
- `bookingistic_booking_rescheduled`
- `bookingistic_booking_completed`
- `bookingistic_send_reminders` (cron) — Email_Automation_Manager listens.

## Filters

- `bookingistic_available_slots` — `( $slots, $service, $date, $staff_id )`
- `bookingistic_can_remove_branding` — return `true` from premium / license layer.

## Folder structure

```
bookingistic/
├── bookingistic.php
├── uninstall.php
├── readme.txt
├── README.md
├── includes/
│   ├── Core/        (Plugin, Activator, Deactivator, Installer, Capabilities, Backward_Compatibility)
│   ├── Admin/       (Admin_Menu, Admin_Assets, Dashboard_Page, Bookings_Page, Services_Page,
│   │                 Customers_Page, Staff_Page, Calendar_Page, Email_Automations_Page,
│   │                 Reports_Page, Settings_Page)
│   ├── Database/    (Tables, Service_Repository, Staff_Repository, Customer_Repository,
│   │                 Booking_Repository, Email_Automation_Repository, Email_Log_Repository)
│   ├── Booking/     (Booking_Manager, Availability_Engine, Slot_Generator, Booking_Validator,
│   │                 Cancellation_Manager, Reschedule_Manager)
│   ├── Email/       (Branding_Manager, Email_Template_Renderer, Email_Sender, Email_Logger,
│   │                 Email_Automation_Manager)
│   ├── Frontend/    (Shortcode, Assets, Booking_Form)
│   ├── REST/        (REST_Controller, Services_Controller, Staff_Controller,
│   │                 Availability_Controller, Bookings_Controller)
│   └── Helpers/     (Sanitizer, Date_Time, Token, Security)
├── assets/
│   ├── admin/{css,js}
│   └── frontend/{css,js}
└── templates/
    ├── admin/    (dashboard, bookings-list, services-list, service-edit, settings)
    └── frontend/ (booking-form.php)
```

## Build phases

| Phase | Status | Scope |
|---|---|---|
| 1 — Core foundation              | ✅ shipped | DB, availability, booking form, REST, customer + admin emails, branding helper |
| 2 — Admin calendar + bookings    | ✅ shipped | Month/week/day/list calendar, manual booking, in-admin reschedule + cancel, customer drilldown w/ revenue |
| 3 — Email automations            | ✅ shipped | Visual builder (CRUD), test send, service-specific overrides, email logs viewer, white-label controls |
| 4 — Advanced availability        | ✅ shipped | Staff CRUD with per-day working hours, days off, site holidays, daily booking limit, optional frontend host picker |
| 5 — Integrations                 | ✅ shipped | Integration_Base + Manager architecture; WooCommerce orders (real), Google + Outlook calendar (.ics + deep links), Messageistic SMS + CRMistic signed webhooks. Memberistic ships in Phase 6 alongside premium product features. |
| 6 — Premium product              | ⏳        | Payments, group bookings, advanced reports, licensing, white-label rollout |

## Security checklist (status)

- [x] Every input sanitized (`Sanitizer`, `sanitize_*`)
- [x] Every output escaped (`esc_*` in all templates)
- [x] Nonces on every admin form
- [x] REST permission callbacks (admin endpoints require `manage_bookingistic` / `manage_options`)
- [x] Prepared SQL on every dynamic query
- [x] Slot validity re-checked server-side; double-booking guard
- [x] Signed cancel + reschedule tokens (90-day TTL, `hash_equals` compare)
- [x] Rate-limited public POST (5/hr per IP) with honeypot
- [x] Email + phone + timezone + ISO date sanitizers
- [x] `defined( 'ABSPATH' ) || exit;` on every PHP file
- [x] `dbDelta` used for custom tables
