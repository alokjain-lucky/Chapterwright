<?php
/**
 * Book and chapter content registration and queries.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the plugin's public data model.
 */
final class Make_A_Book_Content_Types {
	/** Book post type key. */
	const BOOK_POST_TYPE = 'mab_book';

	/** Chapter post type key. */
	const CHAPTER_POST_TYPE = 'mab_chapter';

	/** Register hooks for content types. */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the Book and Chapter custom post types.
	 *
	 * Both types support the block editor and WordPress REST API. Books have a
	 * public archive, while chapters are reached through their own permalinks.
	 */
	public static function register() {
		register_post_type(
			self::BOOK_POST_TYPE,
			array(
				'labels'       => self::book_labels(),
				'public'       => true,
				'has_archive'  => 'books',
				'rewrite'      => array( 'slug' => 'books' ),
				'menu_icon'    => 'dashicons-book-alt',
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
			)
		);

		register_post_type(
			self::CHAPTER_POST_TYPE,
			array(
				'labels'       => self::chapter_labels(),
				'public'       => true,
				'has_archive'  => false,
				'rewrite'      => array( 'slug' => 'book-chapter' ),
				'menu_icon'    => 'dashicons-media-document',
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			)
		);
	}

	/**
	 * Return labels used by the Book editor.
	 *
	 * @return array<string,string> Translated post type labels.
	 */
	private static function book_labels() {
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
			'menu_name'          => __( 'Books', 'make-a-book' ),
		);
	}

	/**
	 * Return labels used by the Chapter editor.
	 *
	 * @return array<string,string> Translated post type labels.
	 */
	private static function chapter_labels() {
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

	/**
	 * Fetch all published chapters assigned to a book in reading order.
	 *
	 * Chapters first sort by the numeric `_mab_order` value. Publication date is
	 * a stable fallback when two chapters have the same order value.
	 *
	 * @param int $book_id Book post ID.
	 * @return WP_Post[] Ordered published chapter posts.
	 */
	public static function get_chapters( $book_id ) {
		return get_posts(
			array(
				'post_type'      => self::CHAPTER_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => '_mab_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for editor-defined chapter order.
				'orderby'        => array( 'meta_value_num' => 'ASC', 'date' => 'ASC' ),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Chapter-to-book relationship is stored as post meta.
					array(
						'key'     => '_mab_book_id',
						'value'   => absint( $book_id ),
						'compare' => '=',
					),
				),
			)
		);
	}
}
