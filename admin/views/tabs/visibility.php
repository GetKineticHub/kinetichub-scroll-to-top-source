<?php
/**
 * Visibility tab: which devices show the control, and on which pages.
 *
 * Injected from page-settings.php:
 *   $settings array Current settings merged with defaults.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$khstt_scope_choices = array(
	'entire_site' => __( 'Entire site', 'kinetichub-scroll-to-top' ),
	'posts'       => __( 'Posts only', 'kinetichub-scroll-to-top' ),
	'pages'       => __( 'Pages only', 'kinetichub-scroll-to-top' ),
	'posts_pages' => __( 'Posts and pages', 'kinetichub-scroll-to-top' ),
	'front_page'  => __( 'Front page only', 'kinetichub-scroll-to-top' ),
);

// The WooCommerce option is offered only when WooCommerce is actually active,
// but it stays listed if it is the currently saved value so switching plugins
// off never silently rewrites the setting behind the user's back.
if ( class_exists( 'WooCommerce' ) || 'wc_products' === $settings['page_scope'] ) {
	$khstt_scope_choices['wc_products'] = __( 'WooCommerce products only', 'kinetichub-scroll-to-top' );
}

KHSTT_UI::section_open(
	__( 'Devices', 'kinetichub-scroll-to-top' ),
	__( 'Device targeting is done in CSS at the breakpoints below, so it stays correct behind page caches.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'show_desktop',
		'label' => __( 'Show on Desktop', 'kinetichub-scroll-to-top' ),
		/* translators: %s: the desktop breakpoint. */
		'help'  => sprintf( __( 'Viewports %s and wider.', 'kinetichub-scroll-to-top' ), '1025px' ),
	)
);
KHSTT_UI::toggle( 'show_desktop', $settings['show_desktop'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'show_tablet',
		'label' => __( 'Show on Tablet', 'kinetichub-scroll-to-top' ),
		/* translators: %s: the tablet breakpoint range. */
		'help'  => sprintf( __( 'Viewports from %s.', 'kinetichub-scroll-to-top' ), '768px - 1024px' ),
	)
);
KHSTT_UI::toggle( 'show_tablet', $settings['show_tablet'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'show_mobile',
		'label' => __( 'Show on Mobile', 'kinetichub-scroll-to-top' ),
		/* translators: %s: the mobile breakpoint. */
		'help'  => sprintf( __( 'Viewports %s and narrower.', 'kinetichub-scroll-to-top' ), '767px' ),
	)
);
KHSTT_UI::toggle( 'show_mobile', $settings['show_mobile'] );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Where It Appears', 'kinetichub-scroll-to-top' ),
	__( 'Straightforward targeting, no rule builder.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'page_scope',
		'label' => __( 'Page Scope', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'The WooCommerce option falls back to the entire site if WooCommerce is deactivated.', 'kinetichub-scroll-to-top' ),
	)
);
KHSTT_UI::select( 'page_scope', $settings['page_scope'], $khstt_scope_choices );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Short Pages', 'kinetichub-scroll-to-top' ),
	__( 'Handled automatically, so there is nothing to configure.', 'kinetichub-scroll-to-top' )
);
?>
<p class="khstt-note">
	<?php
	echo wp_kses(
		sprintf(
			/* translators: %s: the minimum scrollable distance, e.g. 400px. */
			esc_html__( 'On a page with less than %s of scrollable distance, a scroll-to-top control has nothing useful to do, so it never appears. The check re-runs when the page is resized or its height changes.', 'kinetichub-scroll-to-top' ),
			'<strong>400px</strong>'
		),
		array( 'strong' => array() )
	);
	?>
</p>
<?php
KHSTT_UI::section_close();
