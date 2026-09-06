=== KineticHub Scroll to Top ===
Contributors: kinetichub
Tags: scroll to top, back to top, reading progress, scroll progress, accessibility
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A customisable scroll to top control with reading progress, smart reveal and Smart Return - lightweight, accessible, and dependency-free.

== Description ==

Most scroll-to-top plugins give you a button that sits in the corner and does one thing. KineticHub Scroll to Top does three.

**Navigate.** One press returns the reader to the top of the page, or to the start of the article if you would rather skip the header. When someone jumps up from deep in a long page, the same control briefly turns into a Return action that takes them straight back to where they were reading.

**Show position.** An optional progress ring wraps the control and fills as the visitor moves through the page, or through the article itself. You can show the exact percentage inside the button too.

**Stay out of the way.** The control appears only once the reader has genuinely left the first screen, steps aside while they read downward, comes back the moment they scroll up, lifts itself clear of your footer, and never appears at all on a page too short to need it.

It is designed to be quick to set up. Install, activate, and you already have a polished result with sensible defaults. Everything after that is optional.

= What you can configure =

Five tabs, a live preview beside them, and nothing hidden behind a paywall.

* **General** - scroll motion, when the control appears, Smart Reveal, Smart Return, where the control sends the reader, and how the plugin fits alongside your theme.
* **Position** - bottom right, left, or centre, with offsets, per-device overrides, and footer collision avoidance.
* **Appearance** - five style presets, eight icons, three shapes, size, colours, opacity, border, shadow, KineticPowers, and interaction polish.
* **Progress** - the ring, what it measures, the percentage readout, colours, and thickness.
* **Visibility** - which devices show it, and which pages.

= Working with your theme =

Plenty of themes already ship a back-to-top button, and some sites do not scroll the browser window at all. Both are handled in **General → Compatibility**.

* **Scroll Container** - Auto, Window, or Custom. Auto uses the browser window unless the page clearly scrolls inside an element instead; Window forces the browser window; Custom takes a CSS selector for the scrolling element. Every feature - the trigger, Smart Reveal, Smart Return, the progress ring, short-page suppression, and the scroll itself - then works against whichever source is active.
* **Smart Conflict Detection** - looks for a back-to-top control your theme or another plugin already prints and reports what it found, so you can decide what to do about it.
* **Force Replace Existing Back-to-Top** - optional, off by default. Hides a detected control so KineticHub can be the only one. Themes and plugins use different markup, so test your site after turning it on.
* **Existing Control Selector** - name your theme's control yourself when automatic detection does not recognise it.

Two things this plugin will never do: disable your site's native scrolling, and interfere with a smooth-scrolling library. If a library such as Lenis, Locomotive Scroll or Smooth Scrollbar is present, the compatibility panel says so and tells you plainly that KineticHub reads the page's scroll position rather than driving that library. Nothing is dequeued, destroyed, or unhooked.

= The Smart features =

* **Smart Motion** - the scroll duration adapts to the distance travelled, so a short hop stays snappy and a very long page still arrives promptly instead of crawling. Capped at both ends.
* **Smart Trigger** - the control appears once the reader has scrolled roughly one screen, rather than at a fixed pixel value that suits one layout and not another.
* **Smart Reveal** - while the reader is moving down the page, the control gets out of the way. The moment they scroll up, it returns. Keyboard focus always keeps it on screen.
* **Smart Return** - after a long jump to the top, the same control briefly offers to take the reader back to their previous position. No second button appears.
* **Smart Footer Dock** - when your footer scrolls into view, the control lifts above it instead of sitting on top of it.
* **Smart Content** - progress measured through the article itself rather than the whole document, with a safe fallback when no content region can be found.

= KineticPowers =

KineticPowers add a short piece of motion when someone actually uses the control. They are decoration, not behaviour: the scroll goes exactly where it went before, at exactly the speed it went before, and the sequence simply follows along.

One is available, and it is off by default.

* **Rocket Boost** - a compact three-part sequence in **Appearance → KineticPowers**. The control gives a small recoil, a rocket ignites and climbs with a short thrust trail, and a soft spark marks the arrival before the control returns to its resting state. **Power Intensity** sets how far it moves: Subtle, Balanced, or Strong.

