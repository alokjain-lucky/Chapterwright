<?php
/**
 * WordPress Abilities API registration.
 *
 * The Abilities API (WordPress 6.9+) is a discoverable registry that lets AI
 * agents, automation tools, and other plugins find and invoke a plugin's
 * capabilities in a standardized, schema-validated way — see
 * https://developer.wordpress.org/apis/abilities-api/. It is strictly
 * additive: everything registered here is a thin wrapper around the exact
 * same functions the REST controllers in admin/rest/ already call, so there
 * is one source of truth for "what can be done to a book," not two.
 *
 * This plugin's minimum supported version stays WordPress 6.4 (see the
 * plugin header and README), so registration here is feature-detected
 * rather than required: on WordPress 6.8 and earlier, the
 * `wp_abilities_api_init` / `wp_abilities_api_categories_init` action hooks
 * simply do not exist, these callbacks never run, and nothing else in the
 * plugin depends on them.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_abilities_api_categories_init', 'mab_register_ability_category' );
add_action( 'wp_abilities_api_init', 'mab_register_abilities' );

/**
 * Register this plugin's ability category.
 */
function mab_register_ability_category() {
	if ( ! function_exists( 'wp_register_ability_category' ) ) {
		return;
	}

	// phpcs:ignore PluginCheck.CodeAnalysis.WP.WPVersion, WordPress.WP.WordPressVersion -- Guarded above by function_exists( 'wp_register_ability_category' ); never reached on WordPress < 6.9, where this plugin's minimum-supported version stops needing it.
	wp_register_ability_category(
		'make-a-book',
		array(
			'label'       => __( 'Make a Book', 'make-a-book' ),
			'description' => __( 'Create and organize web-native ebooks: books, chapters, and the sections that group them.', 'make-a-book' ),
		)
	);
}

/**
 * Register every ability this plugin exposes.
 */
