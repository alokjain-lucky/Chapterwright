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

$code     = isset( $attributes['code'] ) ? (string) $attributes['code'] : '';
$language = isset( $attributes['language'] ) ? sanitize_key( $attributes['language'] ) : 'text';
$caption  = isset( $attributes['caption'] ) ? (string) $attributes['caption'] : '';

// Nothing to show yet (e.g. a freshly inserted, still-empty block).
if ( '' === trim( $code ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'hsrtech-code' ) );
?>
<figure <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes its own output. ?>>
	<?php if ( $caption ) : ?>
		<figcaption class="hsrtech-code__caption"><?php echo esc_html( $caption ); ?></figcaption>
	<?php endif; ?>
	<div class="hsrtech-code__frame">
		<span class="hsrtech-code__lang" aria-hidden="true"><?php echo esc_html( strtoupper( $language ) ); ?></span>
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
		<pre data-hsrtech-language="<?php echo esc_attr( $language ); ?>"><code><?php echo esc_html( $code ); ?></code></pre>
	</div>
</figure>
