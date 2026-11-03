# Changelog

All notable changes to this project are documented here. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-11-03

First release as **Drift: Veneer**, a free WordPress.org plugin by Drift Creative Systems. Based on Bonsai White Label 0.1.0, which stays a separate Bonsai plugin. There is no data migration between the two.

### Added
- Settings → Drift: Veneer screen with the Drift monogram in rose (`#f43f5e`) and the copyright and company details in the admin footer, on that screen only.
- Copyright and company details in the README, readme.txt and the main file header.
- `uninstall.php` removes the options on every site in a multisite network, plus the agency user meta.
- `readme.txt`, `LICENSE` (GPLv2), `phpcs.xml.dist`, WordPress.org icon and banners, release workflow with optional SVN deploy.

### Changed
- Renamed to Drift: Veneer: slug and text domain `drift-veneer`, prefix `drift_veneer_` / `DRIFT_VENEER_` / `Drift_Veneer_`.
- Stored data renamed: options `drift_veneer_settings` and `drift_veneer_hidden_menus`, user meta `drift_veneer_agency_user`.
- Hooks renamed: `drift_veneer_defaults`, `drift_veneer_default_hidden_menus`.
- Defaults are neutral. Only "Hide WordPress branding" is on; the Bonsai colours, logos and "Website by The Bonsai Digital Collective" footer credit are gone. Agencies can set their own with `drift_veneer_defaults`.
- The admin bar logo CSS is added to core's `admin-bar` stylesheet instead of a `<style>` block.
- Minimum PHP lowered to 7.4.

### Removed
- Plugin Update Checker and Composer. Updates come from WordPress.org.
- Bundled default logo support (`assets/images/`). Use the `drift_veneer_defaults` filter instead.
