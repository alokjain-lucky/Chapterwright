<?php
/**
 * Chapters list-table columns and the "filter by book" dropdown.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'manage_' . MAB_CHAPTER_POST_TYPE . '_posts_columns', 'mab_chapter_columns' );
add_action( 'manage_' . MAB_CHAPTER_POST_TYPE . '_posts_custom_column', 'mab_chapter_column_content', 10, 2 );
add_action( 'restrict_manage_posts', 'mab_chapter_book_filter' );
add_filter( 'parse_query', 'mab_filter_chapters_by_book' );

/**
 * Add the parent Book and reading order to the Chapters list table.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string> Filtered columns.
 */
function mab_chapter_columns( $columns ) {
	$columns['mab_book']  = __( 'Book', 'make-a-book' );
	$columns['mab_order'] = __( 'Order', 'make-a-book' );
	return $columns;
}

/**
 * Output one custom Chapters list-table cell.
 *
 * @param string $column  Column identifier.
 * @param int    $post_id Chapter post ID.
 */
function mab_chapter_column_content( $column, $post_id ) {
	if ( 'mab_book' === $column ) {
		$book_id = absint( get_post_meta( $post_id, '_mab_book_id', true ) );
		echo $book_id ? esc_html( get_the_title( $book_id ) ) : '&mdash;';
	} elseif ( 'mab_order' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_mab_order', true ) );
	}
}

/**
 * Add a "filter by book" dropdown above the Chapters list table.
 *
 * Lets an author viewing a book with many chapters narrow the list instead
 * of scanning every chapter across every book.
 *
 * @param string $post_type Current list-table post type.
 */
function mab_chapter_book_filter( $post_type ) {
	if ( MAB_CHAPTER_POST_TYPE !== $post_type ) {
		return;
	}

	$books = get_posts(
		array(
			'post_type'   => MAB_BOOK_POST_TYPE,
			'post_status' => array( 'publish', 'draft', 'private' ),
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);

	if ( ! $books ) {
		return;
	}

	$selected = isset( $_GET['mab_book_filter'] ) ? absint( wp_unslash( $_GET['mab_book_filter'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
	?>
	<label class="screen-reader-text" for="mab_book_filter"><?php esc_html_e( 'Filter chapters by book', 'make-a-book' ); ?></label>
	<select name="mab_book_filter" id="mab_book_filter">
		<option value=""><?php esc_html_e( 'All books', 'make-a-book' ); ?></option>
		<?php foreach ( $books as $book ) : ?>
			<option value="<?php echo esc_attr( (string) $book->ID ); ?>" <?php selected( $selected, $book->ID ); ?>><?php echo esc_html( $book->post_title ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Apply the "filter by book" dropdown to the Chapters list-table query.
 *
 * @param WP_Query $query Current admin query.
 */
function mab_filter_chapters_by_book( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( MAB_CHAPTER_POST_TYPE !== $query->get( 'post_type' ) ) {
		return;
	}

	$book_id = isset( $_GET['mab_book_filter'] ) ? absint( wp_unslash( $_GET['mab_book_filter'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
	if ( ! $book_id ) {
		return;
	}

	$query->set(
		'meta_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Chapter-to-book relationship is stored as post meta.
		array(
			array(
				'key'     => '_mab_book_id',
				'value'   => $book_id,
				'compare' => '=',
			),
		)
	);
}
