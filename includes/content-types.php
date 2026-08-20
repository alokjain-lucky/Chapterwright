<?php
/**
 * Book and Chapter post type registration.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'hsrtech_register_post_types' );
add_action( 'init', 'hsrtech_register_meta_fields', 20 );
add_filter( 'rest_prepare_' . HSRTECH_BOOK_POST_TYPE, 'hsrtech_rest_prepare_book_view_link', 10, 2 );

/**
 * Register the Book and Chapter custom post types.
 *
 * Both types support the block editor and the WordPress REST API. Books
 * have a public archive at /books/; chapters are reached through their own
 * permalinks under /book-chapter/. The post type keys themselves live in
 * HSRTECH_BOOK_POST_TYPE / HSRTECH_CHAPTER_POST_TYPE (defined in chapterwright.php)
 * and are part of the plugin's stable data contract — see AGENTS.md.
 *
 * As of 2.0.0, `show_in_menu` is false for both: the admin app registered in
 * admin/app.php (top-level "Chapterwright" menu) is the primary place authors
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
 * As of 2.2.0, both types register their own `capability_type` (`hsrtech_book`/
 * `hsrtech_books`, `hsrtech_chapter`/`hsrtech_chapters`) with `map_meta_cap => true`,
 * instead of defaulting to generic `post`/`posts` capabilities. This means
 * "who can touch Books/Chapters" is no longer the same permission as "who
 * can edit any post on the site" — a site can now create a role scoped to
 * just this plugin. `hsrtech_add_capabilities_to_roles()` below grants the
 * default roles exactly the capabilities they already effectively had
 * under the old generic-post behavior, so this change is a no-op for any
 * existing site unless it deliberately creates a new role.
 */
function hsrtech_register_post_types() {
	register_post_type(
		HSRTECH_BOOK_POST_TYPE,
		array(
			'labels'          => hsrtech_book_labels(),
			'public'          => true,
			'has_archive'     => 'books',
			'rewrite'         => array( 'slug' => 'books' ),
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
			'capability_type' => array( 'hsrtech_book', 'hsrtech_books' ),
			'map_meta_cap'    => true,
		)
	);

	register_post_type(
		HSRTECH_CHAPTER_POST_TYPE,
		array(
			'labels'          => hsrtech_chapter_labels(),
			'public'          => true,
			'has_archive'     => false,
			'rewrite'         => array( 'slug' => 'book-chapter' ),
			'show_in_menu'    => false,
			'show_in_rest'    => true,
			'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			'capability_type' => array( 'hsrtech_chapter', 'hsrtech_chapters' ),
			'map_meta_cap'    => true,
		)
	);
}

/**
 * Make a Book's REST `link` field usable as a "View" action for a
 * draft/pending/private/future book, not just a published one.
 *
 * `WP_REST_Posts_Controller::prepare_item_for_response()` always sets `link`
 * to a plain `get_permalink()`, regardless of status — for anything other
 * than a published post, that URL 404s for every visitor, including the
 * book's own author, since WordPress only renders a non-published post at
 * its permalink through the separate preview mechanism
 * (`get_preview_post_link()`), which appends the `preview`/`preview_nonce`
 * query args core's own "Preview" button uses. This swaps `link` to that
 * preview-aware URL for any book that isn't published yet, so the admin app's
 * "View" action (books-list.js, book-detail.js) works the same way
 * regardless of status — the same thing admin/rest/chapters.php already does
 * for Chapters, which don't go through this controller at all.
 *
 * Left untouched for published books: `get_preview_post_link()` would still
 * resolve to a working page for them too, but only by tacking on a
 * short-lived nonce for no benefit — a plain, evergreen permalink is the
 * better link to hand out once a book is actually public.
 *
 * @param WP_REST_Response $response REST response being prepared.
 * @param WP_Post           $post     The book post.
 * @return WP_REST_Response
 */
