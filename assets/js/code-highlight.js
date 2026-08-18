/**
 * Front-end syntax highlighting for code blocks.
 *
 * Hand-rolled and dependency-free, matching the rest of this plugin's
 * front-end JavaScript (see blocks/code-snippet/view.js) — vendoring a
 * library like Prism.js was considered but skipped, both to stay consistent
 * with that existing no-build-step, no-external-dependency approach and
 * because this is only ever asked to color six simple languages (PHP, JS,
 * CSS, HTML, Shell, JSON), which a small regex tokenizer handles well enough
 * without pulling in ~20KB+ of a general-purpose highlighter.
 *
 * Handles two cases, wherever this script is loaded — every book/chapter
 * page, and (as of the fix described below) any other post or page that
 * uses the chapterwright/code-snippet block:
 *
 * 1. `chapterwright/code-snippet` blocks (blocks/code-snippet/render.php) —
 *    these already carry an explicit `data-hsrtech-language` attribute on the
 *    <pre>, so the language is exact, not guessed. The frame (language
 *    label + copy button) is already server-rendered; this script only
 *    needs to tokenize the code inside.
 * 2. Plain core Gutenberg Code blocks, and any other bare `<pre><code>` in
 *    chapter content — these carry no language metadata at all, so the
 *    language is a best-effort guess from the code's own shape
 *    (guessLanguage() below). Wrong guesses just fall back to no coloring
 *    (equivalent to the `text` language), never wrong-but-confident colors,
 *    since an unmatched grammar simply isn't picked. These also get a
 *    language label + copy button added by this script, matching the
 *    code-snippet block's markup exactly (same `.hsrtech-code` classes) so
 *    chapterwright.css's existing frame styles apply with no duplication.
 *
 * Security note: every token is inserted via `textContent`/`createElement`,
 * never via innerHTML string concatenation, so arbitrary code content
 * (including anything that looks like HTML) can never be interpreted as
 * markup — the same guarantee `esc_html()` gives server-side, just enforced
 * by the DOM API instead.
 *
 * @package Chapterwright
 */
