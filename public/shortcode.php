<?php
/**
 * [make_a_book] library shortcode.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'make_a_book', 'mab_library_shortcode' );

/**
 * Render a grid of published books.
 *
 * Usage: `[make_a_book]` or `[make_a_book limit="6"]`.
 *
 * @param array<string,mixed>|string $atts User-supplied shortcode attributes.
 * @return string Escaped library HTML rendered by the book-grid template.
 */
function mab_library_shortcode( $atts ) {
	$atts  = shortcode_atts( array( 'limit' => 12 ), (array) $atts, 'make_a_book' );
	$limit = max( 1, min( 100, absint( $atts['limit'] ) ) );
	$query = new WP_Query(
		array(
			'post_type'      => MAB_BOOK_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	ob_start();
	include MAKE_A_BOOK_PATH . 'templates/book-grid.php';
	wp_reset_postdata();

	return (string) ob_get_clean();
}
