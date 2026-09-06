<?php
/**
 * Admin form control renderers.
 *
 * The tab views describe *which* controls exist; this class owns *how* they are
 * marked up, so every field in the settings screen shares one accessible
 * structure and one set of CSS hooks. Admin-only: it is never loaded on a
 * frontend request.
 *
 * Naming contract used throughout:
 *   input name -> khstt_settings[<key>]   (the single registered option)
 *   input id   -> khstt-<key with dashes>
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the settings screen's form controls with one shared structure.
 */
class KHSTT_UI {

	/**
	 * Returns the form input name for a settings key.
	 *
	 * @param string $key Settings key.
	 * @return string
	 */
	public static function name( $key ) {
		return KHSTT_Settings::OPTION_KEY . '[' . $key . ']';
	}

	/**
	 * Returns the DOM id for a settings key.
	 *
	 * @param string $key Settings key.
	 * @return string
	 */
	public static function id( $key ) {
		return 'khstt-' . str_replace( '_', '-', $key );
	}

	/**
	 * Opens a settings card.
	 *
	 * @param string $title Section title.
	 * @param string $note  Optional supporting line under the title.
	 */
	public static function section_open( $title, $note = '' ) {
		echo '<section class="khstt-card">';
		echo '<div class="khstt-card__head">';
		echo '<h3 class="khstt-card__title">' . esc_html( $title ) . '</h3>';
		if ( '' !== $note ) {
			echo '<p class="khstt-card__note">' . esc_html( $note ) . '</p>';
		}
		echo '</div><div class="khstt-card__body">';
	}

	/** Closes a settings card. */
	public static function section_close() {
		echo '</div></section>';
	}

