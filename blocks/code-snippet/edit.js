/**
 * Editor UI for chapterwright/code-snippet.
 *
 * Written directly against the `wp.*` globals WordPress already exposes for
 * these script handles (see edit.asset.php) — no build step needed. The
 * code field uses PlainText, the same component core's own Code block uses,
 * so pasted code keeps its literal whitespace and never gets auto-corrected
 * into rich text.
 *
 * @package Chapterwright
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var PlainText = blockEditor.PlainText;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var __ = i18n.__;

	// hsrtechCodeSnippetLanguages is localized from PHP by
	// hsrtech_localize_code_snippet_languages() (code-snippet.php), built from
	// the filterable 'hsrtech_code_snippet_languages' list. Falling back to
	// this small built-in set only matters if that localization somehow
	// didn't run (e.g. a very old cached editor script from before this
	// existed) — the dropdown still works either way.
	var LANGUAGES = ( window.hsrtechCodeSnippetLanguages && window.hsrtechCodeSnippetLanguages.length )
		? window.hsrtechCodeSnippetLanguages
		: [
			{ label: 'PHP', value: 'php' },
			{ label: 'JavaScript', value: 'js' },
			{ label: 'CSS', value: 'css' },
			{ label: 'HTML', value: 'html' },
			{ label: 'Shell', value: 'shell' },
			{ label: __( 'Plain text', 'chapterwright' ), value: 'text' }
		];

	/**
	 * Reduces a block attribute that may be a plain string, a RichTextData
	 * instance (core/paragraph and core/code's `content` attribute, depending
	 * on WordPress version), or null/undefined, down to plain text — decoding
	 * HTML entities and stripping any inline formatting tags along the way,
	 * since a code block has no use for bold/italic/link markup. `<br>` is
	 * converted to a real newline first so a paragraph written with
	 * shift+enter line breaks doesn't collapse into one line.
	 *
	 * @param {*} value Attribute value to convert.
	 * @return {string} Plain-text content.
	 */
	function toPlainText( value ) {
		var html = ( null === value || 'undefined' === typeof value )
			? ''
			: ( 'function' === typeof value.toString ? value.toString() : String( value ) );
		html = html.replace( /<br\s*\/?>/gi, '\n' );
		var tmp = document.createElement( 'div' );
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText || '';
	}

	/**
	 * Preview-only mirror of hsrtech_parse_code_snippet_line_ranges()
	 * (code-snippet.php) / parseLineRanges() (assets/js/code-highlight.js) —
	 * a third independent implementation of the same small parse, so
	 * buildPreviewElement() below can show "Highlight lines" without pulling
	 * the front-end-only code-highlight.js script into the block editor.
	 * Same trade-off already made for language/line-count elsewhere in this
	 * plugin: independent implementations of one simple parse, rather than
	 * sharing code across editor and front-end bundles that are never
	 * loaded together.
	 *
	 * @param {string} raw Raw field value, comma-separated numbers and/or ranges.
	 * @return {Object<number, boolean>} Set of matching line numbers.
	 */
	function parsePreviewLineRanges( raw ) {
		var lines = {};
		( raw || '' ).split( ',' ).forEach( function ( part ) {
			part = part.trim();
			if ( ! part ) {
				return;
			}
			var rangeMatch = part.match( /^(\d+)\s*-\s*(\d+)$/ );
			if ( rangeMatch ) {
				var start = parseInt( rangeMatch[ 1 ], 10 );
				var end = parseInt( rangeMatch[ 2 ], 10 );
				if ( start > end ) {
					var swap = start;
					start = end;
					end = swap;
				}
				end = Math.min( end, start + 2000 );
				for ( var line = start; line <= end; line++ ) {
					lines[ line ] = true;
				}
			} else if ( /^\d+$/.test( part ) ) {
				lines[ parseInt( part, 10 ) ] = true;
			}
		} );
		return lines;
	}

	/**
	 * React-element equivalent of buildTokenFragment() (assets/js/code-highlight.js)
	 * — same idea (wrap each colored token in a <span class="hsrtech-tok
	 * hsrtech-tok--TYPE">, leave 'plain' runs as bare text) but returning an
	 * array of React children instead of appending real DOM nodes, since
	 * this preview is built with wp.element.createElement() into React's
	 * own virtual tree, not real DOM the way that file's own copy is. Reuses
	 * its .hsrtech-tok--* CSS (style.css) completely unchanged — same class
	 * names, same token `type` strings, since both this and the front end
	 * tokenize with the exact same tokenize()/GRAMMARS (window.hsrtechCodeHighlight
	 * — see that file's own comment on that assignment).
	 *
	 * @param {Array<{type: string, text: string}>} tokens
	 * @param {string} keyPrefix Unique-enough React key prefix for this call
	 *                           (a line index, or 'flat' for the ungrouped case).
	 * @return {Array} React children (a mix of plain strings and <span> elements).
	 */
	function buildTokenElements( tokens, keyPrefix ) {
		return tokens.map( function ( token, index ) {
			if ( 'plain' === token.type ) {
				return token.text;
			}
			return el( 'span', {
				key: keyPrefix + '-' + index,
				className: 'hsrtech-tok hsrtech-tok--' + token.type
			}, token.text );
		} );
	}

	/**
	 * A read-only stand-in for what render.php actually outputs, shown
	 * instead of the editable PlainText field whenever the block isn't
	 * selected — which is also exactly the state the block Inserter's hover
	 * preview renders in, so this is what fixes that previously blank
	 * "No preview available" panel too (block.json's "example" entry
	 * supplies the sample attributes it's shown with). Deliberately reuses
	 * render.php's own class names (.hsrtech-code__lines, .hsrtech-code__line,
	 * etc.) unchanged rather than inventing preview-specific markup, so
	 * every existing style.css rule for numbers/highlighting/wrapping
	 * already applies with no new CSS needed.
	 *
	 * Syntax-colored via window.hsrtechCodeHighlight (assets/js/code-highlight.js,
	 * loaded as an editorScript dependency purely to expose that — see
	 * hsrtech_register_code_highlight_script(), code-snippet.php) when the
	 * block's language has a real grammar there; falls back to plain
	 * uncolored text otherwise (or if that script somehow hasn't loaded),
	 * the same graceful "still renders correctly, just without coloring"
	 * trade-off already documented for lesser-supported languages
	 * (code-snippet.php's own docblock).
	 *
	 * @param {Object} attributes Block attributes.
	 * @return {*} A React element.
	 */
	function buildPreviewElement( attributes ) {
		var code = attributes.code || '';
		var showNumbers = !! attributes.showLineNumbers;
		var startLine = attributes.startLine || 1;
		var highlightSet = parsePreviewLineRanges( attributes.highlightLines );
		var needsRows = showNumbers || Object.keys( highlightSet ).length > 0;

		var hl = window.hsrtechCodeHighlight;
		var rules = hl && hl.GRAMMARS ? hl.GRAMMARS[ attributes.language ] : null;

		if ( ! needsRows ) {
			var flatChildren = rules
				? buildTokenElements( hl.tokenize( code, rules ), 'flat' )
				: code;
			return el( 'pre', {}, el( 'code', {}, flatChildren ) );
		}

		var linesOfTokens = rules
			? hl.splitTokensIntoLines( hl.tokenize( code, rules ) )
			: null;
		var lines = code.split( '\n' );
		var lineDigits = String( startLine + lines.length - 1 ).length;

		return el(
			'div',
			{
				className: 'hsrtech-code__lines',
				style: { '--hsrtech-line-digits': lineDigits }
			},
			lines.map( function ( lineText, index ) {
				var rowClassName = 'hsrtech-code__line';
				if ( highlightSet[ index + 1 ] ) {
					rowClassName += ' hsrtech-code__line--highlighted';
				}
				var codeChildren = linesOfTokens
					? buildTokenElements( linesOfTokens[ index ] || [], String( index ) )
					: lineText;
				return el(
					'div',
					{ className: rowClassName, key: index },
					showNumbers ? el( 'span', { className: 'hsrtech-code__line-number' }, String( startLine + index ) ) : null,
					el( 'span', { className: 'hsrtech-code__line-code' }, codeChildren )
				);
			} )
		);
	}

	blocks.registerBlockType( 'chapterwright/code-snippet', {
		transforms: {
			from: [
				{
					// The core Code block has no language metadata of its own —
					// default to "Plain text" rather than guessing, the same as
					// assets/js/code-highlight.js's guessLanguage() does for
					// untagged code elsewhere in this plugin.
					type: 'block',
					blocks: [ 'core/code' ],
					transform: function ( attributes ) {
						return blocks.createBlock( 'chapterwright/code-snippet', {
							code: toPlainText( attributes.content ),
							language: 'text'
						} );
					}
				},
				{
					// Lets an author who wrote (or pasted) code directly into a
					// normal paragraph convert it after the fact, without having
					// to retype it into a fresh Code Snippet block.
					type: 'block',
					blocks: [ 'core/paragraph' ],
					transform: function ( attributes ) {
						return blocks.createBlock( 'chapterwright/code-snippet', {
							code: toPlainText( attributes.content ),
							language: 'text'
						} );
					}
				}
			]
		},
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var isSelected = props.isSelected;
			var figureClassName = 'hsrtech-code hsrtech-code--editing';
			if ( attributes.hideLanguageLabel ) {
				// See the matching .hsrtech-code--no-lang rule, style.css.
				figureClassName += ' hsrtech-code--no-lang';
			}
			if ( attributes.wrapLines ) {
				// Matches render.php so the read-only preview below (unselected,
				// or the Inserter's hover preview) wraps the same way the front
				// end will.
				figureClassName += ' hsrtech-code--wrap';
			}
			var blockProps = useBlockProps( { className: figureClassName } );

			// The editable PlainText field is best for actually writing code
			// (no risk of a click landing on a token span instead of placing
			// the cursor), but doesn't show numbers, highlighting, or wrapping
			// at all — so swap to the read-only formatted preview
			// (buildPreviewElement() above) whenever the block isn't selected,
			// which is also exactly the state the block Inserter's hover
			// preview renders in (see block.json's "example"). Clicking the
			// preview selects the block same as clicking any other block
			// content, switching straight back to the editable field.
			var showPreview = ! isSelected && !! ( attributes.code && attributes.code.trim() );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Code snippet settings', 'chapterwright' ) },
						el( SelectControl, {
							label: __( 'Language', 'chapterwright' ),
							value: attributes.language,
							options: LANGUAGES,
							onChange: function ( value ) {
								setAttributes( { language: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Caption (optional)', 'chapterwright' ),
							help: __( 'Shown above the code, e.g. a filename or a one-line description.', 'chapterwright' ),
							value: attributes.caption,
							onChange: function ( value ) {
								setAttributes( { caption: value } );
							}
						} ),
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Wrap long lines', 'chapterwright' ),
							help: __( 'Off wraps a horizontal scrollbar under long lines instead of wrapping them onto a second line.', 'chapterwright' ),
							checked: !! attributes.wrapLines,
							onChange: function ( value ) {
								setAttributes( { wrapLines: value } );
							}
						} ),
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show line numbers', 'chapterwright' ),
							checked: !! attributes.showLineNumbers,
							onChange: function ( value ) {
								setAttributes( { showLineNumbers: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Start line', 'chapterwright' ),
							help: __( 'What number the gutter shows next to the snippet\'s first line. Doesn\'t change which lines "Highlight lines" refers to.', 'chapterwright' ),
							type: 'number',
							min: 1,
							value: attributes.startLine,
							onChange: function ( value ) {
								var parsed = parseInt( value, 10 );
								setAttributes( { startLine: ( parsed && parsed > 0 ) ? parsed : 1 } );
							}
						} ),
						el( TextControl, {
							label: __( 'Highlight lines (optional)', 'chapterwright' ),
							help: __( 'e.g. 3-5, 8. Counted from the top of the snippet, regardless of "Start line".', 'chapterwright' ),
							value: attributes.highlightLines,
							onChange: function ( value ) {
								setAttributes( { highlightLines: value } );
							}
						} ),
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show language label', 'chapterwright' ),
							help: __( 'The small label in the top-left corner of the code block, e.g. "PHP".', 'chapterwright' ),
							checked: ! attributes.hideLanguageLabel,
							onChange: function ( value ) {
								setAttributes( { hideLanguageLabel: ! value } );
							}
						} ),
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show copy button', 'chapterwright' ),
							help: __( 'The button in the corner that copies this snippet. Off hides it just for this block.', 'chapterwright' ),
							checked: ! attributes.hideCopyButton,
							onChange: function ( value ) {
								setAttributes( { hideCopyButton: ! value } );
							}
						} ),
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show wrap toggle', 'chapterwright' ),
							help: __( 'Lets a reader turn "Wrap long lines" on or off for just this block. Off hides the button; the block still uses your own "Wrap long lines" setting above.', 'chapterwright' ),
							checked: ! attributes.hideWrapToggle,
							onChange: function ( value ) {
								setAttributes( { hideWrapToggle: ! value } );
							}
						} )
					)
				),
				el(
					'figure',
					blockProps,
					el( TextControl, {
						className: 'hsrtech-code__caption-input',
						label: __( 'Caption', 'chapterwright' ),
						hideLabelFromVision: true,
						value: attributes.caption,
						placeholder: __( 'Caption (optional)', 'chapterwright' ),
						onChange: function ( value ) {
							setAttributes( { caption: value } );
						}
					} ),
					el(
						'div',
						{ className: 'hsrtech-code__frame' },
						attributes.hideLanguageLabel
							? null
							: el( 'span', { className: 'hsrtech-code__lang' }, attributes.language.toUpperCase() ),
						showPreview
							? buildPreviewElement( attributes )
							: el( PlainText, {
								className: 'hsrtech-code__editor',
								value: attributes.code,
								onChange: function ( value ) {
									setAttributes( { code: value } );
								},
								placeholder: __( 'Paste or write your code…', 'chapterwright' ),
								'aria-label': __( 'Code', 'chapterwright' )
							} )
					)
				)
			);
		},
		save: function () {
			// Dynamic block — render.php always produces the front-end markup.
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
