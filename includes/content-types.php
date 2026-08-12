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
 */
function mab_register_post_types() {
	register_post_type(
		MAB_BOOK_POST_TYPE,
		array(
			'labels'       => mab_book_labels(),
			'public'       => true,
			'has_archive'  => 'books',
			'rewrite'      => array( 'slug' => 'books' ),
			'show_in_menu' => false,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
		)
	);

	register_post_type(
		MAB_CHAPTER_POST_TYPE,
		array(
			'labels'       => mab_chapter_labels(),
			'public'       => true,
			'has_archive'  => false,
			'rewrite'      => array( 'slug' => 'book-chapter' ),
			'show_in_menu' => false,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
		)
	);
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
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback' => 'mab_meta_auth_callback',
		)
	);

	register_post_meta(
		MAB_BOOK_POST_TYPE,
		'_mab_accent',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'sanitize_callback' => 'mab_sanitize_accent_color',
			'auth_callback' => 'mab_meta_auth_callback',
		)
	);

	register_post_meta(
		MAB_CHAPTER_POST_TYPE,
		'_mab_book_id',
		array(
			'type'          => 'integer',
			'single'        => true,
			'show_in_rest'  => true,
			'sanitize_callback' => 'absint',
			'auth_callback' => 'mab_meta_auth_callback',
		)
	);

	register_post_meta(
		MAB_CHAPTER_POST_TYPE,
		'_mab_order',
		array(
			'type'          => 'integer',
			'single'        => true,
			'show_in_rest'  => true,
			'sanitize_callback' => 'absint',
			'auth_callback' => 'mab_meta_auth_callback',
		)
	);

	register_post_meta(
		MAB_CHAPTER_POST_TYPE,
		'_mab_section_id',
		array(
			'type'          => 'integer',
			'single'        => true,
			'show_in_rest'  => true,
			'sanitize_callback' => 'absint',
			'auth_callback' => 'mab_meta_auth_callback',
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
