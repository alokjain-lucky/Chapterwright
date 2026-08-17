<?php
/**
 * Reading-time estimation.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estimate focused reading time from a post's visible word count.
 *
 * Uses 220 words per minute, a comfortable rate for technical material, and
 * always returns at least one minute.
 *
 * @param int $post_id Post ID.
 * @return int Estimated whole minutes.
 */
function hsrtech_reading_time( $post_id ) {
	$content = wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $post_id ) ) );
	$words   = str_word_count( $content );

	return max( 1, (int) ceil( $words / 220 ) );
}
