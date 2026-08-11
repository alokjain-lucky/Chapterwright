<?php
/**
 * Plugin Name:       Make a Book
 * Plugin URI:        https://alokjain.dev
 * Description:       Create and publish multiple, beautifully readable ebooks with chapters, sections, and code-friendly formatting.
 * Version:           1.3.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Alok Jain
 * Author URI:        https://alokjain.dev
 * Text Domain:       make-a-book
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAKE_A_BOOK_VERSION', '1.3.0' );
define( 'MAKE_A_BOOK_FILE', __FILE__ );
define( 'MAKE_A_BOOK_PATH', plugin_dir_path( __FILE__ ) );
define( 'MAKE_A_BOOK_URL', plugin_dir_url( __FILE__ ) );

/**
 * Stable post type keys, part of the plugin's public data contract.
 *
 * Referenced throughout the plugin instead of repeating string literals.
 * Do not change these values without a documented migration — see AGENTS.md.
 */
define( 'MAB_BOOK_POST_TYPE', 'mab_book' );
define( 'MAB_CHAPTER_POST_TYPE', 'mab_chapter' );

/*
 * Component loading order matters: content types and shared queries first
 * (everything else depends on the post type keys and query helpers they
 * define), then admin, then public, then the bundled block.
 */
require_once MAKE_A_BOOK_PATH . 'includes/content-types.php';
require_once MAKE_A_BOOK_PATH . 'includes/queries.php';

// mab_get_settings()/mab_show_mode_toggle()/mab_get_text() in
// admin/settings.php are used by the public templates too, so this file
// loads unconditionally rather than only when is_admin().
require_once MAKE_A_BOOK_PATH . 'admin/settings.php';

if ( is_admin() ) {
	require_once MAKE_A_BOOK_PATH . 'admin/meta-boxes.php';
	require_once MAKE_A_BOOK_PATH . 'admin/list-table.php';
	require_once MAKE_A_BOOK_PATH . 'admin/chapter-order.php';
	require_once MAKE_A_BOOK_PATH . 'admin/assets.php';
}

require_once MAKE_A_BOOK_PATH . 'public/assets.php';
require_once MAKE_A_BOOK_PATH . 'public/schema.php';
require_once MAKE_A_BOOK_PATH . 'public/template-router.php';
require_once MAKE_A_BOOK_PATH . 'public/shortcode.php';
require_once MAKE_A_BOOK_PATH . 'public/reading-time.php';
require_once MAKE_A_BOOK_PATH . 'blocks/code-snippet/code-snippet.php';

register_activation_hook( __FILE__, 'mab_activate' );
register_deactivation_hook( __FILE__, 'mab_deactivate' );

/**
 * Register content types and flush rewrite rules once on activation.
 *
 * Post types are registered directly here (in addition to their own `init`
 * hook) so /books/ and /book-chapter/ permalinks resolve immediately after
 * activation, without requiring a manual visit to Settings → Permalinks.
 */
function mab_activate() {
	mab_register_post_types();
	flush_rewrite_rules();
}

/**
 * Flush rewrite rules on deactivation so plugin routes do not linger.
 */
function mab_deactivate() {
	flush_rewrite_rules();
}
