<?php
/**
 * Inline SVG icon library.
 *
 * Every glyph the plugin can ever render is a hardcoded constant in this file,
 * selected by an allowlisted key. No SVG is ever accepted from user input, and
 * nothing is loaded from an external icon library or font.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides every inline SVG glyph the plugin can render.
 */
class KHSTT_Icons {

	/**
	 * Shape markup for each icon, drawn on a 24x24 grid.
	 *
	 * Shapes use currentColor throughout, so a single CSS color property drives
	 * the icon and its hover state. Most are stroke-only; Caret Up and Triangle
	 * Up are filled as well as stroked, which is what gives them a solid read at
	 * small sizes without a heavier stroke. No new attribute is needed for that
	 * - fill is already on the allowlist.
	 *
	 * @var array<string, string>
	 */
	private static $shapes = array(
		'arrow-up'       => '<path d="M12 19V5"/><path d="M5 12l7-7 7 7"/>',
		'chevron-up'     => '<path d="M6 15l6-6 6 6"/>',
		'minimal-arrow'  => '<path d="M12 17.5V7.5"/><path d="M7.5 12l4.5-4.5 4.5 4.5"/>',
		'double-chevron' => '<path d="M6 17l6-6 6 6"/><path d="M6 11l6-6 6 6"/>',
		'long-arrow'     => '<path d="M12 20V4"/><path d="M6.5 9.5L12 4l5.5 5.5"/>',
		'caret-up'       => '<path d="M6 14.5l6-6 6 6" fill="currentColor" stroke-linejoin="round"/>',
		'slim-chevron'   => '<path d="M5.5 14.75l6.5-6.5 6.5 6.5"/>',
		'triangle-up'    => '<path d="M12 6.5l7 11H5l7-11z" fill="currentColor" stroke-linejoin="round"/>',
		'arrow-down'     => '<path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/>',
		'sparkle'        => '<path d="M12 3l1.9 5.2L19 10l-5.1 1.8L12 17l-1.9-5.2L5 10l5.1-1.8L12 3z"/>',

		/*
		 * Settings tab glyphs. They are kept in this map rather than inlined in
		 * the view so every SVG the plugin prints still comes from one place and
		 * goes through one allowlist, and they are deliberately absent from
		 * get_choices() so none of them can turn up in the control's icon
		 * picker. Two of them say what this plugin is: "progress" is the reading
		 * ring with its leading arc lit, and "position" is a viewport with the
		 * control parked in a corner.
		 */
		'tab-general'    => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2.4M12 18.6V21M21 12h-2.4M5.4 12H3M18.36 5.64 15.66 8.34M8.34 15.66 5.64 18.36M18.36 18.36 15.66 15.66M8.34 8.34 5.64 5.64"/>',
		'tab-position'   => '<rect x="3.2" y="4.2" width="17.6" height="15.6" rx="2.6"/><circle cx="16.6" cy="15.6" r="2.1" fill="currentColor" stroke="none"/>',
		'tab-appearance' => '<circle cx="12" cy="12" r="8.4"/><path d="M12 3.6a8.4 8.4 0 0 1 0 16.8z" fill="currentColor" stroke="none"/>',
		'tab-progress'   => '<circle cx="12" cy="12" r="8.4" stroke-opacity="0.38"/><path d="M12 3.6a8.4 8.4 0 0 1 8.4 8.4"/>',
		'tab-visibility' => '<path d="M2.7 12S6.2 5.8 12 5.8 21.3 12 21.3 12 17.8 18.2 12 18.2 2.7 12 2.7 12Z"/><circle cx="12" cy="12" r="2.7"/>',
	);

	/**
	 * Stroke width per icon, tuned so each glyph reads at the same visual
	 * weight despite differing path lengths.
	 *
	 * @var array<string, string>
	 */
	private static $weights = array(
		'arrow-up'       => '2',
		'chevron-up'     => '2.1',
		'minimal-arrow'  => '1.7',
		'double-chevron' => '2',
		'long-arrow'     => '1.9',
		'caret-up'       => '2',
		'slim-chevron'   => '1.5',
		'triangle-up'    => '2',
		'arrow-down'     => '2',
		'sparkle'        => '1.5',
		'tab-general'    => '1.7',
		'tab-position'   => '1.7',
		'tab-appearance' => '1.7',
		'tab-progress'   => '2',
		'tab-visibility' => '1.7',
	);