function mab_register_abilities() {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	// phpcs:ignore PluginCheck.CodeAnalysis.WP.WPVersion, WordPress.WP.WordPressVersion -- Guarded above by function_exists( 'wp_register_ability' ); never reached on WordPress < 6.9.
	wp_register_ability(
		'make-a-book/list-books',
		array(
			'label'               => __( 'List books', 'make-a-book' ),
			'description'         => __( 'Returns every book, including drafts, with its chapter count.', 'make-a-book' ),
			'category'            => 'make-a-book',
			'input_schema'        => array(),
			'output_schema'       => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'            => array( 'type' => 'integer' ),
						'title'         => array( 'type' => 'string' ),
						'status'        => array( 'type' => 'string' ),
						'chapter_count' => array( 'type' => 'integer' ),
					),
				),
			),
			'execute_callback'    => 'mab_ability_list_books',
			'permission_callback' => function () {
				// 'edit_mab_books' as of 2.2.0 — Books have their own
				// capability_type now, see mab_register_post_types().
				return current_user_can( 'edit_mab_books' );
			},
			'meta'                => array(
				'annotations'  => array( 'readonly' => true ),
				'show_in_rest' => true,
			),
		)
	);

	// phpcs:ignore PluginCheck.CodeAnalysis.WP.WPVersion, WordPress.WP.WordPressVersion -- Guarded above by function_exists( 'wp_register_ability' ); never reached on WordPress < 6.9.
	wp_register_ability(
		'make-a-book/get-book-overview',
		array(
			'label'               => __( 'Get book overview', 'make-a-book' ),
			'description'         => __( 'Returns a book with its sections and chapters, grouped and ordered exactly as they appear in the table of contents.', 'make-a-book' ),
			'category'            => 'make-a-book',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'book_id' => array(
						'type'        => 'integer',
						'description' => __( 'Book post ID.', 'make-a-book' ),
					),
				),
				'required'   => array( 'book_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'mab_ability_get_book_overview',
			'permission_callback' => function ( $input ) {
				return current_user_can( 'edit_post', (int) $input['book_id'] );
			},
			'meta'                => array(
				'annotations'  => array( 'readonly' => true ),
				'show_in_rest' => true,
			),
		)
	);

	// phpcs:ignore PluginCheck.CodeAnalysis.WP.WPVersion, WordPress.WP.WordPressVersion -- Guarded above by function_exists( 'wp_register_ability' ); never reached on WordPress < 6.9.
	wp_register_ability(
		'make-a-book/create-section',
		array(
			'label'               => __( 'Create a book section', 'make-a-book' ),
			'description'         => __( 'Adds a named, describable grouping (e.g. "Part I") that chapters can be assigned to within one book.', 'make-a-book' ),
			'category'            => 'make-a-book',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'book_id'     => array( 'type' => 'integer' ),
					'name'        => array( 'type' => 'string' ),
					'description' => array( 'type' => 'string' ),
				),
				'required'   => array( 'book_id', 'name' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'mab_ability_create_section',
			'permission_callback' => function ( $input ) {
				return current_user_can( 'edit_post', (int) $input['book_id'] );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
				'show_in_rest' => true,
			),
		)
	);

	// phpcs:ignore PluginCheck.CodeAnalysis.WP.WPVersion, WordPress.WP.WordPressVersion -- Guarded above by function_exists( 'wp_register_ability' ); never reached on WordPress < 6.9.
	wp_register_ability(
		'make-a-book/create-chapter',
		array(
			'label'               => __( 'Create a chapter', 'make-a-book' ),
			'description'         => __( 'Creates a new draft chapter assigned to a book, optionally within one of its sections, ready to be written up in the block editor.', 'make-a-book' ),
			'category'            => 'make-a-book',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'book_id'    => array( 'type' => 'integer' ),
					'title'      => array( 'type' => 'string' ),
					'section_id' => array( 'type' => 'integer' ),
					'content'    => array(
						'type'        => 'string',
						'description' => __( 'Optional starting content, as block markup or plain text.', 'make-a-book' ),
					),
				),
				'required'   => array( 'book_id', 'title' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'mab_ability_create_chapter',
			'permission_callback' => function ( $input ) {
				// Must be able to edit the target book, and to create a new
				// Chapter at all — 'edit_mab_chapters' as of 2.2.0, replacing
				// the generic 'edit_posts' (see mab_register_post_types()).
				return current_user_can( 'edit_post', (int) $input['book_id'] ) && current_user_can( 'edit_mab_chapters' );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
				'show_in_rest' => true,
			),
		)
	);

	// phpcs:ignore PluginCheck.CodeAnalysis.WP.WPVersion, WordPress.WP.WordPressVersion -- Guarded above by function_exists( 'wp_register_ability' ); never reached on WordPress < 6.9.
	wp_register_ability(
		'make-a-book/delete-section',
		array(
			'label'               => __( 'Delete a book section', 'make-a-book' ),
			'description'         => __( 'Removes a section. Chapters assigned to it are not deleted — they become unassigned and fall under the default "Chapters" heading.', 'make-a-book' ),
			'category'            => 'make-a-book',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'section_id' => array( 'type' => 'integer' ),
				),
				'required'   => array( 'section_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'mab_ability_delete_section',
			'permission_callback' => function ( $input ) {
				$section = mab_get_section( (int) $input['section_id'] );
				return $section && current_user_can( 'edit_post', $section['book_id'] );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);
}

/**
 * Execute callback for make-a-book/list-books.
 *
 * @return array<int,array<string,mixed>>
 */
function mab_ability_list_books() {
	$books = get_posts(
		array(
			'post_type'      => MAB_BOOK_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	return array_map(
		function ( $book ) {
			return array(
				'id'            => $book->ID,
				'title'         => get_the_title( $book ),
				'status'        => $book->post_status,
				'chapter_count' => count( mab_get_all_chapters_for_admin( $book->ID ) ),
			);
		},
		$books
	);
}

/**
 * Execute callback for make-a-book/get-book-overview.
 *
 * @param array<string,mixed> $input Ability input; only `book_id` is used.
 * @return array<string,mixed>|WP_Error
 */
function mab_ability_get_book_overview( $input ) {
	$book_id = (int) $input['book_id'];
	$book    = get_post( $book_id );

	if ( ! $book || MAB_BOOK_POST_TYPE !== $book->post_type ) {
		return new WP_Error( 'mab_ability_book_not_found', __( 'That book does not exist.', 'make-a-book' ) );
	}

	$chapters = mab_get_all_chapters_for_admin( $book_id );

	return array(
		'id'       => $book_id,
		'title'    => get_the_title( $book_id ),
		'subtitle' => get_post_meta( $book_id, '_mab_subtitle', true ),
		'sections' => mab_get_book_sections( $book_id ),
		'chapters' => array_map(
			function ( $chapter ) {
				return array(
					'id'         => $chapter->ID,
					'title'      => get_the_title( $chapter ),
					'status'     => $chapter->post_status,
					'section_id' => (int) get_post_meta( $chapter->ID, '_mab_section_id', true ),
					'order'      => (int) get_post_meta( $chapter->ID, '_mab_order', true ),
				);
			},
			$chapters
		),
	);
}

/**
 * Execute callback for make-a-book/create-section.
 *
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>|WP_Error
 */
function mab_ability_create_section( $input ) {
	$section_id = mab_insert_section(
		(int) $input['book_id'],
		array(
			'name'        => $input['name'],
			'description' => isset( $input['description'] ) ? $input['description'] : '',
		)
	);

	if ( is_wp_error( $section_id ) ) {
		return $section_id;
	}

	return mab_get_section( $section_id );
}

/**
 * Execute callback for make-a-book/create-chapter.
 *
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>|WP_Error
 */
function mab_ability_create_chapter( $input ) {
	$book_id = (int) $input['book_id'];

	if ( MAB_BOOK_POST_TYPE !== get_post_type( $book_id ) ) {
		return new WP_Error( 'mab_ability_book_not_found', __( 'That book does not exist.', 'make-a-book' ) );
	}

	if ( ! empty( $input['section_id'] ) ) {
		$section = mab_get_section( (int) $input['section_id'] );
		if ( ! $section || $book_id !== (int) $section['book_id'] ) {
			return new WP_Error( 'mab_ability_section_mismatch', __( 'That section does not belong to this book.', 'make-a-book' ) );
		}
	}

	$chapter_id = wp_insert_post(
		array(
			'post_type'    => MAB_CHAPTER_POST_TYPE,
			'post_title'   => sanitize_text_field( $input['title'] ),
			'post_content' => isset( $input['content'] ) ? wp_kses_post( $input['content'] ) : '',
			'post_status'  => 'draft',
		),
		true
	);

	if ( is_wp_error( $chapter_id ) ) {
		return $chapter_id;
	}

	update_post_meta( $chapter_id, '_mab_book_id', $book_id );
	update_post_meta( $chapter_id, '_mab_order', mab_get_next_chapter_order( $book_id ) );

	if ( ! empty( $input['section_id'] ) ) {
		update_post_meta( $chapter_id, '_mab_section_id', absint( $input['section_id'] ) );
	}

	return array(
		'id'        => $chapter_id,
		'edit_link' => get_edit_post_link( $chapter_id, 'raw' ),
	);
}

/**
 * Execute callback for make-a-book/delete-section.
 *
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>|WP_Error
 */
function mab_ability_delete_section( $input ) {
	$result = mab_delete_section( (int) $input['section_id'] );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return array( 'deleted' => true );
}
