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

/**
 * Register the Book and Chapter custom post types.
 *
 * Both types support the block editor and the WordPress REST API. Books
 * have a public archive at /books/; chapters are reached through their own
 * permalinks under /book-chapter/. The post type keys themselves live in
 * MAB_BOOK_POST_TYPE / MAB_CHAPTER_POST_TYPE (defined in make-a-book.php)
 * and are part of the plugin's stable data contract — see AGENTS.md.
 */
function mab_register_post_types() {
	register_post_type(
		MAB_BOOK_POST_TYPE,
		array(
			'labels'       => mab_book_labels(),
			'public'       => true,
			'has_archive'  => 'books',
			'rewrite'      => array( 'slug' => 'books' ),
			'menu_icon'    => 'dashicons-book-alt',
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
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			// Nest under the Books admin menu (relabeled "Make a Book" — see
			// mab_book_labels()) instead of giving Chapters its own
			// top-level menu, so Books, Chapters, and Settings all live
			// under one entry in the admin sidebar.
			'show_in_menu' => 'edit.php?post_type=' . MAB_BOOK_POST_TYPE,
		)
	);
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
