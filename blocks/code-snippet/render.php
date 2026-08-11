<?php
/**
 * Server-side render for make-a-book/code-snippet.
 *
 * WordPress includes this file for every instance of the block and makes
 * `$attributes`, `$content`, and `$block` available. The block is dynamic
 * (edit.js's save() returns null) specifically so all output escaping lives
 * in this one place instead of being duplicated between PHP and JS.
 *
 * @package Make_A_Book
 *
 * @var array<string,mixed> $attributes Block attributes (code, language, caption).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$code     = isset( $attributes['code'] ) ? (string) $attributes['code'] : '';
$language = isset( $attributes['language'] ) ? sanitize_key( $attributes['language'] ) : 'text';
$caption  = isset( $attributes['caption'] ) ? (string) $attributes['caption'] : '';

// Nothing to show yet (e.g. a freshly inserted, still-empty block).
if ( '' === trim( $code ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'mab-code' ) );
?>
<figure <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes its own output. ?>>
	<?php if ( $caption ) : ?>
		<figcaption class="mab-code__caption"><?php echo esc_html( $caption ); ?></figcaption>
	<?php endif; ?>
	<div class="mab-code__frame">
		<span class="mab-code__lang" aria-hidden="true"><?php echo esc_html( strtoupper( $language ) ); ?></span>
		<button
			class="mab-code__copy"
			type="button"
			data-mab-copy-label="<?php esc_attr_e( 'Copy', 'make-a-book' ); ?>"
			data-mab-copied-label="<?php esc_attr_e( 'Copied!', 'make-a-book' ); ?>"
		><?php esc_html_e( 'Copy', 'make-a-book' ); ?></button>
		<pre data-mab-language="<?php echo esc_attr( $language ); ?>"><code><?php echo esc_html( $code ); ?></code></pre>
	</div>
</figure>
