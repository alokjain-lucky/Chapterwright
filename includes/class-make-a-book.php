<?php
/**
 * Plugin bootstrap and lifecycle coordinator.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the plugin's components and coordinates activation.
 *
 * This class intentionally contains no presentation or persistence logic. Each
 * component owns one WordPress-facing responsibility and registers its own hooks.
 */
final class Make_A_Book {
	/**
	 * Singleton plugin instance.
	 *
	 * @var Make_A_Book|null
	 */
	private static $instance = null;

	/**
	 * Return the shared plugin instance.
	 *
	 * @return Make_A_Book
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load components and register their hooks.
	 */
	private function __construct() {
		$this->load_dependencies();

		new Make_A_Book_Content_Types();
		new Make_A_Book_Admin();
		new Make_A_Book_Public();
	}

	/**
	 * Load required class files in dependency order.
	 */
	private function load_dependencies() {
		require_once MAKE_A_BOOK_PATH . 'includes/class-make-a-book-content-types.php';
		require_once MAKE_A_BOOK_PATH . 'admin/class-make-a-book-admin.php';
		require_once MAKE_A_BOOK_PATH . 'public/class-make-a-book-public.php';
	}

	/**
	 * Register rewrite-dependent content before flushing rules on activation.
	 */
	public static function activate() {
		require_once MAKE_A_BOOK_PATH . 'includes/class-make-a-book-content-types.php';
		Make_A_Book_Content_Types::register();
		flush_rewrite_rules();
	}

	/**
	 * Flush plugin rewrite rules on deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Backward-compatible access to ordered book chapters for templates and add-ons.
	 *
	 * @param int $book_id Book post ID.
	 * @return WP_Post[] Ordered published chapter posts.
	 */
	public static function get_chapters( $book_id ) {
		return Make_A_Book_Content_Types::get_chapters( $book_id );
	}
}