function hsrtech_rest_prepare_book_view_link( $response, $post ) {
	if ( 'publish' === $post->post_status ) {
		return $response;
	}

	$data         = $response->get_data();
	$data['link'] = get_preview_post_link( $post );
	$response->set_data( $data );

	return $response;
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
function hsrtech_add_capabilities_to_roles() {
	// Mirrors what WordPress itself grants each default role for the 'post'
	// type — see populate_roles() in wp-admin/includes/schema.php. Only the
	// plural/primitive capabilities are listed here; the singular ones
	// (edit_hsrtech_book, read_hsrtech_book, delete_hsrtech_book) are meta capabilities
	// that map_meta_cap resolves per-post from these at request time and are
	// never granted to a role directly.
	$role_caps = array(
		'administrator' => array( 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts' ),
		'editor'        => array( 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts' ),
		'author'        => array( 'edit_posts', 'publish_posts', 'delete_posts', 'delete_published_posts', 'edit_published_posts' ),
		'contributor'   => array( 'edit_posts', 'delete_posts' ),
	);

	foreach ( array( HSRTECH_BOOK_POST_TYPE, HSRTECH_CHAPTER_POST_TYPE ) as $post_type ) {
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
 * `/wp/v2/hsrtech_book/{id}` and `/wp/v2/hsrtech_chapter/{id}` REST endpoints
 * instead of a bespoke controller — `register_post_meta()` is all that is
 * needed for simple scalar fields already covered by core's REST meta
 * handling. `_hsrtech_section_id` is the one relationship not covered here
 * because assigning a chapter to a section also needs its sibling order
 * recalculated in the same request; see admin/rest/chapters.php.
 */
function hsrtech_register_meta_fields() {
	register_post_meta(
		HSRTECH_BOOK_POST_TYPE,
		'_hsrtech_subtitle',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => 'hsrtech_meta_auth_callback',
		)
	);

	register_post_meta(
		HSRTECH_BOOK_POST_TYPE,
		'_hsrtech_accent',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'hsrtech_sanitize_accent_color',
			'auth_callback'     => 'hsrtech_meta_auth_callback',
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
		HSRTECH_BOOK_POST_TYPE,
		'_hsrtech_coming_soon',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'auth_callback'     => 'hsrtech_meta_auth_callback',
		)
	);

	register_post_meta(
		HSRTECH_CHAPTER_POST_TYPE,
		'_hsrtech_book_id',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => 'hsrtech_meta_auth_callback',
		)
	);

	register_post_meta(
		HSRTECH_CHAPTER_POST_TYPE,
		'_hsrtech_order',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => 'hsrtech_meta_auth_callback',
		)
	);

	register_post_meta(
		HSRTECH_CHAPTER_POST_TYPE,
		'_hsrtech_section_id',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => 'hsrtech_meta_auth_callback',
		)
	);
}

/**
 * Shared auth callback for every registered meta field: only someone who
 * can edit the specific post may read or write its Chapterwright metadata
 * through the REST API.
 *
 * @param bool   $allowed Whether the value should be allowed. Ignored; recomputed here.
 * @param string $meta_key Meta key being checked. Unused — every field uses this same rule.
 * @param int    $post_id  Post the meta belongs to.
 * @return bool Whether the current user may read/write this field.
 */
function hsrtech_meta_auth_callback( $allowed, $meta_key, $post_id ) {
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
function hsrtech_sanitize_accent_color( $value ) {
	$color = sanitize_hex_color( $value );
	return $color ? $color : '#f45d48';
}

/**
 * Build translated labels for the Book post type editor.
 *
 * @return array<string,string> Post type labels.
 */
function hsrtech_book_labels() {
	return array(
		'name'               => __( 'Books', 'chapterwright' ),
		'singular_name'      => __( 'Book', 'chapterwright' ),
		'add_new'            => __( 'Add New', 'chapterwright' ),
		'add_new_item'       => __( 'Add New Book', 'chapterwright' ),
		'edit_item'          => __( 'Edit Book', 'chapterwright' ),
		'new_item'           => __( 'New Book', 'chapterwright' ),
		'view_item'          => __( 'View Book', 'chapterwright' ),
		'search_items'       => __( 'Search Books', 'chapterwright' ),
		'not_found'          => __( 'No books found.', 'chapterwright' ),
		'not_found_in_trash' => __( 'No books found in Trash.', 'chapterwright' ),
		'all_items'          => __( 'All Books', 'chapterwright' ),
		// The admin sidebar's top-level label for this whole menu (Books +
		// Chapters + Settings), not just this post type — see
		// hsrtech_register_post_types() for how Chapters nests under it.
		'menu_name'          => __( 'Chapterwright', 'chapterwright' ),
	);
}

/**
 * Build translated labels for the Chapter post type editor.
 *
 * @return array<string,string> Post type labels.
 */
function hsrtech_chapter_labels() {
	return array(
		'name'               => __( 'Chapters', 'chapterwright' ),
		'singular_name'      => __( 'Chapter', 'chapterwright' ),
		'add_new'            => __( 'Add New', 'chapterwright' ),
		'add_new_item'       => __( 'Add New Chapter', 'chapterwright' ),
		'edit_item'          => __( 'Edit Chapter', 'chapterwright' ),
		'new_item'           => __( 'New Chapter', 'chapterwright' ),
		'view_item'          => __( 'View Chapter', 'chapterwright' ),
		'search_items'       => __( 'Search Chapters', 'chapterwright' ),
		'not_found'          => __( 'No chapters found.', 'chapterwright' ),
		'not_found_in_trash' => __( 'No chapters found in Trash.', 'chapterwright' ),
		'all_items'          => __( 'All Chapters', 'chapterwright' ),
		'menu_name'          => __( 'Chapters', 'chapterwright' ),
	);
}
