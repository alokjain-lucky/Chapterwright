# Architecture

Make a Book is a function-based (procedural) plugin: every file registers its own hooks at the top level when it is `require_once`'d, and every public entry point is a global `mab_*()` function rather than a class method. There is no coordinator object — `make-a-book.php` is the only file that decides which other files load, and it does so based on `is_admin()` so admin-only code never loads on the front end.

## Request lifecycle

1. `make-a-book.php` defines version and path constants (`MAKE_A_BOOK_VERSION`, `MAKE_A_BOOK_PATH`, `MAKE_A_BOOK_URL`) and the post type constants (`MAB_BOOK_POST_TYPE`, `MAB_CHAPTER_POST_TYPE`), then `require_once`s the other files and registers activation/deactivation callbacks.
2. `includes/content-types.php` registers Books and Chapters on `init` (`mab_register_post_types()`).
3. `includes/queries.php` exposes the canonical ordered-chapter query (`mab_get_chapters()`) and small admin-facing helpers (`mab_get_all_chapters_for_admin()`, `mab_get_next_chapter_order()`).
4. `admin/settings.php` always loads (not gated by `is_admin()`), since its `mab_get_settings()` / `mab_show_mode_toggle()` / `mab_get_text()` helpers are read by the public templates, not just the admin screens that write them.
5. When `is_admin()` is true, `admin/meta-boxes.php`, `admin/list-table.php`, `admin/chapter-order.php`, and `admin/assets.php` register editor panels, verify and sanitize save requests, extend the Chapters list table, and enqueue admin-only scripts and styles.
6. `public/assets.php`, `public/schema.php`, `public/template-router.php`, `public/shortcode.php`, `public/reading-time.php`, and `public/credit.php` conditionally load front-end CSS, print JSON-LD, choose front-end templates, render the library shortcode, and render the optional "Created with Make a Book" credit line.
7. `blocks/code-snippet/code-snippet.php` registers the Code Snippet block on `init` regardless of admin/front end, since the editor needs it in wp-admin and the dynamic render needs it on the front end.

Each file's functions are prefixed `mab_` and are called directly by name — there is no autoloading and no class instantiation anywhere in the plugin.

## Content model

A Book is a `mab_book` post. A Chapter is a `mab_chapter` post related to its parent through the `_mab_book_id` meta key. Sections are deliberately stored as chapter text metadata (`_mab_section`) rather than a taxonomy so each book may use its own section labels without creating global terms.

Chapter reading order is numeric metadata (`_mab_order`). The publication date provides deterministic ordering when two chapters use the same number.

This data contract — post type keys, meta keys, the `[make_a_book]` shortcode, and the `/books/` and `/book-chapter/` URL bases — is unchanged from earlier versions of the plugin and is treated as stable.

## Admin authoring UX

The admin layer is built around making it fast to write a book with several chapters, not just to create individual posts:

- The Book meta box (`mab_render_book_meta_box()`) lists every chapter already attached to that book, in order, with status, and a one-click "+ Add chapter to this book" link that opens a new Chapter screen with the book and next order number pre-filled via query args.
- The Chapter meta box (`mab_render_chapter_meta_box()`) reads those same query args (`mab_book_id`, `mab_order`) to prefill the Book selector and Order field when a chapter is created from that link.
- `admin/chapter-order.php` also live-suggests the next order number over AJAX whenever an author changes the Book dropdown on an existing chapter screen (`mab_ajax_next_chapter_order()`), via `assets/js/chapter-order.js`. It never overwrites a value the author has typed themselves.
- The Chapters list table gains a Book filter dropdown (`mab_chapter_book_filter()` / `mab_filter_chapters_by_book()`) so an author working on one book isn't scanning every chapter across the library.
- `admin/settings.php` adds a Settings page (`mab_render_settings_page()`) for the handful of things a site owner reasonably wants to change without editing code: whether the reader's own color-mode toggle appears, and the editable/removable copy on the library page and each book's table-of-contents heading (`mab_get_text()`). Settings are stored as one `mab_settings` option array rather than several separate rows.
- The Chapter post type registers with `'show_in_menu' => 'edit.php?post_type=' . MAB_BOOK_POST_TYPE` (see `includes/content-types.php`) and the Book post type's `menu_name` label is "Make a Book", so Books, Chapters, and Settings all appear under one top-level admin menu instead of Chapters getting its own.

