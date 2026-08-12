# Changelog

All notable changes to Make a Book. See [README.md](README.md#changelog) for the most recent entries — this file is the full history.

### 2.0.3

- Added a line of helper text under the **Accent color** field (in both the admin app's Book details panel and the block-editor sidebar) explaining what it actually affects: links, hover states, blockquote/callout borders, and the reading-progress bar on the book's pages.

### 2.0.2

- Fixed the block-editor sidebar panel ("Book Details" / "Chapter Details") crashing on open, shown as "The 'make-a-book' plugin has encountered an error and cannot be rendered." `useEntityProp()` can return `undefined` for a post's `meta` object on the very first render, before the entity has finished loading, and the panel read `meta._mab_subtitle` (etc.) without guarding against that.
- Renamed the book page's "Edit content, cover & excerpt →" button to the clearer "Open in Block Editor →", with a short explanation of what you'll find there underneath it.

### 2.0.1

- Fixed a critical bug in the 2.0.0 admin app: every custom `make-a-book/v1` REST route (sections and chapter reordering) fataled on every request with `is_numeric() expects exactly 1 argument, 3 given`, because WordPress calls a route's `validate_callback` with three arguments and `is_numeric()` is a PHP built-in that doesn't accept extras. Opening a book in the admin app was the most visible symptom. Fixed by wrapping the check in a plain function instead of passing the built-in directly.
- Fixed the **Settings** page rendering blank: it's registered as a submenu of the "Make a Book" top-level page, but was doing so before that top-level page had actually been registered (a file-load-order accident), so WordPress computed the wrong internal hook name and never called the page's render function. Fixed by giving the top-level menu registration an earlier `admin_menu` priority. This also fixes the sidebar order — **Books & Chapters** now consistently appears above **Settings**.
- Refreshed the admin app's visual design: a proper page header, consistent panel styling and spacing, status pills for draft/pending/private items, cleaner section/chapter rows, and modern (40px) form control sizing throughout.
- **Uninstalling the plugin now performs a full clean sweep** instead of retaining content: every Book and Chapter (and their metadata), the `mab_sections` table, and the plugin's settings are all permanently deleted when you remove Make a Book from the Plugins screen. This replaces every earlier version's "retain content" behavior — see "Updating and uninstalling" in README.md.

### 2.0.0

- **Breaking (internal data model):** chapter sections are no longer a free-text `_mab_section` meta value. They are now rows in a new `mab_sections` database table (`id`, `book_id`, `name`, `description`, `menu_order`), each with its own describable text shown in the table of contents, and chapters reference one via `_mab_section_id`. Existing sites migrate automatically and losslessly on the first request after updating (see `mab_migrate_sections_from_meta()` in `includes/upgrade.php`): every distinct `_mab_section` value per book becomes a section row, and every chapter that had it is repointed at the new row. The table is dropped on uninstall.
- Added a new **Make a Book → Books & Chapters** admin app (a `@wordpress/components`-based single-page app) for browsing books and managing a book's sections and chapters — adding, reordering, reassigning, and removing — from one screen instead of several scattered post-type screens.
- Added a block-editor sidebar panel ("Book Details" / "Chapter Details") that replaces the old `add_meta_box()` panels, so the same fields are still available when writing a chapter directly in the block editor.
- The Book and Chapter post type list/edit screens no longer appear in the admin menu (`show_in_menu` is now `false`) — the admin app and Settings are the two Make a Book entries in the sidebar. The screens themselves are unchanged and still load normally when linked to directly.
- Registered a `make-a-book` category and five abilities (list/get/create/delete operations on books, chapters, and sections) with WordPress's Abilities API (6.9+), making the plugin's core operations discoverable and callable by AI agents and automation tools.
- `_mab_subtitle`, `_mab_accent`, `_mab_book_id`, `_mab_order`, and `_mab_section_id` are now registered for the REST API (`register_post_meta()`), and a small `make-a-book/v1` REST namespace was added for sections and bulk chapter reordering — both power the admin app, and both are usable directly by other code.
- Removed `admin/meta-boxes.php` and `admin/chapter-order.php` (superseded by the sidebar panel, REST meta fields, and the admin app's client-side order suggestions).
- Adds a `@wordpress/scripts` build step for `admin/app/src/` only. Every other PHP and JS file in the plugin is unchanged in that respect.

### 1.5.1

- Removed `docs/` and `tests/smoke.php` from the repository to keep it focused on the plugin itself. The architecture reference and wp-env smoke test were internal developer aids, not something an installed site needs.

### 1.5.0

- Added contextual Help tabs (the "Help" panel in the top-right corner of the Book and Chapter list/edit screens) covering how to create a book, add and organize chapters, use the Code Snippet block, and display the library — no separate documentation page to find.
- Reworded the credit line to "This book is created with Make a Book" on book/chapter pages, and "This library is powered by Make a Book" on the `/books/` library page.
- Security/standards fixes: added missing `wp_unslash()` calls before sanitizing a few `$_POST` values (Settings page fields, Chapter Book/Order fields) so a saved value with an apostrophe no longer picks up a stray backslash; added `ABSPATH` guards to the block's `*.asset.php` files for consistency with the rest of the plugin.

### 1.4.0

- Added a "← Back to library" link to the top of every book page, linking to `/books/`.
- Added an optional "This book is created with Make a Book" credit line at the bottom of book, chapter, and library pages, linking to the plugin's repository. On by default; turn it off under **Make a Book → Settings**.

### 1.3.1

- Fixed the book page's hero section forcing `min-height: 72vh`, which left a large, mostly-empty gap above the title on most screens. Removed it and trimmed the top padding on the book hero, book intro, table-of-contents card, and chapter reader, since the header-to-content gap is already handled by `.mab-page`'s own padding.
- Fixed the archive page's `.mab-archive__header` still adding its own top margin on top of `.mab-page`'s padding, which doubled the gap below the site header on `/books/`.
- Fixed `.mab-archive__header h1` still using the old, oversized bundled font size (up to 9rem) — missed in 1.3.0's heading-inheritance change.

### 1.3.0

- Added a **Settings** page (**Make a Book → Settings**) to turn the reader's own color-mode toggle on or off, and to edit or remove the library page's heading text and each book's table-of-contents heading.
- Books, Chapters, and Settings are now grouped under one **Make a Book** admin menu instead of Books and Chapters each having their own top-level entry.
- Heading font sizes (`h1`–`h3`) in the reader no longer ship a large bundled size — they inherit from the active theme (or the browser default) like the font family already did.
- Fixed the gap below the site header on book/chapter/archive pages to reliably match the rest of the theme; it no longer depends on WordPress reprinting the header block's own per-instance layout CSS, which isn't guaranteed on templates rendered outside the full block-template canvas.

### 1.2.0

- **Breaking (internal API):** rewrote the plugin from a class-based architecture to a function-based one. `Make_A_Book`, `Make_A_Book_Content_Types`, `Make_A_Book_Admin`, and `Make_A_Book_Public` no longer exist; use the equivalent `mab_*()` functions instead (for example, `mab_get_chapters()` in place of `Make_A_Book::get_chapters()`). Post types, meta keys, the `[make_a_book]` shortcode, and the `/books/` and `/book-chapter/` URL bases are unchanged.
- Added the **Code Snippet** block (`make-a-book/code-snippet`) for formatted, copyable code examples with a language label and optional caption.
- Added a chapter list with quick-add link to the Book editor, a Book filter on the Chapters list, and automatic chapter-order suggestions.
- Reader and block typography now use `font-family: inherit` so the plugin adopts the active theme's fonts instead of shipping its own.

### 1.1.0

- Added accessible system, light, and dark reading modes.
- Added Book and Chapter JSON-LD structured data.
- Added code, table, callout, keyboard-focus, and skip-link styles.
- Refined chapter typography and added reading metadata and progress feedback.
- Added compatibility with classic and block themes.
- Added wp-env configuration and runtime smoke-test tooling.

### 1.0.0

- Initial release.
