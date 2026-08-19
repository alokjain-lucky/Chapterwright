<?php
/**
 * Book sections: a small custom database table, not a taxonomy or post meta.
 *
 * A section ("Part I", "Getting Started") is scoped to exactly one book and
 * needs its own describable text, so it is stored as a row rather than a
 * post-meta string (which cannot carry a description) or a taxonomy term
 * (which is shared site-wide and does not naturally scope to one book).
 *
 * The table is created on activation and fully dropped on uninstall — see
 * hsrtech_create_sections_table() in includes/upgrade.php and uninstall.php.
 * Deleting a section never deletes the chapters assigned to it; they simply
 * become unassigned and fall back to the default "Chapters" heading.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the sections table name, including the site's table prefix.
 *
 * @return string Fully-prefixed table name.
 */
function hsrtech_get_sections_table() {
	global $wpdb;
	return $wpdb->prefix . 'hsrtech_sections';
}

/**
 * Fetch every section belonging to a book, in display order.
 *
 * @param int $book_id Book post ID.
 * @return array<int,array<string,mixed>> Section rows (id, book_id, name, description, menu_order).
 */
function hsrtech_get_book_sections( $book_id ) {
	global $wpdb;

	$table = hsrtech_get_sections_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table with no WP API equivalent; not cached because sections are small, book-scoped lists edited rarely and read on every book page. $table (interpolated below) is a fixed prefix from hsrtech_get_sections_table(), not user input — flagged here rather than on the string line below because that's the line PluginCheck's DirectDB sniff reports against for a get_results() call, not the string itself.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, book_id, name, description, menu_order FROM {$table} WHERE book_id = %d ORDER BY menu_order ASC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be a placeholder; it is built from a fixed prefix by hsrtech_get_sections_table(), not user input.
			$book_id
		),
		ARRAY_A
	);

	return $rows ? array_map( 'hsrtech_cast_section_row', $rows ) : array();
}

/**
 * Cast a section row's numeric columns to int.
 *
 * $wpdb returns every column as a string regardless of the database
 * column's actual type, which trips up strict comparisons (e.g.
 * wp_list_pluck() + assertSame()) against the ints hsrtech_insert_section()
 * returns and hsrtech_update_section() accepts.
 *
 * @param array<string,mixed> $row Raw section row from the database.
 * @return array<string,mixed> The same row with id, book_id, and menu_order cast to int.
 */
function hsrtech_cast_section_row( $row ) {
	$row['id']         = (int) $row['id'];
	$row['book_id']    = (int) $row['book_id'];
	$row['menu_order'] = (int) $row['menu_order'];
	return $row;
}

/**
 * Fetch a single section row.
 *
 * @param int $section_id Section ID.
 * @return array<string,mixed>|null Section row, or null if it does not exist.
 */
function hsrtech_get_section( $section_id ) {
	global $wpdb;

	$table = hsrtech_get_sections_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table with no WP API equivalent. $table (interpolated below) is fixed, built by hsrtech_get_sections_table(), not user input — flagged here rather than on the string line below because that's the line PluginCheck's DirectDB sniff reports against for a get_row() call, not the string itself.
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id, book_id, name, description, menu_order FROM {$table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is fixed, built by hsrtech_get_sections_table(), not user input.
			$section_id
		),
		ARRAY_A
	);

	return $row ? hsrtech_cast_section_row( $row ) : null;
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
	global $wpdb;

	$name = isset( $args['name'] ) ? trim( sanitize_text_field( $args['name'] ) ) : '';
	if ( '' === $name ) {
		return new WP_Error( 'hsrtech_section_name_required', __( 'A section needs a name.', 'chapterwright' ) );
	}

	$description = isset( $args['description'] ) ? sanitize_textarea_field( $args['description'] ) : '';

	if ( isset( $args['menu_order'] ) ) {
		$menu_order = (int) $args['menu_order'];
	} else {
		$existing   = hsrtech_get_book_sections( $book_id );
		$menu_order = $existing ? ( (int) end( $existing )['menu_order'] + 1 ) : 0;
	}

	$now = current_time( 'mysql' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with no WP API equivalent.
	$inserted = $wpdb->insert(
		hsrtech_get_sections_table(),
		array(
			'book_id'     => absint( $book_id ),
			'name'        => $name,
			'description' => $description,
			'menu_order'  => $menu_order,
			'created_at'  => $now,
			'updated_at'  => $now,
		),
		array( '%d', '%s', '%s', '%d', '%s', '%s' )
	);

	if ( ! $inserted ) {
		return new WP_Error( 'hsrtech_section_insert_failed', __( 'The section could not be saved.', 'chapterwright' ) );
	}

	return (int) $wpdb->insert_id;
}

