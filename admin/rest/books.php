<?php
/**
 * REST controller for the admin app's trash screen.
 *
 * Trashing a book, and every non-trash book listing/editing, already goes
 * through the core `/wp/v2/hsrtech_book` endpoint — see api.js. Two things
 * core doesn't expose over REST, though:
 *
 * - Listing what's currently in the trash. Until now the admin app's "View
 *   trashed books" link sent authors out to wp-admin's own Books list
 *   filtered to `post_status=trash` for this — a context switch to a screen
 *   this plugin doesn't otherwise use, and one more UI to learn. This route
 *   lists trashed books the same way chapters.php lists a book's chapters:
 *   `get_posts()` directly, so the admin app can show and act on them without
 *   leaving.
 * - Restoring one. Core's REST posts controller can trash a post (DELETE)
 *   and can force-permanently-delete one (DELETE ?force=true — used directly
 *   from the admin app for "Delete permanently"; no custom route needed for
 *   that half), but it has no "untrash" verb of its own.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'hsrtech_register_books_routes' );

/**
 * Register the chapterwright/v1 book-trash routes.
 */
function hsrtech_register_books_routes() {
	register_rest_route(
		'chapterwright/v1',
		'/books/trash',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'hsrtech_rest_get_trashed_books',
			'permission_callback' => 'hsrtech_rest_can_list_trashed_books',
		)
	);

	register_rest_route(
		'chapterwright/v1',
		'/books/(?P<id>\d+)/restore',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'hsrtech_rest_restore_book',
			'permission_callback' => 'hsrtech_rest_can_restore_book',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => 'hsrtech_rest_validate_numeric_param',
				),
			),
		)
	);
}

/**
 * Permission check for GET /books/trash.
 *
 * Mirrors chapterwright/list-books' own rule (includes/abilities.php):
 * 'edit_hsrtech_books' to see the trash at all. Whether the caller sees
 * every author's trashed books or only their own is decided inside the
 * callback below, same as that ability does for the non-trashed list.
 *
 * @return bool
 */
function hsrtech_rest_can_list_trashed_books() {
	return current_user_can( 'edit_hsrtech_books' );
}

/**
 * GET /books/trash
 *
 * @return WP_REST_Response
 */
function hsrtech_rest_get_trashed_books() {
	$args = array(
		'post_type'      => HSRTECH_BOOK_POST_TYPE,
		'post_status'    => 'trash',
		'posts_per_page' => -1,
		'orderby'        => 'modified',
		'order'          => 'DESC',
	);

	// Without 'edit_others_hsrtech_books', only show the caller's own
	// trashed books — the same restriction hsrtech_ability_list_books()
	// (includes/abilities.php) applies to the non-trashed list, for the
	// same capability_type.
	if ( ! current_user_can( 'edit_others_hsrtech_books' ) ) {
		$args['author'] = get_current_user_id();
	}

	$books = get_posts( $args );

	return rest_ensure_response( array_map( 'hsrtech_prepare_trashed_book_for_response', $books ) );
}

/**
 * Shape a trashed Book post for the admin app's trash screen.
 *
 * @param WP_Post $book Book post, already confirmed post_status = trash.
 * @return array<string,mixed>
 */
function hsrtech_prepare_trashed_book_for_response( $book ) {
	// wp_trash_post() records the exact moment a post was trashed in its own
	// postmeta key. Using that instead of post_modified — which core also
	// touches for reasons unrelated to trashing — is what makes "Trashed on
	// …" below actually mean when it was trashed.
	$trashed_time = (int) get_post_meta( $book->ID, '_wp_trash_meta_time', true );

	return array(
		'id'      => $book->ID,
		'title'   => array(
			'raw'      => $book->post_title,
			'rendered' => get_the_title( $book ),
		),
		'trashed' => $trashed_time
			? array(
				'timestamp' => $trashed_time,
				'display'   => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $trashed_time ),
			)
			: null,
	);
}

/**
 * Permission check for POST /books/{id}/restore.
 *
 * Matches the capability WordPress core itself requires for the "Restore"
 * row action in wp-admin's own trashed-posts list —
 * `current_user_can( 'delete_post', $id )`, not `edit_post`. Restoring and
 * permanently deleting are both gated on the delete capability there, not
 * the edit one, and this mirrors that rather than inventing a different
 * rule for the same action.
 *
 * @param WP_REST_Request $request Current request.
 * @return bool|WP_Error
 */
function hsrtech_rest_can_restore_book( $request ) {
	$book_id = (int) $request['id'];

	if ( HSRTECH_BOOK_POST_TYPE !== get_post_type( $book_id ) ) {
		return new WP_Error( 'hsrtech_rest_book_not_found', __( 'That book does not exist.', 'chapterwright' ), array( 'status' => 404 ) );
	}

	return current_user_can( 'delete_post', $book_id );
}

/**
 * POST /books/{id}/restore
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function hsrtech_rest_restore_book( $request ) {
	$book_id = (int) $request['id'];

	if ( 'trash' !== get_post_status( $book_id ) ) {
		return new WP_Error( 'hsrtech_rest_book_not_trashed', __( 'That book is not in the trash.', 'chapterwright' ), array( 'status' => 400 ) );
	}

	$restored = wp_untrash_post( $book_id );

	if ( ! $restored ) {
		return new WP_Error( 'hsrtech_rest_restore_failed', __( 'The book could not be restored.', 'chapterwright' ), array( 'status' => 500 ) );
	}

	$book = get_post( $book_id );

	return rest_ensure_response(
		array(
			'id'     => $book->ID,
			'title'  => array(
				'raw'      => $book->post_title,
				'rendered' => get_the_title( $book ),
			),
			'status' => $book->post_status,
		)
	);
}
