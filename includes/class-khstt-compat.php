<?php
/**
 * Compatibility status: what the frontend actually found, stored for the admin
 * screen to read back.
 *
 * PHP cannot see the rendered DOM, so it cannot know which scroll container is
 * active or whether the theme already prints a back-to-top control. Rather than
 * guess from the server, the frontend reports what it found and this class
 * stores a small, strictly typed snapshot of it.
 *
 * What is stored: an environment label, the active scroll source, and up to
 * three short descriptors of competing controls (tag name, id, first classes,
 * and which rule matched). Nothing else. No URLs, no user identifiers, no
 * markup, no record of who visited or when they visited.
 *
 * When it is written: only while an administrator with manage_options is
 * browsing the frontend, and only when the stored snapshot is missing, older
 * than six hours, from a different plugin version, or was taken under different
 * compatibility settings. Visitors never trigger a write - the probe payload is
 * not printed for them at all.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the compatibility status option and the endpoint that fills it.
 */
class KHSTT_Compat {

	/** The option this class owns. Deliberately separate from user settings. */
	const OPTION_KEY = 'khstt_compat_status';

	/** The admin-ajax action, and the nonce action that guards it. */
	const AJAX_ACTION = 'khstt_compat_status';

	/** How long a stored snapshot is treated as current, in seconds. */
	const MAX_AGE = 21600;

	/** Shortest gap between two accepted writes, in seconds. */
	const MIN_GAP = 10;

	/** How many competing controls are described in the snapshot. */
	const MAX_CONFLICTS = 3;

	/** Scroll environments the frontend is allowed to report. */
	const ENVIRONMENTS = array( 'native', 'container', 'lenis', 'locomotive', 'smooth-scrollbar', 'other' );

	/** Scroll sources the frontend is allowed to report. */
	const SOURCES = array( 'window', 'custom' );

	/** Reasons a control can be classified as a competing back-to-top control. */
	const REASONS = array( 'id', 'class', 'label', 'title', 'data', 'href', 'custom' );

	/**
	 * Identifier fragments that, in practice, only appear on a back-to-top
	 * control. This is the same list the engine scans with, mirrored here
	 * because the early hint is built by PHP; t15 asserts the two stay equal.
	 *
	 * Nothing is hidden early unless the recorded id or class contains one of
	 * these, so a stored reading can never be turned into a rule that hides
	 * something unrelated.
	 */
	const EARLY_TOKENS = array(
		'back-to-top',
		'backtotop',
		'back_to_top',
		'back-top',
		'backtop',
		'scroll-to-top',
		'scrolltotop',
		'scroll_to_top',
		'scroll-top',
		'scrolltop',
		'scroll-up',
		'scrollup',
		'to-top',
		'totop',
		'go-top',
		'gotop',
	);

	/**
	 * Element names that carry markup rather than paint a control. The engine
	 * already refuses to record these; the early hint refuses them a second
	 * time, so an old or hand-edited option row still cannot produce a rule
	 * against a script or a stylesheet.
	 */
	const NON_CONTROL_TAGS = array(
		'script',
		'style',
		'link',
		'meta',
		'noscript',
		'template',
		'title',
		'base',
		'head',
		'html',
		'body',
		'source',
		'track',
		'param',
	);

	/** Marks the document as one where the early hint may apply. */
	const EARLY_ATTR = 'data-khstt-early';

	/** Marks the document as one the engine has now graded for itself. */
	const READY_ATTR = 'data-khstt-compat-ready';

	/**
	 * The settings keys whose values change what a probe would find. A change to
	 * any of them retires the stored snapshot immediately instead of leaving a
	 * stale answer on screen until it ages out.
	 */
	const CONTEXT_KEYS = array(
		'scroll_container',
		'scroll_container_selector',
		'conflict_detection',
		'conflict_selector',
		'force_replace',
	);

