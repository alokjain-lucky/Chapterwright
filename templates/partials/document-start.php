<?php
/**
 * Open the theme document for classic and block themes.
 *
 * Block themes do not provide header.php. Calling get_header() under those
 * themes produces a WordPress deprecation notice, so the plugin opens the
 * document and renders the registered Header template part directly.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_is_block_theme() ) :
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
	block_template_part( 'header' );
else :
	get_header();
endif;
