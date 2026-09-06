/**
 * KineticHub Scroll to Top - admin settings screen.
 *
 * Vanilla JS, no dependencies. Responsibilities are kept in separate small
 * modules that share one read of the form state:
 *
 *   Form        - reading current field values by settings key
 *   Tabs        - accessible tab switching and restore-after-save
 *   Conditions  - showing and hiding dependent fields
 *   Pairs       - keeping range/number and swatch/hex inputs in sync
 *   Presets     - applying a Quick Style preset, then stepping aside
 *   Preview     - rendering the live preview from the current form state
 *   PowerPreview- playing the selected KineticPower inside the preview
 *   Polish      - idle fade, micro press and the label inside the preview
 *   ScrollBack  - returning to the same place on the page after a save
 */
( function () {
	'use strict';

	var CFG = window.KHSTT_ADMIN || {};
	var I18N = CFG.i18n || {};
	var PRESETS = CFG.presets || {};
	var ICONS = CFG.icons || {};
	var THUMB = CFG.thumbFloor || { size: 48, offset: 16 };

	var root = document.querySelector( '.khstt-wrap' );
	if ( ! root ) {
		return;
	}

	var form = document.getElementById( 'khstt-form' );

	/* ---------------------------------------------------------------------
	   Form: read the current value of any settings key
	   ------------------------------------------------------------------ */

	var Form = {
		/**
		 * Returns the live value of a settings key as a string.
		 * Checkboxes resolve to "1" or "0"; radio groups to the checked value.
		 *
		 * @param {string} key Settings key.
		 * @return {string} Current value, or an empty string if not present.
		 */
		get: function ( key ) {
			var nodes = root.querySelectorAll( '[data-khstt-key="' + key + '"]' );
			var i;

			if ( ! nodes.length ) {
				return '';
			}

			if ( 'radio' === nodes[ 0 ].type ) {
				for ( i = 0; i < nodes.length; i++ ) {
					if ( nodes[ i ].checked ) {
						return nodes[ i ].value;
					}
				}
				return '';
			}

			if ( 'checkbox' === nodes[ 0 ].type ) {
				return nodes[ 0 ].checked ? '1' : '0';
			}

			return nodes[ 0 ].value;
		},

		/**
		 * Returns a settings key as a number, falling back when unparseable.
		 *
		 * @param {string} key      Settings key.
		 * @param {number} fallback Value to use when the field is empty or invalid.
		 * @return {number}
		 */
		num: function ( key, fallback ) {
			var value = parseInt( Form.get( key ), 10 );
			return isNaN( value ) ? fallback : value;
		},

		/**
		 * Returns whether a boolean settings key is on.
		 *
		 * @param {string} key Settings key.
		 * @return {boolean}
		 */
		bool: function ( key ) {
			return '1' === Form.get( key );
		},

		/**
		 * Writes a value into a field without firing the user-input path.
		 *
		 * @param {string} key   Settings key.
		 * @param {*}      value New value.
		 */
		set: function ( key, value ) {
			var nodes = root.querySelectorAll( '[data-khstt-key="' + key + '"]' );
			var i;

			if ( ! nodes.length ) {
				return;
			}

			if ( 'radio' === nodes[ 0 ].type ) {
				for ( i = 0; i < nodes.length; i++ ) {
					nodes[ i ].checked = ( nodes[ i ].value === String( value ) );
				}
				return;
			}

			if ( 'checkbox' === nodes[ 0 ].type ) {
				nodes[ 0 ].checked = ( true === value || '1' === value || 1 === value );
				return;
			}

			nodes[ 0 ].value = value;
			Pairs.syncFrom( nodes[ 0 ] );
		}
	};

	/* ---------------------------------------------------------------------
	   Tabs
	   ------------------------------------------------------------------ */

	var Tabs = {
		buttons: [],
		STORAGE_KEY: 'khstt_active_tab',

		/** The tab on screen right now. Empty until the first activate(). */
		current: '',

		init: function () {
			var list = root.querySelector( '.khstt-tabs' );
			if ( ! list ) {
				return;
			}

			Tabs.buttons = Array.prototype.slice.call( list.querySelectorAll( '[data-khstt-tab]' ) );

			if ( ! Tabs.buttons.length ) {
				return;
			}

			Tabs.buttons.forEach( function ( button ) {
				button.addEventListener( 'click', function () {
					Tabs.activate( button.getAttribute( 'data-khstt-tab' ), true );
				} );
			} );

			list.addEventListener( 'keydown', Tabs.onKeydown );

			Tabs.activate( Tabs.restore(), false );
		},

		/**
		 * Returns the tab that should open on load: the URL hash wins so a
		 * shared link lands in the right place, then the tab remembered from
		 * before the last save, then the first tab.
		 *
		 * @return {string} Tab id.
		 */
		restore: function () {
			var fromHash = window.location.hash.replace( '#khstt-tab-', '' );
			var stored = '';

			if ( fromHash && Tabs.exists( fromHash ) ) {
				return fromHash;
			}

			try {
				stored = window.sessionStorage.getItem( Tabs.STORAGE_KEY ) || '';
			} catch ( e ) {
				stored = '';
			}

			return Tabs.exists( stored ) ? stored : Tabs.buttons[ 0 ].getAttribute( 'data-khstt-tab' );
		},

		/**
		 * @param {string} id Tab id.
		 * @return {boolean} Whether a tab with this id exists.
		 */
		exists: function ( id ) {
			return !! ( id && root.querySelector( '[data-khstt-tab="' + id + '"]' ) );
		},

		/**
		 * The panel currently on screen, which is the only one whose fields
		 * have a position worth measuring.
		 *
		 * @return {Element|null}
		 */
		activePanel: function () {
			return Tabs.current
				? document.getElementById( 'khstt-panel-' + Tabs.current )
				: null;
		},

		/**
		 * Shows one panel and updates the tablist state.
		 *
		 * @param {string}  id        Tab id to activate.
		 * @param {boolean} moveFocus Whether to focus the newly active tab.
		 */
		activate: function ( id, moveFocus ) {
			Tabs.current = id;

			Tabs.buttons.forEach( function ( button ) {
				var isActive = button.getAttribute( 'data-khstt-tab' ) === id;
				var panel = document.getElementById( 'khstt-panel-' + button.getAttribute( 'data-khstt-tab' ) );

				button.classList.toggle( 'is-active', isActive );
				button.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
				button.tabIndex = isActive ? 0 : -1;

				if ( panel ) {
					if ( isActive ) {
						panel.removeAttribute( 'data-khstt-inactive' );
					} else {
						panel.setAttribute( 'data-khstt-inactive', '1' );
					}
				}

				if ( isActive && moveFocus ) {
					button.focus();
				}
			} );

			try {
				window.sessionStorage.setItem( Tabs.STORAGE_KEY, id );
			} catch ( e ) {
				// Private browsing modes can refuse storage; the tab still switches.
			}
		},

		/**
		 * Arrow, Home and End navigation across the tablist, per the WAI-ARIA
		 * tabs pattern.
		 *
		 * @param {KeyboardEvent} event Key event.
		 */
		onKeydown: function ( event ) {
			var current = Tabs.buttons.indexOf( document.activeElement );
			var next = -1;

			if ( current < 0 ) {
				return;
			}

			if ( 'ArrowRight' === event.key || 'ArrowDown' === event.key ) {
				next = ( current + 1 ) % Tabs.buttons.length;
			} else if ( 'ArrowLeft' === event.key || 'ArrowUp' === event.key ) {
				next = ( current - 1 + Tabs.buttons.length ) % Tabs.buttons.length;
			} else if ( 'Home' === event.key ) {
				next = 0;
			} else if ( 'End' === event.key ) {
				next = Tabs.buttons.length - 1;
			}

			if ( next < 0 ) {
				return;
			}

			event.preventDefault();
			Tabs.activate( Tabs.buttons[ next ].getAttribute( 'data-khstt-tab' ), true );
		}
	};

	/* ---------------------------------------------------------------------
	   Conditional fields
	   ------------------------------------------------------------------ */

	var Conditions = {
		fields: [],

		init: function () {
			Conditions.fields = Array.prototype.slice.call( root.querySelectorAll( '[data-khstt-when]' ) );
			Conditions.apply();
		},

		/**
		 * Shows or hides every dependent field. Conditions are written as
		 * "key=value" or "key!=value" in the markup.
		 */
		apply: function () {
			Conditions.fields.forEach( function ( field ) {
				var rule = field.getAttribute( 'data-khstt-when' );
				var negated = rule.indexOf( '!=' ) > -1;
				var parts = rule.split( negated ? '!=' : '=' );
				var actual = Form.get( parts[ 0 ] );
				var matches = ( actual === parts[ 1 ] );

				field.hidden = negated ? matches : ! matches;
			} );
		}
	};

	/* ---------------------------------------------------------------------
	   Paired inputs: range <-> number, swatch <-> hex
	   ------------------------------------------------------------------ */

	var Pairs = {
		init: function () {
			root.addEventListener( 'input', function ( event ) {
				Pairs.syncFrom( event.target );
			} );

			root.addEventListener( 'change', function ( event ) {
				Pairs.clamp( event.target );
			} );
		},

		/**
		 * Snaps a number field back inside its allowed range as soon as the
		 * user commits an out-of-range value, so what they see is what will be
		 * saved rather than a number the server would quietly correct.
		 *
		 * @param {Element} el The element that changed.
		 */
		clamp: function ( el ) {
			if ( ! el || 'number' !== el.type || '' === el.value ) {
				return;
			}

			var min = parseFloat( el.getAttribute( 'min' ) );
			var max = parseFloat( el.getAttribute( 'max' ) );
			var value = parseFloat( el.value );

			if ( isNaN( value ) ) {
				value = isNaN( min ) ? 0 : min;
			}
			if ( ! isNaN( min ) && value < min ) {
				value = min;
			}
			if ( ! isNaN( max ) && value > max ) {
				value = max;
			}

			if ( String( value ) !== el.value ) {
				el.value = value;
				Pairs.syncFrom( el );
				Preview.render();
			}
		},

		/**
		 * Mirrors a change from one half of a paired control to the other.
		 *
		 * @param {Element} el The element the user just changed.
		 */
		syncFrom: function ( el ) {
			var partner;

			if ( ! el || ! el.getAttribute ) {
				return;
			}

			// Range moved: push the value into its number input.
			if ( el.hasAttribute( 'data-khstt-range-for' ) ) {
				partner = document.getElementById( el.getAttribute( 'data-khstt-range-for' ) );
				if ( partner && partner.value !== el.value ) {
					partner.value = el.value;
					Pairs.fire( partner );
				}
				return;
			}

			// Hex typed: push a valid value into its color swatch.
			if ( el.hasAttribute( 'data-khstt-hex-for' ) ) {
				partner = document.getElementById( el.getAttribute( 'data-khstt-hex-for' ) );
				if ( partner && /^#[0-9a-f]{6}$/i.test( el.value ) && partner.value !== el.value ) {
					partner.value = el.value;
					Pairs.fire( partner );
				}
				return;
			}

			// Number or swatch changed: push the value back to its companion.
			if ( el.id ) {
				partner = root.querySelector( '[data-khstt-range-for="' + el.id + '"], [data-khstt-hex-for="' + el.id + '"]' );
				if ( partner && partner.value !== el.value ) {
					partner.value = el.value;
				}
			}
		},

		/**
		 * Dispatches an input event so the preview and conditions react to a
		 * value this script wrote.
		 *
		 * @param {Element} el Element to notify listeners about.
		 */
		fire: function ( el ) {
			el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}
	};

	/* ---------------------------------------------------------------------
	   Quick Style presets
	   ------------------------------------------------------------------ */

	var Presets = {
		applying: false,
		keys: [],

		init: function () {
			var name;

			for ( name in PRESETS ) {
				if ( Object.prototype.hasOwnProperty.call( PRESETS, name ) ) {
					Object.keys( PRESETS[ name ] ).forEach( function ( key ) {
						if ( Presets.keys.indexOf( key ) < 0 ) {
							Presets.keys.push( key );
						}
					} );
				}
			}

			root.addEventListener( 'change', Presets.onChange );
			root.addEventListener( 'input', Presets.onChange );
		},

		/**
		 * Applies a preset when one is picked, and drops back to "custom" as
		 * soon as the user edits any value a preset owns.
		 *
		 * @param {Event} event Change event.
		 */
		onChange: function ( event ) {
			var key = event.target.getAttribute && event.target.getAttribute( 'data-khstt-key' );

			if ( ! key || Presets.applying ) {
				return;
			}

			if ( 'style_preset' === key ) {
				Presets.apply( Form.get( 'style_preset' ) );
				return;
			}

			if ( Presets.keys.indexOf( key ) > -1 ) {
				Form.set( 'style_preset', 'custom' );
			}
		},

		/**
		 * Writes every value of one preset into the form.
		 *
		 * @param {string} name Preset key, or "custom" for a no-op.
		 */
		apply: function ( name ) {
			var preset = PRESETS[ name ];

			if ( ! preset ) {
				return;
			}

			Presets.applying = true;

			Object.keys( preset ).forEach( function ( key ) {
				Form.set( key, preset[ key ] );
			} );

			Presets.applying = false;

			Conditions.apply();
			Preview.render();
		}
	};

	/* ---------------------------------------------------------------------
	   KineticPower demonstration inside the preview
	   ------------------------------------------------------------------ */

	/**
	 * Plays the selected power's sequence on the preview control.
	 *
	 * The preview cannot scroll anything, so there is no arrival to wait for:
	 * the three phases run back to back on the same envelope the front-end
	 * engine schedules against, which arrives from KHSTT_Powers::timings().
	 * The markup and the stylesheet are the front end's own, so what plays here
	 * is the real choreography rather than a drawing of it.
	 */
	var PowerPreview = {
		host: null,
		phase: '',
		timer: 0,

		/** Phase envelope, shared with the front-end engine through PHP. */
		timing: CFG.powers || { launch: 140, travelMin: 240, arrival: 300 },

		/**
		 * @param {Element} host  The element that carries the power attributes.
		 * @param {Element} hint  The "click to play" line, or null.
		 */
		init: function ( host, hint ) {
			PowerPreview.host = host;
			PowerPreview.hint = hint;

			host.addEventListener( 'click', PowerPreview.play );
		},

		/**
		 * Matches the preview to the current selection. Called on every render,
		 * so switching from Off to Rocket Boost is reflected with no save.
		 *
		 * @param {string} power     Selected power key.
		 * @param {string} intensity Selected amplitude.
		 */
		sync: function ( power, intensity ) {
			if ( ! PowerPreview.host ) {
				return;
			}

			var active = 'off' !== power && '' !== power;

			if ( PowerPreview.hint ) {
				PowerPreview.hint.hidden = ! active;
			}

			if ( ! active ) {
				PowerPreview.reset();
				PowerPreview.host.removeAttribute( 'data-khstt-power' );
				PowerPreview.host.removeAttribute( 'data-khstt-power-i' );
				return;
			}

			PowerPreview.host.setAttribute( 'data-khstt-power', power.replace( /_/g, '-' ) );
			PowerPreview.host.setAttribute( 'data-khstt-power-i', intensity );
		},

		/** Runs one complete sequence. A second click restarts it. */
		play: function () {
			if ( ! PowerPreview.host || ! PowerPreview.host.hasAttribute( 'data-khstt-power' ) ) {
				return;
			}

			// Reduced motion means no choreography here either, exactly as on
			// the front end.
			if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				return;
			}

			PowerPreview.reset();

			// Flushes the removal so a second click restarts the keyframes
			// instead of continuing the first run.
			void PowerPreview.host.offsetWidth;

			PowerPreview.set( 'launch' );

			PowerPreview.timer = window.setTimeout( function () {
				PowerPreview.set( 'travel' );

				PowerPreview.timer = window.setTimeout( function () {
					PowerPreview.set( 'arrival' );
					PowerPreview.timer = window.setTimeout( PowerPreview.reset, PowerPreview.timing.arrival );
				}, PowerPreview.timing.travelMin );
			}, PowerPreview.timing.launch );
		},

		/**
		 * @param {string} phase Phase name, or "" for the resting state.
		 */
		set: function ( phase ) {
			if ( PowerPreview.phase === phase ) {
				return;
			}

			PowerPreview.phase = phase;

			if ( '' === phase ) {
				PowerPreview.host.removeAttribute( 'data-khstt-power-state' );
				return;
			}

			PowerPreview.host.setAttribute( 'data-khstt-power-state', phase );
		},

		/** Cancels anything pending and returns to the resting state. */
		reset: function () {
			window.clearTimeout( PowerPreview.timer );
			PowerPreview.set( '' );
		}
	};

	/* ---------------------------------------------------------------------
	   Interaction polish inside the preview
	   ------------------------------------------------------------------ */

	/**
	 * Demonstrates the three interaction-polish behaviours on the preview
	 * control, using the same timings the front end is given.
	 *
	 * Smart Idle Fade runs for real rather than being illustrated, because the
	 * only honest answer to "what does this setting do" is to do it. Its clock
	 * is restarted by any form input as well as by touching the preview, so it
	 * never softens the control while someone is actively adjusting colours -
	 * which is the same rule the front end follows, transposed to this screen.
	 */
	var Polish = {
		control: null,
		button: null,
		pill: null,

		idle: false,
		idleTimer: 0,
		pressTimer: 0,

		timing: CFG.polish || { idleAfter: 2800, pressMs: 110 },

		/**
		 * @param {Element} control The positioned preview control.
		 * @param {Element} button  The preview button itself.
		 * @param {Element} pill    The label pill, or null.
		 */
		init: function ( control, button, pill ) {
			Polish.control = control;
			Polish.button = button;
			Polish.pill = pill;

			button.addEventListener( 'click', Polish.press );
			control.addEventListener( 'pointerenter', Polish.poke );
			control.addEventListener( 'pointerleave', Polish.poke );
		},

		/** True while something outranks a softened control, as on the front end. */
		suppressed: function () {
			return Preview.hover
				|| Preview.returning
				|| '' !== PowerPreview.phase;
		},

		/** Restarts the idle clock. Called from every render and every touch. */
		poke: function () {
			Polish.wake();
			window.clearTimeout( Polish.idleTimer );

			if ( ! Form.bool( 'idle_fade' ) || Polish.suppressed() ) {
				return;
			}

			Polish.idleTimer = window.setTimeout( Polish.fade, Polish.timing.idleAfter );
		},

		fade: function () {
			if ( ! Form.bool( 'idle_fade' ) || Polish.suppressed() ) {
				return;
			}

			Polish.idle = true;
			Polish.control.setAttribute( 'data-khstt-idle', '1' );
		},

		wake: function () {
			if ( ! Polish.idle ) {
				return;
			}

			Polish.idle = false;
			Polish.control.removeAttribute( 'data-khstt-idle' );
		},

		/**
		 * The micro press, which a KineticPower replaces rather than joins.
		 * PowerPreview owns the click when one is selected.
		 */
		press: function () {
			var boosting = Polish.button.hasAttribute( 'data-khstt-power' );

			if ( boosting || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				return;
			}

			window.clearTimeout( Polish.pressTimer );
			Polish.button.setAttribute( 'data-khstt-press', '1' );

			Polish.pressTimer = window.setTimeout( function () {
				Polish.button.removeAttribute( 'data-khstt-press' );
			}, Polish.timing.pressMs );
		},

		/**
		 * Matches the pill to the current form state on every render.
		 *
		 * @param {Object} v  Resolved device values.
		 * @param {string} bg Background the control is currently painted with.
		 * @param {string} fg Foreground the control is currently painted with.
		 */
		sync: function ( v, bg, fg ) {
			if ( ! Polish.pill ) {
				return;
			}

			var on = Form.bool( 'hover_label' );

			// The front-end pill takes the control's own colours through
			// currentColor and --khstt-bg. The preview paints the button with
			// inline styles instead, so the same two values are handed over
			// here - otherwise a white control would preview a dark pill.
			Polish.pill.style.backgroundColor = bg;
			Polish.pill.style.color = fg;

			Polish.pill.hidden = ! on;
			Polish.control.setAttribute( 'data-khstt-lbl', Polish.side( v.position ) );

			if ( on ) {
				Polish.pill.textContent = Polish.text();
			}

			// The hover simulation is what reveals it, exactly as a real hover
			// or a keyboard focus would on the front end.
			Polish.control.setAttribute( 'data-khstt-lbl-on', ( on && Preview.hover ) ? '1' : '0' );

			Polish.poke();
		},

		/**
		 * Which side of the control the label belongs on. Mirrors
		 * KHSTT_Styles::label_rules().
		 *
		 * @param {string} position Resolved position preset.
		 * @return {string}
		 */
		side: function ( position ) {
			if ( 'bottom-left' === position ) {
				return 'right';
			}
			if ( 'bottom-center' === position ) {
				return 'above';
			}
			return 'left';
		},

		/** @return {string} The action the control is currently offering. */
		text: function () {
			if ( Preview.returning ) {
				return I18N.labelReturn || '';
			}

			return 'content_start' === Form.get( 'scroll_destination' )
				? ( I18N.labelContent || '' )
				: ( I18N.labelTop || '' );
		}
	};

	/* ---------------------------------------------------------------------
	   Live preview
	   ------------------------------------------------------------------ */

	var Preview = {
		device: 'desktop',
		progress: 0.35,
		hover: false,
		returning: false,

		el: {},

		init: function () {
			Preview.el.root = document.getElementById( 'khstt-preview' );
			if ( ! Preview.el.root ) {
				return;
			}

			Preview.el.stage = Preview.el.root.querySelector( '[data-khstt-stage]' );

			if ( ! Preview.el.stage ) {
				Preview.el.root = null;
				return;
			}

			Preview.el.control = Preview.el.root.querySelector( '[data-khstt-control]' );
			Preview.el.button = Preview.el.root.querySelector( '[data-khstt-btn]' );
			Preview.el.ring = Preview.el.root.querySelector( '[data-khstt-ring]' );
			Preview.el.track = Preview.el.root.querySelector( '[data-khstt-ring-track]' );
			Preview.el.bar = Preview.el.root.querySelector( '[data-khstt-ring-bar]' );
			Preview.el.icon = Preview.el.root.querySelector( '[data-khstt-icon]' );
			Preview.el.pct = Preview.el.root.querySelector( '[data-khstt-pct]' );
			Preview.el.pill = Preview.el.root.querySelector( '[data-khstt-pill]' );

			Preview.buildOverlay();
			Preview.bindDevices();
			Preview.bindSimulation();

			PowerPreview.init(
				Preview.el.button,
				Preview.el.root.querySelector( '[data-khstt-power-hint]' )
			);

			Polish.init( Preview.el.control, Preview.el.button, Preview.el.pill );

			root.addEventListener( 'input', Preview.render );
			root.addEventListener( 'change', Preview.render );

			Preview.render();
		},

		/** Creates the "hidden on this device" overlay once, up front. */
		buildOverlay: function () {
			var overlay = document.createElement( 'span' );

			overlay.className = 'khstt-preview__overlay';
			overlay.hidden = true;
			overlay.textContent = I18N.hiddenOnDevice || '';

			Preview.el.stage.appendChild( overlay );
			Preview.el.overlay = overlay;
		},

		bindDevices: function () {
			var buttons = Array.prototype.slice.call( Preview.el.root.querySelectorAll( '[data-khstt-device-btn]' ) );

			buttons.forEach( function ( button ) {
				button.addEventListener( 'click', function () {
					Preview.device = button.getAttribute( 'data-khstt-device-btn' );
					Preview.el.root.setAttribute( 'data-khstt-device', Preview.device );

					buttons.forEach( function ( other ) {
						var isActive = other === button;
						other.classList.toggle( 'is-active', isActive );
						other.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
					} );

					Preview.render();
				} );
			} );
		},

		bindSimulation: function () {
			var slider = Preview.el.root.querySelector( '[data-khstt-progress-sim]' );
			var output = Preview.el.root.querySelector( '[data-khstt-progress-out]' );
			var checks = Array.prototype.slice.call( Preview.el.root.querySelectorAll( '[data-khstt-sim]' ) );

			if ( slider ) {
				slider.addEventListener( 'input', function () {
					Preview.progress = parseInt( slider.value, 10 ) / 100;
					if ( output ) {
						output.textContent = slider.value + '%';
					}
					Preview.render();
				} );
				Preview.progress = parseInt( slider.value, 10 ) / 100;
			}

			checks.forEach( function ( check ) {
				check.addEventListener( 'change', function () {
					if ( 'hover' === check.getAttribute( 'data-khstt-sim' ) ) {
						Preview.hover = check.checked;
					} else {
						Preview.returning = check.checked;
					}
					Preview.render();
				} );
			} );
		},

		/**
		 * Resolves the settings that apply to the currently previewed device,
		 * mirroring the inheritance model used by the generated frontend CSS:
		 * tablet and mobile fall back to the desktop value unless the matching
		 * override group is switched on.
		 *
		 * @return {Object} Effective values for this device.
		 */
		resolve: function () {
			var device = Preview.device;
			var thumb = Form.bool( 'thumb_friendly' );
			var out = {
				position: Form.get( 'position' ),
				offsetX: Form.num( 'offset_x', 24 ),
				offsetY: Form.num( 'offset_y', 24 ),
				size: Form.num( 'button_size', 48 ),
				icon: Form.num( 'icon_size', 20 ),
				thickness: Form.num( 'ring_thickness', 3 ),
				percentage: Form.get( 'show_percentage' ),
				visible: true
			};

			if ( 'tablet' === device ) {
				out.visible = Form.bool( 'show_tablet' );

				if ( Form.bool( 'tablet_position_override' ) ) {
					out.position = Form.get( 'tablet_position' );
					out.offsetX = Form.num( 'tablet_offset_x', out.offsetX );
					out.offsetY = Form.num( 'tablet_offset_y', out.offsetY );
				}
				if ( Form.bool( 'tablet_size_override' ) ) {
					out.size = Form.num( 'tablet_button_size', out.size );
					out.icon = Form.num( 'tablet_icon_size', out.icon );
				}
			} else if ( 'mobile' === device ) {
				out.visible = Form.bool( 'show_mobile' );

				if ( Form.bool( 'mobile_position_override' ) ) {
					out.position = Form.get( 'mobile_position' );
					out.offsetX = Form.num( 'mobile_offset_x', out.offsetX );
					out.offsetY = Form.num( 'mobile_offset_y', out.offsetY );
				}
				if ( Form.bool( 'mobile_size_override' ) ) {
					out.size = Form.num( 'mobile_button_size', out.size );
					out.icon = Form.num( 'mobile_icon_size', out.icon );
				}
				if ( Form.bool( 'mobile_progress_override' ) ) {
					out.thickness = Form.num( 'mobile_ring_thickness', out.thickness );
					out.percentage = Form.get( 'mobile_show_percentage' );
				}
				if ( thumb ) {
					out.size = Math.max( THUMB.size, out.size );
					out.offsetX = Math.max( THUMB.offset, out.offsetX );
					out.offsetY = Math.max( THUMB.offset, out.offsetY );
				}
			} else {
				out.visible = Form.bool( 'show_desktop' );
			}

			return out;
		},

		/** Repaints the whole preview from the current form state. */
		render: function () {
			if ( ! Preview.el.root ) {
				return;
			}

			var v = Preview.resolve();
			var shape = Form.get( 'shape' );
			var hovering = Preview.hover;
			var bg = hovering ? Form.get( 'hover_bg_color' ) : Form.get( 'bg_color' );
			var fg = hovering ? Form.get( 'hover_icon_color' ) : Form.get( 'icon_color' );
			var opacity = Form.num( 'bg_opacity', 100 ) / 100;
			var borderWidth = Form.num( 'border_width', 0 );
			var enabled = Form.bool( 'enabled' );
			var iconKey = Preview.returning ? 'arrow-down' : Form.get( 'icon' );
			var showPct = ! Preview.returning
				&& Form.bool( 'progress_enabled' )
				&& ( 'on' === v.percentage ? ! hovering : ( 'hover' === v.percentage && hovering ) );

			Preview.el.overlay.hidden = ( v.visible && enabled );
			Preview.el.control.hidden = ! ( v.visible && enabled );

			Preview.position( v );
			Preview.paint( v, shape, bg, fg, opacity, borderWidth );
			Preview.ring( v, shape, borderWidth );

			if ( Preview.el.icon.getAttribute( 'data-current' ) !== iconKey ) {
				// ICONS comes from the plugin's own hardcoded SVG constants, keyed
				// by a value the sanitizer restricts to a fixed allowlist. No user
				// input ever reaches this assignment.
				Preview.el.icon.innerHTML = ICONS[ iconKey ] || '';
				Preview.el.icon.setAttribute( 'data-current', iconKey );
			}

			Preview.el.pct.textContent = Math.round( Preview.progress * 100 ) + '%';
			Preview.el.button.setAttribute( 'data-khstt-show', showPct ? 'pct' : 'icon' );

			PowerPreview.sync( Form.get( 'kinetic_power' ), Form.get( 'kinetic_power_intensity' ) );
			// The resting pair, not the hovered pair. The front-end pill is painted
			// from --khstt-bg and --khstt-fg, which do not change on hover, so a
			// preview that swapped them here would flatter the real thing.
			Polish.sync( v, rgba( Form.get( 'bg_color' ), opacity ), Form.get( 'icon_color' ) );
		},

		/**
		 * Places the control inside the stage according to the resolved
		 * position preset.
		 *
		 * @param {Object} v Resolved device values.
		 */
		position: function ( v ) {
			var control = Preview.el.control;

			control.style.bottom = v.offsetY + 'px';

			if ( 'bottom-left' === v.position ) {
				control.style.left = v.offsetX + 'px';
				control.style.right = 'auto';
				control.style.transform = 'none';
			} else if ( 'bottom-center' === v.position ) {
				control.style.left = '50%';
				control.style.right = 'auto';
				control.style.transform = 'translateX(-50%)';
			} else {
				control.style.left = 'auto';
				control.style.right = v.offsetX + 'px';
				control.style.transform = 'none';
			}
		},

		/**
		 * Applies size, colors, radius, border, shadow and blur.
		 *
		 * @param {Object} v           Resolved device values.
		 * @param {string} shape       Current shape key.
		 * @param {string} bg          Background hex.
		 * @param {string} fg          Icon hex.
		 * @param {number} opacity     Background opacity, 0-1.
		 * @param {number} borderWidth Border width in pixels.
		 */
		paint: function ( v, shape, bg, fg, opacity, borderWidth ) {
			var button = Preview.el.button;

			button.style.width = v.size + 'px';
			button.style.height = v.size + 'px';
			button.style.borderRadius = radiusFor( shape, v.size ) + 'px';
			button.style.backgroundColor = rgba( bg, opacity );
			button.style.color = fg;
			button.style.border = borderWidth > 0
				? borderWidth + 'px solid ' + Form.get( 'border_color' )
				: '0 solid transparent';
			button.style.boxShadow = shadowFor( Form.get( 'shadow' ) );
			button.style.backdropFilter = Form.bool( 'backdrop_blur' ) ? 'blur(8px)' : 'none';

			// The KineticPower stylesheet scales everything it draws from these
			// two, exactly as the front-end control does.
			button.style.setProperty( '--khstt-size', v.size + 'px' );
			button.style.setProperty( '--khstt-icon', v.icon + 'px' );

			Preview.el.icon.style.width = v.icon + 'px';
			Preview.el.icon.style.height = v.icon + 'px';
			Preview.el.pct.style.fontSize = Math.max( 11, Math.round( v.size * 0.3 ) ) + 'px';
		},

		/**
		 * Draws the progress ring for the resolved size and thickness.
		 *
		 * @param {Object} v           Resolved device values.
		 * @param {string} shape       Current shape key.
		 * @param {number} borderWidth Button border width in pixels.
		 */
		ring: function ( v, shape, borderWidth ) {
			var on = Form.bool( 'progress_enabled' );
			var geometry;

			Preview.el.ring.style.display = on ? 'block' : 'none';

			if ( ! on ) {
				return;
			}

			geometry = ringGeometry( v.size, v.thickness, shape, borderWidth );

			[ Preview.el.track, Preview.el.bar ].forEach( function ( rect ) {
				rect.setAttribute( 'x', geometry.inset );
				rect.setAttribute( 'y', geometry.inset );
				rect.setAttribute( 'width', geometry.side );
				rect.setAttribute( 'height', geometry.side );
				rect.setAttribute( 'rx', geometry.radius );
				rect.setAttribute( 'stroke-width', v.thickness );
			} );

			Preview.el.track.setAttribute( 'stroke', Form.get( 'track_color' ) );
			Preview.el.track.setAttribute( 'stroke-opacity', Form.num( 'track_opacity', 25 ) / 100 );

			Preview.el.bar.setAttribute( 'stroke', Form.get( 'progress_color' ) );
			Preview.el.bar.setAttribute( 'stroke-dasharray', geometry.length );
			Preview.el.bar.setAttribute(
				'stroke-dashoffset',
				geometry.length * ( 1 - Preview.progress ) - geometry.shift
			);
		}
	};

	/* ---------------------------------------------------------------------
	   Shared geometry and color helpers
	   ------------------------------------------------------------------ */

	/**
	 * Corner radius in pixels for a given shape at a given button size.
	 * Mirrors the values in the frontend stylesheet.
	 *
	 * @param {string} shape Shape key.
	 * @param {number} size  Button size in pixels.
	 * @return {number}
	 */
	function radiusFor( shape, size ) {
		if ( 'circle' === shape ) {
			return size / 2;
		}
		if ( 'rounded' === shape ) {
			return size * 0.28;
		}
		return 4;
	}

	/**
	 * Geometry for the progress ring, drawn as a single rounded rect so one
	 * element covers circle, rounded and square buttons.
	 *
	 * The stroke is centred on the path, so the path sits half a stroke inside
	 * the button box. Perimeter is computed exactly rather than measured, and
	 * the dash pattern is shifted so progress always starts at top centre.
	 *
	 * The border matters and it is easy to miss. `size` is the button's border
	 * box, but the ring SVG is position:absolute inset:0, so its containing
	 * block is the button's *padding* box - smaller by the border on every
	 * side. Drawing border-box coordinates into a padding-box viewport put the
	 * ring's centre a whole border width below and right of the button's, which
	 * is visible the moment a border is set and invisible when it is zero.
	 * Starting at -border puts the two centres back together, exactly as
	 * Ring.sync() in the frontend script does for the real control.
	 *
	 * @param {number} size      Button size in pixels, border box.
	 * @param {number} thickness Stroke width in pixels.
	 * @param {string} shape     Shape key.
	 * @param {number} border    Button border width in pixels.
	 * @return {Object} inset, side, radius, length and shift.
	 */
	function ringGeometry( size, thickness, shape, border ) {
		var edge = border || 0;
		var side = Math.max( 1, size - thickness );
		var radius = Math.min( side / 2, Math.max( 0, radiusFor( shape, size ) - thickness / 2 ) );
		var straight = Math.max( 0, side - 2 * radius );

		return {
			inset: thickness / 2 - edge,
			side: side,
			radius: radius,
			length: 4 * straight + 2 * Math.PI * radius,
			shift: side / 2 - radius
		};
	}

	/**
	 * Converts a hex color plus an opacity into an rgba() string.
	 *
	 * @param {string} hex     Hex color, with or without a leading hash.
	 * @param {number} opacity Opacity from 0 to 1.
	 * @return {string}
	 */
	function rgba( hex, opacity ) {
		var value = String( hex || '' ).replace( '#', '' );

		if ( 3 === value.length ) {
			value = value[ 0 ] + value[ 0 ] + value[ 1 ] + value[ 1 ] + value[ 2 ] + value[ 2 ];
		}

		if ( ! /^[0-9a-f]{6}$/i.test( value ) ) {
			value = '000000';
		}

		return 'rgba(' + parseInt( value.substr( 0, 2 ), 16 ) + ','
			+ parseInt( value.substr( 2, 2 ), 16 ) + ','
			+ parseInt( value.substr( 4, 2 ), 16 ) + ','
			+ opacity + ')';
	}

	/**
	 * Box shadow for each shadow preset. Mirrors the frontend stylesheet.
	 *
	 * @param {string} level Shadow key.
	 * @return {string}
	 */
	function shadowFor( level ) {
		var map = {
			none: 'none',
			soft: '0 4px 14px rgba(0,0,0,.16)',
			medium: '0 8px 24px rgba(0,0,0,.22)',
			strong: '0 12px 34px rgba(0,0,0,.30)'
		};

		return map[ level ] || map.soft;
	}

	/* ---------------------------------------------------------------------
	   Small screen wiring
	   ------------------------------------------------------------------ */

	/** Keeps the hero's Enabled/Disabled caption in step with its switch. */
	function bindEnabledLabel() {
		var label = root.querySelector( '[data-khstt-enabled-label]' );

		if ( ! label ) {
			return;
		}

		root.addEventListener( 'change', function ( event ) {
			if ( event.target.getAttribute && 'enabled' === event.target.getAttribute( 'data-khstt-key' ) ) {
				label.textContent = event.target.checked
					? ( I18N.enabled || 'Enabled' )
					: ( I18N.disabled || 'Disabled' );
			}
		} );
	}

	/** Asks before the destructive reset, which posts to its own form. */
	function bindReset() {
		var button = root.querySelector( '[data-khstt-reset]' );

		if ( ! button ) {
			return;
		}

		button.addEventListener( 'click', function ( event ) {
			if ( ! window.confirm( I18N.resetConfirm || '' ) ) {
				event.preventDefault();
			}
		} );
	}

	/* ---------------------------------------------------------------------
	   ScrollBack: the page you were on, after the Settings API reload
	   ------------------------------------------------------------------ */

	/**
	 * Saving posts to options.php and comes back as a fresh page load, which
	 * lands at the top. On a screen this long, a setting changed near the
	 * bottom put the confirmation - and the control that was just changed -
	 * out of sight.
	 *
	 * Two things make this reliable rather than occasional.
	 *
	 * The first is knowing that a save happened at all. WordPress removes
	 * settings-updated from the address bar in admin_head, so by the time this
	 * file runs the URL no longer says anything about it. The server passes the
	 * answer down instead, because the server is the only thing that still has
	 * it.
	 *
	 * The second is what gets remembered. A raw pixel offset only means
	 * something if the page comes back the same height, and it does not: the
	 * save adds a notice above the form, and a changed setting can show or hide
	 * dependent fields. So what is stored is the setting that was at the top of
	 * the screen and how far down the screen it was. Putting that same setting
	 * back in that same place is stable whatever happened to the height above
	 * it. The pixel offset is kept as well, and used only when the setting
	 * cannot be found again.
	 *
	 * The record lives in sessionStorage, so it is per-tab, never leaves the
	 * browser, and disappears with the tab: no option row, no request, nothing
	 * kept. It is read exactly once and deleted as it is read.
	 */
	var ScrollBack = {
		STORAGE_KEY: 'khstt_scroll_pos',

		/** How long a stored position stays meaningful, in milliseconds. */
		MAX_AGE: 60000,

		init: function () {
			if ( form ) {
				// Capture on submit rather than on unload: unload handlers are
				// unreliable, and this fires only for a real save.
				form.addEventListener( 'submit', ScrollBack.remember );
			}

			ScrollBack.restore();
		},

		/**
		 * The setting nearest the top of the screen, and how far down it is.
		 *
		 * "Nearest the top" means the first one that has not been scrolled past
		 * yet, which is what a reader would point at if asked where they were.
		 * Fields the conditions have hidden have no box, so they are skipped
		 * rather than picked and then found missing later.
		 *
		 * @return {Object|null} Key and viewport offset, or null if none fits.
		 */
		anchor: function () {
			var panel = Tabs.activePanel();
			var nodes = panel ? panel.querySelectorAll( '[data-khstt-key]' ) : [];
			var i;
			var rect;

			for ( i = 0; i < nodes.length; i++ ) {
				rect = nodes[ i ].getBoundingClientRect();

				if ( ! rect.width && ! rect.height ) {
					continue;
				}

				if ( rect.bottom > 0 ) {
					return {
						key: nodes[ i ].getAttribute( 'data-khstt-key' ),
						top: Math.round( rect.top )
					};
				}
			}

			return null;
		},

		/** Stores where the page is, immediately before a save leaves it. */
		remember: function () {
			var at = ScrollBack.anchor();

			try {
				window.sessionStorage.setItem(
					ScrollBack.STORAGE_KEY,
					JSON.stringify( {
						tab: Tabs.current,
						key: at ? at.key : '',
						top: at ? at.top : 0,
						y: Math.round( window.pageYOffset || document.documentElement.scrollTop || 0 ),
						t: Date.now()
					} )
				);
			} catch ( e ) {
				// Private browsing can refuse storage; the save still happens.
			}
		},

		/** Reads the stored position back, once, and only after a save. */
		restore: function () {
			var raw = null;
			var saved;

			try {
				raw = window.sessionStorage.getItem( ScrollBack.STORAGE_KEY );
				window.sessionStorage.removeItem( ScrollBack.STORAGE_KEY );
			} catch ( e ) {
				return;
			}

			if ( ! raw || ! CFG.afterSave ) {
				return;
			}

			try {
				saved = JSON.parse( raw );
			} catch ( e ) {
				return;
			}

			if ( ! saved || 'number' !== typeof saved.y || 'number' !== typeof saved.top ) {
				return;
			}

			// Nothing to put back: no setting was recorded and the page was at
			// the top anyway, which is where this load already is.
			if ( ! saved.key && saved.y <= 0 ) {
				return;
			}

			// A position kept from some earlier visit would be a surprise, not
			// a convenience.
			if ( ! saved.t || Date.now() - saved.t > ScrollBack.MAX_AGE ) {
				return;
			}

			// The reader is looking at a different tab than the one that was
			// saved from, so the setting that was on screen is not on screen
			// now. The top of the page is the only honest answer.
			if ( saved.tab && saved.tab !== Tabs.current ) {
				return;
			}

			ScrollBack.settle( saved );
		},

		/**
		 * Puts the page where the record says, without arguing with the
		 * browser about it.
		 *
		 * scrollRestoration is set to manual for the one call and then put back
		 * exactly as it was found, so a later back navigation still restores
		 * the way the reader expects.
		 *
		 * @param {Object} saved Stored record.
		 */
		settle: function ( saved ) {
			var was = null;

			if ( 'scrollRestoration' in window.history ) {
				was = window.history.scrollRestoration;
				window.history.scrollRestoration = 'manual';
			}

			window.scrollTo( 0, ScrollBack.target( saved ) );

			if ( null !== was ) {
				window.history.scrollRestoration = was;
			}
		},

		/**
		 * Where the page should sit: the remembered setting back at the same
		 * height on screen, or the raw offset when it cannot be found.
		 *
		 * @param {Object} saved Stored record.
		 * @return {number} Scroll offset in pixels.
		 */
		target: function ( saved ) {
			var scope = Tabs.activePanel() || root;
			var node = saved.key
				? scope.querySelector( '[data-khstt-key="' + saved.key + '"]' )
				: null;
			var rect;

			if ( ! node ) {
				return saved.y;
			}

			rect = node.getBoundingClientRect();

			// A field the save has since hidden has no box to aim at.
			if ( ! rect.width && ! rect.height ) {
				return saved.y;
			}

			return Math.max(
				0,
				Math.round( rect.top + ( window.pageYOffset || document.documentElement.scrollTop || 0 ) - saved.top )
			);
		}
	};

	/* ---------------------------------------------------------------------
	   Boot
	   ------------------------------------------------------------------ */

	Tabs.init();
	Pairs.init();
	Conditions.init();
	Presets.init();
	Preview.init();
	ScrollBack.init();
	bindEnabledLabel();
	bindReset();

	// One delegated listener keeps every dependent field in step with the
	// controls it depends on.
	if ( form ) {
		form.addEventListener( 'change', Conditions.apply );
		form.addEventListener( 'input', Conditions.apply );
	}
}() );
