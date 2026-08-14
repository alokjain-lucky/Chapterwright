<?php
/**
 * Book and Chapter post type registration.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'mab_register_post_types' );
add_action( 'init', 'mab_register_meta_fields', 20 );

/**
 * Register the Book and Chapter custom post types.
 *
 * Both types support the block editor and the WordPress REST API. Books
 * have a public archive at /books/; chapters are reached through their own
 * permalinks under /book-chapter/. The post type keys themselves live in
 * MAB_BOOK_POST_TYPE / MAB_CHAPTER_POST_TYPE (defined in make-a-book.php)
 * and are part of the plugin's stable data contract — see AGENTS.md.
 *
 * As of 2.0.0, `show_in_menu` is false for both: the admin app registered in
 * admin/app.php (top-level "Make a Book" menu) is the primary place authors
 * browse and organize Books/Chapters/Sections, so the native list/edit
 * screens no longer clutter the sidebar. `show_ui` stays at its true
 * default so those screens still exist and work — the admin app and the
 * Gutenberg editor sidebar panel (admin/app/src/editor-sidebar.js) both
 * deep-link straight into post.php for writing a chapter's actual content.
 *
 * `'custom-fields'` is in both `supports` arrays for a reason that is easy
 * to miss and expensive to debug without it: `WP_REST_Posts_Controller::
 * get_item_schema()` only adds a `meta` property to a post type's REST
 * schema when `post_type_supports( $post_type, 'custom-fields' )` is true.
 * Without it, `register_post_meta()` still runs and the field is genuinely
 * registered (`get_registered_meta_keys()` reports it correctly), but the
 * REST controller never exposes or accepts it — every create/update
 * request carrying `meta` in the body is silently accepted and silently
 * ignored: no error, no rejected write, meta auth_callback never even
 * invoked, because the request never reaches that code path at all. See the
 * "Unreleased" AGENTS.md entry for how this was actually root-caused (five
 * rounds of live debug.log instrumentation) — don't remove this support
 * flag without re-reading that first.
 *
 * As of 2.2.0, both types register their own `capability_type` (`mab_book`/
 * `mab_books`, `mab_chapter`/`mab_chapters`) with `map_meta_cap => true`,
 * instead of defaulting to generic `post`/`posts` capabilities. This means
 * "who can touch Books/Chapters" is no longer the same permission as "who
 * can edit any post on the site" — a site can now create a role scoped to
 * just this plugin. `mab_add_capabilities_to_roles()` below grants the
 * default roles exactly the capabilities they already effectively had
 * under the old generic-post behavior, so this change is a no-op for any
 * existing site unless it deliberately creates a new role.
 */
function mab_register_post_types() {
	register_post_type(
		MAB_BOOK_POST_TYPE,
		array(
			'labels'          => mab_book_labels(),
			'public'          => true,
			'has_archive'     => 'books',
			'rewrite'         => array( 'slug' => 'books' ),
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
			'capability_type' => array( 'mab_book', 'mab_books' ),
			'map_meta_cap'    => true,
		)
	);

	register_post_type(
		MAB_CHAPTER_POST_TYPE,
		array(
			'labels'          => mab_chapter_labels(),
			'public'          => true,
			'has_archive'     => false,
			'rewrite'         => array( 'slug' => 'book-chapter' ),
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			'capability_type' => array( 'mab_chapter', 'mab_chapters' ),
			'map_meta_cap'    => true,
		)
	);
}

/**
 * Grant the default WordPress roles the same practical access to Books and
 * Chapters that they had before 2.2.0's move to a dedicated capability_type.
 *
 * WordPress never grants a custom post type's mapped capabilities to any
 * role automatically — that has always been the theme/plugin's job, done
 * once (typically on activation). The capability names granted per role
 * below deliberately mirror exactly what WordPress's own `populate_roles()`
 * grants each default role for the built-in `post` type (see
 * wp-admin/includes/schema.php), so switching Books/Chapters onto their own
 * capability_type does not change what an Administrator, Editor, Author, or
 * Contributor could already do — it only makes it *possible* to grant a
 * narrower, dedicated role that skips generic post access entirely.
 *
 * Safe to call repeatedly: WP_Role::add_cap() is idempotent.
 */
function mab_add_capabilities_to_roles() {
	// Mirrors what WordPress itself grants each default role for the 'post'
	// type — see populate_roles() in wp-admin/includes/schema.php. Only the
	// plural/primitive capabilities are listed here; the singular ones
	// (edit_mab_book, read_mab_book, delete_mab_book) are meta capabilities
	// that map_meta_cap resolves per-post from these at request time and are
	// never granted to a role directly.
	$role_caps = array(
		'administrator' => array( 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts' ),
		'editor'        => array( 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts' ),
		'author'        => array( 'edit_posts', 'publish_posts', 'delete_posts', 'delete_published_posts', 'edit_published_posts' ),
		'contributor'   => array( 'edit_posts', 'delete_posts' ),
	);

	foreach ( array( MAB_BOOK_POST_TYPE, MAB_CHAPTER_POST_TYPE ) as $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object ) {
			continue;
		}

		foreach ( $role_caps as $role_name => $generic_caps ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}

			foreach ( $generic_caps as $generic_cap ) {
				if ( isset( $post_type_object->cap->$generic_cap ) ) {
					$role->add_cap( $post_type_object->cap->$generic_cap );
				}
			}
		}
	}
}

