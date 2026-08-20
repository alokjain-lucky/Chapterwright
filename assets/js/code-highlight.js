/**
 * Front-end syntax highlighting for code blocks.
 *
 * Hand-rolled and dependency-free, matching the rest of this plugin's
 * front-end JavaScript (see blocks/code-snippet/view.js) — vendoring a
 * library like Prism.js was considered but skipped, both to stay consistent
 * with that existing no-build-step, no-external-dependency approach and
 * because this only needs to color a modest set of languages (PHP, JS, TS,
 * CSS, HTML, Shell, JSON, YAML, SQL, Markdown, Python), which a small regex
 * tokenizer handles well enough without pulling in ~20KB+ of a
 * general-purpose highlighter. The Language dropdown (code-snippet.php)
 * offers more languages than that — anything not in GRAMMARS below still
 * renders correctly, just without token coloring (see that file's docblock).
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
		],
		ts: [
			{ type: 'comment', re: /\/\/[^\n]*|\/\*[\s\S]*?\*\//y },
			{ type: 'string', re: /`(?:\\.|[^`\\])*`|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'/y },
			{ type: 'number', re: /\b0x[0-9a-fA-F]+\b|\b\d+\.?\d*\b/y },
			// A superset of js's keyword list — adds TypeScript's own type-level
			// keywords (interface, type, keyof, satisfies, etc.) alongside the
			// plain-JS ones, since every .ts file is also valid-ish JS syntax.
			{ type: 'keyword', re: /\b(?:abstract|any|as|asserts|async|await|boolean|break|case|catch|class|const|continue|debugger|declare|default|delete|do|else|enum|export|extends|finally|for|from|function|get|if|implements|import|in|infer|instanceof|interface|is|keyof|let|namespace|never|new|null|number|object|of|private|protected|public|readonly|return|satisfies|set|static|string|super|switch|this|throw|true|false|try|type|typeof|undefined|unknown|var|void|while|yield)\b/y },
			{ type: 'function', re: /\b[a-zA-Z_$][a-zA-Z0-9_$]*(?=\s*\()/y },
			{ type: 'variable', re: /\b[a-zA-Z_$][a-zA-Z0-9_$]*\b/y },
			{ type: 'punctuation', re: /[{}()\[\];,.]/y },
			{ type: 'operator', re: /=>|[+\-*/%=<>!&|^~?:]+/y }
		],
		python: [
			{ type: 'comment', re: /#[^\n]*/y },
			{ type: 'string', re: /("""[\s\S]*?"""|'''[\s\S]*?'''|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')/y },
			{ type: 'attr-name', re: /@[a-zA-Z_][a-zA-Z0-9_.]*/y },
			{ type: 'number', re: /\b0x[0-9a-fA-F]+\b|\b\d+\.?\d*\b/y },
			{ type: 'keyword', re: /\b(?:and|as|assert|async|await|break|class|continue|def|del|elif|else|except|finally|for|from|global|if|import|in|is|lambda|nonlocal|not|or|pass|raise|return|self|try|while|with|yield|True|False|None)\b/y },
			{ type: 'function', re: /\b[a-zA-Z_][a-zA-Z0-9_]*(?=\s*\()/y },
			{ type: 'punctuation', re: /[{}()\[\]:,.]/y },
			{ type: 'operator', re: /[+\-*/%=<>!&|^~]+/y }
		],
		yaml: [
			{ type: 'comment', re: /#[^\n]*/y },
			{ type: 'string', re: /"(?:\\.|[^"\\])*"|'(?:[^'])*'/y },
			// A key is whatever leads a line up to its ':' — only matches at a
			// line start (the 'm' flag makes '^' match right after each '\n'
			// too, not just at the very start of the whole string), so a colon
			// appearing later in a value doesn't get mistaken for one.
			{ type: 'property', re: /^[ \t]*[a-zA-Z0-9_.-]+(?=\s*:)/my },
			{ type: 'keyword', re: /\b(?:true|false|null|yes|no|on|off|True|False|Null|Yes|No|On|Off|~)\b/y },
			{ type: 'number', re: /-?\b\d+\.?\d*\b/y },
			{ type: 'punctuation', re: /^[ \t]*-(?=\s)|[:\[\]{},]/my }
		],
		sql: [
			{ type: 'comment', re: /--[^\n]*|\/\*[\s\S]*?\*\//y },
			{ type: 'string', re: /'(?:[^'])*'/y },
			{ type: 'number', re: /\b\d+\.?\d*\b/y },
			{ type: 'keyword', re: /\b(?:SELECT|FROM|WHERE|INSERT|INTO|VALUES|UPDATE|SET|DELETE|JOIN|LEFT|RIGHT|INNER|OUTER|FULL|ON|GROUP|BY|ORDER|HAVING|LIMIT|OFFSET|AND|OR|NOT|NULL|IS|IN|LIKE|AS|DISTINCT|CREATE|TABLE|ALTER|DROP|PRIMARY|KEY|FOREIGN|REFERENCES|INDEX|UNIQUE|DEFAULT|CASE|WHEN|THEN|ELSE|END|UNION|ALL|EXISTS|BETWEEN|ASC|DESC|CASCADE|CONSTRAINT|CHECK|VIEW|WITH)\b/iy },
			{ type: 'function', re: /\b[a-zA-Z_][a-zA-Z0-9_]*(?=\s*\()/y },
			{ type: 'punctuation', re: /[(),;.]/y },
			{ type: 'operator', re: /[+\-*/%=<>!]+/y }
		],
		markdown: [
			{ type: 'comment', re: /<!--[\s\S]*?-->/y },
			{ type: 'string', re: /`[^`\n]+`/y },
			// A line's leading #'s (a heading) and leading list marker are only
			// recognized at a line start, same 'm'-plus-sticky trick as yaml's
			// property rule above — a stray '#' or '-' mid-sentence stays plain.
			{ type: 'keyword', re: /^#{1,6}[^\n]*/my },
			{ type: 'function', re: /!?\[[^\]\n]*\]\([^)\n]*\)/y },
			{ type: 'variable', re: /\*\*[^*\n]+\*\*|__[^_\n]+__/y },
			{ type: 'attr-value', re: /\*[^*\n]+\*|_[^_\n]+_/y },
			{ type: 'punctuation', re: /^[ \t]*(?:[-*+]|\d+\.)(?=\s)|^[ \t]*>/my }
		]
	};

	var LANGUAGE_LABELS = {
		php: 'PHP',
		js: 'JS',
		ts: 'TS',
		css: 'CSS',
		html: 'HTML',
		shell: 'SHELL',
		json: 'JSON',
		yaml: 'YAML',
		sql: 'SQL',
		markdown: 'MD',
		python: 'PYTHON',
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
	 * Build a fragment of text nodes and colored <span>s for one ordered list
	 * of tokens — the actual DOM-building step shared by highlightElement()
	 * (the whole code block, one flat run) and buildNumberedLines() below
	 * (one call per source line).
	 *
	 * @param {Array<{type: string, text: string}>} tokens
	 * @return {DocumentFragment}
	 */
	function buildTokenFragment( tokens ) {
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
		return frag;
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
		codeEl.textContent = '';
		codeEl.appendChild( buildTokenFragment( tokens ) );
	}

	/**
	 * Regroup a flat token list (as tokenize() produces from the whole code
	 * string at once) into one array per source line, splitting any token
	 * whose own text contains a newline — a multi-line /* *\/ comment, or a
	 * literal newline inside a quoted string — at each "\n" it contains, so
	 * every piece keeps the original token's type/color. Tokenizing is still
	 * done once over the whole string first specifically so a multi-line
	 * comment or string is still recognized correctly in the first place;
	 * this only changes how the *result* is grouped into rows afterward.
	 *
	 * @param {Array<{type: string, text: string}>} tokens
	 * @return {Array<Array<{type: string, text: string}>>} One array per line.
	 */
	function splitTokensIntoLines( tokens ) {
		var lines = [ [] ];
		tokens.forEach( function ( token ) {
			var parts = token.text.split( '\n' );
			parts.forEach( function ( part, index ) {
				if ( index > 0 ) {
					lines.push( [] );
				}
				if ( part.length > 0 ) {
					lines[ lines.length - 1 ].push( { type: token.type, text: part } );
				}
			} );
		} );
		return lines;
	}

	// Exposed so the block editor's own read-only preview (edit.js's
	// buildPreviewElement(), loaded as an editorScript dependency declared
	// in blocks/code-snippet/edit.asset.php — see
	// hsrtech_register_code_highlight_script(), code-snippet.php) can reuse
	// the exact same tokenizer and language rules the front end colors code
	// with, instead of a third hand-maintained copy of every language's
	// regexes. Deliberately narrow — only the pure, DOM-independent pieces
	// are exposed, not anything that touches the real DOM (buildTokenFragment,
	// highlightElement, wrapPlainBlock, etc.), since the editor's own preview
	// is built as React elements (wp.element.createElement), not real DOM
	// nodes — mixing the two would fight React for control of the same
	// elements, which is also why this file's own DOM-walking auto-run at
	// the very bottom is skipped entirely in wp-admin (see that check).
	window.hsrtechCodeHighlight = {
		tokenize: tokenize,
		GRAMMARS: GRAMMARS,
		splitTokensIntoLines: splitTokensIntoLines
	};

	/**
	 * Parse a "Highlight lines" field (e.g. "3-5, 8, 12-14") into a lookup
	 * set of line numbers — matching whatever number is actually displayed
	 * in the gutter (already offset by "Start line"), not counted from the
	 * top of the snippet as pasted — the exact JavaScript mirror of
	 * hsrtech_parse_code_snippet_line_ranges()
	 * (blocks/code-snippet/code-snippet.php), which produces the
	 * `data-hsrtech-highlight-lines` attribute this reads. Kept as two
	 * independent implementations of the same simple parse, the same way
	 * language/line-count/etc. are already re-derived client-side rather
	 * than trusting anything computed server-side — see buildLineRows()
	 * below and processLineRows() further down.
	 *
	 * @param {string} raw Raw field value, comma-separated numbers and/or ranges.
	 * @return {Object<number, boolean>} Set of matching line numbers, as
	 *                                   object keys, for an O(1) lookup per line.
	 */
	function parseLineRanges( raw ) {
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
	 * Build the row-based version of a code block — one row per source line,
	 * a number (when `options.showNumbers`) and that line's (possibly
	 * colored) code, side by side — instead of the flat <pre><code> render.php
	 * falls back to for the no-JS case, or the row markup it renders directly
	 * when JavaScript never runs at all. A wrapped line's continuation
	 * simply has no number of its own (it's still part of the same row),
	 * rather than every number after it silently drifting by however many
	 * extra rows the wrap added — the reason this exists as rows rather than
	 * two independent side-by-side columns in the first place.
	 *
	 * @param {string} codeText Raw, unescaped code text.
	 * @param {string} lang     Language key into GRAMMARS, or an unrecognized
	 *                          one — falls back to uncolored plain text.
	 * @param {Object} options
	 * @param {boolean}             [options.showNumbers]  Render a number per row.
	 * @param {number}              [options.startLine]    Number displayed for row 1.
	 * @param {Object<number, boolean>} [options.highlightSet] Lines (the
	 *        number actually displayed in the gutter, i.e. already offset by
	 *        startLine — see parseLineRanges() above and the matching
	 *        PHP-side note) that get the "highlighted" modifier.
	 * @return {DocumentFragment} One row per source line, ready to replace a
	 *                            .hsrtech-code__lines container's children.
	 */
	function buildLineRows( codeText, lang, options ) {
		options = options || {};
		var showNumbers = !! options.showNumbers;
		var startLine = options.startLine || 1;
		var highlightSet = options.highlightSet || {};

		var rules = GRAMMARS[ lang ];
		var tokens = rules ? tokenize( codeText, rules ) : [ { type: 'plain', text: codeText } ];
		var lines = splitTokensIntoLines( tokens );

		var frag = document.createDocumentFragment();

		lines.forEach( function ( lineTokens, index ) {
			var row = document.createElement( 'div' );
			row.className = 'hsrtech-code__line';
			if ( highlightSet[ startLine + index ] ) {
				row.className += ' hsrtech-code__line--highlighted';
			}

			if ( showNumbers ) {
				var number = document.createElement( 'span' );
				number.className = 'hsrtech-code__line-number';
				number.setAttribute( 'aria-hidden', 'true' );
				number.textContent = String( startLine + index );
				row.appendChild( number );
			}

			var codeSpan = document.createElement( 'span' );
			codeSpan.className = 'hsrtech-code__line-code';
			codeSpan.appendChild( buildTokenFragment( lineTokens ) );
			row.appendChild( codeSpan );

			frag.appendChild( row );
		} );

		return frag;
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
		// visible text — see view.js's click handler, which both buttons
		// share, and which also keeps data-tooltip (the CSS hover tooltip,
		// [data-tooltip]::after in style.css — not the title attribute,
		// which Safari doesn't reliably show on <button> elements at all)
		// in sync with aria-label as the copy state changes.
		var copyButton = document.createElement( 'button' );
		copyButton.type = 'button';
		copyButton.className = 'hsrtech-code__copy';
		copyButton.setAttribute( 'data-hsrtech-copy-label', copyLabel );
		copyButton.setAttribute( 'data-hsrtech-copied-label', copiedLabel );
		copyButton.setAttribute( 'aria-label', copyLabel );
		copyButton.setAttribute( 'data-tooltip', copyLabel );
		copyButton.innerHTML =
			'<svg class="hsrtech-code__copy-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"></rect><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"></path></svg>' +
			'<svg class="hsrtech-code__copied-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';

		// .hsrtech-code__actions (style.css) is what actually positions the
		// button in the frame's top-right corner — required even for this
		// lone copy button (no wrap toggle ever joins it here, a deliberate
		// scope decision noted on this function's own docblock) since that
		// positioning rule lives on the wrapper now, not on .hsrtech-code__copy
		// itself, to support render.php's blocks sliding a remaining button
		// over when the other one's hidden (hideCopyButton/hideWrapToggle).
		var actions = document.createElement( 'div' );
		actions.className = 'hsrtech-code__actions';
		actions.appendChild( copyButton );

		pre.parentNode.insertBefore( figure, pre );
		frame.appendChild( langLabel );
		frame.appendChild( actions );
		frame.appendChild( pre );
		figure.appendChild( frame );
	}

	/**
	 * Re-color and, if needed, re-wrap-correctly rebuild a
	 * .hsrtech-code__lines container render.php already rendered row-based
	 * (see that file — "Show line numbers" and/or "Highlight lines" active).
	 * The server-rendered rows are plain, uncolored text; this discards them
	 * and rebuilds via buildLineRows() using the exact same config server
	 * used (read back from the container's own data-hsrtech-* attributes and
	 * inline --hsrtech-line-digits custom property, not re-derived some other
	 * way), so a visitor with JavaScript disabled still sees correct rows —
	 * just uncolored and, if "Wrap long lines" is also on, potentially
	 * un-renumbered past the first wrapped line, same trade-off as before.
	 *
	 * @param {HTMLElement} container .hsrtech-code__lines element.
	 */
	function processLineRows( container ) {
		var lang = container.getAttribute( 'data-hsrtech-language' ) || 'text';
		var showNumbers = '1' === container.getAttribute( 'data-hsrtech-show-numbers' );
		var startLine = parseInt( container.getAttribute( 'data-hsrtech-start-line' ), 10 ) || 1;
		var highlightSet = parseLineRanges( container.getAttribute( 'data-hsrtech-highlight-lines' ) );

		// Reconstructed from each row's own code span rather than trusting a
		// single flat text source, since there isn't one — this structure
		// has no <code> element at all. The exact inverse of how render.php
		// built these rows in the first place (one array entry per "\n"-split
		// line), and the same reconstruction view.js's copy handler uses.
		var codeText = Array.prototype.map.call(
			container.querySelectorAll( '.hsrtech-code__line-code' ),
			function ( span ) {
				return span.textContent;
			}
		).join( '\n' );

		container.textContent = '';
		container.appendChild( buildLineRows( codeText, lang, {
			showNumbers: showNumbers,
			startLine: startLine,
			highlightSet: highlightSet
		} ) );
	}

	/**
	 * @param {HTMLElement} el A <pre> (flat code, no numbers/highlights) or a
	 *                         .hsrtech-code__lines container (render.php's
	 *                         row-based markup — see that file).
	 */
	function processCodeBlock( el ) {
		if ( el.classList.contains( 'hsrtech-code__lines' ) ) {
			processLineRows( el );
			return;
		}

		var pre = el;
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

	// This script also loads in wp-admin now (as an editorScript dependency,
	// blocks/code-snippet/edit.asset.php) purely so the block editor's own
	// preview can call window.hsrtechCodeHighlight's tokenizer directly —
	// see the comment on that assignment above. It must NOT also run its own
	// DOM-walking auto-processing there: the block editor's preview markup
	// (edit.js's buildPreviewElement()) is React-managed, and this function
	// mutates matching elements' real DOM directly (processLineRows()
	// clears and rebuilds a .hsrtech-code__lines container's children) —
	// doing that to a node React still thinks it owns corrupts React's own
	// bookkeeping for it, surfacing as broken re-renders or thrown
	// removeChild errors later. `wp-admin` on <body> is set server-side in
	// the initial admin HTML, well before this (footer) script runs, so
	// it's a reliable, Gutenberg-internals-independent signal that this is
	// an editor screen rather than a reader's own page.
	if ( ! ( document.body && document.body.classList.contains( 'wp-admin' ) ) ) {
		// The third and fourth clauses are what makes a chapterwright/code-snippet
		// block work outside a book/chapter page at all — see
		// hsrtech_enqueue_public_assets() (public/assets.php) for the enqueue-side
		// half of this fix. Both are scoped to .hsrtech-code specifically (the
		// block's own wrapper, render.php) rather than every <pre>/row on the
		// page, so an unrelated code block placed somewhere else on the same page
		// (a sidebar widget, for instance) isn't swept up along with it. The
		// fourth clause, .hsrtech-code__lines, is render.php's row-based markup —
		// "Show line numbers" and/or "Highlight lines" active — processCodeBlock()
		// branches on which shape it got (see that function).
		document.querySelectorAll( '.hsrtech-chapter__content pre, .hsrtech-book-intro pre, .hsrtech-code pre, .hsrtech-code__lines' ).forEach( processCodeBlock );
	}
} )();
