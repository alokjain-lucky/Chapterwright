<?php
/**
 * Plugin Name:       Make a Book
 * Plugin URI:        https://alokjain.dev
 * Description:       Create and publish multiple, beautifully readable ebooks with chapters, sections, and code-friendly formatting.
 * Version:           2.3.2
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Alok Jain
 * Author URI:        https://alokjain.dev
 * Text Domain:       make-a-book
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAKE_A_BOOK_VERSION', '2.3.2' );
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
 * (everything else depends on the post type keys, query helpers, and the
 * sections table they define), then admin, then public, then the bundled
 * block.
 */
require_once MAKE_A_BOOK_PATH . 'includes/content-types.php';
require_once MAKE_A_BOOK_PATH . 'includes/queries.php';
require_once MAKE_A_BOOK_PATH . 'includes/sections.php';
require_once MAKE_A_BOOK_PATH . 'includes/upgrade.php';
require_once MAKE_A_BOOK_PATH . 'includes/abilities.php';

// mab_get_settings()/mab_show_mode_toggle()/mab_get_text() in
// admin/settings.php are used by the public templates too, so this file
// loads unconditionally rather than only when is_admin().
require_once MAKE_A_BOOK_PATH . 'admin/settings.php';

if ( is_admin() ) {
	require_once MAKE_A_BOOK_PATH . 'admin/app.php';
	require_once MAKE_A_BOOK_PATH . 'admin/list-table.php';
	require_once MAKE_A_BOOK_PATH . 'admin/help.php';
	require_once MAKE_A_BOOK_PATH . 'admin/redirects.php';
}

// The REST controllers are needed whenever the REST API is being served,
// which is not always is_admin() (e.g. an application password request),
// so they load unconditionally like admin/settings.php above.
require_once MAKE_A_BOOK_PATH . 'admin/rest/sections.php';
require_once MAKE_A_BOOK_PATH . 'admin/rest/chapters.php';

require_once MAKE_A_BOOK_PATH . 'public/assets.php';
require_once MAKE_A_BOOK_PATH . 'public/schema.php';
require_once MAKE_A_BOOK_PATH . 'public/template-router.php';
require_once MAKE_A_BOOK_PATH . 'public/shortcode.php';
require_once MAKE_A_BOOK_PATH . 'public/reading-time.php';
require_once MAKE_A_BOOK_PATH . 'public/credit.php';
require_once MAKE_A_BOOK_PATH . 'blocks/code-snippet/code-snippet.php';

add_action( 'init', 'mab_load_textdomain' );
register_activation_hook( __FILE__, 'mab_activate' );
register_deactivation_hook( __FILE__, 'mab_deactivate' );

/**
 * Load translated strings for PHP-rendered output from languages/.
 *
 * `wp_set_script_translations()` (see admin/app.php) already handles the
 * admin app and editor sidebar's JavaScript strings — this call is what
 * makes every PHP-side `__()`/`_e()` call in the plugin (templates, admin
 * screens, REST error messages) translatable too. Without it, a translator
 * can supply a .po/.mo file in languages/ and it will simply never load;
 * WordPress does not do this automatically for plugins that are not hosted
 * on WordPress.org.
 *
 * A starting .pot file for translators lives at languages/make-a-book.pot.
 * See "Translations" in README.md for how to turn it into a usable .mo/.json
 * set (the `wp i18n` WP-CLI commands, which require a WP-CLI environment
 * this plugin does not bundle).
 */
function mab_load_textdomain() {
	load_plugin_textdomain( 'make-a-book', false, dirname( plugin_basename( MAKE_A_BOOK_FILE ) ) . '/languages' );
}

/**
 * Register content types, create the sections table, and flush rewrite
 * rules once on activation.
 *
 * Post types are registered directly here (in addition to their own `init`
 * hook) so /books/ and /book-chapter/ permalinks resolve immediately after
 * activation, without requiring a manual visit to Settings → Permalinks.
 * The sections table is created here too (in addition to
 * mab_maybe_upgrade()'s own version check) so a brand-new install has it
 * from the first request rather than waiting for the next page load.
 */
function mab_activate() {
	mab_register_post_types();
	mab_create_sections_table();
	mab_add_capabilities_to_roles();
	update_option( 'mab_db_version', '2.2.1' );
	flush_rewrite_rules();
}

/**
 * Flush rewrite rules on deactivation so plugin routes do not linger.
 */
function mab_deactivate() {
	flush_rewrite_rules();
}
