=== YOLO Logos ===
Contributors: solomongaby
Tags: logo, logo carousel, logo slider, gutenberg, block
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern WordPress plugin for managing logos and displaying them with responsive showcases, category groups, and full cards.

== Description ==

**YOLO Logos** is a fully-featured logo management and showcase plugin for WordPress. Create logo records with multiple image variants and optional labels, organise them into categories, and display them using native Gutenberg blocks.

= Features =

**Logo Management**
* Add logos with title, optional label, content/description, website URL, and up to three image variants (Main, Light, Dark).
* Organise logos into categories.
* Set custom sort order per logo.
* Full WordPress Media Library integration.

**Native Gutenberg Block**
* Use **Logo Showcase**, **Logo Categories**, or **Logo Full Cards** in the Block Editor.
* Real-time live preview of your logos inside the editor.
* Full InspectorControls panel for every setting.

**Showcase Types**
* **Carousel** – touch-enabled slider with autoplay, infinite loop, swipe, arrows, and pagination dots. Enable **Ticker mode** for continuous non-stop scrolling — ideal for sponsor strips and press logo rows.
* **Grid** – static responsive grid with independent column control across four breakpoints (Mobile, Tablet, Laptop, Desktop). Every logo is visible at once.
* **Flexbox** – CSS flexbox layout where logos wrap naturally based on their configured width. No rigid columns — ideal for mixed-size brand marks.
* **Logo Full Cards** – responsive cards with logos, categories, optional labels and names, descriptions, and website links.

**Logo Theme**
* Standard – use the main logo image.
* Light – use the light/white logo variant (falls back to main).
* Dark – use the dark logo variant (falls back to main).

**Full Visual Control**
* Logo max height and max width.
* Item padding, gap, and alignment.
* Item background colour, border width, border colour, and border radius.
* Greyscale logos that reveal in colour on hover.
* Show or hide logo titles.
* Show or hide optional per-logo label badges. In Showcase and Categories they appear below the logo container.

**Compatibility**
* Fully compatible with WPML, Polylang, Loco Translate, and any other translation plugin.
* Proper WordPress text-domain with included `.pot` file.
* Accessibility-ready (ARIA roles, keyboard navigation, reduced-motion support).
* Zero external dependencies on the frontend.

= Getting Started =

1. Install and activate the plugin.
2. Go to **Logos → Add New** to create your first logo.
3. Upload a Main Logo image, optionally upload Light and Dark variants.
4. Set the website URL, alt text, optional label, and sort order.
5. Assign the logo to a **Logo Category** (optional).
6. In the Block Editor, add a **Logo Showcase**, **Logo Categories**, or **Logo Full Cards** block.
7. Choose categories and customise the appearance, including whether to show names and labels.

== Installation ==

1. Upload the `yolo-logos` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Logos** in the admin menu to add your first logo.

== License ==

This plugin is licensed under the GNU General Public License v2 or later.
See https://www.gnu.org/licenses/gpl-2.0.html for the full license text.

== Frequently Asked Questions ==

= How do I import logos from another plugin? =

Open **Logos → Import**, select a detected source and run **Preview (dry run)** first. Review warnings, then click **Import logos**. Logooos v1.9 and its legacy My Clients data are supported, even when the source plugin is inactive or deleted. Existing Media Library attachments are reused by ID: images are never downloaded, duplicated or regenerated. Source data is not changed.

Re-running skips previously imported logos by default; choose Update to refresh them. Categories with matching slugs are reused unless you choose a separate copy. Use the final old-to-new category ID report to replace Logooos shortcodes and presets manually with Logo Showcase blocks (categoryId = new term ID). Original link targets are retained as metadata, but YOLO Logos always opens links in a new tab.

= Can I display logos from multiple categories in one block? =

Yes. Use **Logo Categories** to choose and order multiple category groups. **Logo Showcase** and **Logo Full Cards** display one category or all logos.

= Are the showcase types responsive? =

Yes. The Grid and Carousel types have independent column settings for Mobile, Tablet, Laptop, and Desktop breakpoints. The Flexbox type wraps automatically.

= Does this plugin require jQuery or any other library? =

No. The frontend JavaScript is vanilla ES5-compatible JavaScript with zero external dependencies.

= Is it compatible with Full Site Editing (FSE)? =

Yes. The blocks can be used in any block context, including site templates, patterns, and widgets.

== Screenshots ==

1. Logos admin list with thumbnail preview.
2. Add/Edit logo screen with image upload meta boxes.
3. Logo Showcase block in the Gutenberg editor.
4. Grid showcase on the frontend.
5. Carousel showcase with arrows and pagination.
6. Flexbox showcase.
7. Plugin settings page.

== Changelog ==

= 1.1.0 =
* Add an extensible, adapter-based logo importer with safe dry-run previews, resumable AJAX batches and WP-CLI commands.
* Import Logooos and legacy My Clients logos and hierarchical categories without copying images or changing source data.
* Report missing images, category collisions and legacy shortcode/preset category mappings.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
