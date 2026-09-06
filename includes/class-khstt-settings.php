<?php
/**
 * Settings: schema, defaults, reader, and sanitizer.
 *
 * Every option lives under a single wp_options row so a frontend request costs
 * one query and the schema can grow without migrations. get_defaults() is the
 * authoritative schema: any key that is not listed there is dropped on save.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the plugin's single option: its schema, its defaults, and its sanitizer.
 */
class KHSTT_Settings {

	const OPTION_KEY   = 'khstt_settings';
	const OPTION_GROUP = 'khstt_settings_group';

	/** Allowed values for every enum field, used by both the sanitizer and the admin UI. */
	const ENUMS = array(
		'scroll_motion'           => array( 'smart', 'smooth', 'instant' ),
		'reveal_trigger'          => array( 'smart', 'custom' ),
		'scroll_destination'      => array( 'page_top', 'content_start' ),
		'scroll_container'        => array( 'auto', 'window', 'custom' ),
		'position'                => array( 'bottom-right', 'bottom-left', 'bottom-center' ),
		'tablet_position'         => array( 'bottom-right', 'bottom-left', 'bottom-center' ),
		'mobile_position'         => array( 'bottom-right', 'bottom-left', 'bottom-center' ),
		'style_preset'            => array( 'minimal', 'soft', 'outline', 'glass', 'bold', 'custom' ),
		'icon'                    => array( 'arrow-up', 'chevron-up', 'minimal-arrow', 'double-chevron', 'long-arrow', 'caret-up', 'slim-chevron', 'triangle-up' ),
		'shape'                   => array( 'circle', 'rounded', 'square' ),
		'kinetic_power'           => array( 'off', 'rocket_boost' ),
		'kinetic_power_intensity' => array( 'subtle', 'balanced', 'strong' ),
		'shadow'                  => array( 'none', 'soft', 'medium', 'strong' ),
		'progress_scope'          => array( 'page', 'content' ),
		'show_percentage'         => array( 'off', 'on', 'hover' ),
		'mobile_show_percentage'  => array( 'off', 'on', 'hover' ),
		'page_scope'              => array( 'entire_site', 'posts', 'pages', 'posts_pages', 'front_page', 'wc_products' ),
	);

	/**
	 * Inclusive [min, max] range for every integer field.
	 *
	 * Button sizes start at 44 so the rendered control can never fall below the
	 * WCAG target-size guidance, whatever a user types into the field.
	 */
	const RANGES = array(
		'trigger_offset'        => array( 50, 5000 ),
		'offset_x'              => array( 0, 200 ),
		'offset_y'              => array( 0, 200 ),
		'tablet_offset_x'       => array( 0, 200 ),
		'tablet_offset_y'       => array( 0, 200 ),
		'mobile_offset_x'       => array( 0, 200 ),
		'mobile_offset_y'       => array( 0, 200 ),
		'button_size'           => array( 44, 80 ),
		'icon_size'             => array( 12, 40 ),
		'tablet_button_size'    => array( 44, 80 ),
		'tablet_icon_size'      => array( 12, 40 ),
		'mobile_button_size'    => array( 44, 80 ),
		'mobile_icon_size'      => array( 12, 40 ),
		'bg_opacity'            => array( 10, 100 ),
		'border_width'          => array( 0, 6 ),
		'track_opacity'         => array( 0, 100 ),
		'ring_thickness'        => array( 1, 8 ),
		'mobile_ring_thickness' => array( 1, 8 ),
	);

	/** Every boolean field in the schema. */
	const BOOLS = array(
		'enabled',
		'smart_reveal',
		'peek_mode',
		'smart_return',
		'conflict_detection',
		'force_replace',
		'tablet_position_override',
		'mobile_position_override',
		'thumb_friendly',
		'smart_footer_dock',
		'tablet_size_override',
		'mobile_size_override',
		'backdrop_blur',
		'idle_fade',
		'hover_label',
		'progress_enabled',
		'mobile_progress_override',
		'show_desktop',
		'show_tablet',
		'show_mobile',
	);

	/** Every hex color field in the schema. */
	const COLORS = array(
		'bg_color',
		'icon_color',
		'hover_bg_color',
		'hover_icon_color',
		'border_color',
		'progress_color',
		'track_color',
	);

	/** Free-text CSS selector fields, validated conservatively. */
	const SELECTORS = array(
		'destination_selector',
		'progress_selector',
		'scroll_container_selector',
		'conflict_selector',
	);