The whole thing runs inside the control, in the icon colour you already chose, so there is no new colour to configure and nothing to clash with your page. The rocket is a dedicated inline SVG that belongs to the power alone - your chosen icon stays the resting icon, and Rocket is not a ninth choice in the icon picker. Smart Return keeps its own arrow and never launches anything.

It costs nothing when it is off. The choreography lives in a separate stylesheet that is downloaded only when a power is selected, no listener, observer, or animation loop is added either way, and nothing repeats: one press, one short sequence, then done.

Readers who ask for reduced motion never see it. `prefers-reduced-motion` switches the sequence off entirely rather than substituting a smaller one.

= Interaction polish =

Three small things in **Appearance → Interaction Polish** that decide how the control feels rather than what it does.

* **Smart Idle Fade** - on by default. Once the control has been sitting there untouched for a few seconds it softens, so it stops competing with the page. Scrolling, hovering, focusing it, or using it brings it straight back. It never hides, never shrinks, and never overrides Smart Reveal, Peek, or your device rules - it only ever adjusts a control that is already on screen.
* **Hover / Focus Label** - off by default. A compact pill beside the control naming what it will do: *Back to top*, *Back to content start*, or *Return to previous position* while Smart Return is offered. It picks its own side from where the control sits, follows your responsive position overrides, and is decoration only - the button's accessible name is unchanged and no extra tab stop appears.
* **Micro press feedback** - no setting, because a control that does not answer a press feels broken rather than restrained. The glyph compresses very briefly on click, tap, Space or Enter. When Rocket Boost is on, its launch is that feedback, so the two never stack.

= Built to stay light =

* No jQuery, no frameworks, no build step, no icon fonts, no external fonts.
* One passive scroll listener and one passive resize listener, both feeding a single animation frame that reads the scroll position once.
* Inline SVG for the icons and the progress ring, so there is nothing extra to download.
* Assets load only where they are used: the settings screen assets never load on the frontend, and the frontend assets never load in the admin or on pages where you have switched the control off.
* The interaction polish adds no scroll listener, no observer and no animation loop. Smart Idle Fade rides the frame the engine already runs and arms a single inactivity timer; the hover label is placed by the cascade, so nothing is measured in the browser.
* KineticPowers are a separate stylesheet, downloaded only when one is selected. With the setting off, nothing extra is requested and the script does no power work at all.
* The compatibility engine is a separate file that most sites never download. It loads only when it has work to do - suppressing a competing control, or reporting the compatibility status for an administrator.
* All settings live in one database row.

= Accessibility =

* A real `<button>` element, never a clickable `div`.
* A meaningful accessible name that updates when Smart Return is offered, announced through a polite live region.
* Fully keyboard operable, with a visible focus indicator.
* Keyboard focus keeps the control on screen, so it never fades out from under someone who has just focused it.
* The button never renders smaller than 44 by 44 pixels, and Thumb Friendly raises it to 48 on phones.
* The progress ring is decorative and hidden from assistive technology.
* KineticPowers are decorative: the button stays a real `<button>`, its accessible name never changes because of a sequence, no tab stop is added, and nothing about the animation is announced.
* The hover label is decoration too: it is hidden from assistive technology, adds no tab stop, and appears on keyboard focus as well as on hover, so it is not a pointer-only affordance.
* Smart Idle Fade never softens a control that is focused, hovered, offering a Smart Return, or playing a KineticPower, and it never hides one.
* No animation on the control repeats. A KineticPower is a single short sequence a person triggered, never continuous motion.
* `prefers-reduced-motion` is respected: readers who ask for reduced motion get an instant jump and no transitions, whatever the motion setting says, no KineticPower runs at all, and the press feedback is dropped rather than replaced. Smart Idle Fade still softens the control, because that is a change of opacity rather than of place - it simply happens at once instead of over a fifth of a second.

== Installation ==

