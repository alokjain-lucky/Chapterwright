<?php
/**
 * Uninstall handler.
 *
 * As of 2.0.1, uninstalling Make a Book performs a full clean sweep: every
 * Book and Chapter post (and their post meta), the mab_sections table, and
 * every option the plugin created are all removed. Nothing the plugin added
 * is left behind. This is a deliberate reversal of the plugin's pre-2.0.1
 * "retain content" behavior, made at the project owner's explicit request —
 * see AGENTS.md, engineering standard 8, for the reasoning. It only runs
 * when a site owner actually deletes the plugin from the Plugins screen
 * (never on deactivation or update), so it is not something that can happen
 * by accident.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( is_multisite() ) {
	$mab_site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $mab_site_ids as $mab_site_id ) {
		switch_to_blog( $mab_site_id );
		mab_uninstall_clean_sweep();
		restore_current_blog();
	}
} else {
	mab_uninstall_clean_sweep();
}

/**
 * Remove every trace of the plugin's data from the current site.
 *
 * Called once per site — directly on a single-site install, or once per
 * site in the network loop above on multisite.
 */
function mab_uninstall_clean_sweep() {
	mab_uninstall_delete_posts( 'mab_book' );
	mab_uninstall_delete_posts( 'mab_chapter' );
	mab_uninstall_drop_sections_table();

	delete_option( 'mab_settings' );
	delete_option( 'mab_db_version' );
}

/**
 * Permanently delete every post of one of this plugin's post types.
 *
 * Uses `wp_delete_post( $id, true )` (force delete, bypassing Trash) so
 * this actually removes the post row, its post meta, and its revisions —
 * consistent with "clean sweep." Media in the Media Library (e.g. a book's
 * featured image) is not touched, since attachments are generic WordPress
 * content that may be reused elsewhere and this plugin did not create them.
 *
 * @param string $post_type One of this plugin's post type keys.
 */
function mab_uninstall_delete_posts( $post_type ) {
	$post_ids = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $post_ids as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}

/**
 * Drop the mab_sections custom table.
 */
function mab_uninstall_drop_sections_table() {
	global $wpdb;

	$mab_sections_table = $wpdb->prefix . 'mab_sections';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Deliberate, one-time schema cleanup on uninstall; no WP API drops a custom table.
	$wpdb->query( "DROP TABLE IF EXISTS {$mab_sections_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a fixed prefix, not user input.
}