	/**
	 * Icon choices offered in the Appearance tab, in display order.
	 *
	 * @return array<string, string> Icon key to translated label.
	 */
	public static function get_choices() {
		return array(
			'arrow-up'       => __( 'Arrow Up', 'kinetichub-scroll-to-top' ),
			'chevron-up'     => __( 'Chevron Up', 'kinetichub-scroll-to-top' ),
			'minimal-arrow'  => __( 'Minimal Arrow', 'kinetichub-scroll-to-top' ),
			'double-chevron' => __( 'Double Chevron', 'kinetichub-scroll-to-top' ),
			'long-arrow'     => __( 'Long Arrow', 'kinetichub-scroll-to-top' ),
			'caret-up'       => __( 'Caret Up', 'kinetichub-scroll-to-top' ),
			'slim-chevron'   => __( 'Slim Chevron', 'kinetichub-scroll-to-top' ),
			'triangle-up'    => __( 'Triangle Up', 'kinetichub-scroll-to-top' ),
		);
	}

	/**
	 * Returns the complete inline SVG markup for an icon key.
	 *
	 * @param string $key   Icon key. Unknown keys fall back to arrow-up.
	 * @param string $css_class  Optional CSS class for the svg element.
	 * @return string SVG markup.
	 */
	public static function get( $key, $css_class = 'khstt__glyph' ) {
		if ( ! isset( self::$shapes[ $key ] ) ) {
			$key = 'arrow-up';
		}

		return sprintf(
			'<svg class="%1$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%2$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
			esc_attr( $css_class ),
			esc_attr( self::$weights[ $key ] ),
			self::$shapes[ $key ]
		);
	}

	/**
	 * Echoes an icon, filtered through the SVG allowlist.
	 *
	 * @param string $key   Icon key.
	 * @param string $css_class  Optional CSS class for the svg element.
	 */
	public static function render( $key, $css_class = 'khstt__glyph' ) {
		echo self::kses( self::get( $key, $css_class ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self::kses() is the escaping step.
	}

	/**
	 * Runs SVG markup through the allowlist and restores the one attribute name
	 * wp_kses lower-cases.
	 *
	 * Attribute names are normalised to lowercase by wp_kses, which turns viewBox
	 * into viewbox. HTML parsers do map that back for inline SVG, but relying on
	 * that would leave every icon one parser quirk away from losing its
	 * coordinate system, so the canonical casing is restored explicitly.
	 *
	 * @param string $markup SVG markup built by this class.
	 * @return string Filtered markup, safe to echo.
	 */
	public static function kses( $markup ) {
		return str_replace( 'viewbox=', 'viewBox=', wp_kses( $markup, self::allowed_svg() ) );
	}

	/**
	 * The wp_kses allowlist used for every SVG this plugin prints.
	 *
	 * Deliberately narrow: shape and container elements only, with no script,
	 * foreignObject, image, use, or event handler attributes.
	 *
	 * @return array
	 */
	public static function allowed_svg() {
		$shape_attrs = array(
			'class'            => true,
			'd'                => true,
			'fill'             => true,
			'stroke'           => true,
			'stroke-width'     => true,
			'stroke-linecap'   => true,
			'stroke-linejoin'  => true,
			'stroke-dasharray' => true,
			'stroke-opacity'   => true,
			'fill-opacity'     => true,
			'opacity'          => true,
			'x'                => true,
			'y'                => true,
			'rx'               => true,
			'ry'               => true,
			'cx'               => true,
			'cy'               => true,
			'r'                => true,
			'width'            => true,
			'height'           => true,
		);

		return array(
			'svg'    => array_merge(
				$shape_attrs,
				array(
					'viewbox'     => true,
					'xmlns'       => true,
					'aria-hidden' => true,
					'focusable'   => true,
					'role'        => true,
				)
			),
			'g'      => $shape_attrs,
			'path'   => $shape_attrs,
			'rect'   => $shape_attrs,
			'circle' => $shape_attrs,
			'line'   => array_merge(
				$shape_attrs,
				array(
					'x1' => true,
					'y1' => true,
					'x2' => true,
					'y2' => true,
				)
			),
		);
	}
}
