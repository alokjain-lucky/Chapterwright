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

$hsrtech_wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $hsrtech_figure_classes ) ) );
?>
<figure <?php echo $hsrtech_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes its own output. ?>>
	<?php if ( $hsrtech_caption ) : ?>
		<figcaption class="hsrtech-code__caption"><?php echo esc_html( $hsrtech_caption ); ?></figcaption>
	<?php endif; ?>
	<div class="hsrtech-code__frame">
		<?php if ( ! $hsrtech_hide_language_label ) : ?>
			<span class="hsrtech-code__lang" aria-hidden="true"><?php echo esc_html( strtoupper( $hsrtech_language ) ); ?></span>
		<?php endif; ?>
		<button
			class="hsrtech-code__copy"
			type="button"
			data-hsrtech-copy-label="<?php esc_attr_e( 'Copy code', 'chapterwright' ); ?>"
			data-hsrtech-copied-label="<?php esc_attr_e( 'Copied!', 'chapterwright' ); ?>"
			aria-label="<?php esc_attr_e( 'Copy code', 'chapterwright' ); ?>"
		>
			<svg class="hsrtech-code__copy-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"></rect><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"></path></svg>
			<svg class="hsrtech-code__copied-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
		</button>
		<div class="hsrtech-code__body">
			<?php if ( $hsrtech_show_line_numbers ) : ?>
				<?php
				// One number per source line, not per rendered/wrapped visual
				// row — see the .hsrtech-code__line-numbers comment in
				// style.css for the "Wrap long lines" + "Show line numbers"
				// combination this doesn't account for.
				$hsrtech_line_count = substr_count( $hsrtech_code, "\n" ) + 1;
				// Echoed via PHP rather than closed/reopened around a literal <pre>
				// tag, specifically so this stays one continuous embedded PHP region
				// with no opening tag sharing a line with markup, which is what
				// tripped Squiz.PHP.EmbeddedPhp.ContentBeforeOpen here before.
				echo '<pre class="hsrtech-code__line-numbers" aria-hidden="true">';
				for ( $hsrtech_i = 1; $hsrtech_i <= $hsrtech_line_count; $hsrtech_i++ ) {
					echo esc_html( (string) $hsrtech_i );
					if ( $hsrtech_i < $hsrtech_line_count ) {
						echo "\n";
					}
				}
				// phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- Deliberately no line break before the closing tag either: same reason as the opening tag above, for the closing </pre>.
				?></pre>
			<?php endif; ?>
			<pre data-hsrtech-language="<?php echo esc_attr( $hsrtech_language ); ?>"><code><?php echo esc_html( $hsrtech_code ); ?></code></pre>
		</div>
	</div>
</figure>
