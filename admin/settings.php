<?php
/**
 * Reading-experience settings page.
 *
 * A small options page (Books → Settings) for the few things site owners
 * reasonably want to tweak without editing code or shipping a translation:
 * whether the reader's own color-mode toggle appears (the theme may already
 * provide one in the site header, making a second toggle redundant on some
 * sites), and the handful of short marketing-style strings that appear on
 * the library archive and on each book's table-of-contents heading.
 *
 * Everything else visitor-facing (button labels, navigation labels, "Skip
 * to content" links, and so on) stays a translatable string in the
 * templates — those are UI chrome, not copy a site owner is likely to want
 * to reword, so they don't need a settings-page field.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Default priority (10) is intentional and must stay *after* admin/app.php's
// mab_add_app_page(), which runs at priority 5 specifically so the
// top-level "make-a-book" menu it registers exists before this file's
// add_submenu_page( 'make-a-book', ... ) call below. See the priority
// comment on that hook in admin/app.php for what breaks if this ordering
// is lost.
add_action( 'admin_menu', 'mab_add_settings_page' );
add_action( 'admin_init', 'mab_register_settings' );

/**
 * The plugin's default settings, including the current default copy for
 * every editable string. Used both as the registered setting's default and
 * to pre-fill the settings form the first time it's opened, so leaving a
 * field untouched keeps today's behavior unchanged.
 *
 * @return array<string,string> Default settings, keyed by field name.
 */
function mab_default_settings() {
	return array(
		'show_mode_toggle'   => '1',
		'show_credit'        => '1',
		'show_toc_excerpt'   => '1',
		'show_toc_button'    => '1',
		'show_draft_chapters' => '0',
		'archive_eyebrow'    => __( 'The library', 'make-a-book' ),
		'archive_heading'    => __( 'Books worth opening', 'make-a-book' ),
		'archive_subheading' => __( 'Read one chapter at a time, right here on the web.', 'make-a-book' ),
		'toc_heading'        => __( 'Read at your own pace', 'make-a-book' ),
	);
}

/**
 * Register the `mab_settings` option, stored as a single array so the
 * plugin's admin footprint in wp_options stays to one row.
 */
function mab_register_settings() {
	register_setting(
		'mab_settings_group',
		'mab_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'mab_sanitize_settings',
			'default'           => mab_default_settings(),
		)
	);
}

/**
 * Sanitize submitted settings before they're saved.
 *
 * @param array<string,mixed> $input Raw $_POST-derived settings array.
 * @return array<string,string> Sanitized settings, merged over the defaults
 *                               so a field missing from $input (e.g. an
 *                               unchecked checkbox) still resolves cleanly.
 */
function mab_sanitize_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$defaults = mab_default_settings();

	return array(
		'show_mode_toggle'   => empty( $input['show_mode_toggle'] ) ? '0' : '1',
		'show_credit'        => empty( $input['show_credit'] ) ? '0' : '1',
		'show_toc_excerpt'   => empty( $input['show_toc_excerpt'] ) ? '0' : '1',
		'show_toc_button'    => empty( $input['show_toc_button'] ) ? '0' : '1',
		'show_draft_chapters' => empty( $input['show_draft_chapters'] ) ? '0' : '1',
		'archive_eyebrow'    => isset( $input['archive_eyebrow'] ) ? sanitize_text_field( wp_unslash( $input['archive_eyebrow'] ) ) : $defaults['archive_eyebrow'],
		'archive_heading'    => isset( $input['archive_heading'] ) ? sanitize_text_field( wp_unslash( $input['archive_heading'] ) ) : $defaults['archive_heading'],
		'archive_subheading' => isset( $input['archive_subheading'] ) ? sanitize_text_field( wp_unslash( $input['archive_subheading'] ) ) : $defaults['archive_subheading'],
		'toc_heading'        => isset( $input['toc_heading'] ) ? sanitize_text_field( wp_unslash( $input['toc_heading'] ) ) : $defaults['toc_heading'],
	);
}

/**
 * Read the saved settings, merged over the defaults so a partially-saved
 * or pre-1.3.0 option value still returns every expected key.
 *
 * @return array<string,string> Current settings.
 */
function mab_get_settings() {
	$settings = get_option( 'mab_settings', array() );
	return wp_parse_args( is_array( $settings ) ? $settings : array(), mab_default_settings() );
}

/**
 * Whether the reader's own color-mode toggle button should be rendered on
 * book and chapter pages. Off by default only if a site owner explicitly
 * disables it — the theme's own header toggle (if any) is a separate,
 * unrelated control and isn't affected either way.
 *
 * @return bool
 */
function mab_show_mode_toggle() {
	$settings = mab_get_settings();
	return '1' === $settings['show_mode_toggle'];
}

/**
 * Whether the "This book is created with Make a Book" / "This library is
 * powered by Make a Book" credit line should be rendered at the bottom of
 * book/chapter/archive pages. On by default; a site owner can turn it off
 * in Settings.
 *
 * @return bool
 */
function mab_show_credit() {
	$settings = mab_get_settings();
	return '1' === $settings['show_credit'];
}

/**
 * Whether each chapter's excerpt is shown below its title in the table of
 * contents on a book's page. On by default; a site owner can turn it off in
 * Settings for a more compact, title-and-number-only list.
 *
 * @return bool
 */
