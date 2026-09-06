/**
 * KineticHub Scroll to Top - frontend engine.
 *
 * No dependencies, no polling, no jQuery. There is exactly one passive scroll
 * listener and one passive resize listener; everything they trigger is
 * coalesced into a single requestAnimationFrame callback that reads the scroll
 * position once and writes only the values that actually changed.
 *
 * Every position-dependent feature reads through one Scroll adapter rather
 * than touching window.scrollY itself, so the same code drives a page that
 * scrolls natively and a page whose scrolling lives inside an element.
 *
 * Responsibilities are deliberately separate:
 *
 *   Scroll      - the active scroll source: window, or a container element
 *   Measure     - viewport, document and element geometry, refreshed on resize
 *   Ring        - progress ring geometry, derived from the measured ring box
 *   Progress    - how far through the page or the article the visitor is
 *   Reveal      - trigger distance, scroll direction, peek and focus states
 *   Destination - where the control sends the visitor
 *   Motion      - the scroll itself, including Smart Motion easing
 *   Return      - the Smart Return state machine
 *   Power       - the KineticPower activation choreography
 *   Idle        - softening an untouched control, and waking it again
 *   Press       - the micro feedback a press gets when no power is on
 *   Label       - which action the hover label is currently naming
 *   Dock        - lifting the control clear of the footer
 */
