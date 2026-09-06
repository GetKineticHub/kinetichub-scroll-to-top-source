<?php
/**
 * Appearance tab: quick presets, icon, shape, size, colors, border, shadow.
 *
 * Injected from page-settings.php:
 *   $settings array Current settings merged with defaults.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$khstt_preset_labels = array(
	'minimal' => __( 'Minimal', 'kinetichub-scroll-to-top' ),
	'soft'    => __( 'Soft', 'kinetichub-scroll-to-top' ),
	'outline' => __( 'Outline', 'kinetichub-scroll-to-top' ),
	'glass'   => __( 'Glass', 'kinetichub-scroll-to-top' ),
	'bold'    => __( 'Bold', 'kinetichub-scroll-to-top' ),
);

$khstt_preset_choices = array();
foreach ( KHSTT_Admin::get_style_presets() as $khstt_preset_key => $khstt_preset ) {
	$khstt_preset_choices[ $khstt_preset_key ] = array(
		'label'  => $khstt_preset_labels[ $khstt_preset_key ],
		'visual' => sprintf(
			'<span class="khstt-preset-dot" style="background-color:%1$s;border-color:%2$s;color:%3$s">%4$s</span>',
			esc_attr( $khstt_preset['bg_color'] ),
			esc_attr( $khstt_preset['border_width'] > 0 ? $khstt_preset['border_color'] : $khstt_preset['bg_color'] ),
			esc_attr( $khstt_preset['icon_color'] ),
			KHSTT_Icons::get( 'arrow-up', 'khstt-preset-dot__icon' )
		),
	);
}

$khstt_icon_choices = array();
foreach ( KHSTT_Icons::get_choices() as $khstt_icon_key => $khstt_icon_label ) {
	$khstt_icon_choices[ $khstt_icon_key ] = array(
		'label'  => $khstt_icon_label,
		'visual' => KHSTT_Icons::get( $khstt_icon_key, 'khstt-icon-choice' ),
	);
}

/**
 * Small rounded-rectangle diagram illustrating each shape option.
 *
 * @param string $radius Corner radius in the 32x32 diagram space.
 * @return string
 */
$khstt_shape_visual = static function ( $radius ) {
	return '<svg viewBox="0 0 32 32" fill="none" focusable="false" aria-hidden="true">'
		. '<rect x="1.25" y="1.25" width="29.5" height="29.5" rx="' . esc_attr( $radius ) . '" stroke="currentColor" stroke-width="2.5"/>'
		. '</svg>';
};

