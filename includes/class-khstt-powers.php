<?php
/**
 * KineticPowers: the activation choreographies the control can play.
 *
 * A KineticPower is a purely decorative layer that follows the existing scroll
 * lifecycle. Nothing here touches scrolling, geometry, or the compatibility
 * engine: this class owns the list of powers, their labels, and the hardcoded
 * SVG each one draws with.
 *
 * One power is implemented, Rocket Boost. The shape of this class is the whole
 * extension point - a new power is a new entry in self::get_choices() plus its
 * own markup constant and its own block of scoped CSS. Anything more abstract
 * than that would be scaffolding for code that does not exist yet.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry for the KineticPowers feature family.
 */
class KHSTT_Powers {

	/** The power that means "no choreography at all". */
	const NONE = 'off';

	/** The one implemented power. */
	const ROCKET = 'rocket_boost';

	/**
	 * Smallest upward distance, in pixels, worth a choreography.
	 *
	 * The sequence is a response to travelling, not to clicking, so an
	 * activation with nowhere meaningful to go gets nothing at all.
	 */
	const MIN_TRAVEL = 120;

	/**
	 * The Rocket glyph, drawn on the same 24x24 grid as the utility icons so it
	 * inherits their optical weight.
	 *
	 * Deliberately not part of KHSTT_Icons: the icon library is the user-facing
	 * picker, and Rocket must never become a ninth choice there. It belongs to
	 * this power and is only ever rendered as part of its choreography.
	 *
	 * Stroke-only except the window, which is filled so it still reads as a
	 * window at 12px. Everything uses currentColor, so the Rocket takes the
	 * button's existing icon colour with no new setting.
	 *
	 * @var string
	 */
	private static $rocket_shapes = '<path d="M12 2.6c2.5 2.4 3.9 5.5 3.9 8.8 0 1.5-.3 2.9-.9 4.2H9c-.6-1.3-.9-2.7-.9-4.2 0-3.3 1.4-6.4 3.9-8.8z"/>'
		. '<path d="M8.4 11.9 6 14.6c-.4.5-.6 1.1-.6 1.7v2.2l3-2.1"/>'
		. '<path d="m15.6 11.9 2.4 2.7c.4.5.6 1.1.6 1.7v2.2l-3-2.1"/>'
		. '<circle cx="12" cy="9.3" r="1.5" fill="currentColor"/>';

	/**
	 * Returns the selectable KineticPowers, in display order.
	 *
	 * @return array<string, array> Power key to label and description.
	 */
	public static function get_choices() {
		return array(
			self::NONE   => array(
				'label' => __( 'Off', 'kinetichub-scroll-to-top' ),
				'note'  => __( 'Use the standard Scroll to Top interaction.', 'kinetichub-scroll-to-top' ),
			),
			self::ROCKET => array(
				'label' => __( 'Rocket Boost', 'kinetichub-scroll-to-top' ),
				'note'  => __( 'Launch a compact rocket sequence with ignition, travel and a soft arrival spark.', 'kinetichub-scroll-to-top' ),
			),
		);
	}

	/**
	 * The phase envelope, in milliseconds.
	 *
	 * This is the one place the numbers live. Both runtimes that play a
	 * sequence - the front-end engine and the admin live preview - are handed
	 * this array rather than carrying their own copy.
	 *
	 * khstt-powers.css draws a launch of exactly "launch" and an arrival of
	 * exactly "arrival"; its travel animation runs 420ms and then holds, so
	 * neither script has to know when travel ends, only how much of it must be
	 * seen before the arrival accent may start.
	 *
	 *   launch     ignition and recoil
	 *   travelMin  how much of the flight must be seen. An instant scroll
	 *              settles in a single frame, and cutting straight from
	 *              ignition to spark reads as a glitch rather than a flight.
	 *   arrival    the spark
	 *   ceiling    longest a sequence may stay on screen without the scroll
	 *              reporting an arrival. Comfortably past the 1500ms ceiling
	 *              native smooth scrolling is given, so it only ever catches a
	 *              scroll that never completed at all.
	 *
	 * @return array<string, int>
	 */
	public static function timings() {
		return array(
			'launch'    => 140,
			'travelMin' => 240,
			'arrival'   => 300,
			'ceiling'   => 2400,
		);
	}