( function khsttBoot() {
	'use strict';

	var CFG = window.KHSTT;
	var el = document.getElementById( 'khstt' );

	// The control is printed on wp_footer at priority 20, and WordPress core
	// prints footer scripts on that same hook and priority but registers its
	// callback during bootstrap, long before a plugin can. Core therefore always
	// runs first, and this script executes while its own markup is still
	// unparsed. Rather than depend on that ordering - which caching and script
	// optimisation plugins reorder freely anyway - initialisation simply waits
	// for the parser and then runs exactly as before.
	if ( ! el && 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', khsttBoot, { once: true } );
		return;
	}

	if ( ! CFG || ! el ) {
		return;
	}

	var btn = el.querySelector( '.khstt__btn' );
	if ( ! btn ) {
		return;
	}

	var ring = el.querySelector( '.khstt__ring' );
	var track = el.querySelector( '.khstt__track' );
	var bar = el.querySelector( '.khstt__bar' );
	var pct = el.querySelector( '.khstt__pct' );
	var status = el.querySelector( '.khstt__status' );

	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	/** Clearance kept between the control and the top of the footer. */
	var FOOTER_GAP = 16;

	/** Breathing room left above the content when scrolling to Content Start. */
	var CONTENT_OFFSET = 16;

	/** Scroll delta, in pixels, below which a direction change is ignored. */
	var DIRECTION_TOLERANCE = 6;

	/** How long the Smart Return offer stands before it expires, in ms. */
	var RETURN_TIMEOUT = 12000;

	/** Smart Motion duration envelope, in ms. */
	var MOTION_MIN = 280;
	var MOTION_MAX = 900;

	/**
	 * Bounds on the Auto scroll container search. The walk is breadth first
	 * from the body and stops at all three, so the cost of Auto is fixed no
	 * matter how large the page is.
	 */
	var AUTO_MAX_NODES = 200;
	var AUTO_MAX_DEPTH = 5;
	var AUTO_MAX_CHILDREN = 40;

	/** How much of the viewport a candidate container must occupy to qualify. */
	var AUTO_MIN_COVERAGE = 0.6;

	/** How far down the viewport a candidate container may start. */
	var AUTO_MAX_TOP = 0.25;

	/** How many container children the ResizeObserver watches for growth. */
	var RO_MAX_CHILDREN = 12;

	/**
	 * Content containers tried in order when resolving Content Start and Smart
	 * Content, after any custom selector the site owner configured.
	 */
	var CONTENT_SELECTORS = [ '.entry-content', 'article', 'main', '#content', '#main' ];

	/* =====================================================================
	   Shared state
	   ================================================================== */

	var state = {
		active: true,        // false when this breakpoint hides the control
		tooShort: false,     // page has nothing worth scrolling
		vh: 0,               // viewport of the active scroll source
		screenH: 0,          // browser viewport, which the docked control lives in
		maxScroll: 0,
		threshold: 0,
		bottomOffset: 0,
		lastY: 0,
		revealed: false,
		focused: false,
		programmatic: false
	};

	// Last written DOM values, so the frame callback only touches the DOM when
	// something has genuinely changed.
	var written = {
		visible: null,
		progress: -1,
		percent: -1,
		dock: -1
	};

	/* =====================================================================
	   Scroll adapter
	   ================================================================== */

	/**
	 * The one place that knows what is actually scrolling.
	 *
	 * Everything downstream - Smart Trigger, Smart Reveal, Smart Return,
	 * progress, short-page detection, and the scroll itself - asks this object
	 * instead of reading window.scrollY, so a page whose scrolling lives inside
	 * an element behaves the same as a page that scrolls natively. Exactly one
	 * source is listened to at a time.
	 *
	 * The coordinate system follows the source. For the window that is the
	 * familiar document offset; for a container it is the distance from that
	 * container's own scroll origin. offsetOf() is the only translation needed,
	 * and it collapses to the document offset when the source is the window.
	 */
	var Scroll = {
		/** The container element, or null while the window is the source. */
		node: null,

		/** True when a configured container could not be used. */
		fallback: false,

		/** @return {boolean} Whether the browser viewport is the scroll source. */
		isWindow: function () {
			return null === Scroll.node;
		},

		/** @return {number} Current scroll position. */
		y: function () {
			if ( Scroll.node ) {
				return Scroll.node.scrollTop || 0;
			}
			return window.pageYOffset || document.documentElement.scrollTop || 0;
		},

		/** @return {number} Height of the scrolling viewport. */
		vh: function () {
			if ( Scroll.node ) {
				return Scroll.node.clientHeight || 0;
			}
			return window.innerHeight || document.documentElement.clientHeight || 0;
		},

		/** @return {number} Furthest position the active source can reach. */
		max: function () {
			if ( Scroll.node ) {
				return Math.max( 0, Scroll.node.scrollHeight - Scroll.node.clientHeight );
			}
			return Scroll.documentMax();
		},

		/**
		 * The window's own scrollable distance, asked for regardless of which
		 * source is active. Container resolution needs to know whether the page
		 * behind the container scrolls on its own.
		 *
		 * @return {number}
		 */
		documentMax: function () {
			var doc = document.documentElement;
			var vh = window.innerHeight || doc.clientHeight || 0;

			return Math.max(
				0,
				Math.max( doc.scrollHeight, document.body ? document.body.scrollHeight : 0 ) - vh
			);
		},

		/** @return {number} Top edge of the scrolling viewport, in client coordinates. */
		top: function () {
			return Scroll.node ? Scroll.node.getBoundingClientRect().top : 0;
		},

		/**
		 * Where an element sits in the active scroll coordinate system.
		 *
		 * @param {Element} node Element to locate.
		 * @return {number}
		 */
		offsetOf: function ( node ) {
			return node.getBoundingClientRect().top - Scroll.top() + Scroll.y();
		},

		/** @return {Window|Element} The object scroll events are dispatched on. */
		target: function () {
			return Scroll.node || window;
		},

		/**
		 * Whether this browser accepts behavior:"instant" in ScrollToOptions.
		 * -1 until the first write answers the question, then 1 or 0. Asking
		 * costs one try/catch on the very first programmatic scroll of the
		 * session and nothing afterwards.
		 */
		instant: -1,

		/**
		 * Jumps straight to a position, with no animation of any kind.
		 *
		 * This is the immediate setter, and it is deliberately explicit. The
		 * shorthand forms - window.scrollTo( x, y ) and the scrollTop setter -
		 * carry no behaviour of their own, so the browser falls back to the
		 * scrolling element's computed scroll-behavior. On a site whose theme
		 * sets html { scroll-behavior: smooth } that turns every one of these
		 * writes into a second, browser-owned smooth animation running
		 * underneath Smart Motion, and the page keeps settling long after the
		 * plugin believes it has arrived. Naming the behaviour takes the
		 * decision away from CSS.
		 *
		 * @param {number} value Destination in the active coordinate system.
		 */
		to: function ( value ) {
			var source = Scroll.target();

			if ( 0 !== Scroll.instant && source.scrollTo ) {
				try {
					// left is omitted on purpose: the spec keeps the current
					// horizontal position, which saves a geometry read.
					source.scrollTo( { top: value, behavior: 'instant' } );
					Scroll.instant = 1;
					return;
				} catch ( e ) {
					// Browsers that predate the instant keyword reject the
					// dictionary outright, before scrolling anything.
					Scroll.instant = 0;
				}
			}

			Scroll.write( value );
		},

		/**
		 * The shorthand write, for browsers that support CSS scroll-behavior
		 * but not the instant keyword - roughly Chrome 61-96 and Firefox
		 * 36-101. There the behaviour is pinned inline instead.
		 *
		 * Restoration needs no lifecycle of its own. The property is set, the
		 * page is moved, and the property is put back inside one synchronous
		 * statement sequence, with the release in a finally block so a throw
		 * cannot leave it behind. Nothing yields in between, so no completion,
		 * abort or error path can ever observe the pin still applied.
		 *
		 * @param {number} value Destination in the active coordinate system.
		 */
		write: function ( value ) {
			var nodes = Scroll.node
				? [ Scroll.node ]
				: [ document.scrollingElement || document.documentElement, document.body ];
			var saved = Scroll.pin( nodes );

			try {
				if ( Scroll.node ) {
					Scroll.node.scrollTop = value;
				} else {
					window.scrollTo( 0, value );
				}
			} finally {
				Scroll.unpin( nodes, saved );
			}
		},

		/**
		 * Pins scroll-behavior to auto on the scrolling nodes and reports what
		 * was inline before, so unpin() can put back exactly that.
		 *
		 * The declaration is marked important because the rule it has to beat
		 * may be important itself. It lives on the element for the length of
		 * one statement.
		 *
		 * @param {Array} nodes Elements to pin.
		 * @return {Array} What each node had inline beforehand.
		 */
		pin: function ( nodes ) {
			var saved = [];
			var i;

			for ( i = 0; i < nodes.length; i++ ) {
				if ( ! nodes[ i ] ) {
					saved.push( null );
					continue;
				}

				saved.push( {
					value: nodes[ i ].style.getPropertyValue( 'scroll-behavior' ),
					priority: nodes[ i ].style.getPropertyPriority( 'scroll-behavior' )
				} );

				nodes[ i ].style.setProperty( 'scroll-behavior', 'auto', 'important' );
			}

			return saved;
		},

		/**
		 * Puts back the declaration pin() found: the same value at the same
		 * priority, or none at all where there was none.
		 *
		 * An element whose style object has been touched keeps an empty style
		 * attribute afterwards - the engine reflects the declaration back and
		 * removeAttribute() does not survive it. That attribute carries no
		 * declaration and changes no computed value, and only ever appears on
		 * the legacy path: a browser that accepts the instant keyword never
		 * reaches pin() at all.
		 *
		 * @param {Array} nodes Elements to release.
		 * @param {Array} saved What pin() reported.
		 */
		unpin: function ( nodes, saved ) {
			var was;
			var i;

			for ( i = 0; i < nodes.length; i++ ) {
				was = saved[ i ];

				if ( ! was ) {
					continue;
				}

				nodes[ i ].style.removeProperty( 'scroll-behavior' );

				if ( was.value ) {
					nodes[ i ].style.setProperty( 'scroll-behavior', was.value, was.priority );
				}
			}
		},

		/**
		 * Asks the browser for its own smooth scroll on the active source.
		 *
		 * Smooth motion is the one mode that wants the browser's animation, so
		 * this setter names smooth just as deliberately as to() names instant.
		 * A theme that already asked for smooth scrolling changes nothing here,
		 * and a theme that did not is still honoured: the mode was chosen in
		 * the plugin's own settings.
		 *
		 * @param {number} value Destination in the active coordinate system.
		 */
		smoothTo: function ( value ) {
			var source = Scroll.target();

			if ( source.scrollTo ) {
				source.scrollTo( { top: value, behavior: 'smooth' } );
				return;
			}

			Scroll.to( value );
		},

		/**
		 * Chooses the scroll source for this page. Run once at start-up, and
		 * never re-run on a whim: a source that changed under the visitor
		 * mid-session would make every stored position meaningless.
		 */
		resolve: function () {
			Scroll.node = null;
			Scroll.fallback = false;

			if ( 'window' === CFG.container ) {
				return;
			}

			if ( 'custom' === CFG.container ) {
				Scroll.node = Scroll.fromSelector( CFG.containerSel );
				Scroll.fallback = null === Scroll.node;
				return;
			}

			Scroll.node = Scroll.detect();
		},

		/**
		 * Resolves the configured Custom Scroll Container.
		 *
		 * Returns null for anything unusable, which sends the adapter back to
		 * the window. A wrong selector has to degrade to ordinary page
		 * scrolling, never to a control that silently does nothing.
		 *
		 * @param {string} selector Configured selector.
		 * @return {Element|null}
		 */
		fromSelector: function ( selector ) {
			var node;

			if ( ! selector ) {
				return null;
			}

			try {
				node = document.querySelector( selector );
			} catch ( e ) {
				return null;
			}

			if ( ! node || 1 !== node.nodeType ) {
				return null;
			}

			// Pointing at the document itself is window scrolling by another
			// name, and the window path is the better tested one.
			if ( node === document.documentElement || node === document.body ) {
				return null;
			}

			// Not rendered: nothing can scroll inside it.
			if ( ! node.getClientRects().length || node.clientHeight <= 0 ) {
				return null;
			}

			// A container with no overflow at all, on a page whose document
			// scrolls perfectly well, is almost always a mistyped selector.
			if (
				node.scrollHeight - node.clientHeight < 1 &&
				Scroll.documentMax() >= CFG.minScroll
			) {
				return null;
			}

			return node;
		},

		/**
		 * Auto: find the element that is genuinely responsible for scrolling
		 * this page, or return null and let the window handle it.
		 *
		 * The first test does most of the work. If the document scrolls at all,
		 * the window is the answer and nothing else is even considered, which is
		 * the correct outcome for effectively every WordPress site. Only an
		 * app-shell layout - one that pins the document and scrolls an inner
		 * element - gets past it.
		 *
		 * A wrong guess here is worse than no guess, so a candidate must be a
		 * genuinely page-sized, genuinely scrollable, genuinely overflowing
		 * element that starts near the top of the viewport. Dropdowns, menus,
		 * sidebars, carousels and modal interiors all fail at least one of
		 * those tests, usually the coverage one.
		 *
		 * @return {Element|null}
		 */
		detect: function () {
			var body = document.body;
			var vh = window.innerHeight || document.documentElement.clientHeight || 0;
			var vw = window.innerWidth || document.documentElement.clientWidth || 0;
			var visited = 0;
			var best = null;
			var bestArea = 0;
			var queue;
			var item;
			var children;
			var area;
			var i;

			if ( Scroll.documentMax() >= CFG.minScroll ) {
				return null;
			}

			if ( ! body || ! body.children || vh <= 0 || vw <= 0 ) {
				return null;
			}

			queue = [ { node: body, depth: 0 } ];

			while ( queue.length && visited < AUTO_MAX_NODES ) {
				item = queue.shift();

				if ( item.node !== body ) {
					visited++;

					if ( Scroll.isPageScroller( item.node, vh, vw ) ) {
						area = item.node.clientHeight * item.node.clientWidth;

						if ( area > bestArea ) {
							best = item.node;
							bestArea = area;
						}

						// An accepted scroller owns everything inside it, so
						// its descendants are not candidates.
						continue;
					}
				}

				if ( item.depth < AUTO_MAX_DEPTH ) {
					children = item.node.children;

					for ( i = 0; i < children.length && i < AUTO_MAX_CHILDREN; i++ ) {
						queue.push( { node: children[ i ], depth: item.depth + 1 } );
					}
				}
			}

			return best;
		},

		/**
		 * Whether one element looks like the page's primary scroll container.
		 *
		 * @param {Element} node Candidate.
		 * @param {number}  vh   Viewport height.
		 * @param {number}  vw   Viewport width.
		 * @return {boolean}
		 */
		isPageScroller: function ( node, vh, vw ) {
			var overflow;

			if ( 'khstt' === node.id || 'wpadminbar' === node.id ) {
				return false;
			}

			if ( node.scrollHeight - node.clientHeight < CFG.minScroll ) {
				return false;
			}

			if (
				node.clientHeight < vh * AUTO_MIN_COVERAGE ||
				node.clientWidth < vw * AUTO_MIN_COVERAGE
			) {
				return false;
			}

			if ( node.getBoundingClientRect().top > vh * AUTO_MAX_TOP ) {
				return false;
			}

			overflow = window.getComputedStyle( node ).overflowY;

			return 'auto' === overflow || 'scroll' === overflow || 'overlay' === overflow;
		},

		/**
		 * Drops a container that has been taken out of the page, so a route
		 * change in a single page application cannot leave the adapter reading
		 * a detached element for the rest of the session.
		 *
		 * @return {boolean} Whether the source changed.
		 */
		revalidate: function () {
			if ( ! Scroll.node ) {
				return false;
			}

			if ( document.contains && document.contains( Scroll.node ) ) {
				return false;
			}

			Scroll.node = null;
			Scroll.fallback = true;

			return true;
		}
	};

	/* =====================================================================
	   Measure
	   ================================================================== */

	var Measure = {
		/** Recomputes everything that only changes on resize or reflow. */
		all: function () {
			// A container that has left the page hands scrolling back to the
			// window, and the listener has to move with it.
			if ( Scroll.revalidate() ) {
				bindScroll();
			}

			state.vh = Scroll.vh();
			state.maxScroll = Scroll.max();

			// The control is fixed to the browser viewport whatever is
			// scrolling, so the footer dock measures against the screen rather
			// than against the scroll source.
			state.screenH = window.innerHeight || document.documentElement.clientHeight || 0;

			// getClientRects() is empty only for display:none, which is exactly
			// how the Visibility tab switches the control off per breakpoint.
			state.active = el.getClientRects().length > 0;
			state.tooShort = state.maxScroll < CFG.minScroll;

			state.threshold = 'custom' === CFG.trigger
				? CFG.triggerOffset
				: state.vh;

			state.bottomOffset = parseFloat( window.getComputedStyle( el ).bottom ) || 0;

			Ring.sync();
			Progress.resolve();
			Dock.resolve();
			Relevance.resolve();
		}
	};

	/* =====================================================================
	   Ring geometry
	   ================================================================== */

	var Ring = {
		length: 0,
		shift: 0,

		/**
		 * Corner radius for the ring path, mirroring the stylesheet's shapes.
		 * The stroke is centred on the path, so the radius is pulled in by half
		 * a stroke to keep the ring inside the button's edge.
		 *
		 * @param {number} size      Measured ring box size.
		 * @param {number} thickness Stroke width.
		 * @return {number}
		 */
		radius: function ( size, thickness ) {
			var shape = el.getAttribute( 'data-shape' );
			var base;

			if ( 'circle' === shape ) {
				base = size / 2;
			} else if ( 'rounded' === shape ) {
				base = size * 0.28;
			} else {
				base = 4;
			}

			return Math.max( 0, base - thickness / 2 );
		},

		/**
		 * Writes the ring rectangles for the current rendered size.
		 *
		 * The box is measured rather than assumed so a border, a responsive size
		 * change or a theme's own box model can never desynchronise the stroke
		 * from the button it wraps.
		 */
		sync: function () {
			if ( ! ring || ! track || ! bar ) {
				return;
			}

			// getBoundingClientRect() reports the *transformed* box, and the
			// control carries a scale transform whenever it is hidden or
			// peeking - which is exactly the state it is in the first time this
			// runs. The SVG's user units are its untransformed layout box, so
			// measuring the visual box drew the ring about 8% small and
			// off-centre, and nothing re-measured it once the control scaled
			// back up. Computed style reports the layout size and ignores
			// transforms, so it stays correct in every reveal state.
			var inner = parseFloat( window.getComputedStyle( ring ).width );

			// Should a browser ever decline to report a used width for an SVG
			// box, the painted box is a workable second best: slightly off
			// while the control is mid-transition, but drawn rather than
			// missing.
			if ( ! inner || inner <= 0 ) {
				inner = ring.getBoundingClientRect().width;
			}

			if ( ! inner || inner <= 0 ) {
				return;
			}

			var thickness = parseFloat(
				window.getComputedStyle( el ).getPropertyValue( '--khstt-ring-t' )
			) || 3;

			// The SVG fills the button's padding box, so a border sits outside
			// it. Growing the geometry by the border width puts the stroke back
			// on the button's outer edge - the same edge the CSS draws the
			// shape on - instead of floating inside it.
			var border = parseFloat( window.getComputedStyle( btn ).borderTopWidth ) || 0;
			var outer = inner + 2 * border;

			var side = Math.max( 1, outer - thickness );
			var radius = Math.min( side / 2, Ring.radius( outer, thickness ) );
			var straight = Math.max( 0, side - 2 * radius );

			Ring.length = 4 * straight + 2 * Math.PI * radius;

			// The rect path starts at (x + radius, y). Shifting the dash pattern
			// by the remaining distance to the top centre makes progress start
			// from twelve o'clock for every shape, not just the circle.
			Ring.shift = side / 2 - radius;

			[ track, bar ].forEach( function ( rect ) {
				rect.setAttribute( 'x', thickness / 2 - border );
				rect.setAttribute( 'y', thickness / 2 - border );
				rect.setAttribute( 'width', side );
				rect.setAttribute( 'height', side );
				rect.setAttribute( 'rx', radius );
				rect.setAttribute( 'stroke-width', thickness );
			} );

			bar.setAttribute( 'stroke-dasharray', Ring.length );

			written.progress = -1;
		},

		/**
		 * Points the stroke at a new progress value.
		 *
		 * @param {number} value Progress from 0 to 1.
		 */
		draw: function ( value ) {
			if ( ! bar || Ring.length <= 0 ) {
				return;
			}
			bar.setAttribute( 'stroke-dashoffset', Ring.length * ( 1 - value ) - Ring.shift );
		}
	};

	/* =====================================================================
	   Progress
	   ================================================================== */

	var Progress = {
		start: 0,
		height: 0,
		useContent: false,

		/**
		 * Locates the content region for Smart Content and caches its absolute
		 * bounds. Falls back to whole-page progress whenever the region is
		 * missing or too small for the reading measurement to mean anything.
		 */
		resolve: function () {
			Progress.useContent = false;

			if ( ! CFG.progress || 'content' !== CFG.progressScope ) {
				return;
			}

			var node = findContent( CFG.progSelector );

			if ( ! node ) {
				return;
			}

			var box = node.getBoundingClientRect();

			// Shorter than the viewport means the reader can see all of it at
			// once, so a reading percentage would be meaningless.
			if ( box.height < state.vh ) {
				return;
			}

			Progress.start = Scroll.offsetOf( node );
			Progress.height = box.height;
			Progress.useContent = true;
		},

		/**
		 * Progress from 0 to 1 for the current scroll position.
		 *
		 * @param {number} y Current scroll position.
		 * @return {number}
		 */
		value: function ( y ) {
			if ( Progress.useContent && Progress.height > 0 ) {
				return clamp( ( y + state.vh - Progress.start ) / Progress.height, 0, 1 );
			}

			if ( state.maxScroll <= 0 ) {
				return 0;
			}

			return clamp( y / state.maxScroll, 0, 1 );
		}
	};

	/* =====================================================================
	   Reveal
	   ================================================================== */

	var Reveal = {
		/**
		 * Decides the reveal state for a scroll position.
		 *
		 * Keyboard focus and an open Smart Return offer both pin the control in
		 * place: hiding a control the visitor has just focused, or has just been
		 * offered, would strand them.
		 *
		 * @param {number} y Current scroll position.
		 * @return {string} "1", "0", or "peek".
		 */
		stateFor: function ( y ) {
			if ( state.focused || Return.active ) {
				return '1';
			}

			if ( state.tooShort || y < state.threshold ) {
				return '0';
			}

			if ( ! CFG.smartReveal ) {
				return '1';
			}

			if ( state.revealed ) {
				return '1';
			}

			return CFG.peek ? 'peek' : '0';
		},

		/**
		 * Updates the tracked scroll direction, ignoring deltas small enough to
		 * be a trackpad tremor so the control cannot flicker.
		 *
		 * @param {number} y Current scroll position.
		 */
		track: function ( y ) {
			var delta = y - state.lastY;

			if ( Math.abs( delta ) < DIRECTION_TOLERANCE ) {
				return;
			}

			state.revealed = delta < 0;
			state.lastY = y;
		}
	};

	/* =====================================================================
	   Destination
	   ================================================================== */

	var Destination = {
		/**
		 * Resolves the scroll target, in the active scroll coordinate system.
		 *
		 * Content Start is resolved at activation time rather than cached, so a
		 * page whose layout has shifted since load still lands correctly. If the
		 * resolved target is not above the visitor, the page top is used
		 * instead: a "scroll to top" control must never scroll downward.
		 *
		 * @param {number} y Current scroll position.
		 * @return {number}
		 */
		resolve: function ( y ) {
			if ( 'content_start' !== CFG.destination ) {
				return 0;
			}

			var node = findContent( CFG.destSelector );

			if ( ! node ) {
				return 0;
			}

			var target = Math.max( 0, Scroll.offsetOf( node ) - CONTENT_OFFSET );

			return target < y ? target : 0;
		}
	};

	/* =====================================================================
	   Motion
	   ================================================================== */

	var Motion = {
		frame: 0,

		/**
		 * Smart Motion duration: proportional to the square root of the
		 * distance, so a short hop stays snappy and a very long page still
		 * finishes promptly instead of crawling.
		 *
		 * @param {number} distance Pixels to travel.
		 * @return {number} Duration in milliseconds.
		 */
		duration: function ( distance ) {
			return clamp( 14 * Math.sqrt( distance ), MOTION_MIN, MOTION_MAX );
		},

		/**
		 * Scrolls to a target position using the configured motion, then calls
		 * back once the page has settled.
		 *
		 * @param {number}   target Destination in the active coordinate system.
		 * @param {Function} done   Called after the scroll settles.
		 */
		to: function ( target, done ) {
			var from = Scroll.y();
			var distance = Math.abs( target - from );

			state.programmatic = true;

			if ( reduceMotion.matches || 'instant' === CFG.motion || distance < 1 ) {
				Scroll.to( target );
				Motion.settle( 0, done );
				return;
			}

			if ( 'smooth' === CFG.motion ) {
				Scroll.smoothTo( target );
				Motion.settle( 1500, done );
				return;
			}

			Motion.animate( from, target, Motion.duration( distance ), done );
		},

		/**
		 * Smart Motion: an eased scroll driven by requestAnimationFrame.
		 *
		 * @param {number}   from     Starting position.
		 * @param {number}   target   Destination.
		 * @param {number}   duration Duration in milliseconds.
		 * @param {Function} done     Called on arrival.
		 */
		animate: function ( from, target, duration, done ) {
			var startedAt = 0;
			var cancelled = false;

			function abort() {
				cancelled = true;
			}

			// These listeners exist only while an animation is in flight, so an
			// idle page carries no extra input handlers.
			window.addEventListener( 'wheel', abort, { passive: true } );
			window.addEventListener( 'touchstart', abort, { passive: true } );

			cancelAnimationFrame( Motion.frame );

			Motion.frame = requestAnimationFrame( function step( now ) {
				if ( ! startedAt ) {
					startedAt = now;
				}

				var elapsed = Math.min( 1, ( now - startedAt ) / duration );

				if ( ! cancelled ) {
					Scroll.to( from + ( target - from ) * easeInOutCubic( elapsed ) );
				}

				if ( elapsed < 1 && ! cancelled ) {
					Motion.frame = requestAnimationFrame( step );
					return;
				}

				window.removeEventListener( 'wheel', abort );
				window.removeEventListener( 'touchstart', abort );

				Motion.settle( 0, cancelled ? null : done );
			} );
		},

		/**
		 * Clears the programmatic flag once scrolling has stopped, so the
		 * direction tracker and Smart Return never mistake the plugin's own
		 * scroll for the visitor moving the page.
		 *
		 * Native smooth scrolling has no fixed duration, so the scrollend event
		 * is preferred where the browser supports it and the timeout acts only
		 * as a ceiling. Settling early there would capture the Smart Return
		 * anchor mid-flight, and the rest of the scroll would then look like the
		 * visitor navigating away.
		 *
		 * @param {number}   ceiling Longest time to wait, in milliseconds.
		 * @param {Function} done    Called once settled.
		 */
		settle: function ( ceiling, done ) {
			var finished = false;
			var source = Scroll.target();
			var timer;

			function finish() {
				if ( finished ) {
					return;
				}

				finished = true;
				window.clearTimeout( timer );
				source.removeEventListener( 'scrollend', finish );

				state.lastY = Scroll.y();
				state.programmatic = false;

				if ( done ) {
					done();
				}
			}

			timer = window.setTimeout( finish, ceiling );

			if ( ceiling > 0 && 'onscrollend' in window ) {
				source.addEventListener( 'scrollend', finish );
			}
		}
	};

	/* =====================================================================
	   Smart Return
	   ================================================================== */

	var Return = {
		active: false,
		target: 0,
		anchor: 0,
		timer: 0,
		restingLabel: '',

		/** Distance below which a jump is too short to be worth offering a return. */
		minDistance: function () {
			return Math.max( 600, state.vh * 1.5 );
		},

		/**
		 * Offers the return, turning the same control into a Return action.
		 *
		 * @param {number} from Position the visitor jumped away from.
		 */
		arm: function ( from ) {
			Return.active = true;
			Return.target = from;
			Return.anchor = Scroll.y();

			el.setAttribute( 'data-return', '1' );
			btn.setAttribute( 'aria-label', CFG.i18n.back );
			Label.set( true );

			// An offer that is standing is the opposite of an idle control.
			Idle.poke();

			if ( status ) {
				status.textContent = CFG.i18n.backAvailable;
			}

			window.clearTimeout( Return.timer );
			Return.timer = window.setTimeout( function () {
				Return.disarm();
				schedule();
			}, RETURN_TIMEOUT );
		},

		/** Withdraws the offer and restores the control's resting state. */
		disarm: function () {
			if ( ! Return.active ) {
				return;
			}

			Return.active = false;

			el.removeAttribute( 'data-return' );
			btn.setAttribute( 'aria-label', Return.restingLabel );
			Label.set( false );
			Idle.poke();

			if ( status ) {
				status.textContent = '';
			}

			window.clearTimeout( Return.timer );
			written.visible = null;
		},

		/**
		 * Withdraws the offer once the visitor starts navigating on their own.
		 * Half a viewport of manual scrolling is a clear signal they are no
		 * longer interested in going back.
		 *
		 * @param {number} y Current scroll position.
		 */
		checkCancel: function ( y ) {
			if ( ! Return.active || state.programmatic ) {
				return;
			}

			if ( Math.abs( y - Return.anchor ) > state.vh * 0.5 ) {
				Return.disarm();
			}
		}
	};

	/* =====================================================================
	   KineticPower
	   ================================================================== */

	/**
	 * The activation choreography, if the site selected one.
	 *
	 * This is decoration and nothing else. It owns no scrolling, no listener,
	 * no frame loop and no observer: it is driven entirely by the two moments
	 * the existing lifecycle already knows about - the press, and the arrival -
	 * and by a handful of timers that exist only while a sequence is running.
	 *
	 * The whole module is inert unless a power is selected. PHP sends false for
	 * CFG.power in that case, so the default configuration pays for one
	 * truthiness check at start-up and nothing else for the rest of the session.
	 *
	 * Rapid input policy: a fresh press restarts the sequence from ignition.
	 * Queueing would leave the visual running long after the page had settled,
	 * and ignoring the press would make an impatient second click feel dead.
	 */
	var Power = {
		/**
		 * The selected power's runtime configuration, or false. Every phase
		 * duration comes from here, which is KHSTT_Powers::timings() in PHP -
		 * the one place the numbers live for the engine, the live preview and
		 * the stylesheet alike.
		 */
		cfg: false,

		/** Whether a power is selected and its stage is in the DOM. */
		on: false,

		/** Current phase: "", "launch", "travel" or "arrival". */
		phase: '',

		/** When the running sequence started, so arrival can wait its turn. */
		startedAt: 0,

		/**
		 * Three independent timers.
		 *
		 * travelTimer moves launch on to travel and is deliberately separate
		 * from timer: an instant scroll reports its arrival before the rocket
		 * has left the button, and sharing one handle there would cancel the
		 * flight rather than schedule the spark after it.
		 */
		travelTimer: 0,
		timer: 0,
		ceiling: 0,

		/** Reads the configuration once. */
		init: function () {
			Power.cfg = CFG.power || false;
			Power.on = !! Power.cfg && !! el.querySelector( '.khstt__power' );
		},

		/**
		 * Whether this activation deserves a choreography.
		 *
		 * Reduced motion is read here rather than cached, so a visitor who
		 * changes the system setting mid-session is honoured on their very next
		 * press. A press with nowhere meaningful to go gets nothing: the
		 * sequence is a response to travelling, not to clicking.
		 *
		 * @param {number} travelled Upward distance this activation will cover.
		 * @return {boolean}
		 */
		wants: function ( travelled ) {
			return Power.on
				&& ! reduceMotion.matches
				&& travelled >= Power.cfg.minTravel;
		},

		/**
		 * Writes the phase attribute the stylesheet keys off.
		 *
		 * @param {string} phase Phase name, or "" for the resting state.
		 */
		set: function ( phase ) {
			if ( Power.phase === phase ) {
				return;
			}

			Power.phase = phase;

			if ( '' === phase ) {
				el.removeAttribute( 'data-khstt-power-state' );
				return;
			}

			el.setAttribute( 'data-khstt-power-state', phase );
		},

		/** Ignition. Called on the press, never waited on by the scroll. */
		launch: function () {
			Power.clear();

			Power.set( '' );

			// Reading a layout property flushes that removal, so a second press
			// restarts the launch keyframes instead of continuing the first
			// run. Nothing is measured here and nothing is written: the read is
			// the entire point.
			void el.offsetWidth;

			Power.startedAt = Date.now();
			Power.set( 'launch' );

			Power.travelTimer = window.setTimeout( function () {
				Power.set( 'travel' );
			}, Power.cfg.launch );

			// Not every scroll reports an arrival. Smart Motion abandons its
			// callback when the visitor takes the page back mid-flight, and a
			// container can leave the document entirely. One timer guarantees
			// the control returns to its resting state whatever happens.
			Power.ceiling = window.setTimeout( Power.reset, Power.cfg.ceiling );
		},

		/**
		 * The scroll has settled.
		 *
		 * An instant scroll settles before the rocket has left the button, so
		 * the spark waits for the flight to read as a flight. Nothing is
		 * blocked by that wait: the page is already where it was going.
		 */
		arrive: function () {
			if ( '' === Power.phase ) {
				return;
			}

			window.clearTimeout( Power.timer );

			var due = Power.cfg.launch + Power.cfg.travelMin - ( Date.now() - Power.startedAt );

			Power.timer = window.setTimeout( Power.spark, Math.max( 0, due ) );
		},

		/** The arrival accent, then the resting state. */
		spark: function () {
			if ( '' === Power.phase ) {
				return;
			}

			window.clearTimeout( Power.travelTimer );
			window.clearTimeout( Power.ceiling );

			Power.set( 'arrival' );
			Power.timer = window.setTimeout( Power.reset, Power.cfg.arrival );
		},

		/**
		 * Returns the control to its resting state and cancels everything
		 * pending. Safe to call at any point, from any phase, including none.
		 */
		reset: function () {
			Power.clear();
			Power.set( '' );

			// A sequence ends on a timer, not on a frame, so nothing else would
			// restart the idle clock until the reader scrolled again.
			Idle.poke();
		},

		/** Cancels every pending phase change. */
		clear: function () {
			window.clearTimeout( Power.travelTimer );
			window.clearTimeout( Power.timer );
			window.clearTimeout( Power.ceiling );
		}
	};

	/* =====================================================================
	   Interaction polish
	   ================================================================== */

	/**
	 * Smart Idle Fade.
	 *
	 * Softens a control that is already legitimately on screen once the reader
	 * has stopped interacting with the page. It is not a reveal state and it
	 * never hides anything: Smart Reveal, Peek and the device rules decide
	 * whether the control is there at all, and this only ever adjusts one that
	 * already is.
	 *
	 * There is no listener of its own. Every frame the engine runs for any
	 * reason - a scroll, a resize, a focus change, an observer - is an
	 * interaction, and pokes the timer. When the page goes quiet no frames run
	 * at all, which is precisely when the timer should be allowed to expire.
	 * The two pointer listeners on the button are the only additions, and only
	 * when the feature is switched on.
	 *
	 * Pointer movement across the wider page is deliberately not watched. A
	 * global pointermove handler is the most expensive listener a page can
	 * carry, and scrolling already covers a reader who is doing anything with
	 * the page at all.
	 */
	var Idle = {
		on: false,
		idle: false,
		hovered: false,
		timer: 0,

		init: function () {
			Idle.on = !! CFG.idleFade;
		},

		/**
		 * Whether the control is in a state that may soften at all.
		 *
		 * Every one of these is a higher-priority state that must show the
		 * control at full strength: it is away or peeking, the reader has it
		 * focused or under the pointer, Smart Return is offering something, or
		 * a KineticPower is mid-sequence.
		 *
		 * @return {boolean}
		 */
		allowed: function () {
			return Idle.on
				&& '1' === written.visible
				&& ! state.focused
				&& ! Idle.hovered
				&& ! Return.active
				&& '' === Power.phase;
		},

		/** Wakes the control and restarts the clock. */
		poke: function () {
			if ( ! Idle.on ) {
				return;
			}

			Idle.wake();
			window.clearTimeout( Idle.timer );

			if ( ! Idle.allowed() ) {
				return;
			}

			Idle.timer = window.setTimeout( Idle.fade, CFG.idleAfter );
		},

		/** Softens the control, if nothing has claimed it in the meantime. */
		fade: function () {
			if ( ! Idle.allowed() ) {
				return;
			}

			Idle.idle = true;
			el.setAttribute( 'data-idle', '1' );
		},

		/** Restores full presence. Safe to call when already awake. */
		wake: function () {
			if ( ! Idle.idle ) {
				return;
			}

			Idle.idle = false;
			el.removeAttribute( 'data-idle' );
		}
	};

	/**
	 * Micro press feedback.
	 *
	 * A brief compression of the glyph layer so a press feels answered. Not a
	 * setting: a control that does not react to being pressed reads as broken
	 * rather than as restrained.
	 *
	 * It never runs alongside a KineticPower - the launch recoil is the same
	 * gesture, and playing both would stack two recoils on one press.
	 */
	var Press = {
		timer: 0,

		/** @param {boolean} boosting Whether a KineticPower is taking this press. */
		fire: function ( boosting ) {
			if ( boosting || reduceMotion.matches ) {
				return;
			}

			window.clearTimeout( Press.timer );
			el.setAttribute( 'data-press', '1' );

			Press.timer = window.setTimeout( function () {
				el.removeAttribute( 'data-press' );
			}, CFG.pressMs );
		}
	};

	/**
	 * The hover and focus label.
	 *
	 * Showing and hiding it is entirely CSS - a sibling combinator off the
	 * button's hover and focus-visible states - so all this owns is the one
	 * thing CSS cannot know: which action the control is currently offering.
	 */
	var Label = {
		node: null,
		resting: '',

		init: function () {
			Label.node = el.querySelector( '.khstt__label' );

			if ( Label.node ) {
				Label.resting = Label.node.textContent;
			}
		},

		/**
		 * @param {boolean} returning Whether Smart Return is being offered.
		 */
		set: function ( returning ) {
			if ( ! Label.node ) {
				return;
			}

			var text = returning ? CFG.i18n.labelReturn : Label.resting;

			if ( Label.node.textContent !== text ) {
				Label.node.textContent = text;
			}
		}
	};

	/* =====================================================================
	   Smart Footer Dock
	   ================================================================== */

	var Dock = {
		node: null,
		near: false,
		observer: null,

		/** Finds the footer once, and watches for it entering the viewport. */
		resolve: function () {
			if ( ! CFG.footerDock || Dock.node ) {
				return;
			}

			var selectors = [ 'footer', '.site-footer', '#colophon' ];
			var i;
			var candidate;

			for ( i = 0; i < selectors.length; i++ ) {
				candidate = document.querySelector( selectors[ i ] );
				if ( candidate && candidate.getClientRects().length ) {
					Dock.node = candidate;
					break;
				}
			}

			if ( ! Dock.node ) {
				return;
			}

			// Without IntersectionObserver the gate simply stays open: the
			// geometry read then runs every frame, which is the slower path but
			// keeps the feature working rather than silently doing nothing.
			if ( ! ( 'IntersectionObserver' in window ) ) {
				Dock.near = true;
				return;
			}

			// The observer is only a gate: it says whether measuring the footer
			// this frame is worth doing at all, so the geometry read never runs
			// while the footer is nowhere near the viewport.
			Dock.observer = new IntersectionObserver( function ( entries ) {
				Dock.near = entries[ 0 ].isIntersecting;
				schedule();
			} );

			Dock.observer.observe( Dock.node );
		},

		/**
		 * How far the control must lift to clear the footer.
		 *
		 * Computed from the control's untransformed bottom edge rather than from
		 * its live rectangle, so the value cannot chase its own transform.
		 *
		 * @return {number} Offset in pixels.
		 */
		offset: function () {
			if ( ! Dock.near || ! Dock.node ) {
				return 0;
			}

			var footerTop = Dock.node.getBoundingClientRect().top;
			var restingBottom = state.screenH - state.bottomOffset;

			return Math.max( 0, restingBottom - footerTop + FOOTER_GAP );
		}
	};

	/* =====================================================================
	   Mixed page and container scrolling
	   ================================================================== */

	/**
	 * Keeps a container-bound control honest on a page that also scrolls.
	 *
	 * When the control follows a custom scroll container, the single scroll
	 * listener lives on that container. Scrolling the page behind it therefore
	 * produces no events at all, and the control sat there wearing whatever
	 * progress the container was last left at - stale rather than wrong, but it
	 * reads as broken.
	 *
	 * The adapter is deliberately not swapped underneath the visitor. Choosing
	 * a source from where the pointer happens to be is fragile, means nothing
	 * on touch, and means nothing for the keyboard. Instead the control stands
	 * down while the area it reports on is off screen, and picks up again -
	 * remeasured - when that area comes back.
	 *
	 * IntersectionObserver does the watching: no polling, no second frame loop,
	 * and no geometry read per scroll frame. Where it is unavailable the guard
	 * simply never engages, which leaves the previous behaviour untouched.
	 */
	var Relevance = {
		observer: null,
		watching: null,
		relevant: true,

		/** How much of the container must be on screen for it to count. */
		MIN_RATIO: 0.15,

		/** Document scroll below this is not meaningful independent scroll. */
		MIN_DOC: 200,

		/**
		 * Whether this is a mixed-scroll page: a container adapter, and a
		 * document that scrolls on its own behind it.
		 *
		 * @return {boolean}
		 */
		applies: function () {
			return !! Scroll.node
				&& 'IntersectionObserver' in window
				&& Scroll.documentMax() > Relevance.MIN_DOC;
		},

		/** Starts, moves, or stops the observer to match the current adapter. */
		resolve: function () {
			var node = Relevance.applies() ? Scroll.node : null;

			if ( node === Relevance.watching ) {
				return;
			}

			if ( Relevance.observer ) {
				Relevance.observer.disconnect();
				Relevance.observer = null;
			}

			Relevance.watching = node;

			if ( ! node ) {
				Relevance.relevant = true;
				return;
			}

			Relevance.observer = new window.IntersectionObserver(
				Relevance.onChange,
				{ threshold: [ 0, Relevance.MIN_RATIO ] }
			);
			Relevance.observer.observe( node );
		},

		/**
		 * @param {Array} entries Observer entries.
		 */
		onChange: function ( entries ) {
			var entry = entries[ entries.length - 1 ];
			var now = entry.isIntersecting && entry.intersectionRatio >= Relevance.MIN_RATIO;

			if ( now === Relevance.relevant ) {
				return;
			}

			Relevance.relevant = now;

			if ( now ) {
				// The container could have been resized, or its content grown,
				// while it was away, so nothing measured earlier is trusted.
				Measure.all();
			}

			schedule();
		},

		/**
		 * Whether the control should be following its container right now.
		 *
		 * A control the visitor has tabbed to is never taken out from under
		 * them: hiding it would drop focus to the top of the document with no
		 * warning. It stays until focus leaves, and the frame that follows the
		 * blur stands it down.
		 *
		 * @return {boolean}
		 */
		ok: function () {
			if ( Relevance.relevant ) {
				return true;
			}

			return !! ( el && el.contains( document.activeElement ) );
		}
	};

	/* =====================================================================
	   Frame loop
	   ================================================================== */

	var ticking = false;

	/** The object the scroll listener is currently attached to. */
	var listening = null;

	/**
	 * Points the single scroll listener at the active source. Calling it again
	 * with the same source is a no-op, so no page can end up with two.
	 */
	function bindScroll() {
		var source = Scroll.target();

		if ( listening === source ) {
			return;
		}

		if ( listening ) {
			listening.removeEventListener( 'scroll', schedule );
		}

		source.addEventListener( 'scroll', schedule, { passive: true } );
		listening = source;
	}

	/** Queues one frame; repeated calls inside a frame collapse into one. */
	function schedule() {
		if ( ! ticking ) {
			ticking = true;
			requestAnimationFrame( run );
		}
	}

	/** The single place the DOM is read and written per frame. */
	function run() {
		ticking = false;

		if ( ! state.active ) {
			return;
		}

		// The container this control reports on is off screen: show nothing
		// rather than a progress value that stopped meaning anything.
		if ( ! Relevance.ok() ) {
			if ( '0' !== written.visible ) {
				el.setAttribute( 'data-visible', '0' );
				written.visible = '0';

				// The control is standing down. A sequence left mid-flight
				// would be waiting to finish behind a hidden button, and would
				// still be there when the container came back. The idle flag
				// goes with it, so the control that returns is a fresh one.
				Power.reset();
				Idle.poke();
			}
			return;
		}

		var y = Scroll.y();
		var visible;
		var progress;
		var percent;
		var dock;

		if ( ! state.programmatic ) {
			Reveal.track( y );
			Return.checkCancel( y );
		}

		visible = Reveal.stateFor( y );
		if ( visible !== written.visible ) {
			el.setAttribute( 'data-visible', visible );
			written.visible = visible;
		}

		// Any frame at all means something happened - a scroll, a resize, a
		// focus change. That is the whole interaction signal Idle needs, and it
		// is why the feature adds no listener of its own.
		Idle.poke();

		if ( CFG.progress ) {
			progress = Progress.value( y );

			if ( Math.abs( progress - written.progress ) > 0.001 ) {
				Ring.draw( progress );
				written.progress = progress;

				percent = Math.round( progress * 100 );

				if ( pct && percent !== written.percent ) {
					pct.textContent = percent + '%';
					written.percent = percent;
				}
			}
		}

		if ( CFG.footerDock ) {
			dock = Dock.offset();

			if ( dock !== written.dock ) {
				el.style.setProperty( '--khstt-dock', dock + 'px' );
				written.dock = dock;
			}
		}
	}

	/* =====================================================================
	   Activation
	   ================================================================== */

	/** Handles a press on the control, in either of its two states. */
	function activate() {
		var y = Scroll.y();

		if ( Return.active ) {
			var back = Return.target;
			Press.fire( false );
			Return.disarm();
			Motion.to( back, schedule );
			return;
		}

		var target = Destination.resolve( y );
		var travelled = y - target;

		// The choreography starts here and the scroll starts on the next line.
		// Nothing waits for the other: one is the page moving, the other is a
		// decoration that happens to be watching.
		var boosting = Power.wants( travelled );

		if ( boosting ) {
			Power.launch();
		}

		// One gesture, one recoil: the launch already is the press feedback.
		Press.fire( boosting );
		Idle.poke();

		Motion.to( target, function () {
			if ( boosting ) {
				Power.arrive();
			}

			if ( CFG.smartReturn && travelled >= Return.minDistance() ) {
				Return.arm( y );
			}

			schedule();
		} );
	}

	/* =====================================================================
	   Helpers
	   ================================================================== */

	/**
	 * Finds a content region, preferring the site owner's selector and then the
	 * documented fallback order. A selector that matches nothing, or that the
	 * browser rejects, simply drops through to the next candidate.
	 *
	 * @param {string} custom Optional configured selector.
	 * @return {Element|null}
	 */
	function findContent( custom ) {
		var candidates = custom ? [ custom ].concat( CONTENT_SELECTORS ) : CONTENT_SELECTORS;
		var i;
		var node;

		for ( i = 0; i < candidates.length; i++ ) {
			try {
				node = document.querySelector( candidates[ i ] );
			} catch ( e ) {
				node = null;
			}

			if ( node && node.getClientRects().length ) {
				return node;
			}
		}

		return null;
	}

	/**
	 * @param {number} value Value to constrain.
	 * @param {number} min   Lower bound.
	 * @param {number} max   Upper bound.
	 * @return {number}
	 */
	function clamp( value, min, max ) {
		return Math.max( min, Math.min( max, value ) );
	}

	/**
	 * @param {number} t Normalised time from 0 to 1.
	 * @return {number} Eased value.
	 */
	function easeInOutCubic( t ) {
		return t < 0.5
			? 4 * t * t * t
			: 1 - Math.pow( -2 * t + 2, 3 ) / 2;
	}

	/* =====================================================================
	   Wiring
	   ================================================================== */

	Return.restingLabel = btn.getAttribute( 'aria-label' ) || '';

	Power.init();
	Idle.init();
	Label.init();

	Scroll.resolve();

	// The compatibility engine, when the site has a reason to load it, reports
	// what the adapter actually resolved to rather than guessing a second time.
	CFG.state = {
		source: Scroll.isWindow() ? 'window' : 'custom',
		fallback: Scroll.fallback,
		mixed: Relevance.applies()
	};

	state.lastY = Scroll.y();

	btn.addEventListener( 'click', activate );

	btn.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && Return.active ) {
			Return.disarm();
			schedule();
		}
	} );

	// Focus pins the control visible so keyboard users are never left pressing
	// a control that has faded out from under them.
	btn.addEventListener( 'focus', function () {
		try {
			state.focused = btn.matches( ':focus-visible' );
		} catch ( e ) {
			state.focused = true;
		}
		schedule();
	} );

	btn.addEventListener( 'blur', function () {
		state.focused = false;
		schedule();
	} );

	// The only listeners Smart Idle Fade adds, and only when it is switched on.
	// A reader whose pointer is resting on the control is interacting with it,
	// and the frame loop cannot see that on its own because hovering scrolls
	// nothing.
	if ( Idle.on ) {
		btn.addEventListener( 'pointerenter', function () {
			Idle.hovered = true;
			Idle.poke();
		} );

		btn.addEventListener( 'pointerleave', function () {
			Idle.hovered = false;
			Idle.poke();
		} );
	}

	bindScroll();

	window.addEventListener( 'resize', function () {
		Measure.all();
		schedule();
	}, { passive: true } );

	// Restoring from the back/forward cache keeps the old geometry otherwise.
	window.addEventListener( 'pageshow', function () {
		Measure.all();
		schedule();
	} );

	// A ResizeObserver on the root element catches the page growing after load,
	// from lazy-loaded images or expanding blocks, without polling the document
	// height on every scroll frame.
	if ( 'ResizeObserver' in window ) {
		observeGrowth();
	}

	/**
	 * Watches for the scrollable extent changing.
	 *
	 * A scroll container with a fixed height never changes size when its own
	 * content grows, so watching the container alone would miss exactly the
	 * event that matters. Its children are watched too, since that is what
	 * actually gets taller when a lazy image finally arrives.
	 */
	function observeGrowth() {
		var observer = new ResizeObserver( function () {
			Measure.all();
			schedule();
		} );
		var children;
		var i;

		observer.observe( document.documentElement );

		if ( Scroll.isWindow() ) {
			return;
		}

		observer.observe( Scroll.node );
		children = Scroll.node.children;

		for ( i = 0; i < children.length && i < RO_MAX_CHILDREN; i++ ) {
			observer.observe( children[ i ] );
		}
	}

	Measure.all();
	schedule();
}() );
