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
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_abilities_api_categories_init', 'hsrtech_register_ability_category' );
add_action( 'wp_abilities_api_init', 'hsrtech_register_abilities' );

/**
 * Register this plugin's ability category.
 */
function hsrtech_register_ability_category() {
	if ( ! function_exists( 'wp_register_ability_category' ) ) {
		return;
	}

	// phpcs:ignore PluginCheck.CodeAnalysis.WP.WPVersion, WordPress.WP.WordPressVersion -- Guarded above by function_exists( 'wp_register_ability_category' ); never reached on WordPress < 6.9, where this plugin's minimum-supported version stops needing it.
	wp_register_ability_category(
		'chapterwright',
		array(
			'label'       => __( 'Chapterwright', 'chapterwright' ),
			'description' => __( 'Create and organize web-native ebooks: books, chapters, and the sections that group them.', 'chapterwright' ),
		)
	);
}

/**
 * Register every ability this plugin exposes.
 */
function hsrtech_register_abilities() {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	// phpcs:ignore PluginCheck.CodeAnalysis.WP.WPVersion, WordPress.WP.WordPressVersion -- Guarded above by function_exists( 'wp_register_ability' ); never reached on WordPress < 6.9.
	wp_register_ability(
		'chapterwright/list-books',
		array(
			'label'               => __( 'List books', 'chapterwright' ),
			'description'         => __( 'Returns every book, including drafts, with its chapter count.', 'chapterwright' ),
			'category'            => 'chapterwright',
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
			'execute_callback'    => 'hsrtech_ability_list_books',
			'permission_callback' => function () {
				// 'edit_hsrtech_books' as of 2.2.0 — Books have their own
				// capability_type now, see hsrtech_register_post_types(). This
				// only gates whether the ability can be called at all; it does
				// not by itself mean the caller can see every author's drafts
				// and private books — hsrtech_ability_list_books() below
				// additionally restricts the returned list to the caller's own
				// books unless they also have 'edit_others_hsrtech_books',
				// mirroring the classic admin list table's own restriction for
				// this capability_type.
				return current_user_can( 'edit_hsrtech_books' );
			},
			'meta'                => array(
				'annotations'  => array( 'readonly' => true ),
				'show_in_rest' => true,
			),
		)
	);

	// phpcs:ignore PluginCheck.CodeAnalysis.WP.WPVersion, WordPress.WP.WordPressVersion -- Guarded above by function_exists( 'wp_register_ability' ); never reached on WordPress < 6.9.
	wp_register_ability(
		'chapterwright/get-book-overview',
		array(
			'label'               => __( 'Get book overview', 'chapterwright' ),
			'description'         => __( 'Returns a book with its sections and chapters, grouped and ordered exactly as they appear in the table of contents.', 'chapterwright' ),
			'category'            => 'chapterwright',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'book_id' => array(
						'type'        => 'integer',
						'description' => __( 'Book post ID.', 'chapterwright' ),
					),
				),
				'required'   => array( 'book_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'hsrtech_ability_get_book_overview',
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
		'chapterwright/create-section',
		array(
			'label'               => __( 'Create a book section', 'chapterwright' ),
			'description'         => __( 'Adds a named, describable grouping (e.g. "Part I") that chapters can be assigned to within one book.', 'chapterwright' ),
			'category'            => 'chapterwright',
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
			'execute_callback'    => 'hsrtech_ability_create_section',
			'permission_callback' => function ( $input ) {
				$book_id = (int) $input['book_id'];

				// current_user_can( 'edit_post', $book_id ) alone doesn't confirm
				// $book_id is actually a Book — for a post ID belonging to some
				// other post type that the same user can edit, map_meta_cap would
				// still resolve truthy here. Require both.
				if ( HSRTECH_BOOK_POST_TYPE !== get_post_type( $book_id ) ) {
					return false;
				}

				return current_user_can( 'edit_post', $book_id );
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
		'chapterwright/create-chapter',
		array(
			'label'               => __( 'Create a chapter', 'chapterwright' ),
			'description'         => __( 'Creates a new draft chapter assigned to a book, optionally within one of its sections, ready to be written up in the block editor.', 'chapterwright' ),
			'category'            => 'chapterwright',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'book_id'    => array( 'type' => 'integer' ),
					'title'      => array( 'type' => 'string' ),
					'section_id' => array( 'type' => 'integer' ),
					'content'    => array(
						'type'        => 'string',
						'description' => __( 'Optional starting content, as block markup or plain text.', 'chapterwright' ),
					),
				),
				'required'   => array( 'book_id', 'title' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'hsrtech_ability_create_chapter',
			'permission_callback' => function ( $input ) {
				// Must be able to edit the target book, and to create a new
				// Chapter at all — 'edit_hsrtech_chapters' as of 2.2.0, replacing
				// the generic 'edit_posts' (see hsrtech_register_post_types()).
				return current_user_can( 'edit_post', (int) $input['book_id'] ) && current_user_can( 'edit_hsrtech_chapters' );
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
		'chapterwright/delete-section',
		array(
			'label'               => __( 'Delete a book section', 'chapterwright' ),
			'description'         => __( 'Removes a section. Chapters assigned to it are not deleted — they become unassigned and fall under the default "Chapters" heading.', 'chapterwright' ),
			'category'            => 'chapterwright',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'section_id' => array( 'type' => 'integer' ),
				),
				'required'   => array( 'section_id' ),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => 'hsrtech_ability_delete_section',
			'permission_callback' => function ( $input ) {
				$section = hsrtech_get_section( (int) $input['section_id'] );

				// hsrtech_user_can_delete_section() (includes/sections.php) also
				// requires edit rights on every chapter assigned to the section,
				// not just on its book — deleting a section writes to each of
				// those chapters too (see hsrtech_delete_section()). Shared with
				// the DELETE /sections/{id} REST route so both enforce the same
				// rule.
				return $section && hsrtech_user_can_delete_section( $section );
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
 * Execute callback for chapterwright/list-books.
 *
 * @return array<int,array<string,mixed>>
 */
function hsrtech_ability_list_books() {
	$args = array(
		'post_type'      => HSRTECH_BOOK_POST_TYPE,
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	// The permission_callback only confirms the caller can edit *some*
	// Books ('edit_hsrtech_books'); it doesn't mean they can see every
	// author's drafts and private books. Mirror the same restriction the
	// admin post list table applies for this capability_type: without
	// 'edit_others_hsrtech_books', only return the caller's own.
	if ( ! current_user_can( 'edit_others_hsrtech_books' ) ) {
		$args['author'] = get_current_user_id();
	}

	$books = get_posts( $args );

	return array_map(
		function ( $book ) {
			return array(
				'id'            => $book->ID,
				'title'         => get_the_title( $book ),
				'status'        => $book->post_status,
				'chapter_count' => count( hsrtech_get_all_chapters_for_admin( $book->ID ) ),
			);
		},
		$books
	);
}

/**
 * Execute callback for chapterwright/get-book-overview.
 *
 * @param array<string,mixed> $input Ability input; only `book_id` is used.
 * @return array<string,mixed>|WP_Error
 */
function hsrtech_ability_get_book_overview( $input ) {
	$book_id = (int) $input['book_id'];
	$book    = get_post( $book_id );

	if ( ! $book || HSRTECH_BOOK_POST_TYPE !== $book->post_type ) {
		return new WP_Error( 'hsrtech_ability_book_not_found', __( 'That book does not exist.', 'chapterwright' ) );
	}

	// The permission_callback only confirms the caller can edit *this book*;
	// a role can have edit_post on a Book without edit rights on every
	// Chapter within it (they're separate capability_types as of 2.2.0 — see
	// hsrtech_register_post_types()), and hsrtech_get_all_chapters_for_admin()
	// deliberately returns every chapter regardless of status or author.
	// Filter down to what the caller can individually edit — the same
	// per-post current_user_can( 'edit_post', ... ) check used everywhere
	// else in this plugin, which already resolves private/draft/others'-post
	// rules correctly via map_meta_cap — same as the list-books ability does
	// for books, just one level down.
	$chapters = array_filter(
		hsrtech_get_all_chapters_for_admin( $book_id ),
		function ( $chapter ) {
			return current_user_can( 'edit_post', $chapter->ID );
		}
	);

	return array(
		'id'       => $book_id,
		'title'    => get_the_title( $book_id ),
		'subtitle' => get_post_meta( $book_id, '_hsrtech_subtitle', true ),
		'sections' => hsrtech_get_book_sections( $book_id ),
		'chapters' => array_values(
			array_map(
				function ( $chapter ) {
					return array(
						'id'         => $chapter->ID,
						'title'      => get_the_title( $chapter ),
						'status'     => $chapter->post_status,
						'section_id' => (int) get_post_meta( $chapter->ID, '_hsrtech_section_id', true ),
						'order'      => (int) get_post_meta( $chapter->ID, '_hsrtech_order', true ),
					);
				},
				$chapters
			)
		),
	);
}

/**
 * Execute callback for chapterwright/create-section.
 *
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>|WP_Error
 */
function hsrtech_ability_create_section( $input ) {
	$section_id = hsrtech_insert_section(
		(int) $input['book_id'],
		array(
			'name'        => $input['name'],
			'description' => isset( $input['description'] ) ? $input['description'] : '',
		)
	);

	if ( is_wp_error( $section_id ) ) {
		return $section_id;
	}

	return hsrtech_get_section( $section_id );
}

/**
 * Execute callback for chapterwright/create-chapter.
 *
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>|WP_Error
 */
function hsrtech_ability_create_chapter( $input ) {
	$book_id = (int) $input['book_id'];

	if ( HSRTECH_BOOK_POST_TYPE !== get_post_type( $book_id ) ) {
		return new WP_Error( 'hsrtech_ability_book_not_found', __( 'That book does not exist.', 'chapterwright' ) );
	}

	if ( ! empty( $input['section_id'] ) ) {
		$section = hsrtech_get_section( (int) $input['section_id'] );
		if ( ! $section || $book_id !== (int) $section['book_id'] ) {
			return new WP_Error( 'hsrtech_ability_section_mismatch', __( 'That section does not belong to this book.', 'chapterwright' ) );
		}
	}

	$chapter_id = wp_insert_post(
		array(
			'post_type'    => HSRTECH_CHAPTER_POST_TYPE,
			'post_title'   => sanitize_text_field( $input['title'] ),
			'post_content' => isset( $input['content'] ) ? wp_kses_post( $input['content'] ) : '',
			'post_status'  => 'draft',
		),
		true
	);

	if ( is_wp_error( $chapter_id ) ) {
		return $chapter_id;
	}

	update_post_meta( $chapter_id, '_hsrtech_book_id', $book_id );
	update_post_meta( $chapter_id, '_hsrtech_order', hsrtech_get_next_chapter_order( $book_id ) );

	if ( ! empty( $input['section_id'] ) ) {
		update_post_meta( $chapter_id, '_hsrtech_section_id', absint( $input['section_id'] ) );
	}

	return array(
		'id'        => $chapter_id,
		'edit_link' => get_edit_post_link( $chapter_id, 'raw' ),
	);
}

/**
 * Execute callback for chapterwright/delete-section.
 *
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>|WP_Error
 */
function hsrtech_ability_delete_section( $input ) {
	$result = hsrtech_delete_section( (int) $input['section_id'] );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return array( 'deleted' => true );
}
