<?php
/**
 * REST controller for book sections.
 *
 * Sections live in a custom table (includes/sections.php), so unlike Book
 * and Chapter fields — which piggyback on the core `/wp/v2/{post_type}`
 * endpoints via register_post_meta() — sections need their own routes.
 * Every route is scoped to a specific book and checked against that book's
 * `edit_post` capability, the same permission the classic editor already
 * required to change a book's structure.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'hsrtech_register_sections_routes' );

/**
 * `validate_callback` for a numeric route/body param.
 *
 * WordPress calls every `validate_callback` with three arguments —
 * `( $value, $request, $param )`, see WP_REST_Request::has_valid_params()```
 * — not just the value. A bare `'is_numeric'` string as the callback
 * therefore fatals with "is_numeric() expects exactly 1 argument, 3 given"
 * on PHP 8, because `is_numeric()` is a built-in function and built-ins
 * enforce their exact declared argument count. A user-defined function does
 * not have that restriction — PHP silently ignores extra arguments — so
 * this thin wrapper is the fix, not a difference in what it validates.
 *
 * @param mixed $value Parameter value to validate.
 * @return bool Whether the value is numeric.
 */
function hsrtech_rest_validate_numeric_param( $value ) {
	return is_numeric( $value );
}

/**
 * Register the chapterwright/v1 section routes.
 */
function hsrtech_register_sections_routes() {
	register_rest_route(
		'chapterwright/v1',
		'/books/(?P<book_id>\d+)/sections',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'hsrtech_rest_get_sections',
				'permission_callback' => 'hsrtech_rest_can_edit_book',
				'args'                => array(
					'book_id' => array(
						'required'          => true,
						'validate_callback' => 'hsrtech_rest_validate_numeric_param',
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'hsrtech_rest_create_section',
				'permission_callback' => 'hsrtech_rest_can_edit_book',
				'args'                => array(
					'book_id'     => array(
						'required'          => true,
						'validate_callback' => 'hsrtech_rest_validate_numeric_param',
					),
					'name'        => array(
						'required' => true,
						'type'     => 'string',
					),
					'description' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			),
		)
	);

	register_rest_route(
		'chapterwright/v1',
		'/books/(?P<book_id>\d+)/sections/reorder',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'hsrtech_rest_reorder_sections',
			'permission_callback' => 'hsrtech_rest_can_edit_book',
			'args'                => array(
				'book_id' => array(
					'required'          => true,
					'validate_callback' => 'hsrtech_rest_validate_numeric_param',
				),
				'order'   => array(
					'required' => true,
					'type'     => 'array',
					'items'    => array( 'type' => 'integer' ),
				),
			),
		)
	);

	register_rest_route(
		'chapterwright/v1',
		'/sections/(?P<id>\d+)',
		array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'hsrtech_rest_update_section',
				'permission_callback' => 'hsrtech_rest_can_edit_section',
				'args'                => array(
					'id'          => array(
						'required'          => true,
						'validate_callback' => 'hsrtech_rest_validate_numeric_param',
					),
					'name'        => array( 'type' => 'string' ),
					'description' => array( 'type' => 'string' ),
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => 'hsrtech_rest_delete_section',
				'permission_callback' => 'hsrtech_rest_can_delete_section',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => 'hsrtech_rest_validate_numeric_param',
					),
				),
			),
		)
	);
}

/**
 * Permission check shared by every route scoped to a `book_id` URL param.
 *
 * @param WP_REST_Request $request Current request.
 * @return bool|WP_Error
 */
function hsrtech_rest_can_edit_book( $request ) {
	$book_id = (int) $request['book_id'];

	if ( HSRTECH_BOOK_POST_TYPE !== get_post_type( $book_id ) ) {
		return new WP_Error( 'hsrtech_rest_book_not_found', __( 'That book does not exist.', 'chapterwright' ), array( 'status' => 404 ) );
	}

	return current_user_can( 'edit_post', $book_id );
}

/**
 * Permission check for routes scoped to a section `id`: resolves the
 * section's owning book, then applies the same `edit_post` rule.
 *
 * @param WP_REST_Request $request Current request.
 * @return bool|WP_Error
 */
function hsrtech_rest_can_edit_section( $request ) {
	$section = hsrtech_get_section( (int) $request['id'] );

	if ( ! $section ) {
		return new WP_Error( 'hsrtech_rest_section_not_found', __( 'That section no longer exists.', 'chapterwright' ), array( 'status' => 404 ) );
	}

	return current_user_can( 'edit_post', $section['book_id'] );
}

/**
 * Permission check for `DELETE /sections/{id}` specifically — stronger than
 * hsrtech_rest_can_edit_section() above. Deleting a section also strips
 * `_hsrtech_section_id` from every chapter assigned to it (see
 * hsrtech_delete_section()), a write to each of those chapters, not just to
 * the section's own book. Shared with the delete-section Ability
 * (includes/abilities.php) via hsrtech_user_can_delete_section()
 * (includes/sections.php) so both enforce the same rule.
 *
 * @param WP_REST_Request $request Current request.
 * @return bool|WP_Error
 */
function hsrtech_rest_can_delete_section( $request ) {
	$section = hsrtech_get_section( (int) $request['id'] );

	if ( ! $section ) {
		return new WP_Error( 'hsrtech_rest_section_not_found', __( 'That section no longer exists.', 'chapterwright' ), array( 'status' => 404 ) );
	}

	return hsrtech_user_can_delete_section( $section );
}

/**
 * GET /books/{book_id}/sections
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response
 */
function hsrtech_rest_get_sections( $request ) {
	return rest_ensure_response( hsrtech_get_book_sections( (int) $request['book_id'] ) );
}

/**
 * POST /books/{book_id}/sections
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function hsrtech_rest_create_section( $request ) {
	$section_id = hsrtech_insert_section(
		(int) $request['book_id'],
		array(
			'name'        => $request['name'],
			'description' => $request['description'],
		)
	);

	if ( is_wp_error( $section_id ) ) {
		$section_id->add_data( array( 'status' => 400 ) );
		return $section_id;
	}

	return rest_ensure_response( hsrtech_get_section( $section_id ) );
}

/**
 * POST /books/{book_id}/sections/reorder
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function hsrtech_rest_reorder_sections( $request ) {
	$result = hsrtech_reorder_sections( (int) $request['book_id'], array_map( 'absint', (array) $request['order'] ) );

	if ( is_wp_error( $result ) ) {
		$result->add_data( array( 'status' => 400 ) );
		return $result;
	}

	return rest_ensure_response( hsrtech_get_book_sections( (int) $request['book_id'] ) );
}

/**
 * PUT/PATCH /sections/{id}
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function hsrtech_rest_update_section( $request ) {
	$args = array();
	foreach ( array( 'name', 'description', 'menu_order' ) as $field ) {
		if ( null !== $request->get_param( $field ) ) {
			$args[ $field ] = $request->get_param( $field );
		}
	}

	$result = hsrtech_update_section( (int) $request['id'], $args );

	if ( is_wp_error( $result ) ) {
		$result->add_data( array( 'status' => 400 ) );
		return $result;
	}

	return rest_ensure_response( hsrtech_get_section( (int) $request['id'] ) );
}

/**
 * DELETE /sections/{id}
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function hsrtech_rest_delete_section( $request ) {
	$result = hsrtech_delete_section( (int) $request['id'] );

	if ( is_wp_error( $result ) ) {
		$result->add_data( array( 'status' => 400 ) );
		return $result;
	}

	return rest_ensure_response( array( 'deleted' => true ) );
}
