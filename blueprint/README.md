# Playground demo blueprint

This directory holds the **WordPress Playground blueprint** behind the *Live
Preview* button on the plugin's WordPress.org page. It is development material:
`.distignore` excludes the whole directory, so nothing here ever reaches the
distributed plugin ZIP or a user's site.

## Layout

| Path | What it is |
| --- | --- |
| `src/demo.php` | The demo mu-plugin: template routing, asset loading, the variation filter, and the render helpers. |
| `src/template.php` | The showcase page itself — nine sections and the footer. |
| `src/demo.css` | Demo styles. Every selector is namespaced `.khsttd-`. |
| `src/demo.js` | Section reveal and the four-step checklist. No libraries. |
| `src/setup.php` | The Playground `runPHP` step: showcase settings and the demo front page. |
| `build.js` | Inlines the five sources into `blueprint.json` and runs the leak scan. |
| `blueprint.json` | **Generated. Do not hand-edit.** |

## Build

```
node blueprint/build.js
```

Re-run after changing anything in `src/`. The script re-parses its own output,
so a malformed blueprint fails at build time rather than silently in Playground.
It also scans the generated file for Windows paths, LocalWP host names,
loopback addresses, temp paths and any external host outside a small allowlist,
and exits non-zero if it finds one.

## Deploy

`blueprint.json` is the only file that ships, and it goes to the SVN **assets**
directory (not `trunk`):

```
cp blueprint/blueprint.json <svn-checkout>/assets/blueprints/blueprint.json
```

Then commit that from the SVN checkout as part of the normal release routine.

**Not yet.** The blueprint's `installPlugin` step resolves the plugin from
`wordpress.org/plugins` by slug, which cannot succeed until the plugin is
actually published there. Until then the blueprint is complete but unbootable in
Playground, and the demo has to be validated locally instead (see below).

## How this demo differs from the Page Loader one

Page Loader's product is a full-screen overlay that is over before the visitor
can look at it, so its demo is built out of *scaled preview stages* that replay
the loader on demand. Scroll to Top has the opposite shape: every feature it has
is a response to scrolling a real document. So there are no stages here. The
page is simply long, and the control in the corner is the real plugin, printed
by the real plugin, reading the real settings.

That is why the page is the length it is. The control needs a document tall
enough to reveal itself in, fill a progress ring against, and make a Smart
Return worth offering — and a `<footer>` tall enough that Smart Footer Dock can
be seen engaging.

## Notes for future edits

- `setup.php` writes its settings through `KHSTT_Settings::sanitize_settings()`
  rather than raw, and so does the variation filter in `demo.php`. Keep it that
  way. The sanitiser is the plugin's own schema: any key it does not recognise
  is dropped and any value outside an enum or a range falls back to its default,
  so a demo written against a newer build cannot quietly configure a site with
  settings the installed plugin does not have.
- Two showcase values are deliberately not the plugin's defaults, and both are
  explained in `setup.php`: `peek_mode` is on so Smart Reveal demonstrates
  itself instead of looking like a missing button, and `show_percentage` is
  `on` so reading progress is visible without hovering.
- The variations are a filter on `option_khstt_settings`, never a write. A
  variation lasts exactly as long as the request that asked for it, so the
  stored showcase settings survive and the Settings screen keeps showing what is
  really saved.
- `demo.js` watches the control and never drives it: `data-visible` and
  `data-return` on `#khstt`, the `.khstt__pct` readout, and ordinary click
  events. If you need a new checklist step, find the state the plugin already
  publishes rather than adding a hook into it.
- `mu-plugins` only autoloads top-level `.php` files, which is why the entry
  point is `khstt-demo.php` with its assets in the sibling `khstt-demo/`.
- `writeFile` does not create parent directories. The `mkdir` step before the
  three asset writes is load-bearing.

## Local validation

Playground cannot boot this until the plugin is on WordPress.org, so the demo is
validated against the LocalWP install instead. The scratchpad harness renders
`template.php` through the real plugin with the showcase settings applied by an
`option_khstt_settings` filter — no database write — and drives the result in
headless Chrome to confirm the control reveals, the ring advances, a press
returns the page to the top and Rocket Boost runs.
