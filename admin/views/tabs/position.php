<?php
/**
 * Position tab: base placement, responsive overrides, mobile ergonomics, and
 * footer collision avoidance.
 *
 * Injected from page-settings.php:
 *   $settings array Current settings merged with defaults.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$khstt_positions = KHSTT_Admin::get_position_choices();

KHSTT_UI::section_open(
	__( 'Desktop Placement', 'kinetichub-scroll-to-top' ),
	__( 'The base position. Tablet and mobile inherit these values unless you override them below.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'      => 'position',
		'label'    => __( 'Position', 'kinetichub-scroll-to-top' ),
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards( 'position', $settings['position'], $khstt_positions, 'tiles' );
KHSTT_UI::field_close( true );

KHSTT_UI::field_open(
	array(
		'key'   => 'offset_x',
		'label' => __( 'Horizontal Offset', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Distance from the left or right edge. Ignored for the centered position.', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::number( 'offset_x', $settings['offset_x'], 0, 200 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'offset_y',
		'label' => __( 'Bottom Offset', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Distance from the bottom edge. Safe-area insets on phones are added on top of this automatically.', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::number( 'offset_y', $settings['offset_y'], 0, 200 );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Tablet Override', 'kinetichub-scroll-to-top' ),
	/* translators: %s: the tablet breakpoint range. */
	sprintf( __( 'Applies between %s. Off means tablet uses the desktop placement.', 'kinetichub-scroll-to-top' ), '768px - 1024px' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'tablet_position_override',
		'label' => __( 'Override on Tablet', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::toggle( 'tablet_position_override', $settings['tablet_position_override'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'      => 'tablet_position',
		'label'    => __( 'Tablet Position', 'kinetichub-scroll-to-top' ),
		'when'     => 'tablet_position_override=1',
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards( 'tablet_position', $settings['tablet_position'], $khstt_positions, 'tiles' );
KHSTT_UI::field_close( true );

KHSTT_UI::field_open(
	array(
		'key'   => 'tablet_offset_x',
		'label' => __( 'Tablet Horizontal Offset', 'kinetichub-scroll-to-top' ),
		'when'  => 'tablet_position_override=1',
	)
);
KHSTT_UI::number( 'tablet_offset_x', $settings['tablet_offset_x'], 0, 200 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'tablet_offset_y',
		'label' => __( 'Tablet Bottom Offset', 'kinetichub-scroll-to-top' ),
		'when'  => 'tablet_position_override=1',
	)
);
KHSTT_UI::number( 'tablet_offset_y', $settings['tablet_offset_y'], 0, 200 );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Mobile Override', 'kinetichub-scroll-to-top' ),
	/* translators: %s: the mobile breakpoint. */
	sprintf( __( 'Applies at %s and below. Off means mobile uses the desktop placement.', 'kinetichub-scroll-to-top' ), '767px' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'mobile_position_override',
		'label' => __( 'Override on Mobile', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::toggle( 'mobile_position_override', $settings['mobile_position_override'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'      => 'mobile_position',
		'label'    => __( 'Mobile Position', 'kinetichub-scroll-to-top' ),
		'when'     => 'mobile_position_override=1',
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards( 'mobile_position', $settings['mobile_position'], $khstt_positions, 'tiles' );
KHSTT_UI::field_close( true );

KHSTT_UI::field_open(
	array(
		'key'   => 'mobile_offset_x',
		'label' => __( 'Mobile Horizontal Offset', 'kinetichub-scroll-to-top' ),
		'when'  => 'mobile_position_override=1',
	)
);
KHSTT_UI::number( 'mobile_offset_x', $settings['mobile_offset_x'], 0, 200 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'mobile_offset_y',
		'label' => __( 'Mobile Bottom Offset', 'kinetichub-scroll-to-top' ),
		'when'  => 'mobile_position_override=1',
	)
);
KHSTT_UI::number( 'mobile_offset_y', $settings['mobile_offset_y'], 0, 200 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'thumb_friendly',
		'label' => __( 'Thumb Friendly', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'On phones, raises the control to a comfortable 48px target and keeps at least 16px of clearance from the screen edges, on top of the safe area. It only ever increases these values, never shrinks what you set.', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::toggle( 'thumb_friendly', $settings['thumb_friendly'] );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Footer Collision', 'kinetichub-scroll-to-top' ),
	__( 'Keep the control clear of the page footer.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'smart_footer_dock',
		'label' => __( 'Smart Footer Dock', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'When the footer scrolls into view, the control lifts just above it instead of sitting on top of it. Detects footer, .site-footer, and #colophon; does nothing if none of them exist.', 'kinetichub-scroll-to-top' ),
		'smart' => true,
	)
);
KHSTT_UI::toggle( 'smart_footer_dock', $settings['smart_footer_dock'] );
KHSTT_UI::field_close();

KHSTT_UI::section_close();
