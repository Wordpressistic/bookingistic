# Contributing to Bookingistic

Thanks for helping improve Bookingistic. This repository holds the free WordPress plugin — a booking, calendar and email-automation engine maintained by WordPressistic LLC.

## Before you start

- **Security issues:** don't open an issue or pull request. Follow [SECURITY.md](SECURITY.md).
- **Bugs and feature ideas:** open an [issue](https://github.com/Wordpressistic/bookingistic/issues/new/choose) using one of the templates. For larger changes, please agree on the approach in an issue before writing code.
- **Usage questions:** the [WordPress.org support forum](https://wordpress.org/support/plugin/bookingistic/) reaches more people than GitHub issues.
- **Commercial / partnership questions:** [support@wpistic.com](mailto:support@wpistic.com).

## Development setup

Requirements: PHP 8.0+ CLI and a local WordPress 6.4+ install for manual testing.

```bash
git clone https://github.com/Wordpressistic/bookingistic.git
cp -r bookingistic /path/to/wp-content/plugins/
```

| Path | What it is |
| --- | --- |
| `bookingistic.php` | Plugin bootstrap: header, constants, autoloader, activation hooks |
| `includes/` | Namespaced classes (`Bookingistic\Admin`, `\Booking`, `\Core`, `\Database`, `\Email`, `\Frontend`, `\Helpers`, `\Payments`) |
| `templates/` | Admin screens and the four frontend form templates (theme-overridable) |
| `assets/` | Admin and frontend CSS/JS |
| `languages/` | `bookingistic.pot` |
| `tests/` | Standalone test suite — no WordPress install needed |
| `tools/` | Internal dev scripts (`build-zip.py` rebuilds the distributable zip) |
| `wordpress-org-assets/` | WordPress.org banner, icons and screenshots (not shipped in the plugin zip) |

## Checks to run before a pull request

```bash
# Standalone test suite (crypto round-trip, payment token expiry, deposit math)
php tests/run-all.php

# Lint every PHP file
git ls-files '*.php' | xargs -n1 php -l
```

## Coding standards

This codebase is held to the bar the WordPress.org Plugin Check review enforces — please keep it there:

- Sanitize on input, escape on output, and route **every** database query through `$wpdb->prepare()`.
- Guard every file with `defined( 'ABSPATH' ) || exit;`.
- Respect capabilities (`manage_bookingistic`, `view_bookingistic`) and check nonces on state changes.
- Make new strings translatable with the `bookingistic` text domain and update `languages/bookingistic.pot`.
- Schema changes go through `dbDelta()` with a transparent upgrade path from previous versions.
- Add a user-facing entry to the changelog in `readme.txt` (and mirror it in `CHANGELOG.md`).

## Pull requests

- One topic per PR; keep refactors separate from fixes.
- Include tests when touching crypto, tokens or payment math (`tests/run-all.php` runs without WordPress).
- State the WordPress and PHP versions you tested against.

By opening a pull request you agree your contribution is licensed under GPL-2.0-or-later, the license of this project.

## Release process

Releases are cut by the maintainers: `main` carries development, `vX.Y.Z` tags mark releases with release notes, a downloadable `bookingistic-X.Y.Z.zip` and `SHA256SUMS.txt`, and immutable `release/X.Y.Z` branches preserve each shipped version snapshot.
