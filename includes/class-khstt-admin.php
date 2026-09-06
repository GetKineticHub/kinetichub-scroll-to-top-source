<?php
/**
 * Admin: menu registration, asset loading, the reset handler, and the settings
 * screen entry point.
 *
 * Saving is handled entirely by the Settings API (options.php), which owns the
 * nonce and capability checks and calls KHSTT_Settings::sanitize_settings().
 * The only custom write path in this class is Reset to Defaults, which carries
 * its own nonce and capability check.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the settings screen: its menu entry, assets, and reset action.
 */
class KHSTT_Admin {

	const PAGE_SLUG    = 'kinetichub-scroll-to-top';
	const RESET_ACTION = 'khstt_reset';
	const RESET_NONCE  = 'khstt_reset_nonce';

	/** Discards the stored compatibility reading so a fresh one is taken. */
	const RECHECK_ACTION = 'khstt_recheck';
	const RECHECK_NONCE  = 'khstt_recheck_nonce';

	/**
	 * Shared settings service.
	 *
	 * @var KHSTT_Settings
	 */
	private $settings;

	/**
	 * Shared compatibility status service.
	 *
	 * @var KHSTT_Compat
	 */
	private $compat;

	/**
	 * Registers the admin hooks this class owns.
	 *
	 * @param KHSTT_Settings $settings Shared settings service.
	 * @param KHSTT_Compat   $compat   Shared compatibility status service.
	 */
	public function __construct( KHSTT_Settings $settings, KHSTT_Compat $compat ) {
		$this->settings = $settings;
		$this->compat   = $compat;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_' . self::RESET_ACTION, array( $this, 'handle_reset' ) );
		add_action( 'admin_post_' . self::RECHECK_ACTION, array( $this, 'handle_recheck' ) );
		add_filter( 'plugin_action_links_' . KHSTT_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Registers the settings screen under Settings.
	 */
	public function register_menu() {
		add_options_page(
			__( 'KineticHub Scroll to Top', 'kinetichub-scroll-to-top' ),
			__( 'Scroll to Top', 'kinetichub-scroll-to-top' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Adds a Settings link to this plugin's row on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$url = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'options-general.php' ) );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'kinetichub-scroll-to-top' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Loads the admin CSS and JS on this plugin's screen only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'khstt-admin',
			KHSTT_URL . 'admin/css/khstt-admin.css',
			array(),
			KHSTT_VERSION
		);

		// The live preview plays the real choreography, so it loads the same
		// stylesheet the front end does rather than a copy of it.
		wp_enqueue_style(
			'khstt-powers',
			KHSTT_URL . 'assets/css/khstt-powers.css',
			array( 'khstt-admin' ),
			KHSTT_VERSION
		);

		wp_enqueue_script(
			'khstt-admin',
			KHSTT_URL . 'admin/js/khstt-admin.js',
			array(),
			KHSTT_VERSION,
			true
		);

		wp_add_inline_script(
			'khstt-admin',
			'window.KHSTT_ADMIN=' . wp_json_encode( $this->get_js_config() ) . ';',
			'before'
		);
	}

	/**
	 * Data the admin script needs: the icon markup it swaps into the live
	 * preview, the style presets it applies, the KineticPower phase envelope,
	 * and translated strings.
	 *
	 * @return array
	 */
	private function get_js_config() {
		return array(
			'icons'      => $this->get_icon_markup_map(),
			'presets'    => self::get_style_presets(),
			'afterSave'  => self::is_after_save(),
			// The preview schedules its demonstration against the same phase
			// envelope the front-end engine uses.
			'powers'     => KHSTT_Powers::timings(),
			'thumbFloor' => array(
				'size'   => KHSTT_Styles::THUMB_MIN_SIZE,
				'offset' => KHSTT_Styles::THUMB_MIN_OFFSET,
			),
			// The preview demonstrates the same timings the visitor will get.
			'polish'     => array(
				'idleAfter' => KHSTT_Styles::IDLE_AFTER,
				'pressMs'   => KHSTT_Styles::PRESS_MS,
			),
			'i18n'       => array(
				'resetConfirm'   => __( 'Reset every setting back to its default? This cannot be undone.', 'kinetichub-scroll-to-top' ),
				'enabled'        => __( 'Enabled', 'kinetichub-scroll-to-top' ),
				'disabled'       => __( 'Disabled', 'kinetichub-scroll-to-top' ),
				'hiddenOnDevice' => __( 'Hidden on this device', 'kinetichub-scroll-to-top' ),
				// The same three strings the front end prints, so the preview
				// shows the label a visitor would actually read.
				'labelTop'       => __( 'Back to top', 'kinetichub-scroll-to-top' ),
				'labelContent'   => __( 'Back to content start', 'kinetichub-scroll-to-top' ),
				'labelReturn'    => __( 'Return to previous position', 'kinetichub-scroll-to-top' ),
			),
		);
	}

	/**
	 * Whether this request is the one WordPress serves straight after a save.
	 *
	 * The script cannot work this out for itself. WordPress removes
	 * settings-updated from the address bar in admin_head, through
	 * wp_admin_canonical_url(), which rewrites the URL with history
	 * .replaceState() before a single footer script has run. Anything reading
	 * window.location.search later sees a URL with the flag already gone, so
	 * the answer has to come from the request itself, which only PHP still has.
	 *
	 * A reset is deliberately not a save. Every value on the screen has just
	 * changed, so the place the reader was before means nothing and the top of
	 * the page is the honest answer.
	 *
	 * @return bool
	 */
	private static function is_after_save() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only, and only to decide where to put the scrollbar.
		if ( isset( $_GET['khstt-reset'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only, and only to decide where to put the scrollbar.
		return isset( $_GET['settings-updated'] );
	}

	/**
	 * Icon key to inline SVG map, including the Smart Return arrow used by the
	 * preview's return state.
	 *
	 * @return array<string, string>
	 */
	private function get_icon_markup_map() {
		$map = array();

		foreach ( array_keys( KHSTT_Icons::get_choices() ) as $key ) {
			$map[ $key ] = KHSTT_Icons::get( $key );
		}

		$map['arrow-down'] = KHSTT_Icons::get( 'arrow-down' );

		return $map;
	}

	/**
	 * The Quick Style presets offered in the Appearance tab.
	 *
	 * A preset is a one-shot writer: choosing one fills the individual
	 * appearance fields and then gets out of the way, so every value stays
	 * editable afterwards and nothing is locked to the preset.
	 *
	 * @return array
	 */
	public static function get_style_presets() {
		return array(
			'minimal' => array(
				'shape'            => 'circle',
				'bg_color'         => '#ffffff',
				'icon_color'       => '#111827',
				'hover_bg_color'   => '#f3f4f6',
				'hover_icon_color' => '#111827',
				'bg_opacity'       => 100,
				'backdrop_blur'    => false,
				'border_width'     => 1,
				'border_color'     => '#e5e7eb',
				'shadow'           => 'none',
				'track_color'      => '#111827',
				'track_opacity'    => 12,
				'progress_color'   => '#111827',
			),
			'soft'    => array(
				'shape'            => 'circle',
				'bg_color'         => '#111827',
				'icon_color'       => '#ffffff',
				'hover_bg_color'   => '#1f2937',
				'hover_icon_color' => '#ffffff',
				'bg_opacity'       => 100,
				'backdrop_blur'    => false,
				'border_width'     => 0,
				'border_color'     => '#111827',
				'shadow'           => 'soft',
				'track_color'      => '#ffffff',
				'track_opacity'    => 25,
				'progress_color'   => '#2563eb',
			),
			'outline' => array(
				'shape'            => 'rounded',
				'bg_color'         => '#ffffff',
				'icon_color'       => '#111827',
				'hover_bg_color'   => '#111827',
				'hover_icon_color' => '#ffffff',
				'bg_opacity'       => 100,
				'backdrop_blur'    => false,
				'border_width'     => 2,
				'border_color'     => '#111827',
				'shadow'           => 'none',
				'track_color'      => '#111827',
				'track_opacity'    => 15,
				'progress_color'   => '#111827',
			),
			'glass'   => array(
				'shape'            => 'circle',
				'bg_color'         => '#0f172a',
				'icon_color'       => '#ffffff',
				'hover_bg_color'   => '#1e293b',
				'hover_icon_color' => '#ffffff',
				'bg_opacity'       => 55,
				'backdrop_blur'    => true,
				'border_width'     => 1,
				'border_color'     => '#ffffff',
				'shadow'           => 'medium',
				'track_color'      => '#ffffff',
				'track_opacity'    => 30,
				'progress_color'   => '#38bdf8',
			),
			'bold'    => array(
				'shape'            => 'rounded',
				'bg_color'         => '#2563eb',
				'icon_color'       => '#ffffff',
				'hover_bg_color'   => '#1d4ed8',
				'hover_icon_color' => '#ffffff',
				'bg_opacity'       => 100,
				'backdrop_blur'    => false,
				'border_width'     => 0,
				'border_color'     => '#2563eb',
				'shadow'           => 'strong',
				'track_color'      => '#ffffff',
				'track_opacity'    => 35,
				'progress_color'   => '#ffffff',
			),
		);
	}

	/**
	 * The three placement choices, each with a small screen diagram, shared by
	 * the desktop, tablet, and mobile position controls.
	 *
	 * @return array
	 */
	public static function get_position_choices() {
		$diagram = static function ( $dot_x ) {
			return '<svg viewBox="0 0 40 28" fill="none" focusable="false" aria-hidden="true">'
				. '<rect x="0.75" y="0.75" width="38.5" height="26.5" rx="3.5" stroke="currentColor" stroke-opacity="0.35" stroke-width="1.5"/>'
				. '<circle cx="' . (int) $dot_x . '" cy="20" r="4" fill="currentColor"/>'
				. '</svg>';
		};

		return array(
			'bottom-right'  => array(
				'label'  => __( 'Bottom Right', 'kinetichub-scroll-to-top' ),
				'visual' => $diagram( 32 ),
			),
			'bottom-left'   => array(
				'label'  => __( 'Bottom Left', 'kinetichub-scroll-to-top' ),
				'visual' => $diagram( 8 ),
			),
			'bottom-center' => array(
				'label'  => __( 'Bottom Center', 'kinetichub-scroll-to-top' ),
				'visual' => $diagram( 20 ),
			),
		);
	}

	/**
	 * Restores every setting to its default.
	 *
	 * Reached only through the Reset to Defaults button, which posts to
	 * admin-post.php with a dedicated nonce.
	 */
	public function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kinetichub-scroll-to-top' ) );
		}

		check_admin_referer( self::RESET_ACTION, self::RESET_NONCE );

		update_option( KHSTT_Settings::OPTION_KEY, KHSTT_Settings::get_defaults() );

		// The stored compatibility snapshot describes the settings it was taken
		// under, so it is meaningless after a reset. Clearing it asks the next
		// administrator page view for a fresh reading instead of showing an
		// answer that no longer matches the configuration.
		delete_option( KHSTT_Compat::OPTION_KEY );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => self::PAGE_SLUG,
					'khstt-reset' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Discards the stored compatibility reading.
	 *
	 * Only the browser can see a rendered page, so PHP cannot re-run the check
	 * from here; claiming otherwise would be a button that reprints the same
	 * stored answer. What this does instead is real: it throws the stored
	 * reading away, which re-arms the probe, so the next front-end view by an
	 * administrator records a genuinely fresh one. The screen then says so and
	 * offers a link to the site.
	 *
	 * Reached only through the Check Again button, which posts to
	 * admin-post.php with its own nonce.
	 */
	public function handle_recheck() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'kinetichub-scroll-to-top' ) );
		}

		check_admin_referer( self::RECHECK_ACTION, self::RECHECK_NONCE );

		delete_option( KHSTT_Compat::OPTION_KEY );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => self::PAGE_SLUG,
					'khstt-recheck' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Renders the settings screen. The capability is re-checked here as
	 * defense in depth, independently of the menu registration.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->settings->get_settings();
		$compat   = $this->compat;

		require KHSTT_PATH . 'admin/views/page-settings.php';
	}
}
