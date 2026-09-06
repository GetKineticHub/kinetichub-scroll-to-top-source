/**
 * KineticHub Scroll to Top - compatibility engine.
 *
 * Loaded only when the site has a reason to run it: an administrator whose
 * compatibility status needs refreshing, or a site with Force Replace enabled.
 * A visitor on an ordinary site never downloads this file.
 *
 * Three jobs, in order:
 *
 *   Detect   - find controls that are already doing this plugin's job
 *   Suppress - hide those controls, and only those, when asked to
 *   Report   - hand a small technical summary back to the admin screen
 *
 * The page scans twice: once when the DOM is ready and once after load, which
 * covers controls that a theme injects late. There is deliberately no
 * MutationObserver: watching the whole document for the rest of the session to
 * catch an unusual injection is not a trade this plugin makes.
 */
( function khsttCompatBoot() {
	'use strict';

	var CFG = window.KHSTT_COMPAT;

	if ( ! CFG ) {
		return;
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', khsttCompatBoot, { once: true } );
		return;
	}

	/**
	 * Identifier fragments that, in practice, only appear on a back-to-top
	 * control. Bare "top" is deliberately absent: it matches anchors, layout
	 * utilities and navigation items far more often than it matches a control.
	 */
	var ID_TOKENS = [
		'back-to-top', 'backtotop', 'back_to_top', 'back-top', 'backtop',
		'scroll-to-top', 'scrolltotop', 'scroll_to_top', 'scroll-top', 'scrolltop',
		'scroll-up', 'scrollup', 'to-top', 'totop', 'go-top', 'gotop'
	];

	/** Phrases that name the action outright, wherever they are written. */
	var LABEL_PHRASES = [
		'back to top', 'back to the top',
		'scroll to top', 'scroll to the top',
		'return to top', 'return to the top',
		'go to top', 'go to the top',
		'top of page', 'top of the page'
	];

	/** Anchor targets a back-to-top control commonly points at. */
	var TOP_HREFS = [ '#top', '#page-top', '#top-of-page', '#masthead' ];

	/** Score at which a match is reported. */
	var REPORT_AT = 3;

	/** Score at which a match may be suppressed. */
	var SUPPRESS_AT = 5;

	/** Ceiling on how many matches are examined, so a strange page stays cheap. */
	var MAX_CANDIDATES = 60;

	/** How many matches are described in the report. */
	var MAX_REPORTED = 3;

	/** Delay before the second scan when the page had already loaded. */
	var LATE_SCAN_DELAY = 1200;

	/** The attribute that carries suppression, and its stylesheet hook. */
	var SUPPRESS_ATTR = 'data-khstt-suppressed';

	/**
	 * The attribute that tells the early hint to stand down.
	 *
	 * When a previous reading was confident about a control, the server prints
	 * a rule in the head that holds that control back until this engine is in a
	 * position to judge it for itself. Setting this ends that rule, whatever
	 * this pass concluded: from here on the only thing hiding anything is what
	 * this engine actually found on this page.
	 */
	var READY_ATTR = 'data-khstt-compat-ready';

	/**
	 * Elements that carry markup rather than paint a control. A resource or
	 * metadata node can hold an id that names the feature it powers without
	 * ever being the thing a visitor clicks: OceanWP enqueues its control's
	 * behaviour as <script id="oceanwp-scroll-top-js">, which carries the same
	 * "scroll-top" token as the real <a id="scroll-top"> and was being counted
	 * as a second, non-existent conflict.
	 *
	 * The test is on the element name rather than on whether the browser gave
	 * the node a box: back-to-top controls are routinely display:none until the
	 * visitor scrolls - Astra's is - so "has no layout box" would rule out the
	 * real controls this engine exists to find.
	 */
	var NON_CONTROL_TAGS = [
		'SCRIPT', 'STYLE', 'LINK', 'META', 'NOSCRIPT', 'TEMPLATE',
		'TITLE', 'BASE', 'HEAD', 'HTML', 'BODY', 'SOURCE', 'TRACK', 'PARAM'
	];

	var found = [];
	var seen = [];
	var suppressedCount = 0;
	var candidateSelector = null;

	/* =====================================================================
	   Candidate selection
	   ================================================================== */

	/**
	 * Builds the one selector every scan runs, cached after the first call.
	 *
	 * Everything here is a literal assembled from the token lists above. No
	 * part of it comes from a setting, so nothing a site owner types can widen
	 * what this matches.
	 *
	 * @return {string}
	 */
	function selector() {
		var parts = [];

		if ( candidateSelector ) {
			return candidateSelector;
		}

		ID_TOKENS.forEach( function ( token ) {
			parts.push( '[id*="' + token + '" i]' );
			parts.push( '[class*="' + token + '" i]' );
		} );

		LABEL_PHRASES.forEach( function ( phrase ) {
			parts.push( '[aria-label*="' + phrase + '" i]' );
			parts.push( '[title*="' + phrase + '" i]' );
		} );

		parts.push( '[data-back-to-top]', '[data-scroll-to-top]' );

		TOP_HREFS.forEach( function ( href ) {
			parts.push( 'a[href="' + href + '"]' );
		} );

		candidateSelector = parts.join( ',' );

		return candidateSelector;
	}

	/**
	 * Whether a node is off limits, whatever else it looks like.
	 *
	 * This plugin's own control is the first thing excluded: a control that
	 * reported itself as a conflict would be both wrong and alarming.
	 *
	 * @param {Element} node Candidate.
	 * @return {boolean}
	 */
	function excluded( node ) {
		if ( ! node || 1 !== node.nodeType || ! node.closest ) {
			return true;
		}

		if ( node.closest( '#khstt' ) || node.closest( '.khstt' ) ) {
			return true;
		}

		if ( node.closest( '#wpadminbar' ) ) {
			return true;
		}

		// A resource or metadata element is never the control, however its id
		// or class reads, and nothing inside <head> is on screen to be one.
		if ( NON_CONTROL_TAGS.indexOf( node.tagName ) > -1 ) {
			return true;
		}

		if ( document.head && document.head.contains( node ) ) {
			return true;
		}

		// A skip link is a navigation aid for exactly the people who would be
		// hurt most by losing it.
		if ( node.closest( '.skip-link, .screen-reader-text, .sr-only, .visually-hidden' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The strongest reason to believe a node is a back-to-top control, or null.
	 *
	 * @param {Element} node Candidate.
	 * @return {string|null}
	 */
	function primaryReason( node ) {
		var id = ( node.getAttribute( 'id' ) || '' ).toLowerCase();
		var cls = ( node.getAttribute( 'class' ) || '' ).toLowerCase();
		var label = ( node.getAttribute( 'aria-label' ) || '' ).toLowerCase();
		var title = ( node.getAttribute( 'title' ) || '' ).toLowerCase();
		var i;

		for ( i = 0; i < ID_TOKENS.length; i++ ) {
			if ( id.indexOf( ID_TOKENS[ i ] ) > -1 ) {
				return 'id';
			}
		}

		for ( i = 0; i < ID_TOKENS.length; i++ ) {
			if ( cls.indexOf( ID_TOKENS[ i ] ) > -1 ) {
				return 'class';
			}
		}

		for ( i = 0; i < LABEL_PHRASES.length; i++ ) {
			if ( label.indexOf( LABEL_PHRASES[ i ] ) > -1 ) {
				return 'label';
			}
		}

		for ( i = 0; i < LABEL_PHRASES.length; i++ ) {
			if ( title.indexOf( LABEL_PHRASES[ i ] ) > -1 ) {
				return 'title';
			}
		}

		if ( node.hasAttribute( 'data-back-to-top' ) || node.hasAttribute( 'data-scroll-to-top' ) ) {
			return 'data';
		}

		if ( TOP_HREFS.indexOf( node.getAttribute( 'href' ) ) > -1 ) {
			return 'href';
		}

		return null;
	}

	/**
	 * How far a node sits from the body, giving up early: a floating control
	 * lives near the root of the document, never twenty levels deep inside an
	 * article.
	 *
	 * @param {Element} node Candidate.
	 * @return {number}
	 */
	function depthOf( node ) {
		var depth = 0;
		var walk = node.parentElement;

		while ( walk && walk !== document.body && depth < 8 ) {
			depth++;
			walk = walk.parentElement;
		}

		return depth;
	}

	/**
	 * Grades one candidate.
	 *
	 * An identifier or a spoken label is worth believing on its own. An anchor
	 * pointing at the top of the page is not: that is ordinary navigation
	 * markup, so it only counts when the element is also floating over the page
	 * the way a back-to-top control does.
	 *
	 * @param {Element} node Candidate.
	 * @return {Object|null} Classification, or null when it is not a control.
	 */
	function classify( node ) {
		var reason = primaryReason( node );
		var position;
		var score;

		if ( ! reason ) {
			return null;
		}

		position = window.getComputedStyle( node ).position;

		if ( 'href' === reason && 'fixed' !== position && 'sticky' !== position ) {
			return null;
		}

		score = 'href' === reason ? 1 : 3;

		if ( 'A' === node.tagName || 'BUTTON' === node.tagName ) {
			score += 1;
		}

		if ( 'fixed' === position || 'sticky' === position ) {
			score += 2;
		}

		if ( depthOf( node ) <= 3 ) {
			score += 1;
		}

		// A menu entry is not a floating control, however it is named. Themes
		// do print "Top" links inside navigation, and hiding one would remove a
		// real navigation option from the page.
		if ( node.closest( 'li' ) || node.closest( '.menu-item' ) ) {
			score -= 4;
		}

		if ( score < REPORT_AT ) {
			return null;
		}

		return {
			node: node,
			via: reason,
			confident: score >= SUPPRESS_AT
		};
	}

	/* =====================================================================
	   Scanning
	   ================================================================== */

	/**
	 * Runs one pass over the document and records anything new.
	 */
	function scan() {
		var nodes;

		try {
			nodes = document.querySelectorAll( selector() );
		} catch ( e ) {
			nodes = [];
		}

		Array.prototype.slice.call( nodes, 0, MAX_CANDIDATES ).forEach( function ( node ) {
			var match;

			if ( excluded( node ) || seen.indexOf( node ) > -1 ) {
				return;
			}

			seen.push( node );
			match = classify( node );

			if ( match ) {
				found.push( match );
			}
		} );

		scanCustom();
	}

	/**
	 * Adds anything the site owner named explicitly.
	 *
	 * Naming a selector is an instruction, not a guess, so a match is trusted
	 * without scoring. It still cannot reach this plugin's own control or the
	 * admin bar.
	 */
	function scanCustom() {
		var nodes;

		if ( ! CFG.selector ) {
			return;
		}

		try {
			nodes = document.querySelectorAll( CFG.selector );
		} catch ( e ) {
			return;
		}

		Array.prototype.slice.call( nodes, 0, MAX_CANDIDATES ).forEach( function ( node ) {
			if ( excluded( node ) ) {
				return;
			}

			// A node already graded by the automatic pass is upgraded rather
			// than listed twice.
			for ( var i = 0; i < found.length; i++ ) {
				if ( found[ i ].node === node ) {
					found[ i ].via = 'custom';
					found[ i ].confident = true;
					return;
				}
			}

			if ( seen.indexOf( node ) < 0 ) {
				seen.push( node );
			}

			found.push( { node: node, via: 'custom', confident: true } );
		} );
	}

	/* =====================================================================
	   Suppression
	   ================================================================== */

	/**
	 * Whether this plugin's own control is actually available here.
	 *
	 * The Visibility tab hides the control per breakpoint with display:none.
	 * Suppressing the theme's control on a breakpoint where this plugin shows
	 * nothing would leave the visitor with no control at all.
	 *
	 * @return {boolean}
	 */
	function ownControlAvailable() {
		var own = document.getElementById( 'khstt' );

		if ( ! own ) {
			return false;
		}

		return 'none' !== window.getComputedStyle( own ).display;
	}

	/**
	 * Hides one competing control.
	 *
	 * Nothing is removed, dequeued, unhooked or rewritten. An attribute goes on
	 * the element and the plugin's own stylesheet hides it, so the theme keeps
	 * its markup, its script and its behaviour, and turning the setting off
	 * brings the control straight back on the next page load.
	 *
	 * @param {Element} node Control to hide.
	 * @return {boolean} Whether it was hidden now.
	 */
	function suppress( node ) {
		if ( node.hasAttribute( SUPPRESS_ATTR ) ) {
			return false;
		}

		// Hiding the element a keyboard user is standing on would drop their
		// focus to the top of the document with no warning. The suppression
		// waits for them to move on.
		if ( node.contains( document.activeElement ) ) {
			node.addEventListener( 'focusout', function whenIdle( event ) {
				if ( event.relatedTarget && node.contains( event.relatedTarget ) ) {
					return;
				}

				node.removeEventListener( 'focusout', whenIdle );
				node.setAttribute( SUPPRESS_ATTR, '1' );
			} );

			return false;
		}

		node.setAttribute( SUPPRESS_ATTR, '1' );

		return true;
	}

	/** Hides every match confident enough to act on. */
	function suppressAll() {
		if ( ! CFG.replace || ! ownControlAvailable() ) {
			return;
		}

		found.forEach( function ( match ) {
			if ( match.confident && suppress( match.node ) ) {
				suppressedCount++;
			}
		} );
	}

	/* =====================================================================
	   Reporting
	   ================================================================== */

	/**
	 * Names the scrolling environment.
	 *
	 * Detected is not the same as supported. A library being present says
	 * nothing about whether this plugin drives it, and the admin screen words
	 * it that way.
	 *
	 * @param {string} source The adapter's own answer: "window" or "custom".
	 * @return {string}
	 */
	function environment( source ) {
		var root = document.documentElement;

		if ( window.Lenis || window.lenis || root.classList.contains( 'lenis' ) ) {
			return 'lenis';
		}

		if (
			window.LocomotiveScroll ||
			root.classList.contains( 'has-scroll-smooth' ) ||
			document.querySelector( '[data-scroll-container]' )
		) {
			return 'locomotive';
		}

		if ( window.Scrollbar && window.Scrollbar.has ) {
			return 'smooth-scrollbar';
		}

		if ( 'custom' === source ) {
			return 'container';
		}

		// The document cannot scroll and nothing recognisable is driving it.
		// Worth saying out loud rather than reporting a native page.
		if ( 'hidden' === window.getComputedStyle( root ).overflowY ) {
			return 'other';
		}

		if ( document.body && 'hidden' === window.getComputedStyle( document.body ).overflowY ) {
			return 'other';
		}

		return 'native';
	}

	/**
	 * Reduces one match to the few short strings the status card shows.
	 *
	 * @param {Object} match Classification.
	 * @return {Object}
	 */
	function describe( match ) {
		var classes = ( match.node.getAttribute( 'class' ) || '' ).split( /\s+/ );

		return {
			tag: ( match.node.tagName || '' ).toLowerCase(),
			id: match.node.getAttribute( 'id' ) || '',
			cls: classes.slice( 0, 2 ).join( ' ' ).trim(),
			via: match.via,
			confident: match.confident
		};
	}

	/**
	 * Posts the status snapshot back to WordPress.
	 *
	 * Same origin, one small form post, and only when the server armed it,
	 * which it does exclusively for administrators and only while the stored
	 * snapshot is out of date.
	 */
	function report() {
		var probe = CFG.probe;
		var state = ( window.KHSTT && window.KHSTT.state ) || { source: 'window', fallback: false };
		var body;

		if ( ! probe || ! window.fetch || ! window.URLSearchParams ) {
			return;
		}

		body = new window.URLSearchParams();
		body.set( 'action', probe.action );
		body.set( 'nonce', probe.nonce );
		body.set(
			'payload',
			JSON.stringify( {
				env: environment( state.source ),
				source: state.source,
				fallback: !! state.fallback,
				mixed: !! state.mixed,
				scanned: !! CFG.detect,
				count: found.length,
				suppressed: suppressedCount,
				conflicts: found.slice( 0, MAX_REPORTED ).map( describe )
			} )
		);

		window.fetch( probe.url, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).catch( function () {} );
	}

	/* =====================================================================
	   Run
	   ================================================================== */

	/** One pass: look, hide, and remember. */
	function pass() {
		if ( CFG.detect ) {
			scan();
			suppressAll();
		}
	}

	pass();

	// Order matters: the hand-off happens after this pass has already hidden
	// whatever it decided to hide, so there is no frame in which the early rule
	// has let go and the engine has not yet taken hold.
	document.documentElement.setAttribute( READY_ATTR, '1' );

	// The second pass catches a control the theme injects after DOM ready,
	// which is common enough to be worth one extra look and nowhere near
	// common enough to be worth watching the document forever.
	if ( 'complete' === document.readyState ) {
		window.setTimeout( function () {
			pass();
			report();
		}, LATE_SCAN_DELAY );
	} else {
		window.addEventListener( 'load', function () {
			pass();
			report();
		}, { once: true } );
	}
}() );
