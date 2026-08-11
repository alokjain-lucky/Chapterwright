<?php
/**
 * Script dependency manifest for edit.js.
 *
 * WordPress looks for a file named exactly like this next to a block.json
 * "file:" script reference and uses it to register the right `wp-*` script
 * dependencies and a cache-busting version, without requiring @wordpress/scripts
 * or any other build tool.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
	'version'      => MAKE_A_BOOK_VERSION,
);
