# YOLO Logos

Manage logos and display them with the Logo Showcase, Logo Categories, and Logo
Full Cards blocks. Each logo can have an optional label, such as “Official
Nutrition”; enable **Show logo labels** in a block to display it.
Showcase places labels below the logo container; Categories centers badges on
the container's bottom edge without adding extra bottom margins.
**Logo Full Cards** displays a responsive card grid with the logo, assigned
categories, optional label and name, description, and a **Partner's page** link.
Cards have aligned logo areas, accent-colored category text, and readable
descriptions, with website links aligned along the bottom of each row.
The name and label can be hidden using the block settings.
The same card stylesheet loads inside Gutenberg's editor canvas and on the
frontend: by default, cards use one column on mobile, two on tablet, and three
on larger screens. Adjust these in **Card layout**.

The admin logo listing includes an **Order** column; click its heading to sort
numerically in either direction. Logos without an explicit order remain listed.

## Logo Categories block

Insert **Logo Categories** to display several category groups. Select categories
in **Categories and order**, then use the up/down arrows to sort them. Selection
and order are saved per block; newly created categories must be selected manually.
Only selected categories with published logos and a usable image are displayed.
Each group includes directly assigned logos, not logos assigned only to its child
categories. Logos retain their existing logo sort order.

Choose category headings on the **left** or **top**, their heading level and
column width, and category spacing. Below 782px headings always move above the
logos, which wrap to fit the screen. **Dividers** supports none, solid, dashed,
and dotted separators with configurable width and color.

**Logo appearance** controls image variant (standard/light/dark), original or
fixed sizing, dimensions, alignment, spacing, padding, grayscale, titles,
background, borders, and rounded corners. The editor uses the same server
renderer as the frontend, including image variants, links, and ordering.

## Importing from other logo plugins

Version 1.1.0 supports Logooos v1.9 and its legacy My Clients format. The source
plugin need not be active or installed: its WordPress database records and Media
Library attachments must still exist.

1. Back up the database, then open **Logos → Import** (also linked from the Plugins screen).
2. Select a detected source, choose skip/update and category collision handling,
   and click **Preview (dry run)**. Preview does not write posts, terms or options.
3. Review the warnings and click **Import logos**. Batches default to 50 logos.
   A failed request can be retried at the same offset; if you reload the page,
   preview and start again (already imported logos are skipped).
4. Review missing images, errors and the category ID mapping. Replace each
   `[logooos]` shortcode or preset manually with a **Logo Showcase** block, setting
   `categoryId` to the new `wp_logo_category` term ID (0 means all).

**No media files are copied, downloaded, sideloaded or regenerated.** Attachment
IDs are checked and reused; a missing image is reported but does not prevent
importing the logo. Source records, attachments, presets and page content are
never changed. Imported links always open in a new tab; original link targets
and image-size settings are preserved as private metadata.

Matching category slugs reuse existing target categories, keeping their hierarchy
and metadata. Choose “Create a separate category” to preserve a distinct imported
hierarchy when slugs collide. Persisted category mappings are reused on later
runs, regardless of the collision setting. Skip is the default for existing
logos; Update refreshes mapped fields, image variants and category assignments.
Do not edit source records while a batched import is running.

### WP-CLI

```sh
wp yolo-logos import list
wp yolo-logos import run logooos --dry-run
# Dry-run is also the default when no execution flag is supplied.
wp yolo-logos import run logooos --execute --batch=50
wp yolo-logos import run logooos --execute --update-existing
wp yolo-logos import run logooos --execute --offset=100 --copy-categories
wp yolo-logos import map logooos
```

Batch sizes are 1–500. Maps live in `wp_logos_import_map_<source>` options.
Dry-run IDs marked “new on import” are placeholders, not usable block IDs.
Errors return a nonzero CLI exit status. Resume with the same options and
reported offset, or rerun from zero after correcting errors.

Imports of a source are serialised per batch. If a PHP process is forcibly
terminated, first confirm no import is running, then remove its stale lock with
`wp option delete wp_logos_import_lock_logooos` (substitute the source ID).

See [Import adapters](docs/import-adapters.md) for registering another source
without changing the engine, admin page or CLI.

## Block editor regression tests

Run `npm run build && npm run test:js` to check the compiled Full Cards editor
and its server-rendered preview dependency.

## PHP integration tests

Prerequisites: PHP 8.0+, Composer, Node.js and Docker.

```sh
composer install
npm ci
npx wp-env start
npm run test:php
npx wp-env stop
```

Tests seed **unregistered** source post types/taxonomies and run against the real
WordPress database APIs and block renderer. Test tooling is excluded from the
WordPress.org distribution.

For an existing standard WordPress test suite (for example one installed with
`install-wp-tests.sh`), no Docker/wp-env is required:

```sh
composer install
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit
```

Configure that suite's `wp-tests-config.php` to use an **isolated disposable test
database** and point `ABSPATH` to its WordPress installation. WordPress tests
drop/recreate tables; never point them at production data. The bootstrap finds
the PHPUnit polyfills in this repository's `vendor/` directory. When WordPress.org
network discovery is unavailable, the standard suite can also be sourced from
the matching `WordPress/wordpress-develop` GitHub tag (`tests/phpunit/`) with core
from the same `WordPress/WordPress` tag.

## Page builder integrations

When Elementor or WPBakery is active, YOLO Logos registers **Logo Showcase**,
**Logo Categories**, and **Logo Full Cards** widgets/elements in that builder.
Their controls expose the corresponding block display settings, including
category selection (and selection order for Logo Categories). Both the builder
preview and the frontend use the same registered WordPress block renderers.
The integrations are optional; the plugin continues to work normally when
neither page builder is installed.

In WPBakery, find the three elements under **Add Element → YOLO Logos**.
Elements are mapped on `vc_before_init` and rendered through their registered
shortcodes, without a separate WPBakery PHP template. For Logo Categories,
search for categories, select multiple results, and drag them to reorder.
WPBakery shortcode attribute names are lowercase so selections and display
settings survive saving and reopening.

Builder integration files are organized per display under `blocks/`, alongside
the `wpbakery.php` and `elementor.php` integration definitions. Gutenberg source
files live in each display's `gutenberg/` directory; production block assets are
built under `build/<display>/gutenberg/`.