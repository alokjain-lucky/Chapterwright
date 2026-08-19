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
add_action( 'enqueue_block_editor_assets', 'hsrtech_localize_code_snippet_languages' );

/**
 * Register the Code Snippet block from its block.json.
 */
function hsrtech_register_code_snippet_block() {
	register_block_type( __DIR__ );
}

/**
 * Pass the Language dropdown's options to edit.js as JSON, built from a
 * filterable default list rather than hardcoded in JavaScript.
 *
 * Only PHP, JavaScript, TypeScript, CSS, HTML, Shell, JSON, YAML, SQL,
 * Markdown, and Python get real syntax-highlighting grammars on the front
 * end (assets/js/code-highlight.js's GRAMMARS) — every other language here
 * still renders correctly (labeled frame, copy button, monospace block)
 * just without token coloring, the same graceful fallback "Plain text"
 * already has. That's a deliberate trade-off, not a bug: hand-writing an
 * accurate tokenizer per language is a lot of surface area for a "nice to
 * have," so the language list is intentionally broader than the set of
 * languages that get colored.
 *
 * `hsrtech_code_snippet_languages` lets a site add/remove entries (e.g. a
 * documentation site could add GraphQL or Dockerfile) without editing this
 * plugin's own JavaScript:
 *
 *     add_filter( 'hsrtech_code_snippet_languages', function ( $languages ) {
 *         $languages[] = array( 'label' => 'GraphQL', 'value' => 'graphql' );
 *         return $languages;
 *     } );
 */
function hsrtech_localize_code_snippet_languages() {
	$languages = apply_filters(
		'hsrtech_code_snippet_languages',
		array(
			array(
				'value' => 'php',
				'label' => 'PHP',
			),
			array(
				'value' => 'js',
				'label' => 'JavaScript',
			),
			array(
				'value' => 'ts',
				'label' => 'TypeScript',
			),
			array(
				'value' => 'css',
				'label' => 'CSS',
			),
			array(
				'value' => 'html',
				'label' => 'HTML',
			),
			array(
				'value' => 'shell',
				'label' => 'Shell',
			),
			array(
				'value' => 'json',
				'label' => 'JSON',
			),
			array(
				'value' => 'yaml',
				'label' => 'YAML',
			),
			array(
				'value' => 'sql',
				'label' => 'SQL',
			),
			array(
				'value' => 'markdown',
				'label' => 'Markdown',
			),
			array(
				'value' => 'python',
				'label' => 'Python',
			),
			array(
				'value' => 'ruby',
				'label' => 'Ruby',
			),
			array(
				'value' => 'go',
				'label' => 'Go',
			),
			array(
				'value' => 'java',
				'label' => 'Java',
			),
			array(
				'value' => 'c',
				'label' => 'C',
			),
			array(
				'value' => 'cpp',
				'label' => 'C++',
			),
			array(
				'value' => 'csharp',
				'label' => 'C#',
			),
			array(
				'value' => 'rust',
				'label' => 'Rust',
			),
			array(
				'value' => 'swift',
				'label' => 'Swift',
			),
			array(
				'value' => 'kotlin',
				'label' => 'Kotlin',
			),
			array(
				'value' => 'diff',
				'label' => 'Diff',
			),
			array(
				'value' => 'text',
				'label' => __( 'Plain text', 'chapterwright' ),
			),
		)
	);

	wp_localize_script( 'chapterwright-code-snippet-editor-script', 'hsrtechCodeSnippetLanguages', array_values( $languages ) );
}

/**
 * Parse a "Highlight lines" field (e.g. "3-5, 8, 12-14") into a lookup set
 * of 1-based line numbers, counted from the top of the snippet as pasted —
 * independent of the "Start line" option, which only changes what number is
 * *displayed* next to each line, not which ones this refers to. Keeping the
 * two independent means an author doesn't have to redo their highlight
 * ranges every time they adjust where the gutter starts counting from.
 *
 * Mirrored in JavaScript by parseLineRanges() (assets/js/code-highlight.js),
 * which rebuilds this same set client-side from the same raw string (see
 * the `data-hsrtech-highlight-lines` attribute render.php outputs below)
 * rather than trusting anything computed server-side, the same pattern
 * already used for line numbers and language.
 *
 * Unrecognized junk (an empty segment, non-numeric text, a reversed or
 * malformed range) is silently skipped rather than erroring — this only
 * ever affects which lines get a visual tint, never what code is shown or
 * whether the block renders at all.
 *
 * @param string $raw Raw field value, comma-separated numbers and/or ranges.
 * @return array<int,bool> Set of matching line numbers, as array keys, for
 *                          an O(1) isset() check per line.
 */
function hsrtech_parse_code_snippet_line_ranges( $raw ) {
	$lines = array();

	foreach ( explode( ',', (string) $raw ) as $part ) {
		$part = trim( $part );
		if ( '' === $part ) {
			continue;
		}

		if ( preg_match( '/^(\d+)\s*-\s*(\d+)$/', $part, $matches ) ) {
			$start = (int) $matches[1];
			$end   = (int) $matches[2];
			if ( $start > $end ) {
				list( $start, $end ) = array( $end, $start );
			}
			// Arbitrary but generous ceiling — guards against a typo like
			// "3-30000" turning into a very large loop for no visual benefit
			// beyond what any real snippet in a code block would need.
			$end = min( $end, $start + 2000 );
			for ( $line = $start; $line <= $end; $line++ ) {
				$lines[ $line ] = true;
			}
		} elseif ( ctype_digit( $part ) ) {
			$lines[ (int) $part ] = true;
		}
	}

	return $lines;
}
