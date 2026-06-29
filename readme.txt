=== FBV — Favourite Bible Verses ===
Contributors: mariuszmirecki
Tags: bible, verses, nwt, polish, english
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A curated, bilingual (Polish/English) collection of favourite Bible verses from
the New World Translation, shown as responsive, searchable, tag-filterable cards.

== Description ==

FBV manages a personal collection of favourite Bible verses. Verses are stored
in both Polish and English and displayed as responsive cards with tag-based
filtering, live search and a per-page language toggle.

Logged-in administrators get a frontend admin panel — no wp-admin needed — for
adding, editing and deleting verses, with semi-automatic text extraction from
jw.org.

= Features =

* Bilingual storage (Polish primary, English secondary)
* Responsive card layout (1/2/3 columns)
* Live client-side search across references, text and tags
* Single-tag filtering with a scrollable pill row
* Per-card flag language switch (Polish/English)
* Canonical Bible-order sorting
* Frontend add/edit/delete for administrators (manual PL/EN text entry)
* REST API with nonce authentication
* WP-CLI importer for bulk loading from CSV

== Installation ==

1. Upload the `fbv` folder to `/wp-content/plugins/`.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Create a page and add the `[fbv]` shortcode.
4. (Optional) Import the starter set:
   `wp fbv import --fetch-en`

== Usage ==

Place the shortcode on any page:

    [fbv]

Administrators will see an "Add Verse" button and per-card edit/delete controls.

== Frequently Asked Questions ==

= Where does the verse text come from? =

Both Polish and English text are entered manually by the administrator in the
add/edit form (copy-paste from your preferred source).

= How do I remove all data on uninstall? =

Add `define( 'FBV_DELETE_DATA_ON_UNINSTALL', true );` to wp-config.php before
deleting the plugin.

== Changelog ==

= 1.0.0 =
* Initial release.
