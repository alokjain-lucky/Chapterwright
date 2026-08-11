/**
 * Editor UI for make-a-book/code-snippet.
 *
 * Written directly against the `wp.*` globals WordPress already exposes for
 * these script handles (see edit.asset.php) — no build step needed. The
 * code field uses PlainText, the same component core's own Code block uses,
 * so pasted code keeps its literal whitespace and never gets auto-corrected
 * into rich text.
 *
 * @package Make_A_Book
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

	var LANGUAGES = [
		{ label: 'PHP', value: 'php' },
		{ label: 'JavaScript', value: 'js' },
		{ label: 'CSS', value: 'css' },
		{ label: 'HTML', value: 'html' },
		{ label: 'Shell', value: 'shell' },
		{ label: __( 'Plain text', 'make-a-book' ), value: 'text' }
	];

	blocks.registerBlockType( 'make-a-book/code-snippet', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( { className: 'mab-code mab-code--editing' } );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Code snippet settings', 'make-a-book' ) },
						el( SelectControl, {
							label: __( 'Language', 'make-a-book' ),
							value: attributes.language,
							options: LANGUAGES,
							onChange: function ( value ) {
								setAttributes( { language: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Caption (optional)', 'make-a-book' ),
							help: __( 'Shown above the code, e.g. a filename or a one-line description.', 'make-a-book' ),
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
						className: 'mab-code__caption-input',
						label: __( 'Caption', 'make-a-book' ),
						hideLabelFromVision: true,
						value: attributes.caption,
						placeholder: __( 'Caption (optional)', 'make-a-book' ),
						onChange: function ( value ) {
							setAttributes( { caption: value } );
						}
					} ),
					el(
						'div',
						{ className: 'mab-code__frame' },
						el( 'span', { className: 'mab-code__lang' }, attributes.language.toUpperCase() ),
						el( PlainText, {
							className: 'mab-code__editor',
							value: attributes.code,
							onChange: function ( value ) {
								setAttributes( { code: value } );
							},
							placeholder: __( 'Paste or write your code…', 'make-a-book' ),
							'aria-label': __( 'Code', 'make-a-book' )
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
