<?php
/**
 * Registers the chapterwright/note block.
 *
 * A static block: unlike chapterwright/code-snippet, there's no
 * server-computed data to fold in at render time, so edit.js's own save()
 * function produces the block's actual stored/front-end markup directly —
 * no render.php or viewScript needed here.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'hsrtech_register_note_block' );

/**
 * Register the Note block from its block.json.
 */
function hsrtech_register_note_block() {
	register_block_type( __DIR__ );
}
