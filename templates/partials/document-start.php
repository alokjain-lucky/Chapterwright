<?php
/**
 * Open the theme document for classic and block themes.
 *
 * Block themes do not provide header.php. Calling get_header() under those
 * themes produces a WordPress deprecation notice, so the plugin opens the
 * document and renders the registered Header template part directly.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_is_block_theme() ) :
	// Render the header template part BEFORE wp_head(), not after — even
	// though it's only echoed once <body> opens, below. A normal block-theme
	// page avoids ever needing this because WordPress renders the ENTIRE
	// canvas (header + content + footer) in one pass before <head> is
	// printed, so every block's enqueue side effects (wp_enqueue_script(),
	// wp_enqueue_script_module(), etc.) are already registered by the time
	// wp_head() runs. This plugin's routed book/chapter/archive pages don't
	// go through that canvas at all — document-start.php builds the page by
	// hand — so calling wp_head() first and block_template_part('header')
	// second (the original order here) let wp_head() print its import map
	// for the Script Modules API (used by core/navigation's mobile overlay,
	// via its @wordpress/interactivity dependency) BEFORE the header's own
	// Navigation block had even rendered and registered that dependency,
	// leaving the import map empty. The block's view script itself still
	// printed later (in wp_footer(), by which point it *was* registered),
	// but by then the browser had already parsed an import-map-less <head>
	// and rejected the bare "@wordpress/interactivity" specifier outright —
	// confirmed live via the console error "Failed to resolve module
	// specifier" and by directly invoking the nav toggle's click handler,
	// which silently no-opped instead of opening the mobile menu. Capturing
	// the header's markup here, before wp_head(), and echoing the captured
	// string after <body> opens reproduces the same registration order a
	// normal block-theme canvas guarantees, without changing where the
	// header actually appears in the document.
	ob_start();
	block_template_part( 'header' );
	$hsrtech_header_html = ob_get_clean();
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php
	wp_body_open();
	echo $hsrtech_header_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already-rendered block markup captured above via block_template_part(), not raw user input.
else :
	get_header();
endif;
