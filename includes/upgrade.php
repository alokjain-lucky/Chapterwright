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
 * Get the (now-retired, pre-3.0.0) sections table name, including the site's
 * table prefix.
 *
 * Section became a post type in 3.0.0 (includes/sections.php) — this table
 * only still matters here as the source data for
 * hsrtech_migrate_sections_table_to_posts() below, on a site upgrading from
 * before that version.
 *
 * @return string Fully-prefixed table name.
 */
function hsrtech_get_sections_table() {
	global $wpdb;
	return $wpdb->prefix . 'hsrtech_sections';
}

/**
 * Create (or update) the pre-3.0.0 sections table, idempotently.
 *
 * Still runs on every activation and every version bump — even on a fresh
 * install, which has nothing in it to migrate — because the 3.0.0 upgrade
 * gate below expects the table to exist (even empty) so it can query and
 * then drop it; that keeps this function and the 2.0.0 migration below it
 * simple and unconditional rather than needing their own "is this a fresh
 * install" branch. dbDelta() only applies the difference between this schema
 * and what already exists, so calling it repeatedly is harmless.
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

	if ( version_compare( $installed, '2.9.0', '<' ) ) {
		// Chapters moved from a flat /book-chapter/%postname%/ permalink to
		// one nested under their book (includes/permalinks.php). Needs the
		// rewrite rules regenerated once for an existing site, same as any
		// other permalink structure change. New installs get this for free
		// from hsrtech_activate()'s own flush_rewrite_rules() call.
		flush_rewrite_rules();
		update_option( 'hsrtech_db_version', '2.9.0' );
	}

	if ( version_compare( $installed, '3.0.0', '<' ) ) {
		// Section used to be two things — a plain name/description row in the
		// hsrtech_sections database table, and a separately-linked
		// hsrtech_module post an author could optionally create for a longer
		// introduction. Both folded into one hsrtech_section post type (see
		// includes/content-types.php and includes/sections.php) — this is the
		// one-time carry-over for a site that already has real section data
		// in the old table.
		$hsrtech_sections_migrated = hsrtech_migrate_sections_table_to_posts();

		// New default-role capabilities for the new post type, the same way
		// every other new Chapterwright post type has needed this on an
		// already-active site (see the 2.2.0/2.2.1/2.9.0 blocks above) —
		// hsrtech_add_capabilities_to_roles() only grants whatever post types
		// exist *at the time it runs*. Safe to run regardless of whether the
		// migration above fully finished.
		hsrtech_add_capabilities_to_roles();

		// The permalink structure changed (Section replaces Module's
		// /module/%postname%/ with /section/%postname%/), same as the 2.9.0
		// block above. Also safe to run either way.
		flush_rewrite_rules();

		// Only advance past 3.0.0 once every row has actually migrated —
		// hsrtech_migrate_sections_table_to_posts() returns false if any row
		// failed (see its own docblock) and leaves that row in the old table
		// specifically so this block runs again on the next request, instead
		// of the site getting silently stuck with some sections never
		// migrated and the table never cleaned up.
		if ( $hsrtech_sections_migrated ) {
			update_option( 'hsrtech_db_version', '3.0.0' );
		}
	}
}

/**
 * One-time migration: turn every row of the old `hsrtech_sections` database
 * table into a Section post, repoint every chapter's `_hsrtech_section_id`
 * meta from the old row ID to the new post ID, remove any leftover
 * `hsrtech_module` post from before this version (the pre-3.0.0 optional,
 * separately-linked introduction page — its job is now just Section's own
 * post content, and this plugin never shipped a version where a real one
 * could have existed to preserve), and finally drop the table once every row
 * is gone.
 *
 * Safe to run more than once, including a partial re-run: each row is
 * deleted from the old table immediately after its post is successfully
 * created (not in one final sweep at the end), so a retry only ever
 * re-processes rows that genuinely never made it across — an already-migrated
 * row can never be turned into a second, duplicate post. A site with no
 * `hsrtech_sections` table at all (a fresh install, or one that already
 * finished migrating) simply has nothing to do.
 *
 * @return bool True once every row has migrated and the table is gone; false
 *              if any row failed and was left in place for the next attempt
 *              — see hsrtech_maybe_upgrade(), which only advances
 *              `hsrtech_db_version` past 3.0.0 on a true return.
 */
