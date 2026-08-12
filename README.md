# Make a Book

Publish multiple web-native ebooks in WordPress, with book landing pages, grouped tables of contents, ordered chapters, and a focused reading experience.

## Requirements

| Requirement | Version |
| --- | --- |
| WordPress | 6.4 or newer |
| PHP | 7.4 or newer |
| Tested through | WordPress 6.8 |
| Plugin version | 2.0.2 |

Make a Book adds two content types to WordPress: **Books** and **Chapters**. Each book can have its own cover, subtitle, accent color, introduction, and table of contents. Chapters can be grouped into sections — each with its own name and description — and receive automatic previous/next navigation.

> [!NOTE]
> The Abilities API integration (see [Abilities API](#abilities-api)) only registers on WordPress 6.9 and newer, since that API doesn't exist on older versions. Everything else in this plugin works on the minimum WordPress 6.4 listed above.

## Features

- Publish any number of books.
- Organize chapters into sections, each with its own name and optional description shown in the table of contents.
- Control chapter order.
- Display a book library at `/books/` or with a shortcode.
- Use responsive book and chapter templates with theme-compatible headers and footers.
- Support the block editor, revisions, and the WordPress REST API.
- Let readers choose system, light, or dark color mode.
- Provide reading progress and estimated reading time.
- Add Book and Chapter schema.org structured data.
- Provide accessible skip links, landmarks, focus indicators, and reduced-motion support.
- Style code blocks, tables, and reusable note or warning callouts for comfortable technical reading.
- Add a **Code Snippet** block for formatted, copyable code examples with an optional caption and language label.
- A single **Make a Book** admin page lists every book, and lets you manage a book's sections and chapters — adding, reordering, and reassigning them — in one place, without the classic post-type screens' back-and-forth. See [The admin app](#the-admin-app).
- Typography inherits the active theme's fonts and heading sizes, so the reader looks like a native part of the site instead of a bundled font stack.
- A Settings page lets you turn the reader's color-mode toggle on or off, and edit or remove the library page's heading text and each book's table-of-contents heading.
- Built-in usage instructions in the Book and Chapter screens' native WordPress "Help" tab — no separate documentation page to hunt for.
- Registers with the WordPress [Abilities API](#abilities-api) (WordPress 6.9+) so AI agents and automation tools can discover and use the plugin's book/chapter/section operations in a standardized, permission-checked way.

## Installation

1. Download the plugin ZIP.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and activate **Make a Book**.
4. Go to **Make a Book** in the admin sidebar and select **Add Book**.
5. Open the new book, add a chapter from its detail screen, then follow the **Edit content →** link to write it in the block editor.
6. Open the book's **View** link, visit `/books/`, or add `[make_a_book]` to a page.

You can also install the plugin manually by copying the `make-a-book` directory to `/wp-content/plugins/` and activating it from the **Plugins** screen.

> [!TIP]
> If a book URL returns a 404 after activation, go to **Settings → Permalinks** and click **Save Changes** once.

The admin app and Settings both live under one **Make a Book** entry in the admin sidebar.

## The admin app

**Make a Book → Books & Chapters** is a single-page app (built with the same `@wordpress/components` used throughout the block editor, so it looks and feels native) for organizing your library:

- The books list shows every book with its cover and status, and a quick "Add Book" field.
- Opening a book shows its subtitle and accent color, its sections (add, rename, describe, reorder, delete), and every chapter (add, reassign to a section, reorder, move to Trash).
- "Open in Block Editor →" opens the book or chapter in the normal block editor — the app manages structure, not the writing itself.

The classic Book and Chapter list/edit screens still exist (a "Chapter Details" panel in the block editor's own sidebar mirrors the same book/section/order fields), so nothing about how content is written has changed — only where you go to organize the library.

## Settings

Go to **Make a Book → Settings** to:

- Turn the reader's own light/dark color-mode toggle on or off (shown on book and chapter pages). This is separate from any color-mode switch your theme puts in the site header — turn this off if the two feel redundant.
- Turn the "This book is created with Make a Book" credit line at the bottom of book, chapter, and library pages on or off.
- Edit or remove the library page's (`/books/`) eyebrow label, heading, and subheading.
- Edit or remove the "Read at your own pace" heading shown above each book's table of contents.

Clearing a text field removes that line from the page entirely rather than falling back to the default wording — this is the intended way to drop placeholder copy you don't want.

## Creating a book

Books provide the landing page and table of contents for a publication.

1. Go to **Make a Book**, and use **Add Book** to create one with a title.
2. Open the new book and follow **Open in Block Editor →** to write the introduction, set a featured image (used as the cover), and add an excerpt in the normal block editor. The excerpt appears in the book hero, library cards, and structured data; when omitted, WordPress may generate one from the main content.
3. Back on the book's admin-app page, fill in an optional subtitle and choose an accent color.
4. Choose the author and publish the book from the block editor.

The table of contents is built from all published Chapters assigned to the book. Draft and private chapters are not displayed publicly.

## Adding and organizing chapters

Open a book in **Make a Book → Books & Chapters** to see its sections and chapters together. Use **+ Add chapter** to create one (optionally into a section right away) — it opens in the block editor as a draft, ready to write.

1. Use the title for the chapter name and the main editor for the complete chapter content. You can use normal blocks, headings, images, links, lists, tables, code, and shortcodes.
2. Add an optional excerpt. It appears beneath the chapter title, in the table of contents, and in structured data.
3. Add an optional featured image. It appears below the chapter header.
4. Publish the chapter.

Sections are their own thing, not just a text label: create one from the book's admin-app page with a name (such as `Getting Started` or `Part II`) and an optional description, which appears under the heading in the table of contents. Assign a chapter to a section — or move it between sections — from the same page, or from the "Chapter Details" panel in the chapter's own block-editor sidebar. Chapters left unassigned appear under the default **Chapters** heading.

Chapter order runs from the lowest number to the highest and controls both the table of contents and previous/next navigation; reorder chapters with the up/down controls on the book's admin-app page. Publication date breaks ties if two chapters ever share a number.

> [!IMPORTANT]
> A chapter must be published and assigned to a Book to participate in that book's table of contents and reading navigation.

Deleting a section never deletes its chapters — they simply become unassigned and fall back to the default **Chapters** heading.

## The Code Snippet block

For chapters that include code, add a **Code Snippet** block (search for "Code Snippet" in the block inserter) instead of a plain Code block. It provides:

- A monospace editor that preserves exact whitespace, like the core Code block.
- A language label (PHP, JavaScript, CSS, HTML, Shell, or Plain text).
- An optional caption, useful for a filename or a one-line description.
- A front-end **Copy** button so readers can copy the snippet without selecting text manually.

## Displaying the book library

The plugin automatically provides a public book archive at:

```text
/books/
```

To display the library inside an existing page or post, add a Shortcode block containing:

```text
[make_a_book]
```

The shortcode displays up to 12 published books, newest first. Set a limit between 1 and 100 with:

```text
[make_a_book limit="6"]
```

The reader stylesheet and script load only on Book pages, Chapter pages, the book archive, and singular pages whose saved content contains the shortcode.

## Reader experience

Chapter pages automatically include:

- The chapter author and publication date.
- An estimated reading time based on approximately 220 words per minute.
- A quiet reading-progress indicator.
- A link back to the parent book.
- Previous and next chapter navigation.
- A system, light, and dark color-mode control.

The reader stores the visitor's color preference under the `make-a-book-color-mode` local-storage key. The selected mode is exposed on the document root through `data-mab-mode`, allowing custom styles for each mode.

Book pages also include a "← Back to library" link to `/books/`. Book, chapter, and library pages can optionally show a small credit at the bottom linking to the plugin's repository — "This book is created with Make a Book" on book/chapter pages, "This library is powered by Make a Book" on `/books/` — see [Settings](#settings).

## Custom styling

The bundled stylesheet is [`assets/css/make-a-book.css`](assets/css/make-a-book.css). Visitor-facing selectors use the `.mab-` prefix, and the selected book accent is available through the `--mab-accent` custom property.

Body and heading text use `font-family: inherit` throughout, so the reader automatically picks up your active theme's fonts — there is nothing to configure. Code blocks are the one deliberate exception and always use a fixed monospace stack, since code needs to stay legible and evenly spaced regardless of theme.

Headings (`h1`–`h3`) don't set their own `font-size` either — they inherit whatever size your theme applies to headings (or the browser default if your theme doesn't style headings globally), rather than a large size bundled with the plugin.

Do not edit the bundled stylesheet directly because plugin updates will overwrite those changes. Add overrides in one of these places instead:

- A child theme's `style.css`.
- Your theme's supported custom-CSS area.
- A small site-specific plugin.
- A dedicated stylesheet your theme enqueues itself, with `array( 'make-a-book' )` as its dependency so it's guaranteed to print after this plugin's own CSS. This is the most maintainable option for a theme that wants to closely match its own design tokens (color palette, card radius, button styling) — remap the `--mab-*` custom properties to your theme's own CSS variables rather than hardcoding colors, so the reader keeps tracking your theme's light/dark toggle automatically.

Example child-theme CSS:

```css
.mab-page {
    --mab-accent: #2563eb;
}

.mab-chapter__content {
    font-family: "Iowan Old Style", Georgia, serif;
}

.mab-book-hero {
    border-radius: 0;
}

[data-mab-mode="dark"] .mab-chapter__content a {
    color: #93c5fd;
}
```

Your theme may require more specific selectors for headings, links, figures, or content blocks. When overriding the defaults, preserve visible focus indicators, adequate color contrast, responsive layouts, and reduced-motion behavior. The reader intentionally uses a text column of approximately 680–720px for comfortable long-form reading.

## Template customization

The presentation files are organized as follows:

| Template | Purpose |
| --- | --- |
| [`templates/single-mab_book.php`](templates/single-mab_book.php) | Book landing page and grouped table of contents |
| [`templates/single-mab_chapter.php`](templates/single-mab_chapter.php) | Chapter reader and previous/next navigation |
| [`templates/archive-mab_book.php`](templates/archive-mab_book.php) | `/books/` archive shell |
| [`templates/book-grid.php`](templates/book-grid.php) | Cards used by the archive and shortcode |
| [`templates/partials/document-start.php`](templates/partials/document-start.php) | Classic- and block-theme document opening |
| [`templates/partials/document-end.php`](templates/partials/document-end.php) | Classic- and block-theme document closing |

### Overriding routed templates

Editing bundled templates directly is not update-safe. Copy the template into a child theme and select it with a late `template_include` filter.

For example, copy templates to `your-child-theme/make-a-book/`, then add this to the child theme's `functions.php` or a site-specific plugin:

```php
/**
 * Load child-theme templates for Make a Book views.
 *
 * @param string $template Selected WordPress template path.
 * @return string Filtered template path.
 */
function mysite_make_a_book_templates( $template ) {
    $directory = get_stylesheet_directory() . '/make-a-book/';

    if ( is_singular( 'mab_book' ) ) {
        $custom_template = $directory . 'single-mab_book.php';
    } elseif ( is_singular( 'mab_chapter' ) ) {
        $custom_template = $directory . 'single-mab_chapter.php';
    } elseif ( is_post_type_archive( 'mab_book' ) ) {
        $custom_template = $directory . 'archive-mab_book.php';
    } else {
        return $template;
    }

    return file_exists( $custom_template ) ? $custom_template : $template;
}
add_filter( 'template_include', 'mysite_make_a_book_templates', 99 );
```

The plugin does not automatically discover template copies in a theme, so this filter is required.

The bundled single and archive templates include the document-start and document-end partials. A copied template can continue using those plugin partials or use the child theme's normal header and footer. Ensure the final page contains only one document header and footer.

### Customizing the book grid

`book-grid.php` is included directly by the archive and shortcode templates and does not have a separate lookup filter.

- To change the archive grid, copy and adapt `archive-mab_book.php` and the grid it includes.
- To build a custom library elsewhere, query published `mab_book` posts from a child theme or site-specific plugin.

### Template safety checklist

When editing templates:

- Escape attributes with `esc_attr()`.
- Escape URLs with `esc_url()`.
- Escape plain text with `esc_html()` at output time.
- Render editor content through `the_content()` so blocks, shortcodes, and content filters continue to work.
- Retain landmarks, navigation labels, skip links, visible focus, and keyboard behavior.
- Keep customizations outside the plugin directory so updates remain safe.

## Content and URL reference

| Item | Identifier |
| --- | --- |
| Book post type | `mab_book` |
| Chapter post type | `mab_chapter` |
| Book archive and single base | `/books/` |
| Chapter base | `/book-chapter/` |
| Chapter parent-book metadata | `_mab_book_id` |
| Chapter section metadata | `_mab_section_id` (points to a row in the `mab_sections` table) |
| Chapter order metadata | `_mab_order` |
| Book subtitle metadata | `_mab_subtitle` |
| Book accent metadata | `_mab_accent` |
| Sections table | `{$wpdb->prefix}mab_sections` (`id`, `book_id`, `name`, `description`, `menu_order`) |
| Library shortcode | `[make_a_book]` |

Books and Chapters support the block editor, revisions, and the WordPress REST API — including reading and writing `_mab_subtitle`, `_mab_accent`, `_mab_book_id`, `_mab_order`, and `_mab_section_id` through the standard `/wp/v2/mab_book` and `/wp/v2/mab_chapter` endpoints. Books also support author selection. Sections have their own small REST namespace, `make-a-book/v1` (see `admin/rest/`), since they live in a custom table rather than post meta. Use standard WordPress APIs when reading or writing metadata, and validate that `_mab_book_id` points to a Book and `_mab_section_id` points to a section belonging to that same book.

## Abilities API

As of 2.0.0, the plugin registers a `make-a-book` category and a handful of abilities with WordPress's [Abilities API](https://developer.wordpress.org/apis/abilities-api/) (introduced in WordPress 6.9): `make-a-book/list-books`, `make-a-book/get-book-overview`, `make-a-book/create-section`, `make-a-book/create-chapter`, and `make-a-book/delete-section`. Each one is a thin, permission-checked, schema-validated wrapper around the same functions the admin app and REST controllers already call — see `includes/abilities.php`. This lets AI agents, MCP servers, and other automation discover and use the plugin's core operations without any bespoke integration. Registration is skipped automatically (not an error) on WordPress versions before 6.9, where the Abilities API doesn't exist.

## Frequently asked questions

<details>
<summary><strong>Can I publish more than one book?</strong></summary>

Yes. Each chapter is assigned to a specific book, and the library supports any number of published books.
</details>

<details>
<summary><strong>Can I show books on an existing page?</strong></summary>

Yes. Add `[make_a_book]` to a Shortcode block. Use the optional `limit` attribute to control how many books appear.
</details>

<details>
<summary><strong>Why is a chapter missing from the table of contents?</strong></summary>

Confirm that the chapter is published and has the correct Book selected — check the "Chapter Details" panel in the block editor, or the chapter's row on the book's admin-app page. Only published chapters assigned to that book are listed.
</details>

<details>
<summary><strong>How do I change chapter order?</strong></summary>

Use the up/down controls on the book's admin-app page, or set **Chapter number / order** directly in the "Chapter Details" panel in the block editor.
</details>

<details>
<summary><strong>What happens to a section's chapters if I delete the section?</strong></summary>

They are not deleted or unpublished. They simply become unassigned and fall back to the default "Chapters" heading in that book's table of contents.
</details>

<details>
<summary><strong>Can one chapter belong to more than one book?</strong></summary>

No. A Chapter has one parent Book. Duplicate or adapt the chapter if separate books require independently ordered versions.
</details>

<details>
<summary><strong>Can I change the URL bases?</strong></summary>

The `/books/` and `/book-chapter/` bases are not configurable through plugin settings. They are part of the stable public URL contract. Changing them requires custom development plus redirects for existing links.
</details>

<details>
<summary><strong>Will my theme's header and footer appear?</strong></summary>

Yes. Bundled views support classic and block themes and render the active theme's header and footer. Highly customized themes may require a child-theme template override.
</details>

<details>
<summary><strong>Is it safe to edit plugin templates or CSS directly?</strong></summary>

No. Direct changes will be lost during an update. Put CSS in a child theme or supported custom-CSS area, and override routed templates using the documented `template_include` filter.
</details>

## Updating and uninstalling

Back up the site before updating WordPress, a theme, or any plugin. Custom changes should live in a child theme or site-specific plugin so Make a Book updates cannot overwrite them.

> [!WARNING]
> Deleting Make a Book from the **Plugins** screen performs a full clean sweep: every Book and Chapter (and their metadata), the `mab_sections` table, and the plugin's saved settings are all permanently deleted. Nothing is kept. This is deliberate — back up the site first if you want to keep any of it. Deactivating the plugin (without deleting it) does **not** touch your data; only actually removing it from the Plugins screen does.

## Development

The plugin is organized by responsibility:

```text
admin/          Admin-only PHP: the app page, list-table columns, Settings, Help tabs
admin/app/src/  React admin-app and block-editor-sidebar source (built with @wordpress/scripts)
admin/rest/     Custom REST controllers for sections and bulk chapter reordering
assets/         Scoped visitor-facing CSS and JavaScript
blocks/         The Code Snippet Gutenberg block (make-a-book/code-snippet)
includes/       Content types, meta registration, the sections table, upgrades, Abilities API
public/         Template routing, conditional assets, structured data, and shortcodes
templates/      Visitor-facing presentation files
```

Every PHP file is function-based: hooks are registered at the top level when the file loads, and every public entry point is a global `mab_*()` function rather than a class method. `admin/app/src/` is the one part of the plugin written against a build step (JSX, `@wordpress/components`) rather than plain `window.wp.*` globals — a real admin single-page app is impractical to hand-write the way the small Code Snippet block editor script is, so this is a deliberate, scoped exception, not a change to the rest of the plugin's conventions.

Start the local WordPress environment with:

```bash
npm install
npm run env:start
```

Build (or watch) the admin app after changing anything under `admin/app/src/`:

```bash
npm run build   # one-off production build, output to admin/app/build/
npm run start   # rebuilds on every save, for active development
```

`admin/app/build/` is gitignored, like every other generated artifact in this repository, but is expected to exist (run `npm run build` first) in any copy of the plugin actually installed in WordPress — the admin app and block-editor sidebar panel silently don't load without it.

## Changelog

### 2.0.2

- Fixed the block-editor sidebar panel ("Book Details" / "Chapter Details") crashing on open, shown as "The 'make-a-book' plugin has encountered an error and cannot be rendered." `useEntityProp()` can return `undefined` for a post's `meta` object on the very first render, before the entity has finished loading, and the panel read `meta._mab_subtitle` (etc.) without guarding against that.
- Renamed the book page's "Edit content, cover & excerpt →" button to the clearer "Open in Block Editor →", with a short explanation of what you'll find there underneath it.

### 2.0.1

- Fixed a critical bug in the 2.0.0 admin app: every custom `make-a-book/v1` REST route (sections and chapter reordering) fataled on every request with `is_numeric() expects exactly 1 argument, 3 given`, because WordPress calls a route's `validate_callback` with three arguments and `is_numeric()` is a PHP built-in that doesn't accept extras. Opening a book in the admin app was the most visible symptom. Fixed by wrapping the check in a plain function instead of passing the built-in directly.
- Fixed the **Settings** page rendering blank: it's registered as a submenu of the "Make a Book" top-level page, but was doing so before that top-level page had actually been registered (a file-load-order accident), so WordPress computed the wrong internal hook name and never called the page's render function. Fixed by giving the top-level menu registration an earlier `admin_menu` priority. This also fixes the sidebar order — **Books & Chapters** now consistently appears above **Settings**.
- Refreshed the admin app's visual design: a proper page header, consistent panel styling and spacing, status pills for draft/pending/private items, cleaner section/chapter rows, and modern (40px) form control sizing throughout.
- **Uninstalling the plugin now performs a full clean sweep** instead of retaining content: every Book and Chapter (and their metadata), the `mab_sections` table, and the plugin's settings are all permanently deleted when you remove Make a Book from the Plugins screen. This replaces every earlier version's "retain content" behavior — see "Updating and uninstalling."

### 2.0.0

- **Breaking (internal data model):** chapter sections are no longer a free-text `_mab_section` meta value. They are now rows in a new `mab_sections` database table (`id`, `book_id`, `name`, `description`, `menu_order`), each with its own describable text shown in the table of contents, and chapters reference one via `_mab_section_id`. Existing sites migrate automatically and losslessly on the first request after updating (see `mab_migrate_sections_from_meta()` in `includes/upgrade.php`): every distinct `_mab_section` value per book becomes a section row, and every chapter that had it is repointed at the new row. The table is dropped on uninstall — see "Updating and uninstalling."
- Added a new **Make a Book → Books & Chapters** admin app (a `@wordpress/components`-based single-page app) for browsing books and managing a book's sections and chapters — adding, reordering, reassigning, and removing — from one screen instead of several scattered post-type screens.
- Added a block-editor sidebar panel ("Book Details" / "Chapter Details") that replaces the old `add_meta_box()` panels, so the same fields are still available when writing a chapter directly in the block editor.
- The Book and Chapter post type list/edit screens no longer appear in the admin menu (`show_in_menu` is now `false`) — the admin app and Settings are the two Make a Book entries in the sidebar. The screens themselves are unchanged and still load normally when linked to directly.
- Registered a `make-a-book` category and five abilities (list/get/create/delete operations on books, chapters, and sections) with WordPress's Abilities API (6.9+), making the plugin's core operations discoverable and callable by AI agents and automation tools. See [Abilities API](#abilities-api).
- `_mab_subtitle`, `_mab_accent`, `_mab_book_id`, `_mab_order`, and `_mab_section_id` are now registered for the REST API (`register_post_meta()`), and a small `make-a-book/v1` REST namespace was added for sections and bulk chapter reordering — both power the admin app, and both are usable directly by other code.
- Removed `admin/meta-boxes.php` and `admin/chapter-order.php` (superseded by the sidebar panel, REST meta fields, and the admin app's client-side order suggestions).
- Adds a `@wordpress/scripts` build step for `admin/app/src/` only — see "Development." Every other PHP and JS file in the plugin is unchanged in that respect.

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

## License

Make a Book is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).

Copyright © Alok Jain.
