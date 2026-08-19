<?php
/**
 * Script dependency manifest for edit.js.
 *
 * WordPress looks for a file named exactly like this next to a block.json
 * "file:" script reference and uses it to register the right `wp-*` script
 * dependencies and a cache-busting version, without requiring @wordpress/scripts
 * or any other build tool.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	// chapterwright-code-highlight (assets/js/code-highlight.js, registered by
	// hsrtech_register_code_highlight_script(), code-snippet.php) is listed
	// as a dependency purely so WordPress loads it before this script runs —
	// edit.js's buildPreviewElement() reads window.hsrtechCodeHighlight
	// (that file's tokenizer + language rules) to color its own read-only
	// preview the same way the front end does.
	'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'chapterwright-code-highlight' ),
	'version'      => HSRTECH_VERSION,
);
