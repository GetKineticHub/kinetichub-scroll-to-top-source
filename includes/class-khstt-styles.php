<?php
/**
 * Generates the per-site CSS custom property block for the frontend control.
 *
 * The static stylesheet holds every rule; this class only emits the values that
 * change from site to site (sizes, offsets, colors) plus the breakpoint blocks
 * that implement the responsive override model. Anything that can be expressed
 * as a data attribute on the wrapper (shape, shadow, icon) stays in the static
 * stylesheet instead so this generated block stays small.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the CSS custom property block that carries the site's own values.
 */
class KHSTT_Styles {

	/** Tablet range, matching the documented breakpoint model. */
	const TABLET_MIN = 768;
	const TABLET_MAX = 1024;

	/** Mobile is everything at or below this width. */
	const MOBILE_MAX = 767;

	/**
	 * Thumb Friendly floors applied to the mobile breakpoint: a comfortable
	 * touch target and enough clearance from the screen edges that the control
	 * does not sit under browser chrome or a gesture bar.
	 */
	const THUMB_MIN_SIZE   = 48;
	const THUMB_MIN_OFFSET = 16;

	/**
	 * Interaction timings, in milliseconds.
	 *
	 * Neither is a setting: each is one opinionated number, and a slider for it
	 * would be a worse product than a good default. They live here, beside the
	 * Thumb Friendly floors, because this is the class both the front end and
	 * the admin live preview already read shared values from - and the preview
	 * has to demonstrate the same timing the visitor will actually get.
	 *
	 * IDLE_AFTER is long enough not to fire between two deliberate scrolls, and
	 * short enough that a reader who has settled gets the quieter control while
	 * they are still on the same screen.
	 */
	const IDLE_AFTER = 2800;
	const PRESS_MS   = 110;

	/**
	 * Builds the complete inline CSS for the current settings.
	 *
	 * @param array $s Sanitized settings.
	 * @return string CSS, ready for wp_add_inline_style().
	 */
	public static function build( array $s ) {
		$css = '#khstt{' . self::base_vars( $s ) . self::position_rules( $s['position'], $s['offset_x'], $s['offset_y'], ! empty( $s['hover_label'] ) ) . '}';

		$css .= self::tablet_block( $s );
		$css .= self::mobile_block( $s );
		$css .= self::visibility_blocks( $s );

		return $css;
	}

	/**
	 * The desktop (base) custom properties every breakpoint inherits from.
	 *
	 * @param array $s Sanitized settings.
	 * @return string
	 */
	private static function base_vars( array $s ) {
		return sprintf(
			'--khstt-size:%1$dpx;--khstt-icon:%2$dpx;--khstt-bg:%3$s;--khstt-fg:%4$s;--khstt-bg-h:%5$s;--khstt-fg-h:%6$s;--khstt-bw:%7$dpx;--khstt-bc:%8$s;--khstt-ring-t:%9$dpx;--khstt-ring-c:%10$s;--khstt-track-c:%11$s;--khstt-track-o:%12$s;',
			(int) $s['button_size'],
			(int) $s['icon_size'],
			self::rgba( $s['bg_color'], $s['bg_opacity'] ),
			self::rgba( $s['icon_color'], 100 ),
			self::rgba( $s['hover_bg_color'], $s['bg_opacity'] ),
			self::rgba( $s['hover_icon_color'], 100 ),
			(int) $s['border_width'],
			self::rgba( $s['border_color'], 100 ),
			(int) $s['ring_thickness'],
			self::rgba( $s['progress_color'], 100 ),
			self::rgba( $s['track_color'], 100 ),
			self::ratio( $s['track_opacity'] )
		);
	}

