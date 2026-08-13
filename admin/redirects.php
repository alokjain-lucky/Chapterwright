<?php
/**
 * Redirect native post-type screens this plugin hides from the admin menu
 * back to the equivalent screen in the "Make a Book" admin app.
 *
 * Books and Chapters register with `show_ui => true` (so post.php — the
 * actual Block Editor screen — keeps working; the admin app and the editor
 * sidebar panel both deep-link straight into it) but `show_in_menu => false`
 * (see mab_register_post_types(), includes/content-types.php). That leaves
 * their native list-table and "Add New" screens still directly reachable by
 * URL even though nothing in the plugin links to them anymore, and landing
 * on one shows the old, scattered pre-2.0.0 interface the admin app
 * replaced. This redirects those specific URLs back to the app.
 *
 * Deliberately does NOT touch:
 * - post.php?action=edit (needed to write a Book/Chapter's actual content).
 * - The native Trash list (edit.php?...&post_status=trash) — restoring or
 *   permanently deleting a trashed Book/Chapter happens there, not in the
 *   admin app; see the "View trashed books" link in
 *   admin/app/src/screens/books-list.js, which relies on this staying
 *   reachable.
 *
 * There are no custom taxonomies in this plugin (Sections are a database
 * table, not a taxonomy — see includes/sections.php and the "Notable
 * history" entry on why), so there is nothing to redirect on that front.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'load-edit.php', 'mab_redirect_post_type_list_screen' );
add_action( 'load-post-new.php', 'mab_redirect_post_type_new_screen' );

/**
 * Redirect the native Books/Chapters list-table screens
 * (edit.php?post_type=mab_book, edit.php?post_type=mab_chapter) to the
 * admin app — except when viewing Trash, which the admin app does not
 * reimplement.
 */
function mab_redirect_post_type_list_screen() {
	if ( ! mab_current_request_targets_plugin_post_type() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect decision (which screen to show), not a form submission being processed.
	if ( isset( $_GET['post_status'] ) && 'trash' === $_GET['post_status'] ) {
		return;
	}

	wp_safe_redirect( mab_redirect_target_for_list_screen() );
	exit;
}

/**
 * Work out where mab_redirect_post_type_list_screen() should send the user:
 * the specific book they were just working on, when that can be determined,
 * or the general books list otherwise.
 *
 * The only realistic way to land on edit.php for these post types at all
 * (nothing in the plugin links here) is WordPress's own post-action
 * redirect after something happens on post.php or the Trash list — trashing
 * a chapter, restoring one, permanently deleting one, and so on all end with
 * a redirect to edit.php?post_type=...&ids={id} (see wp_redirect_post_location()
 * in WordPress core). That `ids` value is the thread back to "which book was
 * this about," so following it beats always dropping the user on the
 * general list regardless of what they were just doing.
 *
 * @return string Admin URL to redirect to.
 */
function mab_redirect_target_for_list_screen() {
	$books_list_url = admin_url( 'admin.php?page=make-a-book' );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect decision.
	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect decision. Comma-separated on bulk actions; only the first is used.
	$ids = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : '';

	$first_id = $ids ? absint( strtok( $ids, ',' ) ) : 0;
	if ( ! $first_id ) {
		return $books_list_url;
	}

	if ( MAB_BOOK_POST_TYPE === $post_type ) {
		// The book itself was just acted on. If it was trashed or
		// permanently deleted, its own detail page in the app has nothing to
		// show — fall back to the list. Otherwise (most commonly: restored
		// from Trash), send the user straight back to it.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect decision.
		if ( isset( $_GET['trashed'] ) || isset( $_GET['deleted'] ) ) {
			return $books_list_url;
		}

		return $books_list_url . '#/books/' . $first_id;
	}

	if ( MAB_CHAPTER_POST_TYPE === $post_type ) {
		// A chapter's own post meta survives trashing (only a permanent
		// delete removes it, and that path keeps post_status=trash in the
		// query — exempted before this function is ever called), so this
		// works for the trash/restore cases that actually reach here.
		$book_id = absint( get_post_meta( $first_id, '_mab_book_id', true ) );
		if ( $book_id ) {
			return $books_list_url . '#/books/' . $book_id;
		}
	}

	return $books_list_url;
}

/**
 * Redirect the native "Add New Book" / "Add New Chapter" screens
 * (post-new.php?post_type=...) to the admin app, which has its own add-book
 * form (on the books list) and add-chapter form (on a book's detail page).
 *
 * Creating a Chapter directly on this native screen would skip setting
 * `_mab_book_id`, leaving an orphaned chapter assigned to no book — one more
 * reason this redirect matters beyond just UI consistency.
 */
function mab_redirect_post_type_new_screen() {
	if ( ! mab_current_request_targets_plugin_post_type() ) {
		return;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=make-a-book' ) );
	exit;
}

/**
 * Whether the current request's `post_type` query var is one of this
 * plugin's own post types.
 *
 * @return bool
 */
function mab_current_request_targets_plugin_post_type() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect decision, not a form submission.
	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';

	return in_array( $post_type, array( MAB_BOOK_POST_TYPE, MAB_CHAPTER_POST_TYPE ), true );
}
