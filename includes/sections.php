<?php
/**
 * Book sections: a chapter-grouping heading that is also, optionally, its own
 * standalone introduction page — one `hsrtech_section` post, not a database
 * row plus a separately-linked post.
 *
 * A section's post_title is the heading shown in the table of contents, its
 * post_excerpt is the short description shown under that heading, and its
 * post_content is the full page an author can optionally write; the table of
 * contents links the heading to that page only when there is actually
 * content in it (see hsrtech_build_toc_sections(), includes/queries.php).
 * Every function below still returns/accepts the same plain array shape
 * (id, book_id, name, description, menu_order, has_content) callers used
 * before this was a post type, so admin/rest/sections.php and the rest of
 * the plugin needed no changes beyond that one new `has_content` key.
 *
 * Before 3.0.0, a section was a row in a small custom database table with no
 * page of its own, and an author could separately create a `hsrtech_module`
 * post and link it to a section via a meta box if they wanted a longer
 * introduction. Those were folded into this one post type —
 * hsrtech_migrate_sections_table_to_posts() (includes/upgrade.php) carries an
 * existing site's old section rows over automatically. Deleting a section
 * never deletes the chapters assigned to it; they simply become unassigned
 * and fall back to the default "Chapters" heading.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turn a Section post into the plain array shape the rest of the plugin
 * (admin/rest/sections.php, includes/queries.php, the React admin app) reads
 * and writes — the same shape this returned back when a section was a
 * database row, plus `has_content`.
 *
 * @param WP_Post $post Section post.
 * @return array<string,mixed> {
 *     @type int    $id          Section post ID.
 *     @type int    $book_id     Owning book's post ID.
 *     @type string $name        Section heading (the post title).
 *     @type string $description Short description shown under the heading (the post excerpt).
 *     @type int    $menu_order  Display order among the book's sections.
 *     @type bool   $has_content Whether the section has its own introduction page worth linking to.
 * }
 */
function hsrtech_prepare_section_post( $post ) {
	return array(
		'id'          => $post->ID,
		'book_id'     => absint( get_post_meta( $post->ID, '_hsrtech_book_id', true ) ),
		'name'        => $post->post_title,
		'description' => $post->post_excerpt,
		'menu_order'  => (int) $post->menu_order,
		'has_content' => '' !== trim( wp_strip_all_tags( $post->post_content ) ),
	);
}

/**
 * Fetch every section belonging to a book, in display order.
 *
 * @param int      $book_id  Book post ID.
 * @param string[] $statuses Post statuses to include. Defaults to only
 *                            'publish' — mirrors hsrtech_get_chapters()'s own
 *                            default (includes/queries.php) for the same
 *                            reason: hsrtech_build_toc_sections() calls this
 *                            with no status argument to build the public
 *                            table of contents, and a draft/pending/private/
 *                            future section's heading has no business
 *                            appearing there for an anonymous visitor — its
 *                            own permalink still 404s for them regardless, so
 *                            leaving it in would only leak the title, not
 *                            offer working access to it. Every admin-context
 *                            caller in this file and elsewhere explicitly
 *                            passes the full any-status array instead.
 * @return array<int,array<string,mixed>> Section rows — see hsrtech_prepare_section_post().
 */
function hsrtech_get_book_sections( $book_id, $statuses = array( 'publish' ) ) {
	$posts = get_posts(
		array(
			'post_type'      => HSRTECH_SECTION_POST_TYPE,
			'post_status'    => $statuses,
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Section-to-book relationship is stored as post meta, same as chapter-to-book.
				array(
					'key'   => '_hsrtech_book_id',
					'value' => absint( $book_id ),
				),
			),
		)
	);

	return array_map( 'hsrtech_prepare_section_post', $posts );
}

/**
 * Fetch a single section.
 *
 * @param int $section_id Section post ID.
 * @return array<string,mixed>|null Section row, or null if it does not exist.
 */
function hsrtech_get_section( $section_id ) {
	$post = get_post( absint( $section_id ) );

	if ( ! $post || HSRTECH_SECTION_POST_TYPE !== $post->post_type ) {
		return null;
	}

	return hsrtech_prepare_section_post( $post );
}