KHSTT_UI::section_open(
	__( 'Quick Style', 'kinetichub-scroll-to-top' ),
	__( 'A starting point, not a lock. Pick a preset, then change anything you like.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'      => 'style_preset',
		'label'    => __( 'Preset', 'kinetichub-scroll-to-top' ),
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards( 'style_preset', $settings['style_preset'], $khstt_preset_choices, 'presets' );
printf(
	// A state carrier, not a control: it records that the appearance values no
	// longer match any preset, so no preset card shows as selected.
	'<input type="radio" class="khstt-offscreen" tabindex="-1" aria-hidden="true" name="%1$s" value="custom" data-khstt-key="style_preset"%2$s>',
	esc_attr( KHSTT_UI::name( 'style_preset' ) ),
	checked( $settings['style_preset'], 'custom', false )
);
KHSTT_UI::field_close( true );

KHSTT_UI::section_close();


KHSTT_UI::section_open( __( 'Icon and Shape', 'kinetichub-scroll-to-top' ) );

KHSTT_UI::field_open(
	array(
		'key'      => 'icon',
		'label'    => __( 'Icon', 'kinetichub-scroll-to-top' ),
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards( 'icon', $settings['icon'], $khstt_icon_choices, 'tiles' );
KHSTT_UI::field_close( true );

KHSTT_UI::field_open(
	array(
		'key'      => 'shape',
		'label'    => __( 'Shape', 'kinetichub-scroll-to-top' ),
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards(
	'shape',
	$settings['shape'],
	array(
		'circle'  => array(
			'label'  => __( 'Circle', 'kinetichub-scroll-to-top' ),
			'visual' => $khstt_shape_visual( '15' ),
		),
		'rounded' => array(
			'label'  => __( 'Rounded', 'kinetichub-scroll-to-top' ),
			'visual' => $khstt_shape_visual( '9' ),
		),
		'square'  => array(
			'label'  => __( 'Square', 'kinetichub-scroll-to-top' ),
			'visual' => $khstt_shape_visual( '2.5' ),
		),
	),
	'tiles'
);
KHSTT_UI::field_close( true );

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'KineticPowers', 'kinetichub-scroll-to-top' ),
	__( 'Add lightweight motion choreography when the control is activated.', 'kinetichub-scroll-to-top' )
);

$khstt_power_choices = array();
foreach ( KHSTT_Powers::get_choices() as $khstt_power_key => $khstt_power ) {
	$khstt_power_choices[ $khstt_power_key ] = array(
		'label'  => $khstt_power['label'],
		'note'   => $khstt_power['note'],
		'visual' => KHSTT_Powers::card_visual( $khstt_power_key ),
	);
}

KHSTT_UI::field_open(
	array(
		'key'      => 'kinetic_power',
		'label'    => __( 'KineticPower', 'kinetichub-scroll-to-top' ),
		'help'     => __( 'A short sequence that plays when someone uses the control. It never changes where or how fast the page scrolls.', 'kinetichub-scroll-to-top' ),
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards( 'kinetic_power', $settings['kinetic_power'], $khstt_power_choices, 'powers' );
KHSTT_UI::field_close( true );

KHSTT_UI::field_open(
	array(
		'key'   => 'kinetic_power_intensity',
		'label' => __( 'Power Intensity', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'How much the effect moves. Visual amplitude only.', 'kinetichub-scroll-to-top' ),
		'when'  => 'kinetic_power=rocket_boost',
	)
);
KHSTT_UI::select( 'kinetic_power_intensity', $settings['kinetic_power_intensity'], KHSTT_Powers::get_intensities() );
KHSTT_UI::field_close();

KHSTT_UI::section_close();

KHSTT_UI::section_open(
	__( 'Interaction Polish', 'kinetichub-scroll-to-top' ),
	__( 'Small touches that make the control feel answered rather than just present.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'idle_fade',
		'label' => __( 'Smart Idle Fade', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Softens the control after a few seconds of inactivity, then restores it as soon as the visitor interacts again.', 'kinetichub-scroll-to-top' ),
		'smart' => true,
	)
);
KHSTT_UI::toggle( 'idle_fade', $settings['idle_fade'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'hover_label',
		'label' => __( 'Hover / Focus Label', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Shows a short action label beside the control on hover or keyboard focus.', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::toggle( 'hover_label', $settings['hover_label'] );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Size', 'kinetichub-scroll-to-top' ),
	__( 'The button never goes below 44px so it stays a comfortable target on every device.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'button_size',
		'label' => __( 'Button Size', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::number( 'button_size', $settings['button_size'], 44, 80 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'icon_size',
		'label' => __( 'Icon Size', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Capped at 12px below the button size so the glyph always has breathing room.', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::number( 'icon_size', $settings['icon_size'], 12, 40 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'tablet_size_override',
		'label' => __( 'Override Size on Tablet', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::toggle( 'tablet_size_override', $settings['tablet_size_override'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'tablet_button_size',
		'label' => __( 'Tablet Button Size', 'kinetichub-scroll-to-top' ),
		'when'  => 'tablet_size_override=1',
	)
);
KHSTT_UI::number( 'tablet_button_size', $settings['tablet_button_size'], 44, 80 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'tablet_icon_size',
		'label' => __( 'Tablet Icon Size', 'kinetichub-scroll-to-top' ),
		'when'  => 'tablet_size_override=1',
	)
);
KHSTT_UI::number( 'tablet_icon_size', $settings['tablet_icon_size'], 12, 40 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'mobile_size_override',
		'label' => __( 'Override Size on Mobile', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::toggle( 'mobile_size_override', $settings['mobile_size_override'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'mobile_button_size',
		'label' => __( 'Mobile Button Size', 'kinetichub-scroll-to-top' ),
		'when'  => 'mobile_size_override=1',
	)
);
KHSTT_UI::number( 'mobile_button_size', $settings['mobile_button_size'], 44, 80 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'mobile_icon_size',
		'label' => __( 'Mobile Icon Size', 'kinetichub-scroll-to-top' ),
		'when'  => 'mobile_size_override=1',
	)
);
KHSTT_UI::number( 'mobile_icon_size', $settings['mobile_icon_size'], 12, 40 );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open( __( 'Colors', 'kinetichub-scroll-to-top' ) );

KHSTT_UI::field_open(
	array(
		'key'   => 'bg_color',
		'label' => __( 'Background', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::color( 'bg_color', $settings['bg_color'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'icon_color',
		'label' => __( 'Icon', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::color( 'icon_color', $settings['icon_color'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'hover_bg_color',
		'label' => __( 'Hover Background', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::color( 'hover_bg_color', $settings['hover_bg_color'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'hover_icon_color',
		'label' => __( 'Hover Icon', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::color( 'hover_icon_color', $settings['hover_icon_color'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'bg_opacity',
		'label' => __( 'Background Opacity', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Lower this for a translucent, glassy control.', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::number( 'bg_opacity', $settings['bg_opacity'], 10, 100, '%' );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'backdrop_blur',
		'label' => __( 'Backdrop Blur', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Blurs whatever sits behind a translucent button. Ignored by browsers that do not support it.', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::toggle( 'backdrop_blur', $settings['backdrop_blur'] );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open( __( 'Border and Shadow', 'kinetichub-scroll-to-top' ) );

KHSTT_UI::field_open(
	array(
		'key'   => 'border_width',
		'label' => __( 'Border Width', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::number( 'border_width', $settings['border_width'], 0, 6 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'border_color',
		'label' => __( 'Border Color', 'kinetichub-scroll-to-top' ),
		'when'  => 'border_width!=0',
	)
);
KHSTT_UI::color( 'border_color', $settings['border_color'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'shadow',
		'label' => __( 'Shadow', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::select(
	'shadow',
	$settings['shadow'],
	array(
		'none'   => __( 'None', 'kinetichub-scroll-to-top' ),
		'soft'   => __( 'Soft', 'kinetichub-scroll-to-top' ),
		'medium' => __( 'Medium', 'kinetichub-scroll-to-top' ),
		'strong' => __( 'Strong', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::field_close();

KHSTT_UI::section_close();