	/**
	 * The runtime configuration for a selected power, or false when none is.
	 *
	 * @param string $power Power key.
	 * @return array|false
	 */
	public static function runtime_config( $power ) {
		if ( ! self::is_active( $power ) ) {
			return false;
		}

		return array_merge(
			array(
				'name'      => self::slug( $power ),
				'minTravel' => self::MIN_TRAVEL,
			),
			self::timings()
		);
	}

	/**
	 * Returns the amplitude choices offered once a power is selected.
	 *
	 * Intensity scales the visual only - recoil, travel distance, trail and
	 * spark strength. It never reaches the scroll itself.
	 *
	 * @return array<string, string> Intensity key to translated label.
	 */
	public static function get_intensities() {
		return array(
			'subtle'   => __( 'Subtle', 'kinetichub-scroll-to-top' ),
			'balanced' => __( 'Balanced', 'kinetichub-scroll-to-top' ),
			'strong'   => __( 'Strong', 'kinetichub-scroll-to-top' ),
		);
	}

	/**
	 * Whether a power key names a choreography that actually does something.
	 *
	 * @param string $power Power key.
	 * @return bool
	 */
	public static function is_active( $power ) {
		return self::NONE !== $power && array_key_exists( $power, self::get_choices() );
	}

	/**
	 * Maps a settings value to the value used in markup and CSS.
	 *
	 * The settings key uses the plugin's underscore convention; attributes and
	 * class names use dashes, as everything else in the stylesheet does.
	 *
	 * @param string $power Power key.
	 * @return string
	 */
	public static function slug( $power ) {
		return str_replace( '_', '-', (string) $power );
	}

	/**
	 * Returns the Rocket SVG markup.
	 *
	 * @param string $css_class CSS class for the svg element.
	 * @return string SVG markup.
	 */
	public static function rocket_svg( $css_class = 'khstt__rocket-glyph' ) {
		return sprintf(
			'<svg class="%1$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
			esc_attr( $css_class ),
			self::$rocket_shapes
		);
	}

	/**
	 * Returns the small illustration shown inside a power's choice card.
	 *
	 * Off is drawn with the plain arrow so the two cards read as a comparison
	 * of the same control rather than as an icon picker.
	 *
	 * @param string $power Power key.
	 * @return string Markup, still to be filtered through the SVG allowlist.
	 */
	public static function card_visual( $power ) {
		if ( self::ROCKET !== $power ) {
			return '<span class="khstt-power-choice">'
				. KHSTT_Icons::get( 'arrow-up', 'khstt-power-choice__glyph' )
				. '</span>';
		}

		// The two streaks are the card's own shorthand for the travel phase.
		// They are part of the illustration, not of the rendered choreography.
		return '<span class="khstt-power-choice">'
			. self::rocket_svg( 'khstt-power-choice__glyph' )
			. '<svg class="khstt-power-choice__streaks" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
			. ' stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false">'
			. '<path d="M9 19.4v2.2" opacity=".7"/><path d="M15 19.4v2.2" opacity=".7"/>'
			. '<path d="M12 20.2v3" opacity=".45"/>'
			. '</svg></span>';
	}

	/**
	 * Prints the decorative layer a power animates inside the button.
	 *
	 * The stage is absolutely positioned and takes no pointer events, so it
	 * cannot participate in the button's layout, cannot be measured as part of
	 * the progress ring's box, and cannot become a hit target. Nothing is
	 * printed at all when no power is selected.
	 *
	 * @param string $power Power key.
	 */
	public static function render_stage( $power ) {
		if ( ! self::is_active( $power ) ) {
			return;
		}

		echo '<span class="khstt__power" aria-hidden="true">';
		echo '<span class="khstt__trail"></span>';
		echo '<span class="khstt__rocket">' . KHSTT_Icons::kses( self::rocket_svg() ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- KHSTT_Icons::kses() is the escaping step.
		echo '<span class="khstt__spark"></span>';
		echo '</span>';
	}
}
