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

// Priority 20, and on 'init' rather than the earlier 'plugins_loaded' (which
// is where this ran prior to 2.2.1): hsrtech_maybe_upgrade() calls
// hsrtech_add_capabilities_to_roles(), which needs the Book/Chapter post types
// to already be registered (it looks up their capability names via
// get_post_type_object()). Post types register on 'init' at the default
// priority 10 (hsrtech_register_post_types(), includes/content-types.php).
// 'plugins_loaded' always fires before 'init', so running this there meant
// get_post_type_object() returned null every time, the capability grant
// silently did nothing, and hsrtech_db_version still advanced to '2.2.0' as if
// it had worked — see the 2.2.1 note under Notable history in AGENTS.md for
// how this actually surfaced (the "Books & Chapters" admin menu item
// disappearing) and why a version bump alone doesn't fix an already-bumped
// site without the 2.2.1 re-run gate below.
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
		// Books/Chapters moved off generic 'post' capabilities onto their own
		// capability_type in 2.2.0 (see hsrtech_register_post_types() in
		// includes/content-types.php). New installs get this from
		// hsrtech_activate()'s own call; this covers every site that updates the
		// plugin's files without deactivating/reactivating it, which is the
		// common case and would otherwise silently strip existing users'
		// access to Books and Chapters.
		hsrtech_add_capabilities_to_roles();
		update_option( 'hsrtech_db_version', '2.2.0' );
	}

	if ( version_compare( $installed, '2.2.1', '<' ) ) {
		// Re-run of the exact same call as the 2.2.0 block above. On any site
		// that already ran that block before this file's 'init' priority-20
		// fix (see the comment on add_action() above), the capability grant
		// silently did nothing — but hsrtech_db_version still advanced to
		// '2.2.0', so the block above will never run again on that site. This
		// gate exists purely to self-heal that: safe and cheap to run even on
		// a site where 2.2.0 worked correctly the first time, since
		// WP_Role::add_cap() is idempotent.
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