1. Upload the `kinetichub-scroll-to-top` folder to `/wp-content/plugins/`, or install the plugin through the Plugins screen in WordPress.
2. Activate the plugin through the Plugins screen.
3. Go to **Settings → Scroll to Top**.

The defaults are ready to use, so you can stop there. Adjust anything you like and press Save Changes.

== Frequently Asked Questions ==

= The button does not appear when I scroll down. Is it broken? =

Almost certainly not. That is Smart Reveal doing its job: while you are reading downward the control stays out of your way, and it appears as soon as you scroll up. If you would rather it stayed on screen the whole time, turn Smart Reveal off in the General tab. Turning on Peek Mode is a middle ground: the control shrinks to a quiet peek instead of disappearing.

= The button does not appear at all on one of my pages. =

If that page has less than 400 pixels of scrollable distance, the control is hidden on purpose, because there is nothing useful for it to do. Check the Visibility tab as well: the page might fall outside your Page Scope, or the device you are testing on might be switched off.

= What exactly does Smart Return do? =

If you press the control while you are a long way down a page, it remembers where you were before it scrolls you up. When you arrive, the same button turns into a Return action for a short while. Press it again and you land back where you were reading.

The offer clears itself in three situations: after twelve seconds, as soon as you scroll about half a screen on your own, or when you press Escape while the button has focus. It never traps keyboard focus and it never adds a second button to the page.

= Does the progress ring work with square and rounded buttons? =

Yes. The ring is drawn as one rounded rectangle whose corner radius follows the button shape, and its length is calculated exactly rather than estimated, so progress reads correctly on a circle, a rounded square, and a square alike.

= Can I use my own CSS selector for the content region? =

Yes, in two places: Content Start in the General tab and Smart Content in the Progress tab both accept an optional selector such as `#main-content` or `.entry-content`.

Selectors are validated conservatively. Simple class, id, tag, attribute, descendant and child selectors are accepted; pseudo-classes and other punctuation are not, because they add nothing when pointing at a content region. If your selector is rejected or matches nothing, the plugin falls back to its built-in detection and then to the page top, so the control always keeps working.

= What does the plugin look for when detecting content automatically? =

In this order: your custom selector if you set one, then `.entry-content`, `article`, `main`, `#content`, and `#main`. The first one that exists and is actually rendered wins. If none match, Content Start falls back to the page top and Smart Content falls back to whole-page progress.

= Does it work with any WordPress theme? =

It is built to. The control is printed on the standard `wp_footer` hook, it is positioned relative to the viewport rather than to your layout, and its colours are set on the element itself so a theme's own button styling cannot bleed into it. It does not depend on any theme markup, template, or class name.

The two situations that need a decision from you are both handled in **General → Compatibility**: a theme that already prints its own back-to-top control, and a site that scrolls inside an element instead of the browser window. Both have their own questions below.

We have not tested every theme, and no plugin honestly can. What the plugin does instead is tell you what it found on your actual site, in the Compatibility panel, rather than claim a list.

= Can I turn the progress ring off? =

Yes. **Progress → Progress Ring** switches it off entirely, and the control becomes a plain scroll-to-top button. Nothing else changes, and the button is not redrawn or resized.

The percentage readout is a separate setting, so you can keep the ring and hide the number, or the other way round. On phones you can override both without touching your desktop settings.

= Can I choose where the button scrolls to? =

Yes. **General → Destination** offers the top of the page or the start of your content. Content Start is useful on sites with a tall header or a hero section: the reader lands on the article instead of above it.

You can name the content region yourself with a CSS selector such as `#main-content`, or leave it to automatic detection. If the selector matches nothing, the destination falls back to the page top rather than failing.

= Does it work on phones and tablets? =

Yes, and it can be configured differently there. Position, offsets, button and icon size, ring thickness and the percentage readout all have optional per-device overrides, and you can switch the control off entirely on any of the three device groups.

Two things happen without any configuration: safe-area insets are applied so the control clears notches and gesture bars, and Thumb Friendly keeps the button at a comfortable size on phones. The button is never rendered smaller than 44 by 44 pixels on any device.

= Does it respect reduced motion? =

