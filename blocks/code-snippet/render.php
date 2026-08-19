<?php
/**
 * Server-side render for chapterwright/code-snippet.
 *
 * WordPress includes this file for every instance of the block and makes
 * `$attributes`, `$content`, and `$block` available. The block is dynamic
 * (edit.js's save() returns null) specifically so all output escaping lives
 * in this one place instead of being duplicated between PHP and JS.
 *
 * @package Chapterwright
 *
 * @var array<string,mixed> $attributes Block attributes (code, language, caption).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hsrtech_code                = isset( $attributes['code'] ) ? (string) $attributes['code'] : '';
$hsrtech_language            = isset( $attributes['language'] ) ? sanitize_key( $attributes['language'] ) : 'text';
$hsrtech_caption             = isset( $attributes['caption'] ) ? (string) $attributes['caption'] : '';
$hsrtech_wrap_lines          = ! empty( $attributes['wrapLines'] );
$hsrtech_show_line_numbers   = ! empty( $attributes['showLineNumbers'] );
$hsrtech_hide_language_label = ! empty( $attributes['hideLanguageLabel'] );
$hsrtech_highlight_lines_raw = isset( $attributes['highlightLines'] ) ? (string) $attributes['highlightLines'] : '';
$hsrtech_start_line          = isset( $attributes['startLine'] ) ? (int) $attributes['startLine'] : 1;
$hsrtech_hide_copy_button    = ! empty( $attributes['hideCopyButton'] );
$hsrtech_hide_wrap_toggle    = ! empty( $attributes['hideWrapToggle'] );

if ( $hsrtech_start_line < 1 ) {
	$hsrtech_start_line = 1;
}

$hsrtech_highlight_set = hsrtech_parse_code_snippet_line_ranges( $hsrtech_highlight_lines_raw );

// Nothing to show yet (e.g. a freshly inserted, still-empty block).
if ( '' === trim( $hsrtech_code ) ) {
	return;
}

$hsrtech_figure_classes = array( 'hsrtech-code' );
if ( $hsrtech_wrap_lines ) {
	// See the matching .hsrtech-code--wrap rule, blocks/code-snippet/style.css,
	// for why this and "Show line numbers" don't fully agree with each other.
	$hsrtech_figure_classes[] = 'hsrtech-code--wrap';
}
if ( $hsrtech_hide_language_label ) {
	// Lets the .hsrtech-code--no-lang rule, blocks/code-snippet/style.css,
	// give back some of the top padding reserved for the now-absent label.
	$hsrtech_figure_classes[] = 'hsrtech-code--no-lang';
}
if ( $hsrtech_hide_copy_button && $hsrtech_hide_wrap_toggle ) {
	// Both corner buttons off for this block: nothing left in the frame's
	// top-right corner at all, so .hsrtech-code--no-actions (style.css) can
	// give back the rest of the padding reserved for them. Only one of the
	// two being off just lets the other slide over — see .hsrtech-code__actions,
	// style.css — with no class needed for that case.
	$hsrtech_figure_classes[] = 'hsrtech-code--no-actions';
}

$hsrtech_wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $hsrtech_figure_classes ) ) );

// One <div> per source line — with an optional number and/or highlight tint
// — is only worth the extra markup when something actually needs a per-line
// hook. Anything else stays the simpler flat <pre><code>, exactly as before
// "Show line numbers" and "Highlight lines" existed. Either shape is what a
// visitor with JavaScript disabled actually sees; assets/js/code-highlight.js
// discards and rebuilds whichever one rendered, from the raw code and the
// data-hsrtech-* attributes below, so it always ends up correct regardless
// of how a line wraps — see that file's buildLineRows() for why the two
// need to agree on markup shape but not on exact content.
$hsrtech_needs_rows = $hsrtech_show_line_numbers || ! empty( $hsrtech_highlight_set );

$hsrtech_code_lines = explode( "\n", $hsrtech_code );
$hsrtech_line_digits = strlen( (string) ( $hsrtech_start_line + count( $hsrtech_code_lines ) - 1 ) );
?>
<figure <?php echo $hsrtech_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes its own output. ?>>
	<?php if ( $hsrtech_caption ) : ?>
		<figcaption class="hsrtech-code__caption"><?php echo esc_html( $hsrtech_caption ); ?></figcaption>
	<?php endif; ?>
	<div class="hsrtech-code__frame">
		<?php if ( ! $hsrtech_hide_language_label ) : ?>
			<span class="hsrtech-code__lang" aria-hidden="true"><?php echo esc_html( strtoupper( $hsrtech_language ) ); ?></span>
		<?php endif; ?>
		<?php if ( ! $hsrtech_hide_wrap_toggle || ! $hsrtech_hide_copy_button ) : ?>
			<div class="hsrtech-code__actions">
				<?php if ( ! $hsrtech_hide_wrap_toggle ) : ?>
					<button
						class="hsrtech-code__wrap-toggle"
						type="button"
						data-hsrtech-wrap-label="<?php esc_attr_e( 'Wrap long lines', 'chapterwright' ); ?>"
						data-hsrtech-unwrap-label="<?php esc_attr_e( 'Scroll long lines', 'chapterwright' ); ?>"
						aria-label="<?php echo esc_attr( $hsrtech_wrap_lines ? __( 'Scroll long lines', 'chapterwright' ) : __( 'Wrap long lines', 'chapterwright' ) ); ?>"
						data-tooltip="<?php echo esc_attr( $hsrtech_wrap_lines ? __( 'Scroll long lines', 'chapterwright' ) : __( 'Wrap long lines', 'chapterwright' ) ); ?>"
						aria-pressed="<?php echo esc_attr( $hsrtech_wrap_lines ? 'true' : 'false' ); ?>"
					>
						<svg class="hsrtech-code__wrap-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h13a3 3 0 0 1 0 6h-4m2-2-2 2 2 2M3 18h6"></path></svg>
						<svg class="hsrtech-code__unwrap-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h18M3 18h18"></path></svg>
					</button>
				<?php endif; ?>
				<?php if ( ! $hsrtech_hide_copy_button ) : ?>
					<button
						class="hsrtech-code__copy"
						type="button"
						data-hsrtech-copy-label="<?php esc_attr_e( 'Copy code', 'chapterwright' ); ?>"
						data-hsrtech-copied-label="<?php esc_attr_e( 'Copied!', 'chapterwright' ); ?>"
						aria-label="<?php esc_attr_e( 'Copy code', 'chapterwright' ); ?>"
						data-tooltip="<?php esc_attr_e( 'Copy code', 'chapterwright' ); ?>"
					>
						<svg class="hsrtech-code__copy-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"></rect><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"></path></svg>
						<svg class="hsrtech-code__copied-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</button>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( $hsrtech_needs_rows ) : ?>
			<div
				class="hsrtech-code__lines"
				style="--hsrtech-line-digits: <?php echo esc_attr( (string) $hsrtech_line_digits ); ?>;"
				data-hsrtech-language="<?php echo esc_attr( $hsrtech_language ); ?>"
				data-hsrtech-show-numbers="<?php echo esc_attr( $hsrtech_show_line_numbers ? '1' : '0' ); ?>"
				data-hsrtech-start-line="<?php echo esc_attr( (string) $hsrtech_start_line ); ?>"
				data-hsrtech-highlight-lines="<?php echo esc_attr( $hsrtech_highlight_lines_raw ); ?>"
			>
				<?php
				foreach ( $hsrtech_code_lines as $hsrtech_row_index => $hsrtech_row_text ) :
					$hsrtech_row_classes = array( 'hsrtech-code__line' );
					if ( isset( $hsrtech_highlight_set[ $hsrtech_row_index + 1 ] ) ) {
						$hsrtech_row_classes[] = 'hsrtech-code__line--highlighted';
					}
					?>
					<div class="<?php echo esc_attr( implode( ' ', $hsrtech_row_classes ) ); ?>">
						<?php if ( $hsrtech_show_line_numbers ) : ?>
							<span class="hsrtech-code__line-number" aria-hidden="true"><?php echo esc_html( (string) ( $hsrtech_start_line + $hsrtech_row_index ) ); ?></span>
						<?php endif; ?>
						<span class="hsrtech-code__line-code"><?php echo esc_html( $hsrtech_row_text ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<pre data-hsrtech-language="<?php echo esc_attr( $hsrtech_language ); ?>"><code><?php echo esc_html( $hsrtech_code ); ?></code></pre>
		<?php endif; ?>
	</div>
</figure>