function hsrtech_migrate_sections_table_to_posts() {
	global $wpdb;

	$table = hsrtech_get_sections_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time, idempotent upgrade check for this plugin's own table; no WP API for "does this table exist".
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return true;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table with no WP API equivalent, being retired by this very migration; $table is a fixed prefix, not user input.
	$rows = $wpdb->get_results( "SELECT id, book_id, name, description, menu_order FROM {$table} ORDER BY book_id ASC, menu_order ASC, id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is fixed, built from a fixed prefix, not user input.

	$old_id_to_new_post_id = array();
	$had_failures          = false;

	foreach ( $rows as $row ) {
		$new_post_id = wp_insert_post(
			array(
				'post_type'    => HSRTECH_SECTION_POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => $row->name,
				'post_excerpt' => $row->description,
				'menu_order'   => (int) $row->menu_order,
			),
			true
		);

		if ( is_wp_error( $new_post_id ) ) {
			$had_failures = true;

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated behind WP_DEBUG; this background migration has no admin-facing UI of its own to surface a per-row failure in, and the row itself is left in the old table (not deleted below) so the next request retries it automatically.
				error_log( sprintf( 'Chapterwright: could not migrate section "%1$s" (old row id %2$d) to a post: %3$s', $row->name, (int) $row->id, $new_post_id->get_error_message() ) );
			}

			continue;
		}

		update_post_meta( $new_post_id, '_hsrtech_book_id', (int) $row->book_id );
		$old_id_to_new_post_id[ (int) $row->id ] = $new_post_id;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table with no WP API equivalent, being retired by this very migration; deletes only the row whose post was just successfully created, which is what makes a retry after a partial failure safe (see docblock above).
		$wpdb->delete( $table, array( 'id' => (int) $row->id ), array( '%d' ) );
	}

	if ( $old_id_to_new_post_id ) {
		$chapter_ids = get_posts(
			array(
				'post_type'      => HSRTECH_CHAPTER_POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $chapter_ids as $chapter_id ) {
			$old_section_id = get_post_meta( $chapter_id, '_hsrtech_section_id', true );

			if ( '' === $old_section_id ) {
				continue;
			}

			$old_section_id = (int) $old_section_id;

			if ( isset( $old_id_to_new_post_id[ $old_section_id ] ) ) {
				update_post_meta( $chapter_id, '_hsrtech_section_id', $old_id_to_new_post_id[ $old_section_id ] );
			}
		}
	}

	$legacy_module_ids = get_posts(
		array(
			'post_type'      => 'hsrtech_module',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $legacy_module_ids as $legacy_module_id ) {
		wp_delete_post( $legacy_module_id, true );
	}

	if ( $had_failures ) {
		// At least one row is still sitting in the old table, deliberately
		// left there (see the loop above) — do not drop the table out from
		// under it. hsrtech_maybe_upgrade() sees this false return and keeps
		// hsrtech_db_version below 3.0.0, so this whole function runs again
		// on the next request and only reprocesses the rows still here.
		return false;
	}

	// Every row is gone (each one deleted immediately after its post was
	// created, above) — the table itself has no further purpose.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- One-time upgrade cleanup dropping this plugin's own now-retired table; $table is fixed, not user input; no WP API drops a custom table.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is fixed, built from a fixed prefix, not user input.

	return true;
}

/**
 * One-time migration: turn each book's distinct `_hsrtech_section` text values
 * (the pre-2.0.0 grouping mechanism) into Section posts (via
 * hsrtech_insert_section(), includes/sections.php — the same function
 * everything else uses, so this always stays in sync with whatever Section
 * currently is), point every chapter at its new section via
 * `_hsrtech_section_id`, and remove the old meta key. Chapters that had no
 * section text are left unassigned, exactly as before (they fall back to the
 * default "Chapters" heading).
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