/**
 * Create a section for a book.
 *
 * @param int                 $book_id Book post ID.
 * @param array<string,mixed> $args {
 *     Section fields.
 *
 *     @type string $name        Section name. Required.
 *     @type string $description Optional descriptive text shown under the section heading.
 *     @type int    $menu_order  Optional explicit order; appended to the end when omitted.
 * }
 * @return int|WP_Error New section ID, or WP_Error when the name is missing.
 */
function hsrtech_insert_section( $book_id, $args ) {
	$name = isset( $args['name'] ) ? trim( sanitize_text_field( $args['name'] ) ) : '';
	if ( '' === $name ) {
		return new WP_Error( 'hsrtech_section_name_required', __( 'A section needs a name.', 'chapterwright' ) );
	}

	$description = isset( $args['description'] ) ? sanitize_textarea_field( $args['description'] ) : '';

	if ( isset( $args['menu_order'] ) ) {
		$menu_order = (int) $args['menu_order'];
	} else {
		// Any status here, not just published — a new section should never
		// land at the same order as an existing draft one just because the
		// default-arg publish-only query didn't see it.
		$existing   = hsrtech_get_book_sections( $book_id, array( 'publish', 'draft', 'pending', 'private', 'future' ) );
		$menu_order = $existing ? ( (int) end( $existing )['menu_order'] + 1 ) : 0;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => HSRTECH_SECTION_POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_excerpt' => $description,
			'menu_order'   => $menu_order,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return new WP_Error( 'hsrtech_section_insert_failed', __( 'The section could not be saved.', 'chapterwright' ) );
	}

	update_post_meta( $post_id, '_hsrtech_book_id', absint( $book_id ) );

	return (int) $post_id;
}

/**
 * Update a section's name, description, and/or order.
 *
 * @param int                 $section_id Section post ID.
 * @param array<string,mixed> $args       Any of: name, description, menu_order.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function hsrtech_update_section( $section_id, $args ) {
	if ( ! hsrtech_get_section( $section_id ) ) {
		return new WP_Error( 'hsrtech_section_not_found', __( 'That section no longer exists.', 'chapterwright' ) );
	}

	$postarr = array( 'ID' => absint( $section_id ) );

	if ( isset( $args['name'] ) ) {
		$name = trim( sanitize_text_field( $args['name'] ) );
		if ( '' === $name ) {
			return new WP_Error( 'hsrtech_section_name_required', __( 'A section needs a name.', 'chapterwright' ) );
		}
		$postarr['post_title'] = $name;
	}

	if ( isset( $args['description'] ) ) {
		$postarr['post_excerpt'] = sanitize_textarea_field( $args['description'] );
	}

	if ( isset( $args['menu_order'] ) ) {
		$postarr['menu_order'] = (int) $args['menu_order'];
	}

	if ( 1 === count( $postarr ) ) {
		return true;
	}

	$result = wp_update_post( $postarr, true );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( 'hsrtech_section_update_failed', __( 'The section could not be updated.', 'chapterwright' ) );
	}

	return true;
}

/**
 * Delete a section. Chapters assigned to it are unassigned, not deleted.
 *
 * Permanently deletes rather than trashing — sections never had a trash
 * concept before this was a post type, and the confirmation dialog in the
 * admin app ("Delete '%s'? Its chapters will stay, unassigned.") already
 * tells the author this is final.
 *
 * @param int $section_id Section post ID.
 * @return bool|WP_Error True on success, WP_Error if the section does not exist.
 */
function hsrtech_delete_section( $section_id ) {
	$section = hsrtech_get_section( $section_id );
	if ( ! $section ) {
		return new WP_Error( 'hsrtech_section_not_found', __( 'That section no longer exists.', 'chapterwright' ) );
	}

	foreach ( hsrtech_get_chapters_in_section( $section_id ) as $chapter_id ) {
		delete_post_meta( $chapter_id, '_hsrtech_section_id' );
	}

	wp_delete_post( absint( $section_id ), true );

	return true;
}