	/**
	 * The complete default settings array. Also the schema: sanitize_settings()
	 * only ever emits these keys.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			// ---- General ----
			'enabled'                   => true,
			'scroll_motion'             => 'smart',
			'reveal_trigger'            => 'smart',
			'trigger_offset'            => 400,
			'smart_reveal'              => true,
			'peek_mode'                 => false,
			'smart_return'              => true,
			'scroll_destination'        => 'page_top',
			'destination_selector'      => '',

			// ---- Compatibility ----
			'scroll_container'          => 'auto',
			'scroll_container_selector' => '',
			'conflict_detection'        => true,
			'conflict_selector'         => '',
			'force_replace'             => false,

			// ---- Position ----
			'position'                  => 'bottom-right',
			'offset_x'                  => 24,
			'offset_y'                  => 24,
			'tablet_position_override'  => false,
			'tablet_position'           => 'bottom-right',
			'tablet_offset_x'           => 20,
			'tablet_offset_y'           => 20,
			'mobile_position_override'  => false,
			'mobile_position'           => 'bottom-right',
			'mobile_offset_x'           => 16,
			'mobile_offset_y'           => 18,
			'thumb_friendly'            => true,
			'smart_footer_dock'         => true,

			// ---- Appearance ----
			'style_preset'              => 'soft',
			'icon'                      => 'arrow-up',
			'shape'                     => 'circle',
			'kinetic_power'             => 'off',
			'kinetic_power_intensity'   => 'balanced',
			'button_size'               => 48,
			'icon_size'                 => 20,
			'tablet_size_override'      => false,
			'tablet_button_size'        => 46,
			'tablet_icon_size'          => 19,
			'mobile_size_override'      => false,
			'mobile_button_size'        => 46,
			'mobile_icon_size'          => 18,
			'bg_color'                  => '#111827',
			'icon_color'                => '#ffffff',
			'hover_bg_color'            => '#1f2937',
			'hover_icon_color'          => '#ffffff',
			'bg_opacity'                => 100,
			'backdrop_blur'             => false,
			'border_width'              => 0,
			'border_color'              => '#111827',
			'shadow'                    => 'soft',
			'idle_fade'                 => true,
			'hover_label'               => false,

			// ---- Progress ----
			'progress_enabled'          => true,
			'progress_scope'            => 'page',
			'progress_selector'         => '',
			'show_percentage'           => 'off',
			'progress_color'            => '#2563eb',
			'track_color'               => '#ffffff',
			'track_opacity'             => 25,
			'ring_thickness'            => 3,
			'mobile_progress_override'  => false,
			'mobile_ring_thickness'     => 3,
			'mobile_show_percentage'    => 'off',

			// ---- Visibility ----
			'show_desktop'              => true,
			'show_tablet'               => true,
			'show_mobile'               => true,
			'page_scope'                => 'entire_site',
		);
	}

	/**
	 * Registers the option with the Settings API so options.php owns the save
	 * request, including its nonce and capability checks.
	 */
	public function register() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::get_defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Returns the stored settings merged over the defaults, so callers never
	 * have to null-check an individual key.
	 *
	 * @return array
	 */
	public function get_settings() {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, self::get_defaults() );
	}

	/**
	 * Sanitizes a raw settings array coming from the settings form.
	 *
	 * Runs as the registered sanitize_callback, so it is reached only after
	 * options.php has already verified the nonce and the manage_options
	 * capability. Each field is validated independently: one bad value can
	 * never corrupt the rest of the array, it simply falls back to its default.
	 *
	 * @param mixed $input Raw submitted value.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults = self::get_defaults();

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$output = array();

		foreach ( $defaults as $key => $default ) {
			$raw = isset( $input[ $key ] ) ? $input[ $key ] : null;

			if ( in_array( $key, self::BOOLS, true ) ) {
				$output[ $key ] = $this->to_bool( $raw );
				continue;
			}

			if ( isset( self::ENUMS[ $key ] ) ) {
				$value          = is_scalar( $raw ) ? (string) $raw : '';
				$output[ $key ] = in_array( $value, self::ENUMS[ $key ], true ) ? $value : $default;
				continue;
			}

			if ( isset( self::RANGES[ $key ] ) ) {
				$output[ $key ] = $this->clamp_int( $raw, self::RANGES[ $key ][0], self::RANGES[ $key ][1], $default );
				continue;
			}

			if ( in_array( $key, self::COLORS, true ) ) {
				$output[ $key ] = $this->sanitize_color( $raw, $default );
				continue;
			}

			if ( in_array( $key, self::SELECTORS, true ) ) {
				$output[ $key ] = $this->sanitize_selector( $raw );
				continue;
			}

			$output[ $key ] = $default;
		}

		return $this->apply_cross_field_rules( $output );
	}

	/**
	 * Enforces the few constraints that span more than one field, after every
	 * individual field has already been validated.
	 *
	 * An icon larger than its button would overflow the control, so each icon
	 * size is capped against the button size it renders inside. Peek mode is
	 * only meaningful while Smart Reveal is doing the hiding.
	 *
	 * @param array $settings Field-validated settings.
	 * @return array
	 */
	private function apply_cross_field_rules( array $settings ) {
		$pairs = array(
			'icon_size'        => 'button_size',
			'tablet_icon_size' => 'tablet_button_size',
			'mobile_icon_size' => 'mobile_button_size',
		);

		foreach ( $pairs as $icon_key => $button_key ) {
			$max = max( self::RANGES[ $icon_key ][0], $settings[ $button_key ] - 12 );
			if ( $settings[ $icon_key ] > $max ) {
				$settings[ $icon_key ] = $max;
			}
		}

		if ( ! $settings['smart_reveal'] ) {
			$settings['peek_mode'] = false;
		}

		// Force Replace has nothing to act on without detection, and the field
		// is hidden in the UI while detection is off. Storing it as on anyway
		// would leave an invisible setting that appears to do nothing.
		if ( ! $settings['conflict_detection'] ) {
			$settings['force_replace'] = false;
		}

		return $settings;
	}

	/**
	 * Casts a submitted checkbox value to a real boolean.
	 *
	 * Every checkbox in the form is preceded by a hidden input carrying "0",
	 * so an unchecked box arrives as the string "0" rather than being absent.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( $value, array( 1, '1', 'true', 'on', 'yes' ), true );
	}

	/**
	 * Returns an integer clamped to [ $min, $max ], or $fallback when the input
	 * is not numeric at all.
	 *
	 * @param mixed $value    Raw value.
	 * @param int   $min      Lower bound.
	 * @param int   $max      Upper bound.
	 * @param int   $fallback Fallback for non-numeric input.
	 * @return int
	 */
	private function clamp_int( $value, $min, $max, $fallback ) {
		if ( ! is_scalar( $value ) || ! is_numeric( $value ) ) {
			return (int) $fallback;
		}
		return (int) max( $min, min( $max, (int) $value ) );
	}

	/**
	 * Validates a 3 or 6 digit hex color and returns the fallback if invalid.
	 *
	 * @param mixed  $value    Raw value.
	 * @param string $fallback Fallback color.
	 * @return string
	 */
	private function sanitize_color( $value, $fallback ) {
		$color = is_scalar( $value ) ? sanitize_hex_color( (string) $value ) : null;
		return ( is_string( $color ) && '' !== $color ) ? $color : $fallback;
	}

	/**
	 * Conservatively validates an optional CSS selector.
	 *
	 * The value is only ever handed to document.querySelector() in the browser,
	 * so the goal is to reject anything that is not plausibly a simple content
	 * selector rather than to support the full CSS grammar. Anything outside the
	 * allowlisted character set, or longer than 120 characters, is discarded
	 * entirely and the feature falls back to its automatic detection.
	 *
	 * Pseudo-classes and combinator punctuation beyond the child combinator are
	 * deliberately excluded: they buy nothing for pointing at a content region,
	 * and allowing the colon would let values such as "javascript:alert(1)"
	 * through the filter. They cannot execute in querySelector(), but a stored
	 * setting that merely looks like an injection is not worth keeping.
	 *
	 * @param mixed $value Raw value.
	 * @return string Sanitized selector, or an empty string.
	 */
	private function sanitize_selector( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$selector = trim( wp_strip_all_tags( (string) $value ) );

		if ( '' === $selector || strlen( $selector ) > 120 ) {
			return '';
		}

		// Allowlist: class, id, tag, attribute, descendant, child, and comma
		// separated selector lists. A list is useful for the compatibility fields,
		// where a theme can render more than one competing control, and it is no
		// less safe: the value only ever reaches querySelectorAll().
		if ( ! preg_match( '/^[A-Za-z0-9_\-#.\[\]="\' >,]+$/', $selector ) ) {
			return '';
		}

		// A selector must start with a tag, class, id, or attribute token.
		if ( ! preg_match( '/^[A-Za-z.#\[]/', $selector ) ) {
			return '';
		}

		return $selector;
	}
}
