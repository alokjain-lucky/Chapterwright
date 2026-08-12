<?php
/**
 * The "Make a Book" admin app: a top-level admin page that mounts the React
 * interface for browsing and organizing Books, Chapters, and Sections, plus
 * the block editor sidebar panel that replaces the old meta boxes.
 *
 * Both scripts are built with @wordpress/scripts (`npm run build`) from
 * admin/app/src/ into admin/app/build/ — see package.json. The build output
 * is gitignored (like every other generated artifact in this plugin) but is
 * expected to be present in a released copy of the plugin; AGENTS.md's
 * release checklist runs the build before packaging.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'mab_add_app_page' );
add_action( 'admin_enqueue_scripts', 'mab_enqueue_app_assets' );
add_action( 'enqueue_block_editor_assets', 'mab_enqueue_editor_sidebar_assets' );

/**
 * Register the top-level "Make a Book" admin page.
 *
 * The Book/Chapter post type screens themselves stay reachable (they are
 * registered with `show_ui => true`) for direct links from this app and
 * from the block editor sidebar panel, but no longer appear in the nav —
 * this page, and its Settings submenu, are the only Make a Book entries an
 * author sees in the sidebar.
 */
function mab_add_app_page() {
	add_menu_page(
		__( 'Make a Book', 'make-a-book' ),
		__( 'Make a Book', 'make-a-book' ),
		'edit_posts',
		'make-a-book',
		'mab_render_app_page',
		'dashicons-book-alt',
		20
	);

	add_submenu_page(
		'make-a-book',
		__( 'Make a Book', 'make-a-book' ),
		__( 'Books & Chapters', 'make-a-book' ),
		'edit_posts',
		'make-a-book',
		'mab_render_app_page'
	);
}

/**
 * Render the app's mount point. Everything else happens in JavaScript.
 */
function mab_render_app_page() {
	echo '<div class="wrap"><div id="make-a-book-app"></div></div>';
}

/**
 * Load the admin app's compiled script and style on its own admin page only.
 *
 * @param string $hook_suffix Current admin page identifier.
 */
function mab_enqueue_app_assets( $hook_suffix ) {
	if ( 'toplevel_page_make-a-book' !== $hook_suffix ) {
		return;
	}

	$asset_file = MAKE_A_BOOK_PATH . 'admin/app/build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'make-a-book-app',
		MAKE_A_BOOK_URL . 'admin/app/build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_set_script_translations( 'make-a-book-app', 'make-a-book' );

	// wp-scripts names this file style-index.css, not index.css, because the
	// entry point is literally named "index" — a webpack-config quirk
	// specific to that one entry name (the editor-sidebar entry below does
	// not have this prefix). Confirmed by inspecting admin/app/build/ after
	// `npm run build`; do not "fix" this filename without re-checking.
	if ( file_exists( MAKE_A_BOOK_PATH . 'admin/app/build/style-index.css' ) ) {
		wp_enqueue_style(
			'make-a-book-app',
			MAKE_A_BOOK_URL . 'admin/app/build/style-index.css',
			array( 'wp-components' ),
			$asset['version']
		);
	}

	wp_localize_script(
		'make-a-book-app',
		'makeABookApp',
		array(
			'adminUrl' => admin_url(),
		)
	);
}

/**
 * Load the block editor sidebar panel's compiled script and style, only on
 * the Book and Chapter editor screens.
 */
function mab_enqueue_editor_sidebar_assets() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( MAB_BOOK_POST_TYPE, MAB_CHAPTER_POST_TYPE ), true ) ) {
		return;
	}

	$asset_file = MAKE_A_BOOK_PATH . 'admin/app/build/editor-sidebar.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'make-a-book-editor-sidebar',
		MAKE_A_BOOK_URL . 'admin/app/build/editor-sidebar.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_set_script_translations( 'make-a-book-editor-sidebar', 'make-a-book' );

	if ( file_exists( MAKE_A_BOOK_PATH . 'admin/app/build/editor-sidebar.css' ) ) {
		wp_enqueue_style(
			'make-a-book-editor-sidebar',
			MAKE_A_BOOK_URL . 'admin/app/build/editor-sidebar.css',
			array(),
			$asset['version']
		);
	}
}
