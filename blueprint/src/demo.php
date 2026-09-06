<?php
/**
 * Plugin Name: KineticHub Scroll to Top - Demo Showcase
 * Description: Renders the Playground showcase page for KineticHub Scroll to Top. Written by the plugin's blueprint; not part of the distributed plugin.
 * Version:     1.0.0
 * Author:      KineticHub
 *
 * This file exists only inside the WordPress Playground preview that the
 * blueprint boots. It is never shipped in the plugin ZIP and never runs on a
 * real site.
 *
 * Scope rules it follows:
 *   - It only ever touches the one demo page (by slug). Every other request is
 *     left completely alone.
 *   - All markup is namespaced .khsttd- so it cannot collide with the plugin's
 *     own .khstt- frontend classes.
 *   - The control on the page is the real one, printed by the real plugin from
 *     the real settings. The demo renders the page around it and never draws a
 *     scroll control of its own.
 *   - Variation values are filtered through KHSTT_Settings::sanitize_settings()
 *     and never written to the database, so a variation cannot configure the
 *     site into a state the plugin would not accept and cannot outlive the
 *     request that asked for it.
 *
 * @package KineticHub_Scroll_To_Top_Demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KHSTTD_SLUG = 'khstt-demo';

/** Query argument naming the active variation. */
const KHSTTD_ARG = 'khstt-look';

/**
 * Is this request the demo page?
 */
function khsttd_is_demo(): bool {
	return is_page( KHSTTD_SLUG );
}

/**
 * The alternative configurations the page offers, over the stored showcase.
 *
 * Each one is a small diff, not a whole settings array: whatever is not named
 * here keeps the value setup.php wrote. Everything is a real key with a real
 * value from the plugin's own schema, and the sanitiser gets the last word
 * regardless.
 *
 * @return array<string, array>
 */
function khsttd_variations(): array {
	return array(
		'showcase'  => array(
			'label'    => 'Showcase',
			'note'     => 'Progress ring, percentage, hover label and Rocket Boost.',
			'settings' => array(),
		),
		'strong'    => array(
			'label'    => 'Rocket Boost, Strong',
			'note'     => 'The same sequence at its largest amplitude.',
			'settings' => array(
				'kinetic_power'           => 'rocket_boost',
				'kinetic_power_intensity' => 'strong',
			),
		),
		'always'    => array(
			'label'    => 'Always on screen',
			'note'     => 'Smart Reveal off. The control never steps aside.',
			'settings' => array(
				'smart_reveal' => false,
				'peek_mode'    => false,
			),
		),
		'outline'   => array(
			'label'    => 'Outline, square',
			'note'     => 'A quieter treatment: square shape, border, no fill.',
			'settings' => array(
				'shape'            => 'square',
				'border_width'     => 2,
				'bg_color'         => '#ffffff',
				'icon_color'       => '#4338ca',
				'border_color'     => '#4338ca',
				'hover_bg_color'   => '#eef2ff',
				'hover_icon_color' => '#312e81',
				'progress_color'   => '#4338ca',
				'track_color'      => '#4338ca',
				'track_opacity'    => 18,
				'show_percentage'  => 'hover',
				'shadow'           => 'soft',
			),
		),
		'essential' => array(
			'label'    => 'Just the button',
			'note'     => 'No ring, no percentage, no label, no KineticPower.',
			'settings' => array(
				'progress_enabled' => false,
				'show_percentage'  => 'off',
				'hover_label'      => false,
				'kinetic_power'    => 'off',
			),
		),
	);
}

/**
 * The variation key this request asked for, defaulting to the showcase.
 *
 * Read from the query string and matched against the list above, so an
 * unrecognised value resolves to the showcase rather than to nothing.
 */
