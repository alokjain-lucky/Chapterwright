<?php
/**
 * Plugin Name:       Chapterwright
 * Plugin URI:        https://github.com/alokjain-lucky/Chapterwright
 * Description:       Create and publish multiple, beautifully readable ebooks with chapters, sections, and code-friendly formatting.
 * Version:           2.8.1
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Alok Jain
 * Author URI:        https://alokjain.dev
 * Text Domain:       chapterwright
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HSRTECH_VERSION', '2.8.1' );
define( 'HSRTECH_FILE', __FILE__ );
define( 'HSRTECH_PATH', plugin_dir_path( __FILE__ ) );
define( 'HSRTECH_URL', plugin_dir_url( __FILE__ ) );

/**
 * Stable post type keys, part of the plugin's public data contract.
 *
 * Referenced throughout the plugin instead of repeating string literals.
 * Do not change these values without a documented migration — see AGENTS.md.
 */
define( 'HSRTECH_BOOK_POST_TYPE', 'hsrtech_book' );
define( 'HSRTECH_CHAPTER_POST_TYPE', 'hsrtech_chapter' );

/*
 * Component loading order matters: content types and shared queries first
 * (everything else depends on the post type keys, query helpers, and the
 * sections table they define), then admin, then public, then the bundled
 * block.
 */
require_once HSRTECH_PATH . 'includes/content-types.php';
require_once HSRTECH_PATH . 'includes/queries.php';
require_once HSRTECH_PATH . 'includes/sections.php';
require_once HSRTECH_PATH . 'includes/upgrade.php';
require_once HSRTECH_PATH . 'includes/abilities.php';

// hsrtech_get_settings()/hsrtech_show_mode_toggle()/hsrtech_get_text() in
// admin/settings.php are used by the public templates too, so this file
// loads unconditionally rather than only when is_admin().
require_once HSRTECH_PATH . 'admin/settings.php';

if ( is_admin() ) {
	require_once HSRTECH_PATH . 'admin/app.php';
	require_once HSRTECH_PATH . 'admin/list-table.php';
	require_once HSRTECH_PATH . 'admin/help.php';
	require_once HSRTECH_PATH . 'admin/redirects.php';
}

// The REST controllers are needed whenever the REST API is being served,
// which is not always is_admin() (e.g. an application password request),
// so they load unconditionally like admin/settings.php above.
require_once HSRTECH_PATH . 'admin/rest/sections.php';
require_once HSRTECH_PATH . 'admin/rest/chapters.php';
require_once HSRTECH_PATH . 'admin/rest/books.php';

require_once HSRTECH_PATH . 'public/assets.php';
require_once HSRTECH_PATH . 'public/schema.php';
require_once HSRTECH_PATH . 'public/template-router.php';
require_once HSRTECH_PATH . 'public/shortcode.php';
require_once HSRTECH_PATH . 'public/reading-time.php';
require_once HSRTECH_PATH . 'public/credit.php';
require_once HSRTECH_PATH . 'public/prefetch.php';
require_once HSRTECH_PATH . 'blocks/code-snippet/code-snippet.php';

register_activation_hook( __FILE__, 'hsrtech_activate' );
register_deactivation_hook( __FILE__, 'hsrtech_deactivate' );

/**
 * Register content types, create the sections table, and flush rewrite
 * rules once on activation.
 *
 * Post types are registered directly here (in addition to their own `init`
 * hook) so /books/ and /book-chapter/ permalinks resolve immediately after
 * activation, without requiring a manual visit to Settings → Permalinks.
 * The sections table is created here too (in addition to
 * hsrtech_maybe_upgrade()'s own version check) so a brand-new install has it
 * from the first request rather than waiting for the next page load.
 */
function hsrtech_activate() {
	hsrtech_register_post_types();
	hsrtech_create_sections_table();
	hsrtech_add_capabilities_to_roles();
	update_option( 'hsrtech_db_version', '2.2.1' );
	flush_rewrite_rules();
}

/**
 * Flush rewrite rules on deactivation so plugin routes do not linger.
 */
function hsrtech_deactivate() {
	flush_rewrite_rules();
}