## Presentation

Plugin templates own the main content area. Document partials use the active theme's header/footer files for classic themes and render block template parts inside a complete WordPress document for block themes. CSS is scoped with `mab-` classes and loaded only on plugin routes or pages containing the shortcode.

The chapter reader uses a deliberately narrow editorial measure, semantic heading rhythm, an estimated reading time, and a visual-only scroll progress bar. The progress bar is hidden from assistive technology because continuously announcing scroll changes would reduce accessibility.

Typography is intentionally *not* bundled: every `font-family` declaration in `assets/css/make-a-book.css` and in the Code Snippet block's own stylesheet is `inherit` (except the fixed monospace stack used for code, which needs to stay legible and fixed-width regardless of theme), and headings (`h1`–`h3`) don't set a `font-size` at all, so they take on whatever size the active theme applies to headings (or the browser default, if the theme doesn't style headings globally) instead of a large size bundled with the plugin. This makes the reading experience visually part of whichever theme is active rather than a plugin-branded island. Colors, spacing, and the code block's dark background are still plugin-defined via CSS custom properties (`--mab-ink`, `--mab-paper`, `--mab-surface`, `--mab-muted`, `--mab-line`, `--mab-code`, `--mab-code-ink`, `--mab-accent`) so the reader stays legible and on-brand even though its fonts come from the theme.

The gap between the site header and a plugin page's own content uses a fixed `padding-top` on `.mab-page` (see `assets/css/make-a-book.css`) rather than depending on the active theme's own spacing between its header and the next block. That per-instance spacing is generated by WordPress from the header block's own `layout` attribute and isn't reliably reprinted on templates — like this plugin's — that call `block_template_part('header')` directly instead of going through the full block-template canvas.

## The Code Snippet block

`blocks/code-snippet/` is a dynamic Gutenberg block (`make-a-book/code-snippet`) for embedding formatted code inside a chapter, with an optional caption, a language label, and a front-end copy-to-clipboard button. It is a dynamic block on purpose: `edit.js`'s `save()` returns `null`, and `render.php` is the single place that escapes and outputs the final markup, so there is exactly one code path responsible for output safety instead of one in JS and a parallel one in PHP.

The editor script and view script are written directly against WordPress's own `window.wp.*` globals (no build step, no bundler) and declare their script dependencies through the `{script}.asset.php` convention that `register_block_type()` reads automatically.

## Compatibility

The plugin was refactored from a class-based architecture (`Make_A_Book`, `Make_A_Book_Content_Types`, `Make_A_Book_Admin`, `Make_A_Book_Public`) to this function-based architecture in 1.2.0. This is a documented breaking change to the plugin's *internal* API: the old classes and their public methods no longer exist, and any external code that called them directly (e.g. `Make_A_Book::get_chapters()`) must switch to the equivalent function (`mab_get_chapters()`). No compatibility shim is provided. The externally-facing data contract described above (post types, meta keys, shortcode, URL bases) is unaffected.

## Development fixtures

`.wp-env.json` mounts this repository into WordPress 6.8 on PHP 7.4. `tests/fixtures/seed-books.php` seeds three demo books (two practical how-to guides plus "AI in WordPress 7.0", which showcases the Code Snippet block using real code from the `ai-client-course-examples` companion plugin). `tests/smoke.php` verifies post type registration, per-book chapter counts, chapter relationships, ordering, and required metadata.