	/**
	 * Returns the inset declarations for one position preset.
	 *
	 * Safe-area insets are added here rather than exposed as settings, so the
	 * control clears notches and home indicators without any configuration.
	 * The wrapper's own transform consumes --khstt-tx, which is what centres
	 * the bottom-center preset without a second positioned element.
	 *
	 * The hover label is placed from here too, because which side of the
	 * control it belongs on is entirely a function of where the control sits.
	 * Emitting it as custom properties in the same per-breakpoint block means
	 * the label follows every responsive position override for free, with no
	 * media queries in the static stylesheet and no geometry read in the
	 * browser.
	 *
	 * @param string $position One of the position enum values.
	 * @param int    $offset_x Horizontal offset in pixels.
	 * @param int    $offset_y Bottom offset in pixels.
	 * @param bool   $label    Whether the hover label needs placing.
	 * @return string
	 */
	private static function position_rules( $position, $offset_x, $offset_y, $label = false ) {
		$offset_x = (int) $offset_x;
		$offset_y = (int) $offset_y;

		if ( 'bottom-left' === $position ) {
			$horizontal = sprintf( 'right:auto;left:calc(%dpx + env(safe-area-inset-left,0px));--khstt-tx:0px;', $offset_x );
		} elseif ( 'bottom-center' === $position ) {
			$horizontal = 'right:auto;left:50%;--khstt-tx:-50%;';
		} else {
			$horizontal = sprintf( 'left:auto;right:calc(%dpx + env(safe-area-inset-right,0px));--khstt-tx:0px;', $offset_x );
		}

		$rules = $horizontal . sprintf( 'bottom:calc(%dpx + env(safe-area-inset-bottom,0px));', $offset_y );

		return $label ? $rules . self::label_rules( $position ) : $rules;
	}

	/**
	 * Where the hover label sits for one position preset.
	 *
	 * A control in a corner puts its label on the inward side, so it never
	 * points off the edge of the screen. A centred control puts it above,
	 * because either side would be equally arbitrary and a wide label would
	 * reach the viewport edge sooner.
	 *
	 * The label is absolutely positioned inside the fixed wrapper, so "bottom
	 * 50% plus a half-height shift" is what centres it beside the button.
	 *
	 * @param string $position One of the position enum values.
	 * @return string
	 */
	private static function label_rules( $position ) {
		if ( 'bottom-left' === $position ) {
			// To the right of the control.
			return '--khstt-lbl-l:calc(100% + 10px);--khstt-lbl-r:auto;--khstt-lbl-b:50%;--khstt-lbl-tx:0;--khstt-lbl-ty:50%;';
		}

		if ( 'bottom-center' === $position ) {
			// Above the control.
			return '--khstt-lbl-l:50%;--khstt-lbl-r:auto;--khstt-lbl-b:calc(100% + 10px);--khstt-lbl-tx:-50%;--khstt-lbl-ty:0;';
		}

		// To the left of the control.
		return '--khstt-lbl-l:auto;--khstt-lbl-r:calc(100% + 10px);--khstt-lbl-b:50%;--khstt-lbl-tx:0;--khstt-lbl-ty:50%;';
	}

	/**
	 * Tablet overrides. Emitted only for the groups the user explicitly enabled,
	 * so an untouched tablet simply inherits every desktop value.
	 *
	 * @param array $s Sanitized settings.
	 * @return string
	 */
	private static function tablet_block( array $s ) {
		$rules = '';

		if ( $s['tablet_position_override'] ) {
			$rules .= self::position_rules( $s['tablet_position'], $s['tablet_offset_x'], $s['tablet_offset_y'], ! empty( $s['hover_label'] ) );
		}

		if ( $s['tablet_size_override'] ) {
			$rules .= sprintf(
				'--khstt-size:%dpx;--khstt-icon:%dpx;',
				(int) $s['tablet_button_size'],
				(int) $s['tablet_icon_size']
			);
		}

		if ( '' === $rules ) {
			return '';
		}

		return sprintf(
			'@media (min-width:%dpx) and (max-width:%dpx){#khstt{%s}}',
			self::TABLET_MIN,
			self::TABLET_MAX,
			$rules
		);
	}