/**
 * Get every chapter (any status) assigned to a section, for reassignment on delete.
 *
 * @param int $section_id Section post ID.
 * @return int[] Chapter post IDs.
 */
function hsrtech_get_chapters_in_section( $section_id ) {
	$chapters = get_posts(
		array(
			'post_type'      => HSRTECH_CHAPTER_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Chapter-to-section relationship is stored as post meta.
				array(
					'key'   => '_hsrtech_section_id',
					'value' => absint( $section_id ),
				),
			),
		)
	);

	return $chapters ? $chapters : array();
}

/**
 * Whether the current user is allowed to delete a section.
 *
 * Deleting a section also strips `_hsrtech_section_id` from every chapter
 * assigned to it (see hsrtech_delete_section()) — that's a write to each of
 * those chapters, so the caller needs edit rights on all of them too, not
 * just on the section's own post. Shared by the delete-section Ability
 * (includes/abilities.php) and the `DELETE /sections/{id}` REST route
 * (admin/rest/sections.php) so both enforce the same rule.
 *
 * @param array<string,mixed> $section Section row, as returned by hsrtech_get_section().
 * @return bool
 */
function hsrtech_user_can_delete_section( $section ) {
	// Same rule this checked before Section was a post type (edit rights on
	// the owning book), plus delete rights on the section's own post now that
	// it is one.
	if ( ! current_user_can( 'edit_post', $section['book_id'] ) ) {
		return false;
	}

	if ( ! current_user_can( 'delete_post', $section['id'] ) ) {
		return false;
	}

	foreach ( hsrtech_get_chapters_in_section( $section['id'] ) as $chapter_id ) {
		if ( ! current_user_can( 'edit_post', $chapter_id ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Persist a new order for a book's sections in one call.
 *
 * @param int   $book_id     Book post ID.
 * @param int[] $ordered_ids Section IDs in the desired order. Must all belong to $book_id.
 * @return bool|WP_Error True on success, WP_Error if a section does not belong to the book.
 */
function hsrtech_reorder_sections( $book_id, $ordered_ids ) {
	// Any status — a draft section being reordered alongside published ones
	// (both editable from the same admin app screen) must still validate as
	// belonging to the book, not get treated as a mismatch.
	$existing = wp_list_pluck( hsrtech_get_book_sections( $book_id, array( 'publish', 'draft', 'pending', 'private', 'future' ) ), 'id' );

	foreach ( $ordered_ids as $section_id ) {
		if ( ! in_array( (int) $section_id, array_map( 'intval', $existing ), true ) ) {
			return new WP_Error( 'hsrtech_section_mismatch', __( 'One of those sections does not belong to this book.', 'chapterwright' ) );
		}
	}

	foreach ( array_values( $ordered_ids ) as $index => $section_id ) {
		hsrtech_update_section( $section_id, array( 'menu_order' => $index ) );
	}

	return true;
}

/**
 * Delete every section belonging to a book.
 *
 * Used when a book is permanently (not trashed) deleted, so it does not
 * leave orphaned section posts behind. Mirrors how WordPress itself only
 * cascades post meta on hard delete, not trash.
 *
 * @param int $book_id Book post ID.
 */
function hsrtech_delete_book_sections( $book_id ) {
	// Any status — a draft section must not survive its book's own permanent
	// deletion as an orphan just because the publish-only default missed it.
	foreach ( hsrtech_get_book_sections( $book_id, array( 'publish', 'draft', 'pending', 'private', 'future' ) ) as $section ) {
		wp_delete_post( $section['id'], true );
	}
}
add_action( 'before_delete_post', 'hsrtech_on_book_deleted' );

/**
 * Clean up a book's sections when the Book post itself is permanently deleted.
 *
 * @param int $post_id Post being deleted.
 */
function hsrtech_on_book_deleted( $post_id ) {
	if ( HSRTECH_BOOK_POST_TYPE === get_post_type( $post_id ) ) {
		hsrtech_delete_book_sections( $post_id );
	}
}