function khsttd_current_variation(): string {
	// A display preference read from a link on a demo page. It selects between
	// hard-coded variations and writes nothing, so there is no state-changing
	// request here for a nonce to protect.
	$raw = isset( $_GET[ KHSTTD_ARG ] ) ? sanitize_key( wp_unslash( $_GET[ KHSTTD_ARG ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return array_key_exists( $raw, khsttd_variations() ) ? $raw : 'showcase';
}

/**
 * Applies the active variation to the settings the plugin reads.
 *
 * A filter on the option rather than a write to it: the variation lasts exactly
 * as long as the request that asked for it, the stored showcase settings are
 * never touched, and the Settings screen keeps showing what is really saved.
 *
 * The guards matter. get_option() runs long before the main query exists, and
 * is_page() called that early is a "called incorrectly" notice, so the filter
 * stands down until WordPress has answered the query. It also stands down in
 * the admin, where the settings screen must show the stored values.
 *
 * @param mixed $value Stored option value.
 * @return mixed
 */
function khsttd_filter_settings( $value ) {
	static $cache = null;

	if ( is_admin() || ! did_action( 'wp' ) || ! is_array( $value ) ) {
		return $value;
	}

	if ( ! class_exists( 'KHSTT_Settings' ) || ! khsttd_is_demo() ) {
		return $value;
	}

	if ( null !== $cache ) {
		return $cache;
	}

	$variations = khsttd_variations();
	$overrides  = $variations[ khsttd_current_variation() ]['settings'];

	$sanitizer = new KHSTT_Settings();
	$cache     = $sanitizer->sanitize_settings( array_merge( $value, $overrides ) );

	return $cache;
}
add_filter( 'option_khstt_settings', 'khsttd_filter_settings' );

/**
 * URL for one variation, preserving nothing else.
 *
 * @param string $key Variation key.
 * @return string
 */
function khsttd_variation_url( string $key ): string {
	$base = get_permalink( get_queried_object_id() );

	if ( ! $base ) {
		$base = home_url( '/' );
	}

	return 'showcase' === $key ? $base : add_query_arg( KHSTTD_ARG, $key, $base );
}

// -----------------------------------------------------------------------------
// Assets
// -----------------------------------------------------------------------------

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! khsttd_is_demo() ) {
			return;
		}

		$base = WPMU_PLUGIN_URL . '/khstt-demo';

		wp_enqueue_style( 'khsttd-demo', $base . '/demo.css', array(), '1.0.0' );
		wp_enqueue_script( 'khsttd-demo', $base . '/demo.js', array(), '1.0.0', true );
	}
);

// -----------------------------------------------------------------------------
// Template
// -----------------------------------------------------------------------------

add_filter(
	'template_include',
	function ( $template ) {
		if ( ! khsttd_is_demo() ) {
			return $template;
		}
		return __DIR__ . '/khstt-demo/template.php';
	}
);

// -----------------------------------------------------------------------------
// Rendering helpers
// -----------------------------------------------------------------------------

/**
 * Prints one inline icon. Every icon is a static literal - no request, no font.
 *
 * These are the demo page's own decoration. The control's icons come from the
 * plugin's icon library and are never drawn here.
 *
 * @param string $name Icon slug.
 */