	/**
	 * Registers the write endpoint.
	 *
	 * The wp_ajax_ hook has no wp_ajax_nopriv_ counterpart, so the action is
	 * already unreachable for logged-out requests; the capability check inside
	 * the handler is the second gate and the nonce is the third.
	 */
	public function register() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_ajax' ) );
	}

	/**
	 * Returns the stored snapshot, or null when nothing has been recorded yet.
	 *
	 * @return array|null
	 */
	public function get_status() {
		$stored = get_option( self::OPTION_KEY, null );

		if ( ! is_array( $stored ) || empty( $stored['time'] ) ) {
			return null;
		}

		return $stored;
	}

	/**
	 * Whether the stored snapshot still describes the current configuration.
	 *
	 * @param array $settings Current settings.
	 * @return bool
	 */
	public function is_current( array $settings ) {
		$stored = $this->get_status();

		if ( null === $stored ) {
			return false;
		}

		if ( ! isset( $stored['context'] ) || $stored['context'] !== $this->context_hash( $settings ) ) {
			return false;
		}

		if ( ! isset( $stored['version'] ) || KHSTT_VERSION !== $stored['version'] ) {
			return false;
		}

		return ( time() - (int) $stored['time'] ) < self::MAX_AGE;
	}

	/**
	 * The probe payload handed to the frontend script, or false when this
	 * request must not report anything.
	 *
	 * @param array $settings Current settings.
	 * @return array|false
	 */
	public function get_probe_config( array $settings ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( $this->is_current( $settings ) ) {
			return false;
		}

		return array(
			'url'    => admin_url( 'admin-ajax.php' ),
			'action' => self::AJAX_ACTION,
			'nonce'  => wp_create_nonce( self::AJAX_ACTION ),
		);
	}

	// The early hint.

	/**
	 * Selectors for controls that may be hidden before the engine has run.
	 *
	 * Force Replace works, but it works at DOM ready, and a theme that shows
	 * its own control from a scroll handler shows it well before that. On a
	 * reload part-way down a page the visitor sees the theme's control appear
	 * and then vanish. The fix is not to guess harder from the server: it is to
	 * reuse the reading the engine already took and confirmed on this exact
	 * configuration, and to hold that control back for the few frames until the
	 * engine is in a position to decide for itself.
	 *
	 * Every gate below has to pass:
	 *
	 *   - detection and Force Replace are both on
	 *   - a stored reading exists for this plugin version, these compatibility
	 *     settings, and is inside the six hour window
	 *   - the recorded control was graded confident
	 *   - its recorded tag is one that can paint a control at all
	 *   - its recorded id or class carries one of the back-to-top tokens the
	 *     engine itself matches on
	 *   - the id or class survives as a plain CSS identifier
	 *
	 * Anything short of that returns nothing and the page behaves exactly as it
	 * does today. The rule this feeds is disarmed by the engine on its first
	 * pass, so it can only ever cover the gap it was written for.
	 *
	 * @param array $settings Current settings.
	 * @return string[] Selectors, possibly empty.
	 */
	public function get_early_selectors( array $settings ) {
		if ( empty( $settings['conflict_detection'] ) || empty( $settings['force_replace'] ) ) {
			return array();
		}

		if ( ! $this->is_current( $settings ) ) {
			return array();
		}

		$stored = $this->get_status();

		if ( ! isset( $stored['conflicts'] ) || ! is_array( $stored['conflicts'] ) ) {
			return array();
		}

		$selectors = array();

		foreach ( array_slice( $stored['conflicts'], 0, self::MAX_CONFLICTS ) as $conflict ) {
			if ( ! is_array( $conflict ) || empty( $conflict['confident'] ) ) {
				continue;
			}

			$selector = self::early_selector( $conflict );

			if ( '' !== $selector && ! in_array( $selector, $selectors, true ) ) {
				$selectors[] = $selector;
			}
		}

		return $selectors;
	}

	/**
	 * Turns one recorded control into a CSS selector, or an empty string.
	 *
	 * The id is preferred because it identifies one element. Classes are used
	 * together, so a control recorded with two classes is matched on both and
	 * not on either alone.
	 *
	 * @param array $conflict Stored descriptor.
	 * @return string
	 */
	public static function early_selector( array $conflict ) {
		$tag = isset( $conflict['tag'] ) ? strtolower( (string) $conflict['tag'] ) : '';

		if ( '' !== $tag && in_array( $tag, self::NON_CONTROL_TAGS, true ) ) {
			return '';
		}

		$id  = isset( $conflict['id'] ) ? (string) $conflict['id'] : '';
		$cls = isset( $conflict['cls'] ) ? (string) $conflict['cls'] : '';

		if ( ! self::carries_token( $id . ' ' . $cls ) ) {
			return '';
		}

		if ( self::is_ident( $id ) ) {
			return '#' . $id;
		}

		$classes = array();

		foreach ( preg_split( '/\s+/', trim( $cls ) ) as $class ) {
			if ( self::is_ident( $class ) ) {
				$classes[] = '.' . $class;
			}
		}

		return empty( $classes ) ? '' : implode( '', $classes );
	}

	/**
	 * Whether a recorded identifier names the job this plugin does.
	 *
	 * @param string $value Recorded id and classes.
	 * @return bool
	 */
	private static function carries_token( $value ) {
		$value = strtolower( $value );

		foreach ( self::EARLY_TOKENS as $token ) {
			if ( false !== strpos( $value, $token ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a string is safe to write into a stylesheet as a bare CSS
	 * identifier. Deliberately narrower than CSS allows: a leading letter, then
	 * letters, digits, hyphens and underscores, and nothing else.
	 *
	 * @param string $value Candidate.
	 * @return bool
	 */
	private static function is_ident( $value ) {
		return (bool) preg_match( '/^[A-Za-z][A-Za-z0-9_-]{0,79}$/', (string) $value );
	}

	/**
	 * The stylesheet that holds those controls back, or an empty string.
	 *
	 * The rule is gated twice. It applies only while the root element carries
	 * the early attribute, which a one line script in the head sets, so a
	 * visitor without JavaScript - who would also never see this plugin's own
	 * control - keeps the theme's. And it stops applying the moment the engine
	 * marks the document ready, which it does on its first pass whether or not
	 * it decided to suppress anything.
	 *
	 * Device targeting mirrors the Visibility tab exactly. On a breakpoint
	 * where this plugin shows nothing, nothing is held back either.
	 *
	 * @param array $settings Current settings.
	 * @return string
	 */
	public function get_early_css( array $settings ) {
		$selectors = $this->get_early_selectors( $settings );

		if ( empty( $selectors ) ) {
			return '';
		}

		$scope = ':root[' . self::EARLY_ATTR . ']:not([' . self::READY_ATTR . ']) ';
		$rule  = $scope . implode( ',' . $scope, $selectors ) . '{display:none !important}';

		$queries = self::early_media_queries( $settings );

		if ( null === $queries ) {
			return $rule;
		}

		$css = '';

		foreach ( $queries as $query ) {
			$css .= '@media ' . $query . '{' . $rule . '}';
		}

		return $css;
	}

	/**
	 * The breakpoints on which the early rule is allowed to apply, or null when
	 * every device is enabled and no query is needed.
	 *
	 * @param array $settings Current settings.
	 * @return string[]|null
	 */
	private static function early_media_queries( array $settings ) {
		$desktop = ! empty( $settings['show_desktop'] );
		$tablet  = ! empty( $settings['show_tablet'] );
		$mobile  = ! empty( $settings['show_mobile'] );

		if ( $desktop && $tablet && $mobile ) {
			return null;
		}

		$queries = array();

		if ( $desktop ) {
			$queries[] = sprintf( '(min-width:%dpx)', KHSTT_Styles::TABLET_MAX + 1 );
		}

		if ( $tablet ) {
			$queries[] = sprintf(
				'(min-width:%dpx) and (max-width:%dpx)',
				KHSTT_Styles::TABLET_MIN,
				KHSTT_Styles::TABLET_MAX
			);
		}

		if ( $mobile ) {
			$queries[] = sprintf( '(max-width:%dpx)', KHSTT_Styles::MOBILE_MAX );
		}

		return $queries;
	}

	/**
	 * The one line of script that arms the early rule.
	 *
	 * Setting the attribute from script rather than printing it on the element
	 * is what makes the rule safe: with scripting off it is never set, so the
	 * theme keeps its control. The listener is the backstop for the case where
	 * the engine never arrives at all - a blocked or failed request - and it
	 * fires once, on an event the browser always delivers. No timer, no poll.
	 *
	 * @return string
	 */
	public static function early_script() {
		return 'var e=document.documentElement;e.setAttribute("' . self::EARLY_ATTR . '","1");'
			. 'window.addEventListener("load",function(){'
			. 'if(!e.hasAttribute("' . self::READY_ATTR . '")){e.removeAttribute("' . self::EARLY_ATTR . '");}'
			. '},{once:true});';
	}

	/**
	 * Fingerprints the settings that change what a probe would find.
	 *
	 * @param array $settings Current settings.
	 * @return string
	 */
	public function context_hash( array $settings ) {
		$context = array();

		foreach ( self::CONTEXT_KEYS as $key ) {
			$context[ $key ] = isset( $settings[ $key ] ) ? $settings[ $key ] : null;
		}

		return md5( (string) wp_json_encode( $context ) );
	}

	/**
	 * Stores one compatibility snapshot.
	 *
	 * Three gates stand in front of this: the action exists only for logged-in
	 * requests, the nonce is checked, and the capability is checked. Everything
	 * in the payload is then rebuilt from an allowlist rather than trusted, so a
	 * caller that passes all three still cannot write arbitrary data into the
	 * option.
	 */
	public function handle_ajax() {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$existing = $this->get_status();

		// Flood guard: an accepted write is followed by a quiet period, so a
		// page that somehow posted in a loop could not churn the option.
		if ( null !== $existing && ( time() - (int) $existing['time'] ) < self::MIN_GAP ) {
			wp_send_json_success( array( 'stored' => false ) );
		}

		// Defence in depth on the transport itself. The real guarantee is
		// build_record(), which rebuilds every field from an allowlist, but a
		// JSON blob that has had its markup stripped can only decode to
		// something harmless or fail to decode at all. Nothing this endpoint is
		// sent legitimately contains a tag: a tag name, an id and a class have
		// no use for one.
		$raw = isset( $_POST['payload'] ) ? sanitize_textarea_field( wp_unslash( $_POST['payload'] ) ) : '';

		if ( strlen( $raw ) > 4096 ) {
			wp_send_json_error( array( 'message' => 'payload too large' ), 400 );
		}

		$decoded = json_decode( $raw, true, 6 );

		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => 'malformed payload' ), 400 );
		}

		$settings = ( new KHSTT_Settings() )->get_settings();

		update_option( self::OPTION_KEY, $this->build_record( $decoded, $settings ), false );

		wp_send_json_success( array( 'stored' => true ) );
	}

	/**
	 * Rebuilds a storable record from an untrusted payload.
	 *
	 * Nothing is copied across: every field is re-derived from an allowlist, so
	 * the shape of the option is fixed by this method and not by its caller.
	 *
	 * @param array $raw      Decoded payload.
	 * @param array $settings Current settings.
	 * @return array
	 */
	public function build_record( array $raw, array $settings ) {
		$env = isset( $raw['env'] ) && is_scalar( $raw['env'] ) ? (string) $raw['env'] : '';
		$src = isset( $raw['source'] ) && is_scalar( $raw['source'] ) ? (string) $raw['source'] : '';

		$conflicts = array();

		if ( isset( $raw['conflicts'] ) && is_array( $raw['conflicts'] ) ) {
			foreach ( array_slice( $raw['conflicts'], 0, self::MAX_CONFLICTS ) as $entry ) {
				if ( is_array( $entry ) ) {
					$conflicts[] = $this->build_conflict( $entry );
				}
			}
		}

		return array(
			'time'       => time(),
			'version'    => KHSTT_VERSION,
			'context'    => $this->context_hash( $settings ),
			'env'        => in_array( $env, self::ENVIRONMENTS, true ) ? $env : 'other',
			'source'     => in_array( $src, self::SOURCES, true ) ? $src : 'window',
			'fallback'   => ! empty( $raw['fallback'] ),
			'mixed'      => ! empty( $raw['mixed'] ),
			'scanned'    => ! empty( $raw['scanned'] ),
			'suppressed' => isset( $raw['suppressed'] ) ? max( 0, min( 999, (int) $raw['suppressed'] ) ) : 0,
			'count'      => isset( $raw['count'] ) ? max( 0, min( 999, (int) $raw['count'] ) ) : 0,
			'conflicts'  => $conflicts,
		);
	}

	/**
	 * Reduces one reported control to a few short, character-restricted strings.
	 *
	 * @param array $entry Reported descriptor.
	 * @return array
	 */
	private function build_conflict( array $entry ) {
		$reason = isset( $entry['via'] ) && is_scalar( $entry['via'] ) ? (string) $entry['via'] : '';

		return array(
			'tag'       => $this->token( isset( $entry['tag'] ) ? $entry['tag'] : '', 20 ),
			'id'        => $this->token( isset( $entry['id'] ) ? $entry['id'] : '', 60 ),
			'cls'       => $this->token( isset( $entry['cls'] ) ? $entry['cls'] : '', 80 ),
			'via'       => in_array( $reason, self::REASONS, true ) ? $reason : 'class',
			'confident' => ! empty( $entry['confident'] ),
		);
	}

	/**
	 * Strips a reported identifier down to the characters an id or class can
	 * legitimately contain, then truncates it.
	 *
	 * @param mixed $value  Reported value.
	 * @param int   $length Maximum length.
	 * @return string
	 */
	private function token( $value, $length ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$clean = preg_replace( '/[^A-Za-z0-9 _-]/', '', (string) $value );

		return trim( substr( (string) $clean, 0, $length ) );
	}

	/**
	 * A short, readable description of one recorded control.
	 *
	 * Only the identifying attributes are shown. The element's own markup is
	 * never stored, so there is nothing here to leak into the screen.
	 *
	 * @param array $conflict Stored descriptor.
	 * @return string
	 */
	public static function descriptor( array $conflict ) {
		$out = '<' . ( '' !== $conflict['tag'] ? $conflict['tag'] : 'element' );

		if ( '' !== $conflict['id'] ) {
			$out .= ' id="' . $conflict['id'] . '"';
		}

		if ( '' !== $conflict['cls'] ) {
			$out .= ' class="' . $conflict['cls'] . '"';
		}

		return $out . '>';
	}

	/**
	 * Says which rule matched a recorded control.
	 *
	 * @param string $reason Stored reason key.
	 * @return string
	 */
	public static function reason_label( $reason ) {
		switch ( $reason ) {
			case 'id':
				return __( 'matched on its id', 'kinetichub-scroll-to-top' );

			case 'label':
				return __( 'matched on its accessible label', 'kinetichub-scroll-to-top' );

			case 'title':
				return __( 'matched on its title', 'kinetichub-scroll-to-top' );

			case 'data':
				return __( 'matched on a data attribute', 'kinetichub-scroll-to-top' );

			case 'href':
				return __( 'matched on a link to the top of the page', 'kinetichub-scroll-to-top' );

			case 'custom':
				return __( 'matched your Existing Control Selector', 'kinetichub-scroll-to-top' );

			case 'class':
			default:
				return __( 'matched on its class', 'kinetichub-scroll-to-top' );
		}
	}

	/**
	 * Human readable label for a stored environment value.
	 *
	 * @param string $env Environment key.
	 * @return string
	 */
	public static function environment_label( $env ) {
		switch ( $env ) {
			case 'container':
				return __( 'Custom scroll container', 'kinetichub-scroll-to-top' );

			case 'lenis':
				return __( 'Lenis detected', 'kinetichub-scroll-to-top' );

			case 'locomotive':
				return __( 'Locomotive Scroll detected', 'kinetichub-scroll-to-top' );

			case 'smooth-scrollbar':
				return __( 'Smooth Scrollbar detected', 'kinetichub-scroll-to-top' );

			case 'other':
				return __( 'Non-standard scrolling detected', 'kinetichub-scroll-to-top' );

			case 'native':
			default:
				return __( 'Native page scrolling', 'kinetichub-scroll-to-top' );
		}
	}

	/**
	 * Whether an environment label describes something KineticHub has an adapter
	 * for. Detected is not the same as supported, and the admin screen says so
	 * rather than implying the plugin drives the library.
	 *
	 * @param string $env Environment key.
	 * @return bool
	 */
	public static function environment_is_adapted( $env ) {
		return in_array( $env, array( 'native', 'container' ), true );
	}
}
