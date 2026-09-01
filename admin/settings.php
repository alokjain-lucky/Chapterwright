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
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Default priority (10) is intentional and must stay *after* admin/app.php's
// hsrtech_add_app_page(), which runs at priority 5 specifically so the
// top-level "chapterwright" menu it registers exists before this file's
// add_submenu_page( 'chapterwright', ... ) call below. See the priority
// comment on that hook in admin/app.php for what breaks if this ordering
// is lost.
add_action( 'admin_menu', 'hsrtech_add_settings_page' );
add_action( 'admin_init', 'hsrtech_register_settings' );

/**
 * The plugin's default settings, including the current default copy for
 * every editable string. Used both as the registered setting's default and
 * to pre-fill the settings form the first time it's opened, so leaving a
 * field untouched keeps today's behavior unchanged.
 *
 * @return array<string,string> Default settings, keyed by field name.
 */
function hsrtech_default_settings() {
	return array(
		'show_mode_toggle'              => '0',
		'show_credit'                   => '0',
		'show_toc_excerpt'              => '1',
		'show_toc_section_description'  => '1',
		'show_toc_button'               => '1',
		'toc_button_offset'             => '0',
		'toc_button_right_offset'       => '0',
		'show_draft_chapters'           => '0',
		'disable_code_snippet_block'    => '0',
		'archive_eyebrow'               => __( 'The library', 'chapterwright' ),
		'archive_heading'               => __( 'Books worth opening', 'chapterwright' ),
		'archive_subheading'            => __( 'Read one chapter at a time, right here on the web.', 'chapterwright' ),
		'toc_heading'                   => __( 'Read at your own pace', 'chapterwright' ),
	);
}

/**
 * Register the `hsrtech_settings` option, stored as a single array so the
 * plugin's admin footprint in wp_options stays to one row.
 */
