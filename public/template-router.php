<?php
/**
 * Front-end template routing for Book and Chapter views.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'template_include', 'hsrtech_template_include' );

/**
 * Select bundled templates for plugin-owned routes.
 *
 * Themes remain responsible for the surrounding header and footer. A theme
 * or extension can override the final result with a later `template_include`
 * filter — see README.md's "Overriding routed templates" section.
 *
 * @param string $template Absolute path to the theme-selected template.
 * @return string Absolute path to the selected template.
 */
function hsrtech_template_include( $template ) {
	$plugin_template = '';

	if ( is_singular( HSRTECH_BOOK_POST_TYPE ) ) {
		$plugin_template = HSRTECH_PATH . 'templates/single-hsrtech_book.php';
	} elseif ( is_singular( HSRTECH_CHAPTER_POST_TYPE ) ) {
		$plugin_template = HSRTECH_PATH . 'templates/single-hsrtech_chapter.php';
	} elseif ( is_post_type_archive( HSRTECH_BOOK_POST_TYPE ) ) {
		$plugin_template = HSRTECH_PATH . 'templates/archive-hsrtech_book.php';
	}

	return $plugin_template && file_exists( $plugin_template ) ? $plugin_template : $template;
}
