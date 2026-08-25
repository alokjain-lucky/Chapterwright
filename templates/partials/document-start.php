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
	// page renders the ENTIRE canvas (header + content + footer) in one pass
	// before <head> is printed, so every block's enqueue side effects
	// (wp_enqueue_script(), wp_enqueue_script_module(), etc.) are already
	// registered by the time wp_head() runs. This plugin's routed
	// book/chapter/archive pages don't go through that canvas — this file
	// builds the page by hand — so the header must render first to register
	// its own dependencies (e.g. core/navigation's Script Modules import)
	// before wp_head() prints them. Capturing the header's markup here and
	// echoing it after <body> opens reproduces the same registration order a
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
