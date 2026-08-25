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
add_filter( 'allowed_block_types_all', 'hsrtech_maybe_remove_code_snippet_block_from_inserter' );

/**
 * Register the Code Snippet block from its block.json.
 */
function hsrtech_register_code_snippet_block() {
	// Must be registered before register_block_type() below, so
	// blocks/code-snippet/edit.asset.php's dependency on this handle
	// resolves to something real — see hsrtech_register_code_highlight_script()
	// for why the editor needs this script at all.
	hsrtech_register_code_highlight_script();
	register_block_type( __DIR__ );
}

/**
 * Remove the Code Snippet block from the inserter when a site owner has
 * turned that on in Settings (hsrtech_code_snippet_block_disabled(),
 * admin/settings.php), without unregistering the block type itself.
 *
 * Deliberately not done by skipping register_block_type() in
 * hsrtech_register_code_snippet_block() above: this block is dynamic
 * (rendered entirely by render.php, with no save() markup of its own), so a
 * block instance already sitting in a book, chapter, or any other post's
 * content depends on the block type staying registered to render at all —
 * unregistering it would make every existing Code Snippet block on the site
 * quietly render as nothing. Filtering it out of the inserter instead only
 * stops it from being offered for *new* content; anything already published
 * keeps working exactly as it does today.
 *
 * Only accepts the first of the two arguments this filter normally passes
 * (the second is the current block editor context, e.g. post editor vs.
 * widgets screen) — the setting applies everywhere, not per-context, so
 * there's nothing to do with it. WordPress only passes as many arguments as
 * `add_filter()`'s registration declares (default: one), so simply omitting
 * it here is enough; nothing needs an explicit ignore for an unused
 * parameter that was never declared.
 *
 * @param bool|array<int,string> $allowed_block_types True (every registered
 *                                block is allowed, the default) or an array
 *                                of allowed block names, if another plugin
 *                                has already narrowed it.
 * @return bool|array<int,string>
 */
function hsrtech_maybe_remove_code_snippet_block_from_inserter( $allowed_block_types ) {
	if ( ! hsrtech_code_snippet_block_disabled() ) {
		return $allowed_block_types;
	}

	if ( true === $allowed_block_types ) {
		$allowed_block_types = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
	}

	if ( ! is_array( $allowed_block_types ) ) {
		return $allowed_block_types;
	}

	return array_values( array_diff( $allowed_block_types, array( 'chapterwright/code-snippet' ) ) );
}

/**
 * Register (but don't necessarily enqueue) the front-end code-highlighting
 * script under one shared handle, so both its actual conditional front-end
 * enqueuing (hsrtech_enqueue_public_assets(), public/assets.php) and its use
 * here as an editorScript dependency (edit.asset.php) point at the same
 * single registration — one URL/version to keep in sync, not two.
 *
 * The editor needs this script loaded (not just as a nice-to-have) so the
 * block's own read-only preview (edit.js's buildPreviewElement(), shown
 * whenever the block isn't selected — including the Inserter's hover
 * preview) can show real syntax coloring by calling the exact same
 * tokenize()/GRAMMARS this file's docblock already promises the front end
 * uses, via window.hsrtechCodeHighlight (see that file's own comment on
 * this), rather than a third hand-maintained copy of every language's
 * rules. That file guards its own auto-processing behavior (which walks
 * the page's real DOM) so it never runs inside wp-admin, only reusing its
 * plain functions there — see the check at the very bottom of that file.
 */
function hsrtech_register_code_highlight_script() {
	wp_register_script(
		'chapterwright-code-highlight',
		HSRTECH_URL . 'assets/js/code-highlight.js',
		array(),
		HSRTECH_VERSION,
		true
	);
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
 * of line numbers — matching whatever number is actually *displayed* in the
 * gutter for that row, i.e. already offset by "Start line", not counted
 * from the top of the snippet as pasted.
 *
 * Mirrored in JavaScript by parseLineRanges() (assets/js/code-highlight.js),
 * which rebuilds this same set client-side from the same raw string (see
 * the `data-hsrtech-highlight-lines` attribute render.php outputs below).
 *
 * Unrecognized junk (an empty segment, non-numeric text, a reversed or
 * malformed range) is silently skipped rather than erroring — this only
 * affects which lines get a visual tint, never what code is shown.
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
