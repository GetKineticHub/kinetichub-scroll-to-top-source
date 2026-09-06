/**
 * KineticHub Scroll to Top - Playground demo controller.
 *
 * Two jobs, both of them about the page around the control:
 *
 *   1. Reveal sections as they scroll into view.
 *   2. Tick off the four suggested steps as the visitor performs them.
 *
 * The second one is the important one, and the rule it follows is that the demo
 * *watches* the control and never drives it. Every step is decided from state
 * the plugin publishes for itself - the data-visible and data-return attributes
 * on its own root element, its own percentage readout, and ordinary click
 * events that have already reached the button. Nothing here calls into the
 * plugin, overrides its settings, or synthesises an interaction, so the
 * behaviour a visitor sees is exactly the behaviour the plugin has on any site.
 *
 * No libraries. Every listener is passive, and the click listener is registered
 * on the document in the capture phase so it observes the press without
 * standing between the visitor and the button.
 *
 * This file is written by the Playground blueprint into an mu-plugin and never
 * ships inside the plugin.
 */
( function () {
	'use strict';

	var doc = document;

	/* The plugin's own root element and the parts of it worth watching. */
	var control = null;

	var steps = {};
	var done  = {};

	var hint = doc.getElementById( 'khsttd-hint' );

	/* Scroll depth, in pixels, that counts as "you have started reading". */
	var SCROLLED_AT = 600;

	/* Fraction of the page that counts as "the ring has visibly moved". */
	var PROGRESS_AT = 0.2;

	/* How long to keep looking for the control before giving up quietly. */
	var WAIT_MS = 4000;

	/**
	 * Marks one step complete. Idempotent: the first time wins and later calls
	 * do nothing, so a step cannot flicker as the visitor scrolls back and
	 * forth over its threshold.
	 *
	 * @param {string} key  Step name.
	 * @param {string} note Optional line for the status region.
	 */
	function complete( key, note ) {
		if ( done[ key ] || ! steps[ key ] ) {
			return;
		}

		done[ key ] = true;
		steps[ key ].classList.add( 'is-done' );

		if ( note ) {
			say( note );
		}
	}

	/**
	 * Writes the status line. It is a polite live region, so this is announced
	 * rather than only shown.
	 *
	 * @param {string} text Message.
	 */
	function say( text ) {
		if ( hint ) {
			hint.textContent = text;
		}
	}

	/**
	 * How far through the document the visitor is, 0 to 1.
	 *
	 * Preferred source is the control's own percentage readout, so the demo and
	 * the plugin can never disagree about the number on screen. Some of the
	 * variations turn the readout off, and then this falls back to measuring
	 * the document the same way the plugin does.
	 *
	 * @return {number} Fraction between 0 and 1.
	 */
	function progressFraction() {
		var readout = control ? control.querySelector( '.khstt__pct' ) : null;

		if ( readout ) {
			var parsed = parseInt( readout.textContent, 10 );
			if ( ! isNaN( parsed ) ) {
				return Math.max( 0, Math.min( 1, parsed / 100 ) );
			}
		}

		var max = Math.max(
			1,
			doc.documentElement.scrollHeight - doc.documentElement.clientHeight
		);

		return Math.max( 0, Math.min( 1, window.pageYOffset / max ) );
	}

	/* -------------------------------------------------------------------------
	   Steps
	   ---------------------------------------------------------------------- */

	function onScroll() {
		if ( window.pageYOffset > SCROLLED_AT ) {
			complete( 'scrolled', 'The control is on screen. Keep going and watch the ring fill.' );
		}

		if ( done.scrolled && progressFraction() > PROGRESS_AT ) {
			complete( 'progress', 'That is your reading position. Press the control when you are ready.' );
		}
	}

	/**
	 * A press of the real control.
	 *
	 * Which step it satisfies depends on what the control was offering at the
	 * moment it was pressed, which is exactly what data-return records.
	 *
	 * @param {Event} event Click event, already on its way to the button.
	 */
	function onPress( event ) {
		if ( ! control || ! event.target || ! event.target.closest ) {
			return;
		}

		if ( ! event.target.closest( '#khstt .khstt__btn' ) ) {
			return;
		}

		if ( '1' === control.getAttribute( 'data-return' ) ) {
			complete( 'returned', 'Back where you were. That is Smart Return.' );
			return;
		}

		complete( 'used', 'On the way up. Press the control again to come back here.' );
	}

	/** Watches the control's published state for the Smart Return offer. */
	function watchControl() {
		if ( ! window.MutationObserver ) {
			return;
		}

		var observer = new MutationObserver( function () {
			if ( '1' === control.getAttribute( 'data-return' ) && ! done.returned ) {
				say( 'The control is offering to take you back. Press it again.' );
			}
		} );

		observer.observe( control, {
			attributes: true,
			attributeFilter: [ 'data-return', 'data-visible' ],
		} );
	}

	/* -------------------------------------------------------------------------
	   Section reveal
	   ---------------------------------------------------------------------- */

	function revealSections() {
		var targets = doc.querySelectorAll( '[data-khsttd-reveal]' );

		if ( ! window.IntersectionObserver ) {
			Array.prototype.forEach.call( targets, function ( el ) {
				el.classList.add( 'is-in' );
			} );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-in' );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -12% 0px', threshold: 0.06 }
		);

		Array.prototype.forEach.call( targets, function ( el ) {
			observer.observe( el );
		} );
	}

	/* -------------------------------------------------------------------------
	   Boot
	   ---------------------------------------------------------------------- */

	/**
	 * The control is printed on wp_footer and this script runs from the footer
	 * too, so it can be either side of it depending on how the theme orders its
	 * output. Rather than guess, look for it for a few seconds and then stop.
	 * Everything except the checklist works without it.
	 *
	 * @param {Function} ready Called with the element once it exists.
	 */
	function whenControlExists( ready ) {
		var started = Date.now();

		( function look() {
			var el = doc.getElementById( 'khstt' );

			if ( el ) {
				ready( el );
				return;
			}

			if ( Date.now() - started < WAIT_MS ) {
				window.requestAnimationFrame( look );
			}
		} )();
	}

	function init() {
		doc.body.classList.add( 'khsttd-js' );

		revealSections();

		Array.prototype.forEach.call(
			doc.querySelectorAll( '[data-khsttd-step]' ),
			function ( el ) {
				steps[ el.getAttribute( 'data-khsttd-step' ) ] = el;
			}
		);

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		doc.addEventListener( 'click', onPress, true );

		whenControlExists( function ( el ) {
			control = el;
			watchControl();
			onScroll();
		} );

		onScroll();
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
