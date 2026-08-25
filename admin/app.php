<?php
/**
 * The "Chapterwright" admin app: a top-level admin page that mounts the React
 * interface for browsing and organizing Books, Chapters, and Sections, plus
 * the block editor sidebar panel that replaces the old meta boxes.
 *
 * Both scripts are built with @wordpress/scripts (`npm run build`) from
 * admin/app/src/ into admin/app/build/ — see package.json. The build output
 * is gitignored (like every other generated artifact in this plugin) but is
 * expected to be present in a released copy of the plugin; AGENTS.md's
 * release checklist runs the build before packaging.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Priority 5, earlier than admin/settings.php's default-priority
// hsrtech_add_settings_page(): add_menu_page() below must run first so
// WordPress's internal $admin_page_hooks['chapterwright'] lookup exists by
// the time any add_submenu_page( 'chapterwright', ... ) call happens.
// Getting this order backwards doesn't break menu registration outright,
// but WordPress computes the *wrong* per-page hook name for anything
// registered before its own top-level page exists — the Settings link
// still appeared, but WordPress fired the render callback on a hook name
// nothing was actually listening on, so the page body rendered blank.
// This also determines the submenu's visual order (registration order),
// which is why this fix keeps "Books & Chapters" above "Settings".
add_action( 'admin_menu', 'hsrtech_add_app_page', 5 );
add_action( 'admin_enqueue_scripts', 'hsrtech_enqueue_app_assets' );
add_action( 'enqueue_block_editor_assets', 'hsrtech_enqueue_editor_sidebar_assets' );
add_filter( 'plugin_action_links_' . plugin_basename( HSRTECH_FILE ), 'hsrtech_add_plugin_action_links' );

/**
 * Register the top-level "Chapterwright" admin page.
 *
 * The Book/Chapter post type screens themselves stay reachable (they are
 * registered with `show_ui => true`) for direct links from this app and
 * from the block editor sidebar panel, but no longer appear in the nav —
 * this page, and its Settings submenu, are the only Chapterwright entries an
 * author sees in the sidebar.
 *
 * Must run before admin/settings.php's hsrtech_add_settings_page() — see the
 * priority comment above.
 *
 * Gated on 'edit_hsrtech_books' rather than the generic 'edit_posts', so this
 * requires access to this plugin's own content, not blanket access to every
 * post on the site. hsrtech_add_capabilities_to_roles() grants every default
 * role the same practical access.
 */
function hsrtech_add_app_page() {
	add_menu_page(
		__( 'Chapterwright', 'chapterwright' ),
		__( 'Chapterwright', 'chapterwright' ),
		'edit_hsrtech_books',
		'chapterwright',
		'hsrtech_render_app_page',
		'dashicons-book-alt',
		// 100: below Settings (80) and the 99 separator, out of the way of
		// the primary content menus, per the WordPress.org Plugin Directory
		// reviewer's recommendation.
		100
	);

	add_submenu_page(
		'chapterwright',
		__( 'Chapterwright', 'chapterwright' ),
		__( 'Books & Chapters', 'chapterwright' ),
		'edit_hsrtech_books',
		'chapterwright',
		'hsrtech_render_app_page'
	);
}

/**
 * Render the app's mount point. Everything else happens in JavaScript.
 */
function hsrtech_render_app_page() {
	echo '<div class="wrap"><div id="chapterwright-app"></div></div>';
}

/**
 * Add a "Books & Chapters" link to this plugin's row on the Plugins screen,
 * next to Deactivate, for a fast way back to the admin app.
 *
 * @param array<int,string> $links Existing action links (Deactivate, etc.).
 * @return array<int,string>
 */
function hsrtech_add_plugin_action_links( $links ) {
	if ( ! current_user_can( 'edit_hsrtech_books' ) ) {
		return $links;
	}

	array_unshift(
		$links,
		sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'admin.php?page=chapterwright' ) ),
			esc_html__( 'Books & Chapters', 'chapterwright' )
		)
	);

	return $links;
}

/**
 * Load the admin app's compiled script and style on its own admin page only.
 *
 * @param string $hook_suffix Current admin page identifier.
 */
function hsrtech_enqueue_app_assets( $hook_suffix ) {
	if ( 'toplevel_page_chapterwright' !== $hook_suffix ) {
		return;
	}

	$asset_file = HSRTECH_PATH . 'admin/app/build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'chapterwright-app',
		HSRTECH_URL . 'admin/app/build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_set_script_translations( 'chapterwright-app', 'chapterwright' );

	// wp-scripts names this file style-index.css, not index.css, because the
	// entry point is literally named "index" — a webpack-config quirk
	// specific to that one entry name (the editor-sidebar entry below does
	// not have this prefix). Confirmed by inspecting admin/app/build/ after
	// `npm run build`; do not "fix" this filename without re-checking.
	if ( file_exists( HSRTECH_PATH . 'admin/app/build/style-index.css' ) ) {
		wp_enqueue_style(
			'chapterwright-app',
			HSRTECH_URL . 'admin/app/build/style-index.css',
			array( 'wp-components' ),
			$asset['version']
		);
	}

	wp_localize_script(
		'chapterwright-app',
		'hsrtechApp',
		array(
			'adminUrl'       => admin_url(),
			// Lets the chapter list show/hide each chapter's excerpt to
			// match the front-end table of contents' own "Show excerpt in
			// table of contents" setting (Settings → Chapterwright), rather
			// than the admin app always showing it or needing its own,
			// separate toggle for the same thing.
			'showTocExcerpt' => hsrtech_show_toc_excerpt(),
		)
	);
}

/**
 * Load the block editor sidebar panel's compiled script and style, only on
 * the Book and Chapter editor screens.
 */
function hsrtech_enqueue_editor_sidebar_assets() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( HSRTECH_BOOK_POST_TYPE, HSRTECH_CHAPTER_POST_TYPE ), true ) ) {
		return;
	}

	$asset_file = HSRTECH_PATH . 'admin/app/build/editor-sidebar.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'chapterwright-editor-sidebar',
		HSRTECH_URL . 'admin/app/build/editor-sidebar.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_set_script_translations( 'chapterwright-editor-sidebar', 'chapterwright' );

	if ( file_exists( HSRTECH_PATH . 'admin/app/build/editor-sidebar.css' ) ) {
		wp_enqueue_style(
			'chapterwright-editor-sidebar',
			HSRTECH_URL . 'admin/app/build/editor-sidebar.css',
			array(),
			$asset['version']
		);
	}
}