	/**
	 * Mobile overrides, plus the Thumb Friendly floors.
	 *
	 * Thumb Friendly raises the effective size and edge clearance rather than
	 * replacing the user's values, so it can never shrink a control the user
	 * deliberately made larger.
	 *
	 * @param array $s Sanitized settings.
	 * @return string
	 */
	private static function mobile_block( array $s ) {
		$rules = '';
		$thumb = ! empty( $s['thumb_friendly'] );

		$position = $s['mobile_position_override'] ? $s['mobile_position'] : $s['position'];
		$offset_x = (int) ( $s['mobile_position_override'] ? $s['mobile_offset_x'] : $s['offset_x'] );
		$offset_y = (int) ( $s['mobile_position_override'] ? $s['mobile_offset_y'] : $s['offset_y'] );

		if ( $thumb ) {
			$offset_x = max( self::THUMB_MIN_OFFSET, $offset_x );
			$offset_y = max( self::THUMB_MIN_OFFSET, $offset_y );
		}

		// Emitted only when the resolved values actually differ from the base
		// block, so an untouched mobile breakpoint costs nothing at all.
		if ( $position !== $s['position']
			|| $offset_x !== (int) $s['offset_x']
			|| $offset_y !== (int) $s['offset_y'] ) {
			$rules .= self::position_rules( $position, $offset_x, $offset_y, ! empty( $s['hover_label'] ) );
		}

		$size = (int) ( $s['mobile_size_override'] ? $s['mobile_button_size'] : $s['button_size'] );
		$icon = (int) ( $s['mobile_size_override'] ? $s['mobile_icon_size'] : $s['icon_size'] );

		if ( $thumb ) {
			$size = max( self::THUMB_MIN_SIZE, $size );
		}

		if ( $size !== (int) $s['button_size'] || $icon !== (int) $s['icon_size'] ) {
			$rules .= sprintf( '--khstt-size:%dpx;--khstt-icon:%dpx;', $size, $icon );
		}

		if ( $s['mobile_progress_override'] ) {
			$rules .= sprintf( '--khstt-ring-t:%dpx;', (int) $s['mobile_ring_thickness'] );
		}

		if ( '' === $rules ) {
			return '';
		}

		return sprintf( '@media (max-width:%dpx){#khstt{%s}}', self::MOBILE_MAX, $rules );
	}

	/**
	 * Hides the control on any device the user switched off in the Visibility
	 * tab. Device targeting is done in CSS rather than by sniffing the user
	 * agent server-side, so it stays correct behind page caches.
	 *
	 * @param array $s Sanitized settings.
	 * @return string
	 */
	private static function visibility_blocks( array $s ) {
		$css = '';

		if ( empty( $s['show_desktop'] ) ) {
			$css .= sprintf( '@media (min-width:%dpx){#khstt{display:none}}', self::TABLET_MAX + 1 );
		}

		if ( empty( $s['show_tablet'] ) ) {
			$css .= sprintf(
				'@media (min-width:%dpx) and (max-width:%dpx){#khstt{display:none}}',
				self::TABLET_MIN,
				self::TABLET_MAX
			);
		}

		if ( empty( $s['show_mobile'] ) ) {
			$css .= sprintf( '@media (max-width:%dpx){#khstt{display:none}}', self::MOBILE_MAX );
		}

		return $css;
	}

	/**
	 * Converts a validated hex color plus a 0-100 opacity into an rgba() value.
	 *
	 * @param string $hex     Hex color, already validated by the sanitizer.
	 * @param int    $opacity Opacity from 0 to 100.
	 * @return string
	 */
	public static function rgba( $hex, $opacity ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			$hex = '000000';
		}

		return sprintf(
			'rgba(%d,%d,%d,%s)',
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
			self::ratio( $opacity )
		);
	}

	/**
	 * Formats a 0-100 value as a CSS-safe 0-1 ratio, independent of locale.
	 *
	 * @param int $value Value from 0 to 100.
	 * @return string
	 */
	private static function ratio( $value ) {
		$value     = max( 0, min( 100, (int) $value ) );
		$formatted = rtrim( rtrim( number_format( $value / 100, 2, '.', '' ), '0' ), '.' );

		return '' === $formatted ? '0' : $formatted;
	}
}