Yes, and not by making the animation smaller. When a reader's system asks for reduced motion, the scroll becomes an instant jump whatever the motion setting says, transitions are dropped, no KineticPower runs at all, and the press feedback is omitted rather than replaced.

Smart Idle Fade still softens the control, because that is a change of opacity rather than of position - it simply happens at once instead of over a fifth of a second.

= Does it use jQuery, and how much does it add? =

No jQuery, no framework, no build step, no icon font, and no external fonts. The icons and the progress ring are inline SVG, so there is nothing extra to download for them.

At runtime it is one passive scroll listener and one passive resize listener, both feeding a single animation frame that reads the scroll position once. Optional parts are genuinely optional: the KineticPowers stylesheet is downloaded only when a power is selected, and the compatibility engine is a separate file that most sites never request at all.

= Do you collect any data? =

No. There is no tracking, no analytics, and no remote request of any kind - the plugin never contacts an external server for any reason. Nothing is written to cookies, local storage or session storage.

Your settings live in one row of your own database. Compatibility detection stores one small technical record in a second row, described in full in the Privacy section below; it contains no addresses, no user identifiers and no record of who visited. Both are deleted when you delete the plugin.

= My theme already has a back-to-top button. What happens? =

Nothing, unless you ask for something to happen. With Smart Conflict Detection on, the Compatibility panel in the General tab tells you a second control was found and shows enough about it - its tag, id, and first classes - to recognise which one it is. The recommended fix is to turn the other control off in your theme or plugin settings, because that removes its script as well as its button.

If your theme has no such setting, turn on **Force Replace Existing Back-to-Top**. That hides the other control's button and nothing else.

= What exactly does Force Replace do, and what does it not do? =

It adds one attribute to the elements it is confident about, and one rule in the plugin's own stylesheet hides those elements. That is the whole mechanism.

It does not remove anything from the page, does not dequeue or deregister any script or stylesheet, does not unhook anything, does not change any theme or plugin setting, and never touches native page scrolling. The other plugin or theme keeps working exactly as before; only its button is out of sight. Turn the setting off and the button is back on the next page load.

It is also deliberately cautious. It acts only on elements that carry a clear back-to-top identifier, accessible label, or data attribute, and it will not touch a navigation menu item, a skip link, or screen-reader-only markup. If a control currently holds keyboard focus it is left alone until focus moves away, so nobody loses their place. And if KineticHub's own control is hidden on the device being used, nothing is suppressed at all, because that would leave the visitor with no control whatsoever.

= How does detection decide what is a back-to-top control? =

It scores each candidate. A recognisable identifier (`back-to-top`, `scrolltotop`, `to-top`, `gotop` and similar), an accessible label or title reading "Back to top", "Scroll to top", "Return to top" or "Go to top", or a `data-back-to-top` attribute each count as strong evidence. Being a link or a button, being positioned fixed or sticky, and sitting near the top of the document add to that. Being inside a list item or a menu item subtracts heavily, because a floating control is never a menu entry.

A link pointing at `#top` is not treated as a control on its own - that is ordinary navigation markup - and only counts when the element is also floating over the page. Reporting needs a moderate score; suppression needs a high one. There is no vendor database of theme selectors, because a list like that goes stale and starts producing false positives; the Existing Control Selector field covers anything the scoring misses.

The scoring is deliberately generic, but it was developed against real themes rather than in the abstract: Astra and OceanWP were both used to check that a theme's own control is recognised, and that with Force Replace on it never flashes into view before it is suppressed - including on a reload part-way down a page, which is the case that catches naive implementations.

KineticHub's own control is excluded from detection entirely, so it can never report itself.

= My site scrolls inside a container instead of the page. Is that supported? =

Yes. Set Scroll Container to Custom and give it the selector for the element that scrolls, such as `.main-scroll-container` or `#page-scroll`. Every position-dependent feature then reads that element instead of the window.

Auto will find a container on its own only when the evidence is strong: the document itself must not scroll, and the candidate must be a page-sized element that genuinely overflows and genuinely has scrolling overflow. Anything smaller - a sidebar, a dropdown, a carousel, a modal - is rejected. If the evidence is not there, Auto uses the browser window, because a wrong guess is worse than no guess.

