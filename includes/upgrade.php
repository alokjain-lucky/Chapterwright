<?php
/**
 * Database schema creation and versioned upgrade routines.
 *
 * Runs both on activation (register_activation_hook, in make-a-book.php) and
 * on every request (mab_maybe_upgrade(), hooked to plugins_loaded) so a site
 * that updates the plugin's files without deactivating/reactivating it still
 * gets the sections table created and existing data migrated.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'mab_maybe_upgrade' );

/**
 * Create (or update) the sections table, idempotently.
 *
 * Safe to call on every activation and every version bump — dbDelta() only
 * applies the difference between this schema and what already exists.
 */
function mab_create_sections_table() {
	global $wpdb;

	$table           = mab_get_sections_table();
	$charset_collate = $wpdb->get_charset_collate();

	// dbDelta() is whitespace- and keyword-case-sensitive about this exact
	// format (two spaces before "KEY", a line per column) — see
	// https://developer.wordpress.org/reference/functions/dbdelta/.
	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		book_id bigint(20) unsigned NOT NULL,
		name varchar(191) NOT NULL,
		description text NOT NULL,
		menu_order int(11) NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY book_id (book_id)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Compare the installed schema/data version against the current plugin
 * version and run any migrations still owed, then record the new version.
 *
 * Each block below is self-contained and only runs once per site, guarded by
 * its own version check, so this function stays safe to run on every
 * request without redoing work.
 */
function mab_maybe_upgrade() {
	$installed = get_option( 'mab_db_version', '0' );

	if ( version_compare( $installed, '2.0.0', '<' ) ) {
		mab_create_sections_table();
		mab_migrate_sections_from_meta();
		update_option( 'mab_db_version', '2.0.0' );
	}
}

/**
 * One-time migration: turn each book's distinct `_mab_section` text values
 * (the pre-2.0.0 grouping mechanism) into rows in the sections table, point
 * every chapter at its new section via `_mab_section_id`, and remove the old
 * meta key. Chapters that had no section text are left unassigned, exactly
 * as before (they fall back to the default "Chapters" heading).
 *
 * Safe to run more than once: books with no `_mab_section` meta left simply
 * have nothing to migrate.
 */
function mab_migrate_sections_from_meta() {
	global $wpdb;

	$chapters_with_section = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT pm_section.post_id AS chapter_id, pm_section.meta_value AS section_name, pm_book.meta_value AS book_id
			FROM {$wpdb->postmeta} pm_section
			INNER JOIN {$wpdb->postmeta} pm_book ON pm_book.post_id = pm_section.post_id AND pm_book.meta_key = %s
			WHERE pm_section.meta_key = %s AND pm_section.meta_value != ''
			ORDER BY pm_book.meta_value ASC, pm_section.post_id ASC",
			'_mab_book_id',
			'_mab_section'
		)
	);

	if ( ! $chapters_with_section ) {
		return;
	}

	// One lookup table of book_id => (section name => new section id), built
	// as we go so the same section name within a book only creates one row.
	$section_ids_by_book = array();

	foreach ( $chapters_with_section as $row ) {
		$book_id      = absint( $row->book_id );
		$section_name = trim( (string) $row->section_name );

		if ( ! $book_id || '' === $section_name ) {
			continue;
		}

		if ( ! isset( $section_ids_by_book[ $book_id ] ) ) {
			$section_ids_by_book[ $book_id ] = array();
		}

		if ( ! isset( $section_ids_by_book[ $book_id ][ $section_name ] ) ) {
			$new_id = mab_insert_section(
				$book_id,
				array(
					'name'       => $section_name,
					'menu_order' => count( $section_ids_by_book[ $book_id ] ),
				)
			);

			if ( is_wp_error( $new_id ) ) {
				continue;
			}

			$section_ids_by_book[ $book_id ][ $section_name ] = $new_id;
		}

		update_post_meta( (int) $row->chapter_id, '_mab_section_id', $section_ids_by_book[ $book_id ][ $section_name ] );
		delete_post_meta( (int) $row->chapter_id, '_mab_section' );
	}
}
