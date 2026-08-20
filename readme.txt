=== YOLO Logos ===
Contributors: wployos
Tags: logo, logo carousel, logo slider, gutenberg, block
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern WordPress plugin for managing and showcasing logos with Carousel, Grid, and Flexbox layouts. Native Gutenberg block included.

== Description ==

**YOLO Logos** is a fully-featured logo management and showcase plugin for WordPress. Create logo records with multiple image variants, organise them into categories, and display them beautifully using a native Gutenberg block.

= Features =

**Logo Management**
* Add logos with title, content/description, website URL, and up to three image variants (Main, Light, Dark).
* Organise logos into categories.
* Set custom sort order per logo.
* Full WordPress Media Library integration.

**Native Gutenberg Block**
* Insert, preview, and switch your logo showcases directly within the Block Editor.
* Real-time live preview of your logos inside the editor.
* Full InspectorControls panel for every setting.

**Showcase Types**
* **Carousel** – touch-enabled slider with autoplay, infinite loop, swipe, arrows, and pagination dots. Enable **Ticker mode** for continuous non-stop scrolling — ideal for sponsor strips and press logo rows.
* **Grid** – static responsive grid with independent column control across four breakpoints (Mobile, Tablet, Laptop, Desktop). Every logo is visible at once.
* **Flexbox** – CSS flexbox layout where logos wrap naturally based on their configured width. No rigid columns — ideal for mixed-size brand marks.

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

**Compatibility**
* Fully compatible with WPML, Polylang, Loco Translate, and any other translation plugin.
* Proper WordPress text-domain with included `.pot` file.
* Accessibility-ready (ARIA roles, keyboard navigation, reduced-motion support).
* Zero external dependencies on the frontend.

= Getting Started =

1. Install and activate the plugin.
2. Go to **Logos → Add New** to create your first logo.
3. Upload a Main Logo image, optionally upload Light and Dark variants.
4. Set the website URL, alt text, and sort order.
5. Assign the logo to a **Logo Category** (optional).
6. In the Block Editor, add the **Logo Showcase** block.
7. Choose a category (or show all logos), pick a showcase type, and customise the appearance.

== Installation ==

1. Upload the `yolo-logos` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Logos** in the admin menu to add your first logo.

== License ==

This plugin is licensed under the GNU General Public License v2 or later.

== Frequently Asked Questions ==

= Can I display logos from multiple categories in one block? =

Not directly — each block instance shows logos from one category (or all logos). Use multiple blocks on the same page to mix categories.

= Are the showcase types responsive? =

Yes. The Grid and Carousel types have independent column settings for Mobile, Tablet, Laptop, and Desktop breakpoints. The Flexbox type wraps automatically.

= Does this plugin require jQuery or any other library? =

No. The frontend JavaScript is vanilla ES5-compatible JavaScript with zero external dependencies.

= Is it compatible with Full Site Editing (FSE)? =

Yes. The block can be used in any block context, including site templates, patterns, and widgets.

== Screenshots ==

1. Logos admin list with thumbnail preview.
2. Add/Edit logo screen with image upload meta boxes.
3. Logo Showcase block in the Gutenberg editor.
4. Grid showcase on the frontend.
5. Carousel showcase with arrows and pagination.
6. Flexbox showcase.
7. Plugin settings page.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
