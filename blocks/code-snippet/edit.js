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
			var blockProps = useBlockProps( { className: 'hsrtech-code hsrtech-code--editing' } );

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
						el( 'span', { className: 'hsrtech-code__lang' }, attributes.language.toUpperCase() ),
						el( PlainText, {
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
