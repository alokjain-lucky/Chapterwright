# Translations

`chapterwright.pot` is the translation template: one entry per translatable
string in the plugin (PHP, via `__()`/`_e()`/etc., and the admin app's
JavaScript, via `@wordpress/i18n`). It has no translations in it — it's the
starting point for a translator.

## For a translator

Once the plugin is live on WordPress.org, translate it at
[translate.wordpress.org](https://translate.wordpress.org/) — WordPress.org
builds and serves the `.mo`/`.json` files to every site running the plugin
automatically, based on each site's locale. No manual file placement needed;
this is why the plugin doesn't call `load_plugin_textdomain()` itself.

For a site running the plugin from a source other than the WordPress.org
directory (for example, straight from a GitHub release), that automatic
loading does not apply. In that case:

1. Copy `chapterwright.pot` to `chapterwright-{locale}.po` (for example
   `chapterwright-fr_FR.po`).
2. Fill in each `msgstr ""` with the translated string, using a PO editor
   (e.g. [Poedit](https://poedit.net/)) or by hand.
3. Compile it to `chapterwright-{locale}.mo` (Poedit does this automatically
   on save; via WP-CLI: `wp i18n make-mo chapterwright-{locale}.po`).
4. Place both the `.po` and `.mo` file in this `languages/` directory, and
   add back a `load_plugin_textdomain( 'chapterwright', false,
   dirname( plugin_basename( __FILE__ ) ) . '/languages' )` call hooked on
   `init` — WordPress.org's Plugin Check flags this call as unnecessary for
   an org-hosted plugin, which is why it isn't in the shipped code, but it's
   still the correct mechanism for a non-org install.

## Admin app / editor sidebar strings (JavaScript)

The admin app and block-editor sidebar panel are JavaScript, translated via
`@wordpress/i18n` and `wp_set_script_translations()` (see `admin/app.php`).
These strings are included in `chapterwright.pot` too, but WordPress loads
their translations from a separate JSON file per script, not the `.mo`
file above. Generate those with WP-CLI after producing the `.po`:

```
wp i18n make-json languages/chapterwright-{locale}.po languages/ --no-purge
```

This produces files like `chapterwright-{locale}-{md5}.json` — one per script
handle (`chapterwright-app`, `chapterwright-editor-sidebar`) — which
`wp_set_script_translations()` finds automatically.

## Regenerating the .pot

This repository has no WP-CLI available in its dev environment, so
`chapterwright.pot` was generated with a small regex-based scanner
(kept outside this repo, not part of the shipped plugin) rather than
`wp i18n make-pot`. If you have WP-CLI available, prefer regenerating it
properly instead:

```
wp i18n make-pot . languages/chapterwright.pot --domain=chapterwright
```

Re-run this (or the equivalent) after adding or changing any translatable
string, and note it in the version's changelog entry like any other change.
