<?php
/**
 * Playground setup step for the KineticHub Scroll to Top demo.
 *
 * Inlined into blueprint.json as the runPHP step by build.js. It runs once,
 * inside the Playground instance, after the plugin and the demo mu-plugin have
 * been written.
 *
 * Two jobs: write the showcase settings, and create the demo page as the
 * site's front page.
 *
 * @package KineticHub_Scroll_To_Top_Demo
 */

require_once '/wordpress/wp-load.php';

// -----------------------------------------------------------------------------
// 1. Showcase settings
//
// Every value below is one the plugin actually accepts, and the whole array is
// pushed through KHSTT_Settings::sanitize_settings() rather than written raw.
// That sanitiser is the plugin's own schema: any key it does not know is
// dropped and any value outside an enum or a range falls back to its default,
// so a demo written against a newer build can never quietly configure a site
// with settings the installed plugin does not have.
//
// Two of these choices are worth explaining, because the obvious value is the
// wrong one for a thirty-second preview.

/*
 * peek_mode
 *   Smart Reveal alone hides the control while the reader is moving down the
 *   page. That is the right default on a real site, and it reads as a broken
 *   button in a preview, where scrolling down is the first thing anyone does.
 *   Peek keeps the feature switched on but leaves the control on screen in a
 *   reduced state, so the behaviour is demonstrated rather than mistaken for
 *   an absence.
 *
 * show_percentage
 *   Set to "on", so the number is the resting state and the reading-progress
 *   claim is visible without hovering. The icon returns on hover and focus -
 *   that is the plugin's own behaviour, not something the demo arranges - and
 *   the hover label names the action, so the control never becomes a number
 *   nobody can identify.
 */
// -----------------------------------------------------------------------------

$khsttd_settings = array(
	// ---- General ----
	'enabled'                   => true,
	'scroll_motion'             => 'smart',
	'reveal_trigger'            => 'smart',
	'trigger_offset'            => 400,
	'smart_reveal'              => true,
	'peek_mode'                 => true,
	'smart_return'              => true,
	'scroll_destination'        => 'page_top',
	'destination_selector'      => '',

	// ---- Compatibility ----
	// Left at the plugin's defaults. The demo page prints no competing control,
	// so detection has nothing to find and Force Replace has nothing to act on;
	// turning either on would only demonstrate an empty result.
	'scroll_container'          => 'auto',
	'scroll_container_selector' => '',
	'conflict_detection'        => true,
	'conflict_selector'         => '',
	'force_replace'             => false,

	// ---- Position ----
	'position'                  => 'bottom-right',
	'offset_x'                  => 28,
	'offset_y'                  => 28,
	'tablet_position_override'  => false,
	'tablet_position'           => 'bottom-right',
	'tablet_offset_x'           => 20,
	'tablet_offset_y'           => 20,
	'mobile_position_override'  => true,
	'mobile_position'           => 'bottom-right',
	'mobile_offset_x'           => 16,
	'mobile_offset_y'           => 18,
	'thumb_friendly'            => true,
	'smart_footer_dock'         => true,

	// ---- Appearance ----
	// style_preset is 'custom' on purpose: the colours below are the KineticHub
	// palette rather than any shipped preset, and claiming a preset the values
	// no longer match would light up the wrong card on the Appearance tab.
	'style_preset'              => 'custom',
	'icon'                      => 'arrow-up',
	'shape'                     => 'circle',
	'kinetic_power'             => 'rocket_boost',
	'kinetic_power_intensity'   => 'balanced',
	'button_size'               => 60,
	'icon_size'                 => 24,
	'tablet_size_override'      => false,
	'tablet_button_size'        => 46,
	'tablet_icon_size'          => 19,
	'mobile_size_override'      => true,
	'mobile_button_size'        => 56,
	'mobile_icon_size'          => 22,
	'bg_color'                  => '#4f46e5',
	'icon_color'                => '#ffffff',
	'hover_bg_color'            => '#6366f1',
	'hover_icon_color'          => '#ffffff',
	'bg_opacity'                => 100,
	'backdrop_blur'             => false,
	'border_width'              => 0,
	'border_color'              => '#4f46e5',
	'shadow'                    => 'medium',
	'idle_fade'                 => true,
	'hover_label'               => true,

	// ---- Progress ----
	'progress_enabled'          => true,
	'progress_scope'            => 'page',
	'progress_selector'         => '',
	'show_percentage'           => 'on',
	'progress_color'            => '#22d3ee',
	'track_color'               => '#ffffff',
	'track_opacity'             => 30,
	'ring_thickness'            => 4,
	'mobile_progress_override'  => false,
	'mobile_ring_thickness'     => 3,
	'mobile_show_percentage'    => 'off',

	// ---- Visibility ----
	'show_desktop'              => true,
	'show_tablet'               => true,
	'show_mobile'               => true,
	'page_scope'                => 'entire_site',
);

if ( class_exists( 'KHSTT_Settings' ) ) {
	$khsttd_sanitizer = new KHSTT_Settings();
	update_option( 'khstt_settings', $khsttd_sanitizer->sanitize_settings( $khsttd_settings ) );
} else {
	update_option( 'khstt_settings', $khsttd_settings );
}

// -----------------------------------------------------------------------------
// 2. Demo page
//
// The mu-plugin takes the page over by slug and renders the showcase, so the
// stored content is only a human-readable fallback for anyone who opens it in
// the editor.
// -----------------------------------------------------------------------------

$khsttd_existing = get_page_by_path( 'khstt-demo' );
$khsttd_page_id  = $khsttd_existing ? (int) $khsttd_existing->ID : 0;

if ( ! $khsttd_page_id ) {
	$khsttd_page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'A smart scroll companion for WordPress',
			'post_name'    => 'khstt-demo',
			'post_content' => '<!-- wp:paragraph --><p>This page is rendered by the KineticHub Scroll to Top demo showcase.</p><!-- /wp:paragraph -->',
		)
	);
}

if ( $khsttd_page_id && ! is_wp_error( $khsttd_page_id ) ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', (int) $khsttd_page_id );
}

// Tidy permalinks so /khstt-demo/ resolves as well as /.
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules( false );