function hsrtech_register_settings() {
	register_setting(
		'hsrtech_settings_group',
		'hsrtech_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'hsrtech_sanitize_settings',
			'default'           => hsrtech_default_settings(),
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
function hsrtech_sanitize_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$defaults = hsrtech_default_settings();

	return array(
		'show_mode_toggle'              => empty( $input['show_mode_toggle'] ) ? '0' : '1',
		'show_credit'                   => empty( $input['show_credit'] ) ? '0' : '1',
		'show_toc_excerpt'              => empty( $input['show_toc_excerpt'] ) ? '0' : '1',
		'show_toc_section_description'  => empty( $input['show_toc_section_description'] ) ? '0' : '1',
		'show_toc_button'               => empty( $input['show_toc_button'] ) ? '0' : '1',
		// Clamped to a sane range rather than left open-ended — mainly to
		// keep a typo (an extra digit) from pushing the button fully off
		// screen, not because a legitimate value would ever need to be
		// anywhere near 500px.
		'toc_button_offset'             => isset( $input['toc_button_offset'] ) ? (string) min( 500, absint( $input['toc_button_offset'] ) ) : $defaults['toc_button_offset'],
		// Same clamping rationale as toc_button_offset above, applied to the
		// horizontal axis instead of the vertical one.
		'toc_button_right_offset'       => isset( $input['toc_button_right_offset'] ) ? (string) min( 500, absint( $input['toc_button_right_offset'] ) ) : $defaults['toc_button_right_offset'],
		'show_draft_chapters'           => empty( $input['show_draft_chapters'] ) ? '0' : '1',
		'disable_code_snippet_block'    => empty( $input['disable_code_snippet_block'] ) ? '0' : '1',
		'archive_eyebrow'               => isset( $input['archive_eyebrow'] ) ? sanitize_text_field( wp_unslash( $input['archive_eyebrow'] ) ) : $defaults['archive_eyebrow'],
		'archive_heading'               => isset( $input['archive_heading'] ) ? sanitize_text_field( wp_unslash( $input['archive_heading'] ) ) : $defaults['archive_heading'],
		'archive_subheading'            => isset( $input['archive_subheading'] ) ? sanitize_text_field( wp_unslash( $input['archive_subheading'] ) ) : $defaults['archive_subheading'],
		'toc_heading'                   => isset( $input['toc_heading'] ) ? sanitize_text_field( wp_unslash( $input['toc_heading'] ) ) : $defaults['toc_heading'],
	);
}

/**
 * Read the saved settings, merged over the defaults so a partially-saved
 * or pre-1.3.0 option value still returns every expected key.
 *
 * @return array<string,string> Current settings.
 */
function hsrtech_get_settings() {
	$settings = get_option( 'hsrtech_settings', array() );
	return wp_parse_args( is_array( $settings ) ? $settings : array(), hsrtech_default_settings() );
}

/**
 * Whether the reader's own color-mode toggle button should be rendered on
 * book and chapter pages. Off by default, on only if a site owner
 * explicitly enables it in settings — most themes already have their own
 * site-wide color-mode control (this site's header does), and showing a
 * second, separate one on top of it by default reads as redundant/confusing
 * rather than helpful. The theme's own toggle (if any) is unaffected either
 * way, since the two are unrelated controls.
 *
 * @return bool
 */
function hsrtech_show_mode_toggle() {
	$settings = hsrtech_get_settings();
	return '1' === $settings['show_mode_toggle'];
}

/**
 * Whether the "This book is created with Chapterwright" / "This library is
 * powered by Chapterwright" credit line should be rendered at the bottom of
 * book/chapter/archive pages. Off by default — this is front-end attribution,
 * so per the Plugin Directory guidelines it must be an explicit, deliberate
 * opt-in from the site owner in Settings, not something shown until someone
 * notices and turns it off.
 *
 * @return bool
 */
function hsrtech_show_credit() {
	$settings = hsrtech_get_settings();
	return '1' === $settings['show_credit'];
}

/**
 * Whether each chapter's excerpt is shown below its title in the table of
 * contents on a book's page. On by default; a site owner can turn it off in
 * Settings for a more compact, title-and-number-only list.
 *
 * @return bool
 */
function hsrtech_show_toc_excerpt() {
	$settings = hsrtech_get_settings();
	return '1' === $settings['show_toc_excerpt'];
}

/**
 * Whether a section's description is shown under its heading in the table
 * of contents (a book's own page, and the chapter-page TOC drawer). On by
 * default; a site owner can turn it off in Settings for a more compact
 * heading-only list of sections, the same idea as hsrtech_show_toc_excerpt()
 * above but for a section's description rather than a chapter's excerpt.
 * Turning this off never touches the description text itself — it stays
 * exactly as written, and still shows on the section's own introduction
 * page (if it has one), just not repeated under the heading in the list.
 *
 * @return bool
 */
function hsrtech_show_toc_section_description() {
	$settings = hsrtech_get_settings();
	return '1' === $settings['show_toc_section_description'];
}

/**
 * Whether a floating "back to table of contents" button is shown at the
 * bottom-right of chapter pages, linking to the book's table of contents.
 * On by default; a site owner can turn it off in Settings.
 *
 * @return bool
 */
function hsrtech_show_toc_button() {
	$settings = hsrtech_get_settings();
	return '1' === $settings['show_toc_button'];
}

/**
 * Extra spacing (in pixels) added above the floating table of contents
 * button's default bottom-right position, on top of the button itself.
 * Zero by default (today's existing position, untouched). Exists for a site
 * whose theme adds its own floating element in the same corner — a chat
 * widget, a "Buy me a coffee" button, a cookie banner — which would
 * otherwise overlap this button with no way to nudge either one out of the
 * other's way short of editing CSS.
 *
 * @return int Pixels, 0-500.
 */
function hsrtech_toc_button_offset() {
	$settings = hsrtech_get_settings();
	return absint( $settings['toc_button_offset'] );
}

/**
 * Extra spacing (in pixels) added to the right of the floating table of
 * contents button's default bottom-right position, on top of the button
 * itself. Zero by default (today's existing position, untouched). Same
 * purpose as hsrtech_toc_button_offset() above but for the horizontal
 * axis, for a theme's floating element that overlaps from the side rather
 * than from below.
 *
 * @return int Pixels, 0-500.
 */
function hsrtech_toc_button_right_offset() {
	$settings = hsrtech_get_settings();
	return absint( $settings['toc_button_right_offset'] );
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
function hsrtech_show_draft_chapters() {
	$settings = hsrtech_get_settings();
	return '1' === $settings['show_draft_chapters'];
}

/**
 * Whether the Code Snippet block should be removed from the block inserter.
 * Off by default. Turning this on doesn't touch anything already
 * published — a Code Snippet block already placed in a book, chapter, or
 * any other post keeps rendering exactly as it does today; this only stops
 * the block from being offered for *new* content, for a site owner who'd
 * rather standardize on a different code-formatting block or plugin going
 * forward instead of ripping out content that already uses this one.
 *
 * @return bool
 */
function hsrtech_code_snippet_block_disabled() {
	$settings = hsrtech_get_settings();
	return '1' === $settings['disable_code_snippet_block'];
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
function hsrtech_get_text( $key ) {
	$settings = hsrtech_get_settings();
	return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
}

/**
 * Add the settings page under the top-level "Chapterwright" admin menu
 * (registered in admin/app.php, alongside the Books & Chapters app page).
 */
function hsrtech_add_settings_page() {
	add_submenu_page(
		'chapterwright',
		__( 'Chapterwright Settings', 'chapterwright' ),
		__( 'Settings', 'chapterwright' ),
		'manage_options',
		'chapterwright-settings',
		'hsrtech_render_settings_page'
	);
}

/**
 * Render the settings page.
 */
function hsrtech_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = hsrtech_get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Chapterwright Settings', 'chapterwright' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'hsrtech_settings_group' ); ?>

			<h2><?php esc_html_e( 'Reading experience', 'chapterwright' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Color mode toggle', 'chapterwright' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="hsrtech_settings[show_mode_toggle]" value="1" <?php checked( '1', $settings['show_mode_toggle'] ); ?> />
							<?php esc_html_e( 'Show the light/dark reading mode button on book and chapter pages', 'chapterwright' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'This is the reader\'s own toggle and is separate from any color-mode switch your theme adds to the site header. Turn this off if the two feel redundant and let the reader simply follow the system/browser color preference instead.', 'chapterwright' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Credit link', 'chapterwright' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="hsrtech_settings[show_credit]" value="1" <?php checked( '1', $settings['show_credit'] ); ?> />
							<?php esc_html_e( 'Show "This book is created with Chapterwright" at the bottom of book, chapter, and library pages', 'chapterwright' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Table of contents excerpts', 'chapterwright' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="hsrtech_settings[show_toc_excerpt]" value="1" <?php checked( '1', $settings['show_toc_excerpt'] ); ?> />
							<?php esc_html_e( 'Show each chapter\'s excerpt below its title in the table of contents', 'chapterwright' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Turn this off for a shorter, title-and-number-only chapter list.', 'chapterwright' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Table of contents section descriptions', 'chapterwright' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="hsrtech_settings[show_toc_section_description]" value="1" <?php checked( '1', $settings['show_toc_section_description'] ); ?> />
							<?php esc_html_e( 'Show each section\'s description below its heading in the table of contents', 'chapterwright' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Turn this off for a shorter list of section headings only. The description itself is unaffected everywhere else, including on a section\'s own introduction page.', 'chapterwright' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Floating table of contents button', 'chapterwright' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="hsrtech_settings[show_toc_button]" value="1" <?php checked( '1', $settings['show_toc_button'] ); ?> />
							<?php esc_html_e( 'Show a floating button on chapter pages that opens the book\'s table of contents in a side panel', 'chapterwright' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Button position', 'chapterwright' ); ?></th>
					<td>
						<p>
							<label for="hsrtech-toc-button-offset"><?php esc_html_e( 'Bottom offset:', 'chapterwright' ); ?></label>
							<input type="number" id="hsrtech-toc-button-offset" class="small-text" min="0" max="500" step="1" name="hsrtech_settings[toc_button_offset]" value="<?php echo esc_attr( $settings['toc_button_offset'] ); ?>" />
							<?php esc_html_e( 'px of extra space above the button', 'chapterwright' ); ?>
						</p>
						<p>
							<label for="hsrtech-toc-button-right-offset"><?php esc_html_e( 'Right offset:', 'chapterwright' ); ?></label>
							<input type="number" id="hsrtech-toc-button-right-offset" class="small-text" min="0" max="500" step="1" name="hsrtech_settings[toc_button_right_offset]" value="<?php echo esc_attr( $settings['toc_button_right_offset'] ); ?>" />
							<?php esc_html_e( 'px of extra space to the right of the button', 'chapterwright' ); ?>
						</p>
						<p class="description">
							<?php esc_html_e( 'Nudge the button if your theme adds its own floating element in the same bottom-right corner — a chat widget, a "Buy me a coffee" button, a cookie banner — that would otherwise overlap it. Leave both at 0 for the default position.', 'chapterwright' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Draft chapters in the table of contents', 'chapterwright' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="hsrtech_settings[show_draft_chapters]" value="1" <?php checked( '1', $settings['show_draft_chapters'] ); ?> />
							<?php esc_html_e( 'List chapters still in draft alongside published ones, faded and without a link', 'chapterwright' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Gives readers a preview of what\'s coming without letting them open an unfinished chapter. Off by default.', 'chapterwright' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Code Snippet block', 'chapterwright' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Block inserter', 'chapterwright' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="hsrtech_settings[disable_code_snippet_block]" value="1" <?php checked( '1', $settings['disable_code_snippet_block'] ); ?> />
							<?php esc_html_e( 'Remove the Code Snippet block from the block inserter', 'chapterwright' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Use this if you\'d rather use a different code-formatting block or plugin. Any Code Snippet block already in your content keeps working and displaying normally — this only stops new ones from being added.', 'chapterwright' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Library page text', 'chapterwright' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Shown at the top of the book library (/books/). Clear a field to remove that line entirely.', 'chapterwright' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="hsrtech-archive-eyebrow"><?php esc_html_e( 'Eyebrow label', 'chapterwright' ); ?></label></th>
					<td><input type="text" id="hsrtech-archive-eyebrow" class="regular-text" name="hsrtech_settings[archive_eyebrow]" value="<?php echo esc_attr( $settings['archive_eyebrow'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="hsrtech-archive-heading"><?php esc_html_e( 'Heading', 'chapterwright' ); ?></label></th>
					<td><input type="text" id="hsrtech-archive-heading" class="regular-text" name="hsrtech_settings[archive_heading]" value="<?php echo esc_attr( $settings['archive_heading'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="hsrtech-archive-subheading"><?php esc_html_e( 'Subheading', 'chapterwright' ); ?></label></th>
					<td><input type="text" id="hsrtech-archive-subheading" class="regular-text" name="hsrtech_settings[archive_subheading]" value="<?php echo esc_attr( $settings['archive_subheading'] ); ?>" /></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Book page text', 'chapterwright' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="hsrtech-toc-heading"><?php esc_html_e( 'Table of contents heading', 'chapterwright' ); ?></label></th>
					<td><input type="text" id="hsrtech-toc-heading" class="regular-text" name="hsrtech_settings[toc_heading]" value="<?php echo esc_attr( $settings['toc_heading'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Shown above the chapter list on every book\'s page.', 'chapterwright' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