/**
 * Register Book/Chapter meta fields for REST read/write access.
 *
 * The admin app (admin/app/src/) and the Gutenberg editor sidebar panel
 * both read and write these fields through the standard
 * `/wp/v2/mab_book/{id}` and `/wp/v2/mab_chapter/{id}` REST endpoints
 * instead of a bespoke controller — `register_post_meta()` is all that is
 * needed for simple scalar fields already covered by core's REST meta
 * handling. `_mab_section_id` is the one relationship not covered here
 * because assigning a chapter to a section also needs its sibling order
 * recalculated in the same request; see admin/rest/chapters.php.
 */
function mab_register_meta_fields() {
	register_post_meta(
		MAB_BOOK_POST_TYPE,
		'_mab_subtitle',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => 'mab_meta_auth_callback',
		)
	);

	register_post_meta(
		MAB_BOOK_POST_TYPE,
		'_mab_accent',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'mab_sanitize_accent_color',
			'auth_callback'     => 'mab_meta_auth_callback',
		)
	);

	// Marks a published book that has no (or not enough) chapters yet as an
	// intentional announcement rather than an unfinished/broken-looking page:
	// the library card and the book's own hero swap their reading CTA for a
	// "Coming soon" badge instead. Independent of post status on purpose —
	// see admin/app/src/screens/book-detail.js, which sets this flag *and*
	// publishes the book together as one action, since an unpublished draft
	// would not appear in the library at all regardless of this flag.
	register_post_meta(
		MAB_BOOK_POST_TYPE,
		'_mab_coming_soon',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'auth_callback'     => 'mab_meta_auth_callback',
		)
	);

	register_post_meta(
		MAB_CHAPTER_POST_TYPE,
		'_mab_book_id',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => 'mab_meta_auth_callback',
		)
	);

	register_post_meta(
		MAB_CHAPTER_POST_TYPE,
		'_mab_order',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => 'mab_meta_auth_callback',
		)
	);

	register_post_meta(
		MAB_CHAPTER_POST_TYPE,
		'_mab_section_id',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => 'mab_meta_auth_callback',
		)
	);
}

/**
 * Shared auth callback for every registered meta field: only someone who
 * can edit the specific post may read or write its Make a Book metadata
 * through the REST API.
 *
 * @param bool   $allowed Whether the value should be allowed. Ignored; recomputed here.
 * @param string $meta_key Meta key being checked. Unused — every field uses this same rule.
 * @param int    $post_id  Post the meta belongs to.
 * @return bool Whether the current user may read/write this field.
 */
function mab_meta_auth_callback( $allowed, $meta_key, $post_id ) {
	unset( $allowed, $meta_key );
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Sanitize a hex color, falling back to the reader's default accent when the
 * submitted value is not a valid hex color (including an empty string).
 *
 * @param string $value Raw color value.
 * @return string A valid hex color.
 */
function mab_sanitize_accent_color( $value ) {
	$color = sanitize_hex_color( $value );
	return $color ? $color : '#f45d48';
}

/**
 * Build translated labels for the Book post type editor.
 *
 * @return array<string,string> Post type labels.
 */
function mab_book_labels() {
	return array(
		'name'               => __( 'Books', 'make-a-book' ),
		'singular_name'      => __( 'Book', 'make-a-book' ),
		'add_new'            => __( 'Add New', 'make-a-book' ),
		'add_new_item'       => __( 'Add New Book', 'make-a-book' ),
		'edit_item'          => __( 'Edit Book', 'make-a-book' ),
		'new_item'           => __( 'New Book', 'make-a-book' ),
		'view_item'          => __( 'View Book', 'make-a-book' ),
		'search_items'       => __( 'Search Books', 'make-a-book' ),
		'not_found'          => __( 'No books found.', 'make-a-book' ),
		'not_found_in_trash' => __( 'No books found in Trash.', 'make-a-book' ),
		'all_items'          => __( 'All Books', 'make-a-book' ),
		// The admin sidebar's top-level label for this whole menu (Books +
		// Chapters + Settings), not just this post type — see
		// mab_register_post_types() for how Chapters nests under it.
		'menu_name'          => __( 'Make a Book', 'make-a-book' ),
	);
}

/**
 * Build translated labels for the Chapter post type editor.
 *
 * @return array<string,string> Post type labels.
 */
function mab_chapter_labels() {
	return array(
		'name'               => __( 'Chapters', 'make-a-book' ),
		'singular_name'      => __( 'Chapter', 'make-a-book' ),
		'add_new'            => __( 'Add New', 'make-a-book' ),
		'add_new_item'       => __( 'Add New Chapter', 'make-a-book' ),
		'edit_item'          => __( 'Edit Chapter', 'make-a-book' ),
		'new_item'           => __( 'New Chapter', 'make-a-book' ),
		'view_item'          => __( 'View Chapter', 'make-a-book' ),
		'search_items'       => __( 'Search Chapters', 'make-a-book' ),
		'not_found'          => __( 'No chapters found.', 'make-a-book' ),
		'not_found_in_trash' => __( 'No chapters found in Trash.', 'make-a-book' ),
		'all_items'          => __( 'All Chapters', 'make-a-book' ),
		'menu_name'          => __( 'Chapters', 'make-a-book' ),
	);
}
