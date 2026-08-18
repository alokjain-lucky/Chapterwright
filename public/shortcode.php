<?php
/**
 * [hsrtech_books] library shortcode.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'hsrtech_books', 'hsrtech_library_shortcode' );

/**
 * Render a grid of published books.
 *
 * Usage: `[hsrtech_books]` or `[hsrtech_books limit="6"]`.
 *
 * @param array<string,mixed>|string $atts User-supplied shortcode attributes.
 * @return string Escaped library HTML rendered by the book-grid template.
 */
function hsrtech_library_shortcode( $atts ) {
	$atts          = shortcode_atts( array( 'limit' => 12 ), (array) $atts, 'hsrtech_books' );
	$limit         = max( 1, min( 100, absint( $atts['limit'] ) ) );
	$hsrtech_query = new WP_Query(
		array(
			'post_type'      => HSRTECH_BOOK_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	ob_start();
	// templates/book-grid.php reads $hsrtech_query from this function's scope
	// (include() doesn't create a new one) — keep the name in sync if either
	// side changes.
	include HSRTECH_PATH . 'templates/book-grid.php';
	wp_reset_postdata();

	return (string) ob_get_clean();
}
