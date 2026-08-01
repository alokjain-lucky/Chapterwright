<?php
/**
 * Plugin Name:       Make a Book
 * Plugin URI:        https://alokjain.dev
 * Description:       Create and publish multiple, beautifully readable ebooks with chapters and sections.
 * Version:           1.1.0
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

define( 'MAKE_A_BOOK_VERSION', '1.1.0' );
define( 'MAKE_A_BOOK_FILE', __FILE__ );
define( 'MAKE_A_BOOK_PATH', plugin_dir_path( __FILE__ ) );
define( 'MAKE_A_BOOK_URL', plugin_dir_url( __FILE__ ) );

require_once MAKE_A_BOOK_PATH . 'includes/class-make-a-book.php';

register_activation_hook( __FILE__, array( 'Make_A_Book', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Make_A_Book', 'deactivate' ) );

Make_A_Book::instance();