If a custom selector is invalid, matches nothing, or matches something unusable, the plugin falls back to the browser window and the Compatibility panel says so. It never fails hard.

= Does it work with Lenis, Locomotive Scroll, or another smooth-scrolling library? =

It detects them and tells you they are there. It does not claim to drive them, and it never disables, stops, or destroys them.

Those libraries differ in whether they keep the browser's own scroll position in step. Where they do, everything works normally. Where they do not, Smart Motion and Smart Return are the two features to test on your own site before relying on them. The Compatibility panel distinguishes between what was detected and what is actually being used, so you are never told something is supported when it is not.

= Why does the Compatibility panel say "Not checked yet"? =

Because only a browser can see the rendered page. PHP cannot know which element is scrolling or what your theme prints, so the plugin does not guess: it reads the answer on the front end and reports it back. Open your site in another tab while signed in as an administrator, then reload the settings screen.

The panel also offers **Check Again**. That button discards the stored reading rather than pretending to re-run a check the server cannot perform; the next front-end view by an administrator records a fresh one. A link to your site sits next to it for exactly that purpose.

= My layout scrolls inside a container, but the page scrolls too. What happens? =

The control follows the container you named, and stands aside while that container is not on screen. It does not switch to the browser window behind your back, and it does not sit there showing a progress value left over from a scroll area you can no longer see. When the container comes back into view the control is re-measured and picks up where the container actually is. The Compatibility panel says when it has seen this arrangement.

= What is a KineticPower, and will it slow my site down? =

It is a short piece of motion that plays when someone presses the control - currently one of them, Rocket Boost, and it is off unless you turn it on. It is decoration only: it never changes where the page scrolls, how fast it scrolls, or when the control appears.

It will not slow your site down. There is no library, no canvas, no particle system, and no image; the sequence is CSS transforms and opacity on a small layer inside the button, and it runs once per press. When the setting is Off, the stylesheet is not even downloaded.

= Will Rocket Boost cover up the progress ring or the percentage? =

Not after it finishes. The rocket is drawn on its own layer that takes no part in the button's layout, so the ring is measured and drawn exactly as it is without a power. During the short sequence the percentage steps aside and the rocket crosses the centre of the button, which is deliberate; when it ends, the ring and the percentage are back and exact.

= The control fades out while I am reading. Is that a bug? =

No, that is Smart Idle Fade, and you can switch it off in **Appearance → Interaction Polish**. After a few seconds of no interaction the control drops to about two thirds opacity so it stops pulling at the corner of your eye. It does not disappear and it does not shrink; scrolling, moving the pointer onto it, or focusing it with the keyboard brings it straight back to full strength.

It also stands aside for anything more important. A control that is focused, hovered, offering a Smart Return, or mid-Rocket Boost is never softened.

= Can I change the hover label's wording, colour, or position? =

Not in 1.0.0, and that is deliberate. The wording follows the action the control is actually offering, the colours come from the button colours you already chose, and the side it appears on follows the position you already set - including your tablet and mobile overrides. There is nothing left to configure that would not just be a way to get it wrong.

= Does it work with WooCommerce? =

Yes, and WooCommerce is not required. If WooCommerce is active you get an extra Page Scope option for product pages only. If you later deactivate WooCommerce, that setting falls back to showing the control across the site rather than hiding it everywhere.

= Will it collide with my footer, or with a phone's home indicator? =

Smart Footer Dock lifts the control above your footer when the footer scrolls into view; it looks for `footer`, `.site-footer`, and `#colophon`, and does nothing if none of them exist. Safe-area insets are added automatically on phones, so the control clears notches and gesture bars without any configuration.

= Does it work without JavaScript? =

No. Scrolling behaviour, progress, and visibility all happen in the browser, so the control stays hidden when JavaScript is unavailable rather than showing a button that would not do anything.

= Are my settings deleted if I deactivate the plugin? =

No. Deactivating keeps everything. Settings are removed only when you delete the plugin through the Plugins screen.

== Privacy ==

