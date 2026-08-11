<?php
/**
 * Shared content queries used by templates, admin screens, and the block.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch all published chapters assigned to a book in reading order.
 *
 * Chapters first sort by the numeric `_mab_order` value. Publication date is
 * a stable fallback when two chapters share the same order value.
 *
 * @param int $book_id Book post ID.
 * @return WP_Post[] Ordered published chapter posts.
 */
function mab_get_chapters( $book_id ) {
	return get_posts(
		array(
			'post_type'      => MAB_CHAPTER_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_mab_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for editor-defined chapter order.
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'date'           => 'ASC',
			),
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

/**
 * Fetch chapters assigned to a book regardless of status, for admin screens.
 *
 * Used by the Book Details meta box so authors can see draft and pending
 * chapters, not only published ones, while building out a book.
 *
 * @param int $book_id Book post ID.
 * @return WP_Post[] Chapters ordered by `_mab_order`.
 */
function mab_get_all_chapters_for_admin( $book_id ) {
	return get_posts(
		array(
			'post_type'      => MAB_CHAPTER_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'meta_key'       => '_mab_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for editor-defined chapter order.
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'date'           => 'ASC',
			),
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

/**
 * Suggest the next chapter order number for a book.
 *
 * Used to prefill new chapters with a sensible order value so authors do not
 * have to look up the highest existing chapter number by hand.
 *
 * @param int $book_id Book post ID.
 * @return int One past the highest existing `_mab_order` value, or 1 if the book has no chapters yet.
 */
function mab_get_next_chapter_order( $book_id ) {
	$chapters = mab_get_all_chapters_for_admin( $book_id );
	$highest  = 0;

	foreach ( $chapters as $chapter ) {
		$highest = max( $highest, absint( get_post_meta( $chapter->ID, '_mab_order', true ) ) );
	}

	return $highest + 1;
}
