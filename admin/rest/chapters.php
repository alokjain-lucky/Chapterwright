<?php
/**
 * REST controller for listing a book's chapters and bulk reordering them.
 *
 * Changing one chapter's own book/section/order at a time already works
 * through the core `/wp/v2/mab_chapter/{id}` endpoint, because those meta
 * fields are registered for REST in includes/content-types.php. This
 * controller covers what core can't express as cleanly:
 *
 * - Listing every chapter that belongs to one book, across every status an
 *   author needs to see (draft, pending, private, future, not just
 *   published). The obvious alternative — querying the core
 *   `/wp/v2/mab_chapter` collection with `status` as an array and filtering
 *   the result client-side by `_mab_book_id` — is what the admin app used
 *   through 2.3.1, and is fragile in exactly the way that class of query
 *   tends to be: it depends on the collection endpoint's own
 *   status/context capability handling behaving as expected for every
 *   status in the array, across every request. The route below instead
 *   wraps `mab_get_all_chapters_for_admin()` (includes/queries.php), the
 *   same plain `get_posts()` query already trusted elsewhere in this
 *   plugin (admin/list-table.php, includes/abilities.php) — one door, not
 *   two, for "every chapter in this book regardless of status."
 * - Reordering several chapters' section and order values together after a
 *   drag-and-drop reorder in the admin app, so the tree never ends up
 *   partially updated.
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
		'/books/(?P<book_id>\d+)/chapters',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'mab_rest_get_book_chapters',
			'permission_callback' => 'mab_rest_can_edit_book',
			'args'                => array(
				'book_id' => array(
					'required'          => true,
					'validate_callback' => 'mab_rest_validate_numeric_param',
				),
			),
		)
	);

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
					'validate_callback' => 'mab_rest_validate_numeric_param',
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
 * GET /books/{book_id}/chapters
 *
 * Every chapter belonging to the book, in `_mab_order` order, regardless of
 * status — see the file docblock for why this exists instead of querying
 * the core collection endpoint.
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response
 */
function mab_rest_get_book_chapters( $request ) {
	$chapters = mab_get_all_chapters_for_admin( (int) $request['book_id'] );

	return rest_ensure_response( array_map( 'mab_prepare_chapter_for_response', $chapters ) );
}

/**
 * Shape a Chapter post the way the admin app's JavaScript expects — the
 * same `{ id, title: { raw, rendered }, status, meta }` shape a core
 * `/wp/v2/mab_chapter` response has, trimmed to just the fields the admin
 * app actually reads.
 *
 * @param WP_Post $chapter Chapter post.
 * @return array<string,mixed>
 */
function mab_prepare_chapter_for_response( $chapter ) {
	return array(
		'id'     => $chapter->ID,
		'title'  => array(
			'raw'      => $chapter->post_title,
			'rendered' => get_the_title( $chapter ),
		),
		'status' => $chapter->post_status,
		'meta'   => array(
			'_mab_book_id'    => absint( get_post_meta( $chapter->ID, '_mab_book_id', true ) ),
			'_mab_order'      => absint( get_post_meta( $chapter->ID, '_mab_order', true ) ),
			'_mab_section_id' => absint( get_post_meta( $chapter->ID, '_mab_section_id', true ) ),
		),
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

		if ( absint( get_post_meta( $chapter_id, '_mab_book_id', true ) ) !== $book_id ) {
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
