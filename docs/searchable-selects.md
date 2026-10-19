# Global searchable select controls

The shared UI script `public/js/ui.js` enhances native `select` elements across both the building workspace and system-admin layout. The shared stylesheet is `public/css/app.css`, which is loaded by both layouts.

## Behavior
- Searchable dropdown styled with the shared theme tokens, RTL support, and dark/light theme support.
- Single-select and multi-select support, including disabled options and optgroups.
- Native `select` remains in the form, so its name/value, server-side Laravel validation, and form serialization are preserved.
- Emits normal bubbling `input` and `change` events when a selection changes.
- Reinitializes selects added by the workspace's live-pagination navigation.
- Keyboard access through the trigger, search field, Escape-to-close, and arrow-key navigation from the search input.
- Dropdown is positioned in the document viewport to avoid being clipped by scrollable tables and cards.
- No external library or CDN dependency is added.

## Opt out
Use `data-search-select="off"` on a select to retain its browser-native appearance. Use `data-native-select` for the same purpose where a page explicitly needs a native select. Selects with the `size` attribute are left native.

## Verification
Run the repository CI and verify representative single-select, multi-select, required, disabled, optgroup, dynamically added, and dark-mode fields in the browser. The enhancement is progressive: if JavaScript does not run, the original native control remains usable.