( function () {
	'use strict';

	/**
	 * One rule per recognizable chunk of source, tried in order — first
	 * match at the current position wins. Order matters: comments and
	 * strings must come before keyword/identifier rules, or a keyword
	 * sitting inside a string would get colored as if it were code.
	 *
	 * Each regex uses the sticky ('y') flag so `exec()` only matches
	 * starting at `lastIndex`, never scanning ahead — that's what lets
	 * tokenize() advance through the string in a single left-to-right pass
	 * without re-slicing it on every token.
	 */
	var GRAMMARS = {
		php: [
			{ type: 'comment', re: /\/\/[^\n]*|#[^\n]*|\/\*[\s\S]*?\*\//y },
			{ type: 'string', re: /"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'/y },
			{ type: 'variable', re: /\$[a-zA-Z_][a-zA-Z0-9_]*/y },
			{ type: 'number', re: /\b0x[0-9a-fA-F]+\b|\b\d+\.?\d*\b/y },
			{ type: 'keyword', re: /\b(?:abstract|and|array|as|break|callable|case|catch|class|clone|const|continue|declare|default|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|extends|final|finally|fn|for|foreach|function|global|goto|if|implements|include|include_once|instanceof|insteadof|interface|isset|list|match|namespace|new|or|print|private|protected|public|readonly|require|require_once|return|static|switch|throw|trait|try|unset|use|var|while|xor|yield|true|false|null)\b/iy },
			{ type: 'function', re: /\b[a-zA-Z_][a-zA-Z0-9_]*(?=\s*\()/y },
			{ type: 'punctuation', re: /[{}()\[\];,.]/y },
			{ type: 'operator', re: /[+\-*/%=<>!&|^~?:]+/y }
		],
		js: [
			{ type: 'comment', re: /\/\/[^\n]*|\/\*[\s\S]*?\*\//y },
			{ type: 'string', re: /`(?:\\.|[^`\\])*`|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'/y },
			{ type: 'number', re: /\b0x[0-9a-fA-F]+\b|\b\d+\.?\d*\b/y },
			{ type: 'keyword', re: /\b(?:async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|export|extends|finally|for|from|function|get|if|import|in|instanceof|let|new|of|return|set|static|super|switch|this|throw|try|typeof|var|void|while|yield|true|false|null|undefined)\b/y },
			{ type: 'function', re: /\b[a-zA-Z_$][a-zA-Z0-9_$]*(?=\s*\()/y },
			{ type: 'variable', re: /\b[a-zA-Z_$][a-zA-Z0-9_$]*\b/y },
			{ type: 'punctuation', re: /[{}()\[\];,.]/y },
			{ type: 'operator', re: /=>|[+\-*/%=<>!&|^~?:]+/y }
		],
		css: [
			{ type: 'comment', re: /\/\*[\s\S]*?\*\//y },
			{ type: 'string', re: /"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'/y },
			{ type: 'variable', re: /--[a-zA-Z0-9-]+|\$[a-zA-Z0-9-]+/y },
			{ type: 'attr-value', re: /#[0-9a-fA-F]{3,8}\b/y },
			{ type: 'keyword', re: /@[a-zA-Z-]+/y },
			{ type: 'number', re: /\b\d+\.?\d*(?:px|em|rem|%|vh|vw|vmin|vmax|s|ms|deg|fr)?\b/y },
			{ type: 'property', re: /[a-zA-Z-]+(?=\s*:)/y },
			{ type: 'function', re: /\b[a-zA-Z-]+(?=\s*\()/y },
			{ type: 'punctuation', re: /[{}():;,]/y }
		],
		html: [
			{ type: 'comment', re: /<!--[\s\S]*?-->/y },
			{ type: 'tag', re: /<\/?[a-zA-Z][a-zA-Z0-9-]*/y },
			{ type: 'attr-value', re: /"(?:[^"])*"|'(?:[^'])*'/y },
			{ type: 'attr-name', re: /[a-zA-Z-:]+(?=\s*=)/y },
			{ type: 'punctuation', re: /\/?>|[<=]/y }
		],
		shell: [
			{ type: 'comment', re: /#[^\n]*/y },
			{ type: 'string', re: /"(?:\\.|[^"\\])*"|'(?:[^'])*'/y },
			{ type: 'variable', re: /\$\{[^}]*\}|\$[a-zA-Z_][a-zA-Z0-9_]*|\$\d+|\$[@#?*]/y },
			{ type: 'keyword', re: /\b(?:if|then|else|elif|fi|for|while|until|do|done|case|esac|function|return|export|local|in|select|break|continue)\b/y },
			{ type: 'attr-name', re: /(?:^|\s)--?[a-zA-Z][a-zA-Z0-9-]*/y },
			{ type: 'punctuation', re: /[|&;()<>]/y }
		],
		json: [
			// A quoted string immediately followed by ':' is an object key,
			// not a value — colored as 'property' instead of 'string' the
			// same way a real JSON viewer distinguishes them. Tried before
			// the general string rule below, since that would otherwise
			// match first and every key would come out colored as a string.
			{ type: 'property', re: /"(?:\\.|[^"\\])*"(?=\s*:)/y },
			{ type: 'string', re: /"(?:\\.|[^"\\])*"/y },
			{ type: 'number', re: /-?\b\d+\.?\d*(?:[eE][+-]?\d+)?\b/y },
			{ type: 'keyword', re: /\b(?:true|false|null)\b/y },
			{ type: 'punctuation', re: /[{}\[\]:,]/y }
		]
	};

	var LANGUAGE_LABELS = {
		php: 'PHP',
		js: 'JS',
		css: 'CSS',
		html: 'HTML',
		shell: 'SHELL',
		json: 'JSON',
		text: 'TEXT'
	};

	/**
	 * Split `code` into an ordered list of `{ type, text }` tokens using
	 * `rules`. Anything not matched by any rule (whitespace, identifiers a
	 * language's rules don't specifically call out, stray punctuation, etc.)
	 * is merged into adjacent `type: 'plain'` runs rather than becoming one
	 * token per character.
	 *
	 * @param {string} code  Source text to tokenize.
	 * @param {Array}  rules Ordered rule list from GRAMMARS.
	 * @return {Array<{type: string, text: string}>}
	 */
	function tokenize( code, rules ) {
		var tokens = [];
		var pos = 0;
		var len = code.length;

		while ( pos < len ) {
			var matchedType = null;
			var matchedText = '';

			for ( var i = 0; i < rules.length; i++ ) {
				var rule = rules[ i ];
				rule.re.lastIndex = pos;
				var m = rule.re.exec( code );
				if ( m && m.index === pos && m[ 0 ].length > 0 ) {
					matchedType = rule.type;
					matchedText = m[ 0 ];
					break;
				}
			}

			if ( matchedType ) {
				tokens.push( { type: matchedType, text: matchedText } );
				pos += matchedText.length;
			} else {
				var last = tokens[ tokens.length - 1 ];
				if ( last && 'plain' === last.type ) {
					last.text += code.charAt( pos );
				} else {
					tokens.push( { type: 'plain', text: code.charAt( pos ) } );
				}
				pos += 1;
			}
		}

		return tokens;
	}

	/**
	 * Replace a <code> element's contents with the same text, wrapped
	 * token-by-token in colored <span>s. No-op (leaves the element as plain
	 * text) if `lang` isn't a recognized grammar.
	 *
	 * @param {HTMLElement} codeEl
	 * @param {string}      lang
	 */
	function highlightElement( codeEl, lang ) {
		var rules = GRAMMARS[ lang ];
		if ( ! rules ) {
			return;
		}

		var tokens = tokenize( codeEl.textContent, rules );
		var frag = document.createDocumentFragment();

		tokens.forEach( function ( token ) {
			if ( 'plain' === token.type ) {
				frag.appendChild( document.createTextNode( token.text ) );
				return;
			}
			var span = document.createElement( 'span' );
			span.className = 'hsrtech-tok hsrtech-tok--' + token.type;
			span.textContent = token.text;
			frag.appendChild( span );
		} );

		codeEl.textContent = '';
		codeEl.appendChild( frag );
	}

	/**
	 * Best-effort language guess for a code block with no language metadata
	 * at all (a plain core Gutenberg Code block). Deliberately conservative:
	 * each check looks for a fairly distinctive marker of that language, in
	 * an order that puts the most specific/unambiguous checks first, and
	 * falls back to 'text' (no coloring) rather than guessing wrong with
	 * false confidence.
	 *
	 * @param {string} text Raw code text.
	 * @return {string} One of the GRAMMARS keys, or 'text'.
	 */
	function guessLanguage( text ) {
		var trimmed = text.trim();

		if (
			/^<\?php/i.test( trimmed ) ||
			/\$this->/.test( text ) ||
			( /\bfunction\s+\w+\s*\(/.test( text ) && /\$[a-zA-Z_]/.test( text ) ) ||
			// A dollar-sigil variable being *assigned to* ($foo = ..., not just
			// read) is a fairly unique PHP marker — shell only prefixes `$` when
			// reading a variable, never on the left side of an assignment (shell
			// writes NAME=value, not $NAME=value), so this doesn't false-positive
			// on shell scripts the way a bare `\$\w+` check would.
			( /\$[a-zA-Z_][a-zA-Z0-9_]*\s*=[^=]/.test( text ) && /;/.test( text ) )
		) {
			return 'php';
		}
		if (
			/^#!.*\b(?:bash|sh|zsh)\b/.test( trimmed ) ||
			/^\$\s+\S/.test( trimmed ) ||
			/\b(?:npm|composer|wp|git|cd|sudo|curl|chmod|echo|export|mkdir)\s+\S/.test( trimmed ) ||
			// NAME=value with no `$` on the left is how shell assigns a variable
			// (PHP's equivalent always has the sigil on the left: $name = value).
			/^[A-Za-z_][A-Za-z0-9_]*=\S/m.test( text )
		) {
			return 'shell';
		}
		if ( /^</.test( trimmed ) && /<\/?[a-zA-Z][\w-]*[^>]*>/.test( trimmed ) ) {
			return 'html';
		}
		if ( /^[^;{}]{1,80}\{/.test( trimmed ) && /[a-zA-Z-]+\s*:\s*[^;{}]+;/.test( text ) ) {
			return 'css';
		}
		if ( /\b(?:const|let|var|function|=>|import\s|export\s)\b/.test( text ) ) {
			return 'js';
		}
		return 'text';
	}

	/**
	 * Wrap a bare <pre> (no `.hsrtech-code` ancestor — i.e. not already the
	 * code-snippet block's own markup) in the same figure/frame structure
	 * render.php produces, so it picks up chapterwright.css's `.hsrtech-code`
	 * frame styles and blocks/code-snippet/view.js's delegated copy-button
	 * click handler (which just looks for the nearest `.hsrtech-code` ancestor,
	 * so it works on these synthesized wrappers without any changes).
	 *
	 * @param {HTMLElement} pre
	 * @param {string}      lang
	 */
	function wrapPlainBlock( pre, lang ) {
		var labels = window.hsrtechCode || {};
		var copyLabel = labels.copyLabel || 'Copy code';
		var copiedLabel = labels.copiedLabel || 'Copied!';

		var figure = document.createElement( 'figure' );
		figure.className = 'hsrtech-code hsrtech-code--auto';

		var frame = document.createElement( 'div' );
		frame.className = 'hsrtech-code__frame';

		var langLabel = document.createElement( 'span' );
		langLabel.className = 'hsrtech-code__lang';
		langLabel.setAttribute( 'aria-hidden', 'true' );
		langLabel.textContent = LANGUAGE_LABELS[ lang ] || LANGUAGE_LABELS.text;

		// Icon-only button, matching blocks/code-snippet/render.php's markup
		// exactly (same classes, same two-SVG swap driven by .is-copied) so a
		// synthesized plain-code-block button is indistinguishable from the
		// custom block's own. The accessible name lives in aria-label, not
		// visible text — see view.js's click handler, which both buttons share.
		var copyButton = document.createElement( 'button' );
		copyButton.type = 'button';
		copyButton.className = 'hsrtech-code__copy';
		copyButton.setAttribute( 'data-hsrtech-copy-label', copyLabel );
		copyButton.setAttribute( 'data-hsrtech-copied-label', copiedLabel );
		copyButton.setAttribute( 'aria-label', copyLabel );
		copyButton.innerHTML =
			'<svg class="hsrtech-code__copy-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"></rect><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"></path></svg>' +
			'<svg class="hsrtech-code__copied-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';

		pre.parentNode.insertBefore( figure, pre );
		frame.appendChild( langLabel );
		frame.appendChild( copyButton );
		frame.appendChild( pre );
		figure.appendChild( frame );
	}

	/**
	 * @param {HTMLElement} pre
	 */
	function processCodeBlock( pre ) {
		var code = pre.querySelector( 'code' );
		if ( ! code ) {
			return;
		}

		var isCodeSnippetBlock = !! pre.closest( '.hsrtech-code' );
		var lang = isCodeSnippetBlock
			? ( pre.getAttribute( 'data-hsrtech-language' ) || 'text' )
			: guessLanguage( code.textContent );

		if ( ! isCodeSnippetBlock ) {
			pre.setAttribute( 'data-hsrtech-language', lang );
			wrapPlainBlock( pre, lang );
		}

		highlightElement( code, lang );
	}

	// The third clause is what makes a chapterwright/code-snippet block work
	// outside a book/chapter page at all — see hsrtech_enqueue_public_assets()
	// (public/assets.php) for the enqueue-side half of this fix. It's scoped
	// to .hsrtech-code specifically (the block's own wrapper, render.php)
	// rather than every <pre> on the page, so an unrelated code block placed
	// somewhere else on the same page (a sidebar widget, for instance) isn't
	// swept up along with it. :not(.hsrtech-code__line-numbers) excludes the
	// line-numbers gutter <pre> render.php adds when that option is on —
	// it's plain digits, not code, and re-tokenizing it would both do
	// nothing useful and fight with its own dedicated muted styling.
	document.querySelectorAll( '.hsrtech-chapter__content pre, .hsrtech-book-intro pre, .hsrtech-code pre:not(.hsrtech-code__line-numbers)' ).forEach( processCodeBlock );
} )();