	/**
	 * Opens one field row.
	 *
	 * @param array $args {
	 *     Field row configuration.
	 *
	 *     @type string $key      Settings key this row is for, used for the label's for attribute.
	 *     @type string $label    Visible label text.
	 *     @type string $help     Optional help text under the label.
	 *     @type bool   $smart    Whether to show the Smart badge.
	 *     @type bool   $caution  Whether to show the Advanced badge.
	 *     @type string $when     Optional dependency, e.g. "reveal_trigger=custom".
	 *     @type bool   $fieldset Render as a fieldset/legend instead of a label.
	 *     @type string $layout   "row" (default) or "stack" for full width controls.
	 * }
	 */
	public static function field_open( array $args ) {
		$args = array_merge(
			array(
				'key'      => '',
				'label'    => '',
				'help'     => '',
				'smart'    => false,
				'caution'  => false,
				'when'     => '',
				'fieldset' => false,
				'layout'   => 'row',
			),
			$args
		);

		$tag     = $args['fieldset'] ? 'fieldset' : 'div';
		$classes = 'khstt-field khstt-field--' . ( 'stack' === $args['layout'] ? 'stack' : 'row' );

		printf(
			'<%1$s class="%2$s"%3$s>',
			esc_html( $tag ),
			esc_attr( $classes ),
			'' !== $args['when'] ? ' data-khstt-when="' . esc_attr( $args['when'] ) . '"' : ''
		);

		echo '<div class="khstt-field__head">';

		$label_inner = esc_html( $args['label'] );
		if ( $args['smart'] ) {
			$label_inner .= ' ' . self::badge_markup();
		}
		if ( $args['caution'] ) {
			$label_inner .= ' ' . self::caution_markup();
		}

		if ( $args['fieldset'] ) {
			echo '<legend class="khstt-field__label">' . self::kses_label( $label_inner ) . '</legend>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses_label() is the escaping step.
		} else {
			printf(
				'<label class="khstt-field__label" for="%1$s">%2$s</label>',
				esc_attr( self::id( $args['key'] ) ),
				self::kses_label( $label_inner ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses_label() is the escaping step.
			);
		}

		if ( '' !== $args['help'] ) {
			echo '<p class="khstt-field__help">' . esc_html( $args['help'] ) . '</p>';
		}

		echo '</div><div class="khstt-field__control">';
	}

	/**
	 * Closes a field row.
	 *
	 * @param bool $fieldset Must match the value passed to field_open().
	 */
	public static function field_close( $fieldset = false ) {
		echo '</div>';
		echo $fieldset ? '</fieldset>' : '</div>';
	}

	/**
	 * Renders an on/off switch.
	 *
	 * The paired hidden input guarantees the key is always present in the POST
	 * body, so an unchecked box is an explicit "0" rather than a missing key.
	 *
	 * @param string $key     Settings key.
	 * @param bool   $checked Current value.
	 * @param string $variant Optional style variant, e.g. "large".
	 */
	public static function toggle( $key, $checked, $variant = '' ) {
		printf(
			'<span class="khstt-switch%1$s"><input type="hidden" name="%2$s" value="0"><input type="checkbox" class="khstt-switch__input" id="%3$s" name="%2$s" value="1" data-khstt-key="%4$s"%5$s><span class="khstt-switch__track" aria-hidden="true"><span class="khstt-switch__thumb"></span></span></span>',
			'' !== $variant ? ' khstt-switch--' . esc_attr( $variant ) : '',
			esc_attr( self::name( $key ) ),
			esc_attr( self::id( $key ) ),
			esc_attr( $key ),
			checked( (bool) $checked, true, false )
		);
	}

	/**
	 * Renders a select control.
	 *
	 * @param string $key     Settings key.
	 * @param string $value   Current value.
	 * @param array  $choices Value to label map.
	 */
	public static function select( $key, $value, array $choices ) {
		printf(
			'<select class="khstt-select" id="%1$s" name="%2$s" data-khstt-key="%3$s">',
			esc_attr( self::id( $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( $key )
		);

		foreach ( $choices as $choice_value => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $choice_value ),
				selected( (string) $value, (string) $choice_value, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Renders a number field paired with a range slider.
	 *
	 * The number input is the accessible control and the one that carries the
	 * form name, so the field still works and submits with JavaScript disabled.
	 * The range is a pointer-only affordance: it is removed from the tab order
	 * and hidden from assistive technology so the setting has exactly one tab
	 * stop and one accessible name.
	 *
	 * @param string $key   Settings key.
	 * @param int    $value Current value.
	 * @param int    $min   Minimum.
	 * @param int    $max   Maximum.
	 * @param string $unit  Unit suffix shown after the field.
	 */
	public static function number( $key, $value, $min, $max, $unit = 'px' ) {
		printf(
			'<span class="khstt-number"><input type="range" class="khstt-number__range" tabindex="-1" aria-hidden="true" min="%1$d" max="%2$d" step="1" value="%3$d" data-khstt-range-for="%4$s"><span class="khstt-number__entry"><input type="number" class="khstt-number__input" id="%4$s" name="%5$s" min="%1$d" max="%2$d" step="1" value="%3$d" data-khstt-key="%6$s">',
			(int) $min,
			(int) $max,
			(int) $value,
			esc_attr( self::id( $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( $key )
		);

		if ( '' !== $unit ) {
			echo '<span class="khstt-number__unit" aria-hidden="true">' . esc_html( $unit ) . '</span>';
		}

		echo '</span></span>';
	}

	/**
	 * Renders a color field: a native swatch that carries the form value, plus
	 * a hex text input for pasting an exact value. Both stay in sync client
	 * side; only the swatch is submitted.
	 *
	 * @param string $key   Settings key.
	 * @param string $value Current hex color.
	 */
	public static function color( $key, $value ) {
		printf(
			'<span class="khstt-color"><input type="color" class="khstt-color__swatch" id="%1$s" name="%2$s" value="%3$s" data-khstt-key="%4$s"><input type="text" class="khstt-color__hex" value="%3$s" maxlength="7" spellcheck="false" autocomplete="off" data-khstt-hex-for="%1$s" aria-label="%5$s"></span>',
			esc_attr( self::id( $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( $value ),
			esc_attr( $key ),
			esc_attr__( 'Hex color value', 'kinetichub-scroll-to-top' )
		);
	}

	/**
	 * Renders a text field.
	 *
	 * @param string $key         Settings key.
	 * @param string $value       Current value.
	 * @param string $placeholder Placeholder text.
	 */
	public static function text( $key, $value, $placeholder = '' ) {
		printf(
			'<input type="text" class="khstt-text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" spellcheck="false" autocomplete="off" data-khstt-key="%5$s">',
			esc_attr( self::id( $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( $value ),
			esc_attr( $placeholder ),
			esc_attr( $key )
		);
	}

	/**
	 * Renders a group of selectable cards backed by native radio inputs, so
	 * arrow-key navigation and screen reader grouping come from the platform
	 * rather than from JavaScript.
	 *
	 * @param string $key     Settings key.
	 * @param string $value   Current value.
	 * @param array  $choices Value => array( 'label' => string, 'visual' => string HTML,
	 *                        'note' => string, 'smart' => bool ).
	 * @param string $variant Optional layout variant, e.g. "tiles" or "compact".
	 */
	public static function cards( $key, $value, array $choices, $variant = '' ) {
		printf(
			'<div class="khstt-cards%s">',
			'' !== $variant ? ' khstt-cards--' . esc_attr( $variant ) : ''
		);

		$index = 0;
		foreach ( $choices as $choice_value => $choice ) {
			$label  = isset( $choice['label'] ) ? $choice['label'] : (string) $choice_value;
			$visual = isset( $choice['visual'] ) ? $choice['visual'] : '';
			$note   = isset( $choice['note'] ) ? $choice['note'] : '';

			printf(
				'<label class="khstt-card-opt"><input type="radio" class="khstt-card-opt__input" %1$s name="%2$s" value="%3$s" data-khstt-key="%4$s"%5$s>',
				0 === $index ? 'id="' . esc_attr( self::id( $key ) ) . '"' : '',
				esc_attr( self::name( $key ) ),
				esc_attr( $choice_value ),
				esc_attr( $key ),
				checked( (string) $value, (string) $choice_value, false )
			);

			echo '<span class="khstt-card-opt__inner">';

			if ( '' !== $visual ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses_visual() is the escaping step.
				echo '<span class="khstt-card-opt__visual" aria-hidden="true">' . self::kses_visual( $visual ) . '</span>';
			}

			echo '<span class="khstt-card-opt__label">' . esc_html( $label );
			if ( ! empty( $choice['smart'] ) ) {
				echo ' ' . self::kses_label( self::badge_markup() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses_label() is the escaping step.
			}
			echo '</span>';

			if ( '' !== $note ) {
				echo '<span class="khstt-card-opt__note">' . esc_html( $note ) . '</span>';
			}

			echo '</span></label>';
			++$index;
		}

		echo '</div>';
	}

	/**
	 * Returns the Smart badge markup used next to adaptive features.
	 *
	 * @return string
	 */
	public static function badge_markup() {
		return '<span class="khstt-badge">' . KHSTT_Icons::get( 'sparkle', 'khstt-badge__icon' )
			. '<span class="khstt-badge__text">' . esc_html__( 'Smart', 'kinetichub-scroll-to-top' ) . '</span></span>';
	}

	/**
	 * Returns the Advanced badge used next to settings that need testing on the
	 * live site before they are trusted.
	 *
	 * @return string
	 */
	public static function caution_markup() {
		return '<span class="khstt-badge khstt-badge--caution">'
			. '<span class="khstt-badge__text">' . esc_html__( 'Advanced', 'kinetichub-scroll-to-top' ) . '</span></span>';
	}

	/**
	 * Filters label markup (text plus the Smart badge) through the allowlist.
	 *
	 * @param string $markup Label markup.
	 * @return string
	 */
	private static function kses_label( $markup ) {
		return str_replace( 'viewbox=', 'viewBox=', wp_kses( $markup, self::label_allowed_html() ) );
	}

	/**
	 * Filters card visual markup through the allowlist.
	 *
	 * @param string $markup Visual markup.
	 * @return string
	 */
	private static function kses_visual( $markup ) {
		return str_replace( 'viewbox=', 'viewBox=', wp_kses( $markup, self::visual_allowed_html() ) );
	}

	/**
	 * Allowed HTML inside a field label: the Smart badge and its icon only.
	 *
	 * @return array
	 */
	private static function label_allowed_html() {
		return array_merge(
			KHSTT_Icons::allowed_svg(),
			array( 'span' => array( 'class' => true ) )
		);
	}

	/**
	 * Allowed HTML inside a card visual: plugin-drawn SVG and styled spans.
	 *
	 * @return array
	 */
	private static function visual_allowed_html() {
		return array_merge(
			KHSTT_Icons::allowed_svg(),
			array(
				'span' => array(
					'class' => true,
					'style' => true,
				),
			)
		);
	}
}