This plugin does not collect, transmit, or store any personal data.

* **No tracking or analytics.** Nothing about you or your visitors is recorded.
* **No remote requests.** The plugin never contacts any external server, for any reason. There are no external scripts, stylesheets, fonts, or icon libraries. The one request it can make is to your own site's `admin-ajax.php`, same-origin, to record the compatibility status described below.
* **Local settings only.** Your configuration is stored in your own WordPress database, in a single option named `khstt_settings`.
* **One technical status record.** Compatibility detection stores a small summary in a second option, `khstt_compat_status`: which scrolling environment was seen, whether the window or a container is in use, how many competing controls were found, and up to three of them described by tag name, id, and first classes. Nothing else. No page addresses, no user identifiers, no markup, no record of who visited or when. It is written only while an administrator with the `manage_options` capability is browsing the front end, and only when the stored record is missing, more than six hours old, from a different plugin version, or was taken under different compatibility settings. Visitors never trigger it: the request is not even printed on their page. Both options are deleted when you delete the plugin.
* **Nothing stored in the browser.** The frontend keeps no cookies and writes nothing to local or session storage. Scroll position and the Smart Return position live in memory for the current page view only and are discarded when the page is left.
* **No third parties.** No advertising, no upsells, no promotional notices, no phoning home.

== Source Code ==

The human-readable source code for KineticHub Scroll to Top is publicly available at:

https://github.com/GetKineticHub/kinetichub-scroll-to-top-source

The plugin has no compilation step: the PHP, CSS, and vanilla JavaScript in the repository are exactly what ships. Packaging is handled by `scripts/build-zip.js`, which collects the files allowed by `.distignore` into the distributable ZIP.

== Screenshots ==

1. Smart scrolling, reveal controls, destinations, and compatibility settings in one focused dashboard.
2. Customize presets, icons, shapes, colors, sizing, and interaction details with live preview.
3. Rocket Boost adds lightweight motion choreography without delaying the actual scroll action.
4. Track reading progress with a configurable ring, percentage display, colors, and responsive overrides.
5. Control placement, offsets, sizing, and responsive behaviour across desktop, tablet, and mobile.
6. Detect existing Back to Top controls and manage theme conflicts with built-in compatibility tools.
7. A lightweight frontend control with live reading progress, smart navigation, and optional interaction labels.

== Changelog ==

= 1.0.0 =
* Initial release.
* Scroll to top with Smart, Smooth, and Instant motion.
* Smart Trigger and Smart Reveal, with an optional Peek Mode.
* Smart Return: the same control briefly offers to take the reader back to their previous position.
* Scroll destination: page top or content start, with an optional custom selector.
* Progress ring with whole-page or Smart Content measurement, an optional percentage readout, and configurable colours, thickness, and track opacity.
* Position presets with horizontal and bottom offsets, plus tablet and mobile overrides.
* Smart Footer Dock and automatic mobile safe-area support.
* Thumb Friendly mobile ergonomics.
* Five Quick Style presets, eight inline SVG icons, three shapes, and full colour, border, and shadow control.
* KineticPowers, with Rocket Boost: an optional launch, travel, and arrival sequence played when the control is used, in three intensities. Off by default, respects reduced motion, and loads nothing when it is not selected.
* Smart Idle Fade: softens an untouched control and restores it on the next interaction. On by default.
* Hover / Focus Label: an optional pill naming the current action, placed automatically from the control's position. Off by default.
* Micro press feedback on activation, by click, tap, Space or Enter.
* Device and page-scope visibility, with automatic short-page suppression.
* Five-tab settings screen with a live preview, a simulated progress slider, and device preview modes.
* Scroll Container support: Auto, Window, or a custom scrolling element, with every scroll-dependent feature reading through one adapter.
* Smart Conflict Detection, with a Compatibility panel reporting the scrolling environment and any competing back-to-top control.
* Optional Force Replace Existing Back-to-Top, which hides a detected control's button without touching the theme or plugin behind it.
* Existing Control Selector for controls automatic detection does not recognise.
* Detection of known smooth-scrolling libraries, reported honestly as detected rather than supported.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
