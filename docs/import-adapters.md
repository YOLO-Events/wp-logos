# Import adapters

Adapters only **read** source data and return `WP_Logos\Import\Import_Category`
and `Import_Logo` value objects. The engine is the sole coordinator of target
writes. Nothing calls a source plugin, imports presets, changes existing content
or creates image files. Raw-table helpers handle inactive/deleted plugins whose
post types and taxonomies are not registered.

## Register a configurable CPT source

Put this in a small site plugin or must-use plugin:

```php
add_filter(
	'wp_logos_import_sources',
	static function ( array $sources ): array {
		$sources['example-showcase'] = array(
			'label'           => 'Example Logo Showcase',
			'post_type'       => 'example_logo',
			'taxonomy'        => 'example_logo_category',
			'image'           => 'featured',
			'light_image_key' => '_example_light_attachment_id',
			'dark_image_key'  => '_example_dark_attachment_id',
			'meta_map'        => array(
				'link'        => '_example_url',
				'link_target' => '_example_target',
				'alt'         => '_example_alt',
				'description' => 'post_content',
				'order'       => 'menu_order',
			),
			'extra_map'       => array( 'image_size' => '_example_size' ),
		);
		return $sources;
	}
);
```

Replace the example names with verified source storage keys. `taxonomy` is
optional. `image` accepts `featured`, `meta_id:<key>` or `meta_url:<key>`.
URL images are resolved **only** by `attachment_url_to_postid()`; unresolved URLs
are reported, not downloaded. Light/dark keys contain attachment IDs.
`meta_map` values name post meta keys, except `post_content` for description and
`menu_order` for order. Order defaults to the post's menu order. Omitted optional
fields are empty. `extra_map` maps preserved field names to source meta keys.
Titles, statuses, dates and authors always come from the source post.

The built-in `logooos-legacy` source proves configuration-only support:
`myclients`, `clientscategory`, featured image, `husamrayan_clientWebsite`,
`client_link_target` and `mc_imageSize`.

The registry also accepts an adapter class name (zero-argument constructor) or
an instance of `Source_Interface`. Keys must be stable, nonempty sanitised
identifiers, and match the adapter's `id()`. Load a custom class yourself before
the registry filter runs; this plugin deliberately has no autoloader.

## Custom adapters

Extend `Abstract_Source` or implement `Source_Interface`:

| Method | Contract |
|---|---|
| `id(): string` | Stable, sanitised registry key; used in options and provenance |
| `label(): string` | Display label |
| `detect(): bool` | Inspect data, not plugin activation/registration |
| `summary(): array` | `logos` and `categories` counts, `warnings` string array |
| `get_categories(): iterable` | `Import_Category` objects, unique source IDs |
| `count_logos(): int` | Exact count of eligible source logos |
| `get_logos(int $offset, int $limit): iterable` | `Import_Logo` objects in stable source order |
| `get_legacy_references(): array` | Optional report entries; return an empty array if none |

`Abstract_Source` provides `read_categories()`, `count_posts()`, `read_posts()`,
`read_meta()` and `read_relationships()`. Reads exclude trash, auto-drafts and
inherited posts. Helpers throw on database failures. Do not register a source
taxonomy merely to read its terms; use the raw helpers.

Construct values with associative arrays:

* `Import_Category`: `source_id`, `name`, `slug`, `description`,
  nullable `parent_source_id`, `extra` array.
* `Import_Logo`: `source_id`, `title`, `status`, `date`, `author_id`, `menu_order`,
  nullable `main_attachment_id`, `light_attachment_id`, `dark_attachment_id`,
  nullable fallback `image_url`, `link`, nullable `link_target` and `alt`,
  `description_html`, `category_source_ids` array, `extra` array.

Treat value objects as immutable after construction. IDs are normalised to
strings (attachment IDs remain integers). Category parents must exist in the
same source and must not form cycles; the engine validates and sorts the entire
hierarchy before inserting terms. Dates are local WordPress `post_date` values.
Only registered WordPress post statuses can be imported.

Legacy references use `type`, `post_id`, `title`, `category_source_id`. The engine
adds nullable `target_term_id`; zero represents all categories. Logooos returns
each `logooo_sc` preset and each real `[logooos]` shortcode in posts/pages.
Escaped `[[logooos]]` examples are excluded. Content replacement is out of scope.

`Engine::run_batch( $source, $options )` accepts `dry_run` (default true), `mode`
(`skip`/`update`), `collision` (`reuse`/`copy`), `offset` (default 0) and `limit`
(default 50, maximum 500). Call again with the returned `next_offset` until
`done`. `Result::to_array()` exposes created/updated/skipped counts, `errors`,
`missing_images`, `slug_collisions`, `term_map`, `legacy_references`, `dry_run`,
`next_offset`, `total` and `done`. Counts are **per batch**, not cumulative.
Negative dry-run category IDs indicate categories that would be created.
Dry-run does not persist its map.

Each created term and imported logo has `_wp_logos_import_source` and
`_wp_logos_import_source_id` metadata. The map is persisted after each category.
Reused target terms are left unchanged, including hierarchy/provenance; the
option retains the source mapping. On logo updates, absent mapped images/order
are removed, and category assignments are replaced. Attachment objects, files,
metadata and parents are never altered.

## Logooos field mapping

| Logooos storage | Normalised field | YOLO Logos storage |
|---|---|---|
| Post ID | `source_id` | `_wp_logos_import_source_id` |
| `post_title` | `title` | `post_title` |
| `post_status`, `post_date`, `post_author` | `status`, `date`, `author_id` | Same post fields |
| `menu_order` | `menu_order` | `menu_order`; `_logo_order` only when > 0 |
| `_thumbnail_id` | `main_attachment_id` | `_logo_main_id` **and** `_thumbnail_id`, same verified ID |
| `description` meta | `description_html` | `post_content`, filtered through `wp_kses_post()` |
| `link` meta | `link` | `_logo_url` |
| `link_target` meta | `link_target` | `_wp_logos_import_link_target` (display still opens a new tab) |
| `imageSize` meta | `extra.image_size` | `_wp_logos_import_image_size`, verbatim (empty means source default 70%) |
| Not provided | `alt` | Empty `_logo_alt`, title fallback |
| Not provided | Light/dark IDs | Not set |
| `logooocategory` terms and relationships | Categories and source IDs | `wp_logo_category`, preserving hierarchy unless reusing an existing term |

## Hooks and filters

| Name | Arguments | Timing |
|---|---|---|
| `wp_logos_import_sources` | `$definitions` | Registry lookup; class, instance or config per key |
| `wp_logos_import_logo_postarr` | `$postarr, $logo, $source_id` | Before post write (also preview) |
| `wp_logos_import_logo_meta` | `$meta, $logo, $source_id` | Before metadata write (also preview); null deletes |
| `wp_logos_import_term_args` | `$args, $category, $source_id` | Before creating/matching a category (also preview) |
| `wp_logos_import_before` | `$source_id, $options` | Before each batch, including previews |
| `wp_logos_import_logo_imported` | `$post_id, $logo, $source_id` | After successful real logo writes only |
| `wp_logos_import_after` | `$result, $source_id, $options` | After each successfully processed batch, including previews |

Filters cannot redirect the writer to another post type/ID, supply `meta_input`
or change import identity. Main image and featured image IDs stay synchronised.
Extensions must not perform writes during dry-run hooks or download images.
Errors are explicit diagnostics, not success-shaped fallbacks.

A future CSV/JSON source can implement the same contract, but file parsing,
uploads and those adapters are not implemented here.
