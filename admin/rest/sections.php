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
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'mab_register_sections_routes' );

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
function mab_rest_validate_numeric_param( $value ) {
	return is_numeric( $value );
}

/**
 * Register the make-a-book/v1 section routes.
 */
function mab_register_sections_routes() {
	register_rest_route(
		'make-a-book/v1',
		'/books/(?P<book_id>\d+)/sections',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'mab_rest_get_sections',
				'permission_callback' => 'mab_rest_can_edit_book',
				'args'                => array(
					'book_id' => array(
						'required'          => true,
						'validate_callback' => 'mab_rest_validate_numeric_param',
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'mab_rest_create_section',
				'permission_callback' => 'mab_rest_can_edit_book',
				'args'                => array(
					'book_id'     => array(
						'required'          => true,
						'validate_callback' => 'mab_rest_validate_numeric_param',
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
		'make-a-book/v1',
		'/books/(?P<book_id>\d+)/sections/reorder',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'mab_rest_reorder_sections',
			'permission_callback' => 'mab_rest_can_edit_book',
			'args'                => array(
				'book_id' => array(
					'required'          => true,
					'validate_callback' => 'mab_rest_validate_numeric_param',
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
		'make-a-book/v1',
		'/sections/(?P<id>\d+)',
		array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'mab_rest_update_section',
				'permission_callback' => 'mab_rest_can_edit_section',
				'args'                => array(
					'id'          => array(
						'required'          => true,
						'validate_callback' => 'mab_rest_validate_numeric_param',
					),
					'name'        => array( 'type' => 'string' ),
					'description' => array( 'type' => 'string' ),
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => 'mab_rest_delete_section',
				'permission_callback' => 'mab_rest_can_edit_section',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => 'mab_rest_validate_numeric_param',
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
function mab_rest_can_edit_book( $request ) {
	$book_id = (int) $request['book_id'];

	if ( MAB_BOOK_POST_TYPE !== get_post_type( $book_id ) ) {
		return new WP_Error( 'mab_rest_book_not_found', __( 'That book does not exist.', 'make-a-book' ), array( 'status' => 404 ) );
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
function mab_rest_can_edit_section( $request ) {
	$section = mab_get_section( (int) $request['id'] );

	if ( ! $section ) {
		return new WP_Error( 'mab_rest_section_not_found', __( 'That section no longer exists.', 'make-a-book' ), array( 'status' => 404 ) );
	}

	return current_user_can( 'edit_post', $section['book_id'] );
}

/**
 * GET /books/{book_id}/sections
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response
 */
function mab_rest_get_sections( $request ) {
	return rest_ensure_response( mab_get_book_sections( (int) $request['book_id'] ) );
}

/**
 * POST /books/{book_id}/sections
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function mab_rest_create_section( $request ) {
	$section_id = mab_insert_section(
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

	return rest_ensure_response( mab_get_section( $section_id ) );
}

/**
 * POST /books/{book_id}/sections/reorder
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function mab_rest_reorder_sections( $request ) {
	$result = mab_reorder_sections( (int) $request['book_id'], array_map( 'absint', (array) $request['order'] ) );

	if ( is_wp_error( $result ) ) {
		$result->add_data( array( 'status' => 400 ) );
		return $result;
	}

	return rest_ensure_response( mab_get_book_sections( (int) $request['book_id'] ) );
}

/**
 * PUT/PATCH /sections/{id}
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function mab_rest_update_section( $request ) {
	$args = array();
	foreach ( array( 'name', 'description', 'menu_order' ) as $field ) {
		if ( null !== $request->get_param( $field ) ) {
			$args[ $field ] = $request->get_param( $field );
		}
	}

	$result = mab_update_section( (int) $request['id'], $args );

	if ( is_wp_error( $result ) ) {
		$result->add_data( array( 'status' => 400 ) );
		return $result;
	}

	return rest_ensure_response( mab_get_section( (int) $request['id'] ) );
}

/**
 * DELETE /sections/{id}
 *
 * @param WP_REST_Request $request Current request.
 * @return WP_REST_Response|WP_Error
 */
function mab_rest_delete_section( $request ) {
	$result = mab_delete_section( (int) $request['id'] );

	if ( is_wp_error( $result ) ) {
		$result->add_data( array( 'status' => 400 ) );
		return $result;
	}

	return rest_ensure_response( array( 'deleted' => true ) );
}
