<?php
/**
 * REST controller for bulk chapter reordering.
 *
 * Reading a chapter's own book/section/order and changing one field at a
 * time already works through the core `/wp/v2/mab_chapter/{id}` endpoint,
 * because those meta fields are registered for REST in
 * includes/content-types.php. This controller exists only for the one
 * operation core cannot express in a single request: after a drag-and-drop
 * reorder in the admin app, several chapters' section and order values need
 * to change together so the list never renders in a half-updated state.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'mab_register_chapters_routes' );

/**
 * Register the make-a-book/v1 chapter routes.
 */
function mab_register_chapters_routes() {
	register_rest_route(
		'make-a-book/v1',
		'/books/(?P<book_id>\d+)/chapters/reorder',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'mab_rest_reorder_chapters',
			'permission_callback' => 'mab_rest_can_edit_book',
			'args'                => array(
				'book_id'  => array(
					'required'          => true,
					'validate_callback' => 'is_numeric',
				),
				'chapters' => array(
					'required' => true,
					'type'     => 'array',
				),
			),
		)
	);
}

/**
 * POST /books/{book_id}/chapters/reorder
 *
 * Expects `chapters` as a list of `{ id, section_id, order }` objects
 * describing every chapter's new position. Every chapter must already
 * belong to `book_id` and be editable by the current user; the whole
 * request is rejected (nothing is written) if any entry fails either check,
 * so the tree never ends up partially reordered.
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function mab_rest_reorder_chapters( $request ) {
	$book_id  = (int) $request['book_id'];
	$chapters = (array) $request['chapters'];

	$updates = array();

	foreach ( $chapters as $entry ) {
		$chapter_id = isset( $entry['id'] ) ? absint( $entry['id'] ) : 0;

		if ( ! $chapter_id || MAB_CHAPTER_POST_TYPE !== get_post_type( $chapter_id ) ) {
			return new WP_Error( 'mab_rest_chapter_not_found', __( 'One of those chapters does not exist.', 'make-a-book' ), array( 'status' => 404 ) );
		}

		if ( $book_id !== absint( get_post_meta( $chapter_id, '_mab_book_id', true ) ) ) {
			return new WP_Error( 'mab_rest_chapter_mismatch', __( 'One of those chapters does not belong to this book.', 'make-a-book' ), array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_post', $chapter_id ) ) {
			return new WP_Error( 'mab_rest_forbidden', __( 'You are not allowed to reorder one of those chapters.', 'make-a-book' ), array( 'status' => 403 ) );
		}

		if ( isset( $entry['section_id'] ) && $entry['section_id'] ) {
			$section = mab_get_section( absint( $entry['section_id'] ) );
			if ( ! $section || $book_id !== (int) $section['book_id'] ) {
				return new WP_Error( 'mab_rest_section_mismatch', __( 'One of those sections does not belong to this book.', 'make-a-book' ), array( 'status' => 400 ) );
			}
		}

		$updates[] = array(
			'chapter_id' => $chapter_id,
			'section_id' => isset( $entry['section_id'] ) ? absint( $entry['section_id'] ) : 0,
			'order'      => isset( $entry['order'] ) ? absint( $entry['order'] ) : 0,
		);
	}

	foreach ( $updates as $update ) {
		if ( $update['section_id'] ) {
			update_post_meta( $update['chapter_id'], '_mab_section_id', $update['section_id'] );
		} else {
			delete_post_meta( $update['chapter_id'], '_mab_section_id' );
		}
		update_post_meta( $update['chapter_id'], '_mab_order', $update['order'] );
	}

	return rest_ensure_response( array( 'updated' => wp_list_pluck( $updates, 'chapter_id' ) ) );
}
