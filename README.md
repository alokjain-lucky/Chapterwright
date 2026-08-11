# Make a Book

Publish multiple web-native ebooks in WordPress, with book landing pages, grouped tables of contents, ordered chapters, and a focused reading experience.

## Requirements

| Requirement | Version |
| --- | --- |
| WordPress | 6.4 or newer |
| PHP | 7.4 or newer |
| Tested through | WordPress 6.8 |
| Plugin version | 1.2.0 |

Make a Book adds two content types to WordPress: **Books** and **Chapters**. Each book can have its own cover, subtitle, accent color, introduction, and table of contents. Chapters can be grouped into named sections and receive automatic previous/next navigation.

## Features

- Publish any number of books.
- Organize chapters into named sections.
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
- See every chapter attached to a book, and jump straight to adding the next one, right from the Book editor.
- Filter the Chapters list by book, and get the next chapter order number suggested automatically.
- Typography inherits the active theme's fonts, so the reader looks like a native part of the site instead of a bundled font stack.

## Installation

1. Download the plugin ZIP.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and activate **Make a Book**.
4. Go to **Books → Add New** and publish your first book.
5. Go to **Chapters → Add New**, add the chapter content, assign it to the book, and publish it.
6. Open the book's **View** link, visit `/books/`, or add `[make_a_book]` to a page.

You can also install the plugin manually by copying the `make-a-book` directory to `/wp-content/plugins/` and activating it from the **Plugins** screen.

> [!TIP]
> If a book URL returns a 404 after activation, go to **Settings → Permalinks** and click **Save Changes** once.

## Creating a book

Books provide the landing page and table of contents for a publication.

1. Go to **Books → Add New**.
2. Enter the book title.
3. Add an excerpt. It appears in the book hero, library cards, and structured data. When omitted, WordPress may generate one from the main content.
4. Add an optional introduction or front matter in the main editor. It appears between the book hero and table of contents.
5. In **Book Details**, enter an optional subtitle and choose an accent color.
6. Set a featured image to use as the book cover.
7. Choose the author and publish the book.

The table of contents is built from all published Chapters assigned to the book. Draft and private chapters are not displayed publicly.

## Adding and organizing chapters

The fastest way to add a chapter is from the Book itself: open the Book, and in **Book Details** use the **"+ Add chapter to this book"** link. It opens a new Chapter screen with the Book and the next chapter number already filled in.

1. Go to **Chapters → Add New**.
2. Use the title for the chapter name and the main editor for the complete chapter content. You can use normal blocks, headings, images, links, lists, tables, code, and shortcodes.
3. Add an optional excerpt. It appears beneath the chapter title, in the table of contents, and in structured data.
4. Add an optional featured image. It appears below the chapter header.
5. In **Chapter Details**, select the parent Book.
6. Enter an optional **Section name**, such as `Getting Started` or `Part II`.
7. Enter a non-negative whole number in **Chapter number / order**.
8. Publish the chapter.

Chapters with exactly the same section name are grouped together. Chapters without a section appear under the default **Chapters** heading.

Chapter order runs from the lowest number to the highest. Publication date breaks ties, but unique order values are recommended. Changing a chapter's order or parent book updates the table of contents and previous/next navigation automatically.

> [!IMPORTANT]
> A chapter must be published and assigned to a Book to participate in that book's table of contents and reading navigation.

If you change the Book selected on a Chapter, the **Chapter number / order** field automatically fills with a suggested next number for that book. Typing your own value always takes priority — the suggestion never overwrites a number you entered yourself.

The **Chapters** admin list can be filtered by Book using the dropdown above the list table, so it's easy to review one book's chapters in order without scanning the whole library.

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

## Custom styling

The bundled stylesheet is [`assets/css/make-a-book.css`](assets/css/make-a-book.css). Visitor-facing selectors use the `.mab-` prefix, and the selected book accent is available through the `--mab-accent` custom property.

Body and heading text use `font-family: inherit` throughout, so the reader automatically picks up your active theme's fonts — there is nothing to configure. Code blocks are the one deliberate exception and always use a fixed monospace stack, since code needs to stay legible and evenly spaced regardless of theme.

Do not edit the bundled stylesheet directly because plugin updates will overwrite those changes. Add overrides in one of these places instead:

- A child theme's `style.css`.
- Your theme's supported custom-CSS area.
- A small site-specific plugin.

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
| Chapter section metadata | `_mab_section` |
| Chapter order metadata | `_mab_order` |
| Book subtitle metadata | `_mab_subtitle` |
| Book accent metadata | `_mab_accent` |
| Library shortcode | `[make_a_book]` |

Books and Chapters support the block editor, revisions, and the WordPress REST API. Books also support author selection. Use standard WordPress APIs when reading or writing metadata, and validate that `_mab_book_id` points to a Book.

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

Confirm that the chapter is published and has the correct Book selected in **Chapter Details**. Only published chapters assigned to that book are listed.
</details>

<details>
<summary><strong>How do I change chapter order?</strong></summary>

Edit each Chapter and set **Chapter number / order** in the **Chapter Details** panel. Use unique whole numbers for the clearest sequence.
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

Uninstalling Make a Book intentionally retains all user-authored Books, Chapters, and their metadata. This protects published content from accidental deletion. Remove retained content manually only after making a backup and confirming it is no longer needed.

## Development

The plugin is organized by responsibility:

```text
admin/       Editor panels, metadata persistence, admin UX, and dashboard columns
assets/      Scoped visitor-facing and admin-only CSS and JavaScript
blocks/      The Code Snippet Gutenberg block (make-a-book/code-snippet)
docs/        Architecture documentation
includes/    Content type registration and the shared chapter/order queries
public/      Template routing, conditional assets, structured data, and shortcodes
templates/   Visitor-facing presentation files
tests/       Runtime smoke checks and demo-content fixtures
```

Every file is function-based: hooks are registered at the top level when the file loads, and every public entry point is a global `mab_*()` function rather than a class method. See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for the full request lifecycle.

Start the local WordPress environment with:

```bash
npm install
npm run env:start
npm run env:test
```

See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for the component and content architecture.

## Changelog

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
