<?php
/**
 * Registers the chapterwright/code-snippet block.
 *
 * A dynamic block: the editor only collects `code`, `language`, and
 * `caption`; the front end is always rendered by render.php from those
 * attributes, so escaping happens in exactly one place. No build step is
 * required — edit.js and view.js are written directly against the `wp.*`
 * script handles WordPress already registers, matching the rest of this
 * plugin's dependency-free JavaScript.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'hsrtech_register_code_snippet_block' );

/**
 * Register the Code Snippet block from its block.json.
 */
function hsrtech_register_code_snippet_block() {
	register_block_type( __DIR__ );
}