/**
 * Update a section's name, description, and/or order.
 *
 * @param int                 $section_id Section ID.
 * @param array<string,mixed> $args       Any of: name, description, menu_order.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function hsrtech_update_section( $section_id, $args ) {
	global $wpdb;

	if ( ! hsrtech_get_section( $section_id ) ) {
		return new WP_Error( 'hsrtech_section_not_found', __( 'That section no longer exists.', 'chapterwright' ) );
	}

	$data   = array();
	$format = array();

	if ( isset( $args['name'] ) ) {
		$name = trim( sanitize_text_field( $args['name'] ) );
		if ( '' === $name ) {
			return new WP_Error( 'hsrtech_section_name_required', __( 'A section needs a name.', 'chapterwright' ) );
		}
		$data['name'] = $name;
		$format[]     = '%s';
	}

	if ( isset( $args['description'] ) ) {
		$data['description'] = sanitize_textarea_field( $args['description'] );
		$format[]            = '%s';
	}

	if ( isset( $args['menu_order'] ) ) {
		$data['menu_order'] = (int) $args['menu_order'];
		$format[]           = '%d';
	}

	if ( ! $data ) {
		return true;
	}

	$data['updated_at'] = current_time( 'mysql' );
	$format[]           = '%s';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with no WP API equivalent.
	$updated = $wpdb->update(
		hsrtech_get_sections_table(),
		$data,
		array( 'id' => absint( $section_id ) ),
		$format,
		array( '%d' )
	);

	if ( false === $updated ) {
		return new WP_Error( 'hsrtech_section_update_failed', __( 'The section could not be updated.', 'chapterwright' ) );
	}

	return true;
}

/**
 * Delete a section. Chapters assigned to it are unassigned, not deleted.
 *
 * @param int $section_id Section ID.
 * @return bool|WP_Error True on success, WP_Error if the section does not exist.
 */
function hsrtech_delete_section( $section_id ) {
	global $wpdb;

	$section = hsrtech_get_section( $section_id );
	if ( ! $section ) {
		return new WP_Error( 'hsrtech_section_not_found', __( 'That section no longer exists.', 'chapterwright' ) );
	}

	foreach ( hsrtech_get_chapters_in_section( $section_id ) as $chapter_id ) {
		delete_post_meta( $chapter_id, '_hsrtech_section_id' );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with no WP API equivalent.
	$wpdb->delete( hsrtech_get_sections_table(), array( 'id' => absint( $section_id ) ), array( '%d' ) );

	return true;
}

/**
 * Get every chapter (any status) assigned to a section, for reassignment on delete.
 *
 * @param int $section_id Section ID.
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
 * just on the section's own book. Shared by the delete-section Ability
 * (includes/abilities.php) and the `DELETE /sections/{id}` REST route
 * (admin/rest/sections.php) so both enforce the same rule instead of two
 * independently maintained checks quietly drifting apart — which is exactly
 * what had happened before this was pulled out: the REST route only checked
 * book-level edit rights, not the per-chapter ones the Ability already did.
 *
 * @param array<string,mixed> $section Section row, as returned by hsrtech_get_section().
 * @return bool
 */
function hsrtech_user_can_delete_section( $section ) {
	if ( ! current_user_can( 'edit_post', $section['book_id'] ) ) {
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
	$existing = wp_list_pluck( hsrtech_get_book_sections( $book_id ), 'id' );

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
 * leave orphaned section rows behind. Mirrors how WordPress itself only
 * cascades post meta on hard delete, not trash.
 *
 * @param int $book_id Book post ID.
 */
function hsrtech_delete_book_sections( $book_id ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with no WP API equivalent.
	$wpdb->delete( hsrtech_get_sections_table(), array( 'book_id' => absint( $book_id ) ), array( '%d' ) );
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
