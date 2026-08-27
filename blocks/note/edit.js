/**
 * Editor UI for chapterwright/note.
 *
 * Written directly against the `wp.*` globals WordPress already exposes for
 * this script handle (see edit.asset.php) — no build step needed, matching
 * the rest of this plugin's dependency-free JavaScript.
 *
 * A static block, not a dynamic one: unlike chapterwright/code-snippet, there
 * is no server-side data to fold in at render time, so save() below produces
 * the actual front-end markup directly (mirroring edit()'s own structure)
 * rather than deferring to a render.php callback.
 *
 * @package Chapterwright
 */
( function ( blocks, element, blockEditor, i18n ) {
	'use strict';

	var el = element.createElement;
	var RichText = blockEditor.RichText;
	var InnerBlocks = blockEditor.InnerBlocks;
	var useBlockProps = blockEditor.useBlockProps;
	var useInnerBlocksProps = blockEditor.useInnerBlocksProps;
	var __ = i18n.__;

	// Only paragraphs are allowed inside, per this block's one job: a label
	// plus one or more paragraphs of note/warning/tip text. Restricting the
	// content keeps the box's typography and spacing predictable — a heading
	// or an image dropped in here would fight the "small highlighted aside"
	// look this block is for.
	var ALLOWED_BLOCKS = [ 'core/paragraph' ];
	var TEMPLATE = [ [ 'core/paragraph', { placeholder: __( 'Add your note…', 'chapterwright' ) } ] ];

	blocks.registerBlockType( 'chapterwright/note', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var blockProps = useBlockProps( { className: 'hsrtech-note' } );
			var innerBlocksProps = useInnerBlocksProps(
				{ className: 'hsrtech-note__content' },
				{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE, templateLock: false }
			);

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'hsrtech-note__label-row' },
					el( RichText, {
						tagName: 'span',
						className: 'hsrtech-note__label',
						value: attributes.label,
						onChange: function ( value ) {
							setAttributes( { label: value } );
						},
						placeholder: __( 'Note', 'chapterwright' ),
						allowedFormats: [],
						withoutInteractiveFormatting: true
					} )
				),
				el( 'div', innerBlocksProps )
			);
		},
		save: function ( props ) {
			var attributes = props.attributes;
			var blockProps = useBlockProps.save( { className: 'hsrtech-note' } );

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'hsrtech-note__label-row' },
					el( RichText.Content, {
						tagName: 'span',
						className: 'hsrtech-note__label',
						value: attributes.label
					} )
				),
				el(
					'div',
					{ className: 'hsrtech-note__content' },
					el( InnerBlocks.Content )
				)
			);
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.i18n );
