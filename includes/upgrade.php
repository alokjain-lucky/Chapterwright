<?php
/**
 * Database schema creation and versioned upgrade routines.
 *
 * Runs both on activation (register_activation_hook, in chapterwright.php) and
 * on every request (hsrtech_maybe_upgrade(), hooked to init — see the priority
 * comment below) so a site that updates the plugin's files without
 * deactivating/reactivating it still gets the sections table created,
 * existing data migrated, and role capabilities granted.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Priority 20 on 'init', not 'plugins_loaded': hsrtech_maybe_upgrade() calls
// hsrtech_add_capabilities_to_roles(), which needs the Book/Chapter post
// types already registered (it looks up their capability names via
// get_post_type_object()). Post types register on 'init' at the default
// priority 10 (hsrtech_register_post_types(), includes/content-types.php).
add_action( 'init', 'hsrtech_maybe_upgrade', 20 );

/**
 * Create (or update) the sections table, idempotently.
 *
 * Safe to call on every activation and every version bump — dbDelta() only
 * applies the difference between this schema and what already exists.
 */
function hsrtech_create_sections_table() {
	global $wpdb;

	$table           = hsrtech_get_sections_table();
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
function hsrtech_maybe_upgrade() {
	$installed = get_option( 'hsrtech_db_version', '0' );

	if ( version_compare( $installed, '2.0.0', '<' ) ) {
		hsrtech_create_sections_table();
		hsrtech_migrate_sections_from_meta();
		update_option( 'hsrtech_db_version', '2.0.0' );
	}

	if ( version_compare( $installed, '2.2.0', '<' ) ) {
		// Books/Chapters use their own capability_type instead of generic
		// 'post' capabilities (see hsrtech_register_post_types()). New
		// installs get this from hsrtech_activate(); this covers sites that
		// update the plugin's files without deactivating/reactivating, so
		// existing users don't silently lose access to Books and Chapters.
		hsrtech_add_capabilities_to_roles();
		update_option( 'hsrtech_db_version', '2.2.0' );
	}

	if ( version_compare( $installed, '2.2.1', '<' ) ) {
		// Re-runs the capability grant above for any site where it ran before
		// the 'init' priority-20 fix and silently did nothing (Book/Chapter
		// post types weren't registered yet at that point in the request).
		// Safe to run again elsewhere too, since WP_Role::add_cap() is
		// idempotent.
		hsrtech_add_capabilities_to_roles();
		update_option( 'hsrtech_db_version', '2.2.1' );
	}
}

/**
 * One-time migration: turn each book's distinct `_hsrtech_section` text values
 * (the pre-2.0.0 grouping mechanism) into rows in the sections table, point
 * every chapter at its new section via `_hsrtech_section_id`, and remove the old
 * meta key. Chapters that had no section text are left unassigned, exactly
 * as before (they fall back to the default "Chapters" heading).
 *
 * Safe to run more than once: books with no `_hsrtech_section` meta left simply
 * have nothing to migrate.
 */
function hsrtech_migrate_sections_from_meta() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time, idempotent upgrade migration (see docblock above); no WP API joins postmeta across two meta keys like this, and there is nothing worth caching about a query that runs at most once per site.
	$chapters_with_section = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT pm_section.post_id AS chapter_id, pm_section.meta_value AS section_name, pm_book.meta_value AS book_id
			FROM {$wpdb->postmeta} pm_section
			INNER JOIN {$wpdb->postmeta} pm_book ON pm_book.post_id = pm_section.post_id AND pm_book.meta_key = %s
			WHERE pm_section.meta_key = %s AND pm_section.meta_value != ''
			ORDER BY pm_book.meta_value ASC, pm_section.post_id ASC",
			'_hsrtech_book_id',
			'_hsrtech_section'
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
			$new_id = hsrtech_insert_section(
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

		update_post_meta( (int) $row->chapter_id, '_hsrtech_section_id', $section_ids_by_book[ $book_id ][ $section_name ] );
		delete_post_meta( (int) $row->chapter_id, '_hsrtech_section' );
	}
}
