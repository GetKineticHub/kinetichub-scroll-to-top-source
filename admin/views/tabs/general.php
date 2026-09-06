<?php
/**
 * General tab: motion, reveal trigger, smart visibility, destination, and how
 * the plugin fits alongside the rest of the site.
 *
 * Injected from page-settings.php:
 *   $settings array        Current settings merged with defaults.
 *   $compat   KHSTT_Compat Compatibility status service.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

KHSTT_UI::section_open(
	__( 'Scroll Motion', 'kinetichub-scroll-to-top' ),
	__( 'How the page travels when the control is activated.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'      => 'scroll_motion',
		'label'    => __( 'Motion', 'kinetichub-scroll-to-top' ),
		'help'     => __( 'Reduced motion is always respected: visitors who ask for it get an instant jump regardless of this setting.', 'kinetichub-scroll-to-top' ),
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards(
	'scroll_motion',
	$settings['scroll_motion'],
	array(
		'smart'   => array(
			'label' => __( 'Smart', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'Duration adapts to distance, capped so long pages never crawl.', 'kinetichub-scroll-to-top' ),
			'smart' => true,
		),
		'smooth'  => array(
			'label' => __( 'Smooth', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'The browser\'s native smooth scrolling.', 'kinetichub-scroll-to-top' ),
		),
		'instant' => array(
			'label' => __( 'Instant', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'Jumps straight to the destination.', 'kinetichub-scroll-to-top' ),
		),
	),
	'stacked'
);
KHSTT_UI::field_close( true );

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Reveal', 'kinetichub-scroll-to-top' ),
	__( 'When the control appears, and whether it stays on screen.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'      => 'reveal_trigger',
		'label'    => __( 'Reveal Trigger', 'kinetichub-scroll-to-top' ),
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards(
	'reveal_trigger',
	$settings['reveal_trigger'],
	array(
		'smart'  => array(
			'label' => __( 'Smart', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'Appears once the visitor has scrolled past the first screen.', 'kinetichub-scroll-to-top' ),
			'smart' => true,
		),
		'custom' => array(
			'label' => __( 'Custom', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'Appears after a fixed scroll distance you choose.', 'kinetichub-scroll-to-top' ),
		),
	),
	'stacked'
);
KHSTT_UI::field_close( true );

KHSTT_UI::field_open(
	array(
		'key'   => 'trigger_offset',
		'label' => __( 'Trigger Distance', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'How far down the page the visitor must scroll before the control appears.', 'kinetichub-scroll-to-top' ),
		'when'  => 'reveal_trigger=custom',
	)
);
KHSTT_UI::number( 'trigger_offset', $settings['trigger_offset'], 50, 5000 );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'smart_reveal',
		'label' => __( 'Smart Reveal', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Keeps the control out of the way while the visitor reads downward, and brings it back the moment they scroll up. Keyboard focus always keeps it visible.', 'kinetichub-scroll-to-top' ),
		'smart' => true,
	)
);
KHSTT_UI::toggle( 'smart_reveal', $settings['smart_reveal'] );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'peek_mode',
		'label' => __( 'Peek Mode', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Instead of disappearing completely, the control shrinks to a quiet peek and restores on upward scroll, hover, touch, or focus.', 'kinetichub-scroll-to-top' ),
		'when'  => 'smart_reveal=1',
	)
);
KHSTT_UI::toggle( 'peek_mode', $settings['peek_mode'] );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Smart Return', 'kinetichub-scroll-to-top' ),
	__( 'Let visitors get back to exactly where they were reading.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'   => 'smart_return',
		'label' => __( 'Smart Return', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'After a long jump to the top, the same control briefly turns into a Return action that sends the visitor back to their previous position. The offer clears after 12 seconds, once they scroll on their own, or on Escape.', 'kinetichub-scroll-to-top' ),
		'smart' => true,
	)
);
KHSTT_UI::toggle( 'smart_return', $settings['smart_return'] );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Destination', 'kinetichub-scroll-to-top' ),
	__( 'Where the control sends the visitor.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'      => 'scroll_destination',
		'label'    => __( 'Scroll Destination', 'kinetichub-scroll-to-top' ),
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards(
	'scroll_destination',
	$settings['scroll_destination'],
	array(
		'page_top'      => array(
			'label' => __( 'Page Top', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'The very top of the page.', 'kinetichub-scroll-to-top' ),
		),
		'content_start' => array(
			'label' => __( 'Content Start', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'The start of the article, skipping the header. Falls back to the page top if no content region is found.', 'kinetichub-scroll-to-top' ),
			'smart' => true,
		),
	),
	'stacked'
);
KHSTT_UI::field_close( true );

KHSTT_UI::field_open(
	array(
		'key'   => 'destination_selector',
		'label' => __( 'Custom Content Selector', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Optional. A CSS selector for the element to scroll to, such as #main-content. Tried before the built-in detection; ignored if it matches nothing.', 'kinetichub-scroll-to-top' ),
		'when'  => 'scroll_destination=content_start',
	)
);
KHSTT_UI::text( 'destination_selector', $settings['destination_selector'], '#main-content' );
KHSTT_UI::field_close();

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Compatibility', 'kinetichub-scroll-to-top' ),
	__( 'Make sure KineticHub Scroll to Top works cleanly with your theme and the way your site scrolls.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'      => 'scroll_container',
		'label'    => __( 'Scroll Container', 'kinetichub-scroll-to-top' ),
		'help'     => __( 'Choose where KineticHub Scroll to Top follows the scroll position.', 'kinetichub-scroll-to-top' ),
		'fieldset' => true,
		'layout'   => 'stack',
	)
);
KHSTT_UI::cards(
	'scroll_container',
	$settings['scroll_container'],
	array(
		'auto'   => array(
			'label' => __( 'Auto', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'Uses normal page scrolling, and switches to a large custom scroll area only when one clearly controls the page.', 'kinetichub-scroll-to-top' ),
			'smart' => true,
		),
		'window' => array(
			'label' => __( 'Window', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'Always use normal browser page scrolling. Best for most WordPress themes.', 'kinetichub-scroll-to-top' ),
		),
		'custom' => array(
			'label' => __( 'Custom', 'kinetichub-scroll-to-top' ),
			'note'  => __( 'Use a specific scrollable section instead of the page. Choose this only when your layout scrolls inside a container.', 'kinetichub-scroll-to-top' ),
		),
	),
	'stacked'
);
KHSTT_UI::field_close( true );

KHSTT_UI::field_open(
	array(
		'key'   => 'scroll_container_selector',
		'label' => __( 'Custom Scroll Container', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'The scrollable section to follow, written as a CSS selector such as .main-scroll-container. If it matches nothing, or matches something that cannot scroll, KineticHub Scroll to Top uses the browser window instead and says so below.', 'kinetichub-scroll-to-top' ),
		'when'  => 'scroll_container=custom',
	)
);
KHSTT_UI::text( 'scroll_container_selector', $settings['scroll_container_selector'], '.main-scroll-container' );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'   => 'conflict_detection',
		'label' => __( 'Smart Conflict Detection', 'kinetichub-scroll-to-top' ),
		'help'  => __( 'Looks for a back-to-top control your theme or another plugin already adds, and reports it below. This only reads your pages; nothing on your site is changed unless you turn on Force Replace.', 'kinetichub-scroll-to-top' ),
		'smart' => true,
	)
);
KHSTT_UI::toggle( 'conflict_detection', $settings['conflict_detection'] );
KHSTT_UI::field_close();

require KHSTT_PATH . 'admin/views/partials/compat-status.php';

KHSTT_UI::section_close();


KHSTT_UI::section_open(
	__( 'Advanced Compatibility', 'kinetichub-scroll-to-top' ),
	__( 'Only needed when your theme adds its own back-to-top control and offers no setting to turn it off.', 'kinetichub-scroll-to-top' )
);

KHSTT_UI::field_open(
	array(
		'key'     => 'conflict_selector',
		'label'   => __( 'Existing Control Selector', 'kinetichub-scroll-to-top' ),
		'help'    => __( 'Identify a competing back-to-top control that automatic detection may miss. Use a CSS selector: #scroll-top for an id, or .scroll-top-right for a class. This only identifies the control - it does not restyle or hide it. Turn on Force Replace below if you also want KineticHub Scroll to Top to hide it.', 'kinetichub-scroll-to-top' ),
		'caution' => true,
		'when'    => 'conflict_detection=1',
	)
);
KHSTT_UI::text( 'conflict_selector', $settings['conflict_selector'], '#back-to-top' );
KHSTT_UI::field_close();

KHSTT_UI::field_open(
	array(
		'key'     => 'force_replace',
		'label'   => __( 'Force Replace Existing Back-to-Top', 'kinetichub-scroll-to-top' ),
		'help'    => __( 'Hides the competing controls found above, so KineticHub Scroll to Top is the only back-to-top control on the page. Themes vary, so check your site after turning this on.', 'kinetichub-scroll-to-top' ),
		'caution' => true,
		'when'    => 'conflict_detection=1',
	)
);
KHSTT_UI::toggle( 'force_replace', $settings['force_replace'] );
KHSTT_UI::field_close();
?>
<p class="khstt-note">
	<?php esc_html_e( 'Only the competing control is hidden, and only where KineticHub Scroll to Top is showing its own control on that device. No theme or plugin script is removed, no setting of theirs is changed, and normal page scrolling is never disabled. Turning this off restores the original control on the next page load.', 'kinetichub-scroll-to-top' ); ?>
</p>
<?php
KHSTT_UI::section_close();
