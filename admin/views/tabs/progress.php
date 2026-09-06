<?php
/**
 * Progress tab: the ring, what it measures, the percentage readout, and colors.
 *
 * Injected from page-settings.php:
 *   $settings array Current settings merged with defaults.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$khstt_percentage_choices = array(
	'off'   => __( 'Off', 'kinetichub-scroll-to-top' ),
	'on'    => __( 'Always', 'kinetichub-scroll-to-top' ),
	'hover' => __( 'On hover and focus', 'kinetichub-scroll-to-top' ),
);

KHSTT_UI::section_open(
	__( 'Progress Ring', 'kinetichub-scroll-to-top' ),
	__( 'A thin ring around the control showing how far through the page the visitor is.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'progress_enabled',
		'label' => __( 'Show Progress Ring', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::toggle( 'progress_enabled', $settings['progress_enabled'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'      => 'progress_scope',
		'label'    => __( 'What It Measures', 'kinetichub-scroll-to-top' ),
		'when'     => 'progress_enabled=1',
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards(
	'progress_scope',
	$settings['progress_scope'],
	array(
		'page'    => array(
			'label' => __( 'Whole Page', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'The full scrollable distance, header and footer included. Predictable on every page.', 'kinetichub-scroll-to-top' ),
		),
		'content' => array(
			'label' => __( 'Smart Content', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'Reading progress through the article itself. Falls back to Whole Page when no content region is found.', 'kinetichub-scroll-to-top' ),
			'smart' => true,
		),
	),
	'stacked'
);
KHSTT_UI::field_close( true );

KHSTT_UI::field_open(
	array(
		'key'   => 'progress_selector',
		'label' => __( 'Custom Content Selector', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Optional. A CSS selector for the region to measure, such as .entry-content. Tried before the built-in detection; ignored if it matches nothing.', 'kinetichub-scroll-to-top' ),
		'when'  => 'progress_scope=content',
	)
);
KHSTT_UI::text( 'progress_selector', $settings['progress_selector'], '.entry-content' );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'ring_thickness',
		'label' => __( 'Ring Thickness', 'kinetichub-scroll-to-top' ),
		'when'  => 'progress_enabled=1',
	)
);
KHSTT_UI::number( 'ring_thickness', $settings['ring_thickness'], 1, 8 );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Percentage', 'kinetichub-scroll-to-top' ),
	__( 'Show the exact number inside the control.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'show_percentage',
		'label' => __( 'Show Percentage', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Always: the number replaces the icon at rest, and the icon returns on hover or focus so the action stays obvious. On hover and focus: the reverse.', 'kinetichub-scroll-to-top' ),
		'when'  => 'progress_enabled=1',
	)
);
KHSTT_UI::select( 'show_percentage', $settings['show_percentage'], $khstt_percentage_choices );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open( __( 'Progress Colors', 'kinetichub-scroll-to-top' ) );

KHSTT_UI::field_open(
	array(
		'key'   => 'progress_color',
		'label' => __( 'Progress Color', 'kinetichub-scroll-to-top' ),
		'when'  => 'progress_enabled=1',
	)
);
KHSTT_UI::color( 'progress_color', $settings['progress_color'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'track_color',
		'label' => __( 'Track Color', 'kinetichub-scroll-to-top' ),
		'when'  => 'progress_enabled=1',
	)
);
KHSTT_UI::color( 'track_color', $settings['track_color'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'track_opacity',
		'label' => __( 'Track Opacity', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'The unfilled part of the ring. Keep it low for a subtle look.', 'kinetichub-scroll-to-top' ),
		'when'  => 'progress_enabled=1',
	)
);
KHSTT_UI::number( 'track_opacity', $settings['track_opacity'], 0, 100, '%' );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Mobile Override', 'kinetichub-scroll-to-top' ),
	/* translators: %s: the mobile breakpoint. */
	sprintf( __( 'Applies at %s and below. Off means mobile uses the values above.', 'kinetichub-scroll-to-top' ), '767px' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'mobile_progress_override',
		'label' => __( 'Override on Mobile', 'kinetichub-scroll-to-top' ),
		'when'  => 'progress_enabled=1',
	)
);
KHSTT_UI::toggle( 'mobile_progress_override', $settings['mobile_progress_override'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'mobile_ring_thickness',
		'label' => __( 'Mobile Ring Thickness', 'kinetichub-scroll-to-top' ),
		'when'  => 'mobile_progress_override=1',
	)
);
KHSTT_UI::number( 'mobile_ring_thickness', $settings['mobile_ring_thickness'], 1, 8 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'mobile_show_percentage',
		'label' => __( 'Mobile Percentage', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Turning the number off on small screens keeps the control clean where space is tightest.', 'kinetichub-scroll-to-top' ),
		'when'  => 'mobile_progress_override=1',
	)
);
KHSTT_UI::select( 'mobile_show_percentage', $settings['mobile_show_percentage'], $khstt_percentage_choices );
KHSTT_UI::field_close();

KHSTT_UI::section_close();
