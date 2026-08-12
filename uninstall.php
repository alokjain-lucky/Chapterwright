<?php
/**
 * Uninstall handler.
 *
 * Books and Chapters (posts, post meta, featured images) are intentionally
 * retained to prevent accidental loss of published content — see AGENTS.md.
 *
 * The mab_sections table is different: it holds only organizational
 * metadata (section names and descriptions), not primary authored content,
 * and is removed entirely so uninstalling the plugin doesn't leave a
 * dangling custom table behind. Any chapters that were assigned to a
 * section simply become unassigned; their titles, content, and other
 * metadata are unaffected.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$mab_sections_table = $wpdb->prefix . 'mab_sections';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Deliberate, one-time schema cleanup on uninstall; no WP API drops a custom table.
$wpdb->query( "DROP TABLE IF EXISTS {$mab_sections_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a fixed prefix, not user input.

delete_option( 'mab_db_version' );