function khsttd_icon( string $name ): void {
	$open = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">';

	switch ( $name ) {
		case 'arrow-up':
			echo $open . '<path d="M12 19.5V4.8M5.6 11.2 12 4.8l6.4 6.4"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'ring':
			echo $open . '<circle cx="12" cy="12" r="8.6"/><path d="M12 3.4a8.6 8.6 0 0 1 8.2 6" stroke-width="3"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'rocket':
			echo $open . '<path d="M12 2.8c2.4 2.3 3.7 5.3 3.7 8.4 0 1.4-.3 2.8-.9 4H9.2c-.6-1.2-.9-2.6-.9-4 0-3.1 1.3-6.1 3.7-8.4z"/><path d="M8.6 11.6 6.3 14.2c-.4.4-.6 1-.6 1.6v2.1l2.9-2"/><path d="m15.4 11.6 2.3 2.6c.4.4.6 1 .6 1.6v2.1l-2.9-2"/><circle cx="12" cy="9.2" r="1.4"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'eye':
			echo $open . '<path d="M2.6 12S6.1 5.6 12 5.6 21.4 12 21.4 12 17.9 18.4 12 18.4 2.6 12 2.6 12Z"/><circle cx="12" cy="12" r="2.9"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'back':
			echo $open . '<path d="M4.6 12.4a7.6 7.6 0 1 0 2.3-5.4"/><path d="M4.4 4.6v4.2h4.2"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'devices':
			echo $open . '<rect x="2.6" y="4.6" width="13" height="10" rx="1.8"/><rect x="17" y="9" width="4.6" height="10.4" rx="1.4"/><path d="M6.6 18.4h5"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'shield':
			echo $open . '<path d="M12 2.8 4.8 5.8v5.6c0 4.4 3 8.2 7.2 9.8 4.2-1.6 7.2-5.4 7.2-9.8V5.8z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'accessible':
			echo $open . '<circle cx="12" cy="12" r="9"/><path d="M8.5 9.5h7M12 9.5V15m-2 2.5L12 15l2 2.5"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'feather':
			echo $open . '<path d="M20.2 3.8a5.5 5.5 0 0 0-7.8 0L4 12.2V20h7.8l8.4-8.4a5.5 5.5 0 0 0 0-7.8Z"/><path d="M16 8 4.5 19.5"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'sliders':
			echo $open . '<path d="M6 3v5.2M6 12.8V21M12 3v9.2M12 16.8V21M18 3v2.2M18 9.8V21"/><circle cx="6" cy="10.5" r="2.3"/><circle cx="12" cy="15" r="2.3"/><circle cx="18" cy="7.5" r="2.3"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'puzzle':
			echo $open . '<path d="M10 3.4h4v2.2a1.8 1.8 0 1 0 3.6 0V3.4h3v3.2h-2.2a1.8 1.8 0 1 0 0 3.6h2.2v10.4H10v-2.4a1.8 1.8 0 1 0-3.6 0v2.4h-3V10.2h2.2a1.8 1.8 0 1 0 0-3.6H3.4V3.4h3"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
		case 'check':
			echo $open . '<path d="m5 12.5 4.5 4.5L19 7.5" stroke-width="2.6"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
			break;
	}
}

/**
 * Opens a content section with a heading and a lead paragraph.
 *
 * @param string $id    Section id, used by the in-page navigation.
 * @param string $kicker Small label above the heading.
 * @param string $title Section heading.
 * @param string $lead  Lead paragraph.
 */
function khsttd_section_open( string $id, string $kicker, string $title, string $lead = '' ): void {
	?>
	<section class="khsttd-section" id="<?php echo esc_attr( $id ); ?>" data-khsttd-reveal>
		<div class="khsttd-shell">
			<p class="khsttd-kicker"><?php echo esc_html( $kicker ); ?></p>
			<h2 class="khsttd-h2"><?php echo esc_html( $title ); ?></h2>
			<?php if ( '' !== $lead ) : ?>
				<p class="khsttd-lead"><?php echo esc_html( $lead ); ?></p>
			<?php endif; ?>
	<?php
}

/** Closes a content section. */
function khsttd_section_close(): void {
	?>
		</div>
	</section>
	<?php
}

/**
 * Renders one feature card.
 *
 * @param string $icon  Icon slug.
 * @param string $title Card title.
 * @param string $body  Card body.
 */
function khsttd_card( string $icon, string $title, string $body ): void {
	?>
	<article class="khsttd-card">
		<span class="khsttd-card__icon"><?php khsttd_icon( $icon ); ?></span>
		<h3 class="khsttd-card__title"><?php echo esc_html( $title ); ?></h3>
		<p class="khsttd-card__body"><?php echo esc_html( $body ); ?></p>
	</article>
	<?php
}
