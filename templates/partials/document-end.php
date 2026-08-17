<?php
/**
 * Close the theme document for classic and block themes.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_is_block_theme() ) :
	block_template_part( 'footer' );
	wp_footer();
	?>
	</body>
	</html>
	<?php
else :
	get_footer();
endif;