function mab_show_toc_excerpt() {
	$settings = mab_get_settings();
	return '1' === $settings['show_toc_excerpt'];
}

/**
 * Whether a floating "back to table of contents" button is shown at the
 * bottom-right of chapter pages, linking to the book's table of contents.
 * On by default; a site owner can turn it off in Settings.
 *
 * @return bool
 */
function mab_show_toc_button() {
	$settings = mab_get_settings();
	return '1' === $settings['show_toc_button'];
}

/**
 * Whether draft chapters should appear in the table of contents (book page
 * and chapter-page drawer) alongside published ones — unlinked and visually
 * faded, per templates/partials/toc-list.php, so a reader can see a chapter
 * is coming without being able to open it. Off by default: showing draft
 * titles publicly is an editorial choice a site owner should opt into, not
 * something that changes just because an author started drafting a chapter.
 *
 * @return bool
 */
function mab_show_draft_chapters() {
	$settings = mab_get_settings();
	return '1' === $settings['show_draft_chapters'];
}

/**
 * Get one of the editable copy strings.
 *
 * Returns an empty string if the site owner cleared the field entirely —
 * templates should treat that as "hide this element" rather than falling
 * back to the default text, so clearing a field is a real way to remove
 * placeholder copy, not just reset it.
 *
 * @param string $key One of: archive_eyebrow, archive_heading,
 *                     archive_subheading, toc_heading.
 * @return string
 */
function mab_get_text( $key ) {
	$settings = mab_get_settings();
	return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
}

/**
 * Add the settings page under the top-level "Make a Book" admin menu
 * (registered in admin/app.php, alongside the Books & Chapters app page).
 */
function mab_add_settings_page() {
	add_submenu_page(
		'make-a-book',
		__( 'Make a Book Settings', 'make-a-book' ),
		__( 'Settings', 'make-a-book' ),
		'manage_options',
		'make-a-book-settings',
		'mab_render_settings_page'
	);
}

/**
 * Render the settings page.
 */
function mab_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = mab_get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Make a Book Settings', 'make-a-book' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'mab_settings_group' ); ?>

			<h2><?php esc_html_e( 'Reading experience', 'make-a-book' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Color mode toggle', 'make-a-book' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="mab_settings[show_mode_toggle]" value="1" <?php checked( '1', $settings['show_mode_toggle'] ); ?> />
							<?php esc_html_e( 'Show the light/dark reading mode button on book and chapter pages', 'make-a-book' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'This is the reader\'s own toggle and is separate from any color-mode switch your theme adds to the site header. Turn this off if the two feel redundant and let the reader simply follow the system/browser color preference instead.', 'make-a-book' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Credit link', 'make-a-book' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="mab_settings[show_credit]" value="1" <?php checked( '1', $settings['show_credit'] ); ?> />
							<?php esc_html_e( 'Show "This book is created with Make a Book" at the bottom of book, chapter, and library pages', 'make-a-book' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Table of contents excerpts', 'make-a-book' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="mab_settings[show_toc_excerpt]" value="1" <?php checked( '1', $settings['show_toc_excerpt'] ); ?> />
							<?php esc_html_e( 'Show each chapter\'s excerpt below its title in the table of contents', 'make-a-book' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Turn this off for a shorter, title-and-number-only chapter list.', 'make-a-book' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Floating table of contents button', 'make-a-book' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="mab_settings[show_toc_button]" value="1" <?php checked( '1', $settings['show_toc_button'] ); ?> />
							<?php esc_html_e( 'Show a floating button on chapter pages that opens the book\'s table of contents in a side panel', 'make-a-book' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Draft chapters in the table of contents', 'make-a-book' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="mab_settings[show_draft_chapters]" value="1" <?php checked( '1', $settings['show_draft_chapters'] ); ?> />
							<?php esc_html_e( 'List chapters still in draft alongside published ones, faded and without a link', 'make-a-book' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Gives readers a preview of what\'s coming without letting them open an unfinished chapter. Off by default.', 'make-a-book' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Library page text', 'make-a-book' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Shown at the top of the book library (/books/). Clear a field to remove that line entirely.', 'make-a-book' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mab-archive-eyebrow"><?php esc_html_e( 'Eyebrow label', 'make-a-book' ); ?></label></th>
					<td><input type="text" id="mab-archive-eyebrow" class="regular-text" name="mab_settings[archive_eyebrow]" value="<?php echo esc_attr( $settings['archive_eyebrow'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="mab-archive-heading"><?php esc_html_e( 'Heading', 'make-a-book' ); ?></label></th>
					<td><input type="text" id="mab-archive-heading" class="regular-text" name="mab_settings[archive_heading]" value="<?php echo esc_attr( $settings['archive_heading'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="mab-archive-subheading"><?php esc_html_e( 'Subheading', 'make-a-book' ); ?></label></th>
					<td><input type="text" id="mab-archive-subheading" class="regular-text" name="mab_settings[archive_subheading]" value="<?php echo esc_attr( $settings['archive_subheading'] ); ?>" /></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Book page text', 'make-a-book' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mab-toc-heading"><?php esc_html_e( 'Table of contents heading', 'make-a-book' ); ?></label></th>
					<td><input type="text" id="mab-toc-heading" class="regular-text" name="mab_settings[toc_heading]" value="<?php echo esc_attr( $settings['toc_heading'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Shown above the chapter list on every book\'s page.', 'make-a-book' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
