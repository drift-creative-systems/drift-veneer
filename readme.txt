=== Drift: Veneer ===
Contributors: driftcreativesystems
Tags: white label, admin branding, login logo, custom login, hide admin menu
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

White label wp-admin for your clients: your logo and colours, no WordPress branding, and only the admin menus they need.

== Description ==

You build the site, then hand it over to a client who sees WordPress logos, a login page that isn't theirs, and menus they shouldn't be touching. Drift: Veneer handles all three from one settings screen, without a dozen options you'll never use.

Features:

* **Hide WordPress branding**: the WordPress logo menu in the admin bar, the "Thank you for creating with WordPress" footer and the version number. On by default.
* **Admin bar logo**, top-left, with your own link.
* **Admin footer text**, with an optional link.
* **Branded login screen**: logo (linking to the site, not wordpress.org), background colour or image, and form, label, button, button hover and link colours.
* **Agency users**: tick "Agency user" on your own profile. Agency users see the full admin; everyone else, whatever their role, has the menus you choose hidden.
* **Hidden means hidden**: hidden menus and their sub-pages are also blocked if someone types the address.
* **Lockout-safe**: nothing is hidden until an agency user exists, and the Dashboard and each user's own Profile are always available.
* Everything is removed when you delete the plugin.

= Privacy =

Drift: Veneer doesn't connect to any external service, track you or collect data. It adds nothing to the front end of your site apart from the admin bar logo, which only logged-in users with the admin bar see.

= For developers =

* `drift_veneer_defaults` (filter): default settings, used for any field left blank. Only existing keys are accepted.
* `drift_veneer_default_hidden_menus` (filter): top-level menu slugs hidden from non-agency users until the settings screen is saved. Default: Appearance, Plugins, Users, Tools, Settings and ACF.

Example, to ship your agency's login colours to every client site from a theme or mu-plugin:

`add_filter( 'drift_veneer_defaults', function ( $d ) { $d['login_button_color'] = '#203364'; $d['footer_text'] = 'Website by Example Agency'; return $d; } );`

Source code, issues and pull requests: [github.com/drift-creative-systems/drift-veneer](https://github.com/drift-creative-systems/drift-veneer).

= Copyright =

Copyright © 2026 Drift Creative Systems Ltd

Drift Creative Systems Ltd. Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF

== Installation ==

1. In your WordPress admin, go to Plugins → Add New, search for "Drift: Veneer", then install and activate it.
2. Go to Users → Profile and tick **Agency user** for yourself (and any other agency staff).
3. Go to Settings → Drift: Veneer, set your logos and colours, and tick the menus to hide from everyone else.

== Frequently Asked Questions ==

= I can't see Settings → Drift: Veneer =

Once at least one agency user exists, only agency users can see the settings screen or change who is an agency user. Ask an agency user to tick "Agency user" on your profile.

= Can I lock myself out? =

No. Menu hiding only switches on once at least one agency user exists, the Dashboard and each user's own Profile are never hidden, and if the last agency user unticks themselves, hiding switches off again.

= Why not just use roles? =

Clients are often Administrators of their own site, so roles and capabilities can't tell your team and the client apart. The "Agency user" flag is per user, so it works whatever role someone has.

= Does it hide the admin bar's "New" and "Customize" shortcuts? =

Not yet. Only sidebar menus are hidden. The pages those shortcuts open are blocked if their menu is hidden.

= Does it work with White Label CMS? =

Don't run both: they change the same login screen and admin bar. The settings screen warns you while White Label CMS is active.

== Screenshots ==

1. Settings → Drift: Veneer: branding and login screen settings.
2. The Admin menus card, where you choose what non-agency users can see.
3. A branded login screen.

== Changelog ==

= 1.0.0 =
* First public release: hide WordPress branding, admin bar logo, admin footer text, branded login screen, agency users and admin menu hiding.

== Upgrade Notice ==

= 1.0.0 =
First public release.
