<?php
/**
 * Frontend: display conditions, asset loading, and the control markup.
 *
 * Every conditional runs at hook time rather than in the constructor, so
 * WP_Query dependent functions such as is_singular() are always answerable.
 * Nothing is enqueued and nothing is printed unless should_render() agrees.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decides whether the control appears, loads its assets, and prints it.
 */
class KHSTT_Frontend {

	/**
	 * Minimum scrollable distance, in pixels, for the control to be worth
	 * showing at all. Enforced in the browser because only the browser knows
	 * the rendered page height. Mirrored in the Visibility tab's help text.
	 */
	const MIN_SCROLLABLE = 400;

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
	 * Per-request settings cache.
	 *
	 * @var array|null
	 */
	private $cache = null;

	/**
	 * Per-request should_render() cache.
	 *
	 * @var bool|null
	 */
	private $render_cache = null;

	/**
	 * Registers the frontend hooks this class owns.
	 *
	 * @param KHSTT_Settings $settings Shared settings service.
	 * @param KHSTT_Compat   $compat   Shared compatibility status service.
	 */
	public function __construct( KHSTT_Settings $settings, KHSTT_Compat $compat ) {
		$this->settings = $settings;
		$this->compat   = $compat;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render' ), 20 );
	}

	/**
	 * Returns the settings, read once per request.
	 *
	 * @return array
	 */
	private function get_settings() {
		if ( null === $this->cache ) {
			$this->cache = $this->settings->get_settings();
		}
		return $this->cache;
	}

	/**
	 * Whether the control should be output for this request. Memoized: both the
	 * enqueue hook and the footer hook ask, and nothing can change in between.
	 *
	 * @return bool
	 */
	private function should_render() {
		if ( null === $this->render_cache ) {
			$this->render_cache = $this->compute_should_render();
		}
		return $this->render_cache;
	}

	/**
	 * The actual display decision.
	 *
	 * @return bool
	 */
	private function compute_should_render() {
		$s = $this->get_settings();

		if ( empty( $s['enabled'] ) ) {
			return false;
		}

		// Request types where a floating control makes no sense, and where the
		// footer hook can still fire.
		if ( is_feed() || is_embed() || is_trackback() || is_robots() ) {
			return false;
		}

		// Switching every device off is the clearest way to say "render nothing".
		if ( empty( $s['show_desktop'] ) && empty( $s['show_tablet'] ) && empty( $s['show_mobile'] ) ) {
			return false;
		}

		return $this->matches_page_scope( $s['page_scope'] );
	}

	/**
	 * Evaluates the Page Scope setting against the current request.
	 *
	 * The WooCommerce option degrades to the entire site rather than to nothing
	 * when WooCommerce is inactive: silently hiding the control everywhere after
	 * deactivating an unrelated plugin would be the more surprising outcome.
	 *
	 * @param string $scope Page scope enum value.
	 * @return bool
	 */
	private function matches_page_scope( $scope ) {
		switch ( $scope ) {
			case 'posts':
				return is_singular( 'post' );

			case 'pages':
				return is_page();

			case 'posts_pages':
				return is_singular( 'post' ) || is_page();

			case 'front_page':
				return is_front_page();

			case 'wc_products':
				if ( ! function_exists( 'is_product' ) ) {
					return true;
				}
				return is_product();

			case 'entire_site':
			default:
				return true;
		}
	}

	/**
	 * Enqueues the stylesheet, the generated custom property block, the script,
	 * and its configuration.
	 */
	public function enqueue_assets() {
		if ( ! $this->should_render() ) {
			return;
		}

		$s = $this->get_settings();

		wp_enqueue_style(
			'khstt-frontend',
			KHSTT_URL . 'assets/css/khstt-frontend.css',
			array(),
			KHSTT_VERSION
		);

		wp_add_inline_style( 'khstt-frontend', KHSTT_Styles::build( $s ) );

		// The choreography is a separate stylesheet because most sites will
		// never select a power, and the ones that do should not pay for it on
		// every other page load either. Nothing extra is downloaded while the
		// setting is Off.
		if ( KHSTT_Powers::is_active( $s['kinetic_power'] ) ) {
			wp_enqueue_style(
				'khstt-powers',
				KHSTT_URL . 'assets/css/khstt-powers.css',
				array( 'khstt-frontend' ),
				KHSTT_VERSION
			);
		}

		wp_enqueue_script(
			'khstt-frontend',
			KHSTT_URL . 'assets/js/khstt-frontend.js',
			array(),
			KHSTT_VERSION,
			true
		);

		wp_add_inline_script(
			'khstt-frontend',
			'window.KHSTT=' . wp_json_encode( $this->get_js_config( $s ) ) . ';',
			'before'
		);

		$this->enqueue_compat( $s );
	}

	/**
	 * Loads the compatibility engine, which is a separate file because most
	 * sites never need it.
	 *
	 * It is enqueued only when it has work to do: suppressing a competing
	 * control, or reporting the compatibility status back for an administrator.
	 * A visitor on a site that is not using Force Replace downloads nothing
	 * extra at all.
	 *
	 * @param array $s Settings.
	 */
	private function enqueue_compat( array $s ) {
		$probe    = $this->compat->get_probe_config( $s );
		$suppress = $s['conflict_detection'] && $s['force_replace'];

		if ( false === $probe && ! $suppress ) {
			return;
		}

		$this->enqueue_early_hint( $s );

		wp_enqueue_script(
			'khstt-compat',
			KHSTT_URL . 'assets/js/khstt-compat.js',
			array( 'khstt-frontend' ),
			KHSTT_VERSION,
			true
		);

		wp_add_inline_script(
			'khstt-compat',
			'window.KHSTT_COMPAT=' . wp_json_encode(
				array(
					'detect'   => (bool) $s['conflict_detection'],
					'selector' => $s['conflict_selector'],
					'replace'  => (bool) $suppress,
					'probe'    => $probe,
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Prints the early suppression hint in the head.
	 *
	 * The compatibility engine runs at DOM ready, which is late enough that a
	 * theme showing its own control from a scroll handler gets a frame or more
	 * on screen first - most visibly on a reload part-way down a page. This
	 * holds back the specific controls the engine already found and confirmed
	 * under this exact configuration, and only until the engine has run.
	 *
	 * Both halves go out through the normal enqueue path with a handle that has
	 * no file of its own, so they land in the head in the usual place and
	 * nothing is echoed straight into the page.
	 *
	 * @param array $s Settings.
	 */
	private function enqueue_early_hint( array $s ) {
		$css = $this->compat->get_early_css( $s );

		if ( '' === $css ) {
			return;
		}

		wp_register_style( 'khstt-early', false, array(), KHSTT_VERSION );
		wp_enqueue_style( 'khstt-early' );
		wp_add_inline_style( 'khstt-early', $css );

		wp_register_script( 'khstt-early', false, array(), KHSTT_VERSION, false );
		wp_enqueue_script( 'khstt-early' );
		wp_add_inline_script( 'khstt-early', KHSTT_Compat::early_script() );
	}

	/**
	 * The runtime configuration handed to the script.
	 *
	 * Only behavioral values live here. Everything visual is already expressed
	 * in CSS, so the script never has to know a color or an offset.
	 *
	 * @param array $s Settings.
	 * @return array
	 */
	private function get_js_config( array $s ) {
		return array(
			'motion'        => $s['scroll_motion'],
			'trigger'       => $s['reveal_trigger'],
			'triggerOffset' => (int) $s['trigger_offset'],
			'smartReveal'   => (bool) $s['smart_reveal'],
			'peek'          => (bool) $s['peek_mode'],
			'smartReturn'   => (bool) $s['smart_return'],
			'destination'   => $s['scroll_destination'],
			'destSelector'  => $s['destination_selector'],
			'container'     => $s['scroll_container'],
			'containerSel'  => $s['scroll_container_selector'],
			'footerDock'    => (bool) $s['smart_footer_dock'],
			'progress'      => (bool) $s['progress_enabled'],
			'progressScope' => $s['progress_scope'],
			'progSelector'  => $s['progress_selector'],
			'minScroll'     => self::MIN_SCROLLABLE,
			// The selected KineticPower and its phase envelope, or false when
			// none is selected - which is the common case, and the case in
			// which the script does no power work at all.
			'power'         => KHSTT_Powers::runtime_config( $s['kinetic_power'] ),
			'idleFade'      => (bool) $s['idle_fade'],
			'hoverLabel'    => (bool) $s['hover_label'],
			// Idle timing is deliberately not a setting: it is one opinionated
			// number, and a slider for it would be a worse product than a good
			// default. It lives here so the script and the tests agree on it.
			'idleAfter'     => KHSTT_Styles::IDLE_AFTER,
			'pressMs'       => KHSTT_Styles::PRESS_MS,
			// The resting labels are already in the markup, so the script reads
			// them back from the DOM rather than being told them twice. Only the
			// Smart Return wording, which no element carries yet, is sent.
			'i18n'          => array(
				'back'          => __( 'Return to your previous reading position', 'kinetichub-scroll-to-top' ),
				'backAvailable' => __( 'Return to your previous reading position is available.', 'kinetichub-scroll-to-top' ),
				'labelReturn'   => __( 'Return to previous position', 'kinetichub-scroll-to-top' ),
			),
		);
	}

	/**
	 * Prints the control in the footer.
	 *
	 * The wrapper starts hidden so nothing flashes before the script decides
	 * whether this page is even long enough to warrant a scroll control.
	 */
	public function render() {
		if ( ! $this->should_render() ) {
			return;
		}

		$s = $this->get_settings();

		$percentage        = $s['progress_enabled'] ? $s['show_percentage'] : 'off';
		$percentage_mobile = $s['progress_enabled'] && $s['mobile_progress_override']
			? $s['mobile_show_percentage']
			: $percentage;

		$label = 'content_start' === $s['scroll_destination']
			? __( 'Scroll to the start of the content', 'kinetichub-scroll-to-top' )
			: __( 'Scroll to top', 'kinetichub-scroll-to-top' );

		// The visible pill says the same thing more briefly than the accessible
		// name does. It is decoration: the button's aria-label stays the one
		// semantic source, and the pill is hidden from assistive technology.
		$pill = 'content_start' === $s['scroll_destination']
			? __( 'Back to content start', 'kinetichub-scroll-to-top' )
			: __( 'Back to top', 'kinetichub-scroll-to-top' );

		$power = KHSTT_Powers::is_active( $s['kinetic_power'] ) ? $s['kinetic_power'] : KHSTT_Powers::NONE;
		?>
		<div id="khstt" class="khstt"
			data-visible="0"
			<?php if ( KHSTT_Powers::NONE !== $power ) : ?>
				data-khstt-power="<?php echo esc_attr( KHSTT_Powers::slug( $power ) ); ?>"
				data-khstt-power-i="<?php echo esc_attr( $s['kinetic_power_intensity'] ); ?>"
			<?php endif; ?>
			data-shape="<?php echo esc_attr( $s['shape'] ); ?>"
			data-shadow="<?php echo esc_attr( $s['shadow'] ); ?>"
			data-blur="<?php echo $s['backdrop_blur'] ? '1' : '0'; ?>"
			data-pct="<?php echo esc_attr( $percentage ); ?>"
			data-pct-mobile="<?php echo esc_attr( $percentage_mobile ); ?>">

			<button type="button" class="khstt__btn" aria-label="<?php echo esc_attr( $label ); ?>">
				<?php if ( $s['progress_enabled'] ) : ?>
					<svg class="khstt__ring" aria-hidden="true" focusable="false">
						<rect class="khstt__track" fill="none"></rect>
						<rect class="khstt__bar" fill="none" stroke-linecap="round"></rect>
					</svg>
				<?php endif; ?>

				<span class="khstt__icon khstt__icon--up khstt-p-rest" aria-hidden="true"><?php KHSTT_Icons::render( $s['icon'] ); ?></span>

				<?php if ( $s['smart_return'] ) : ?>
					<span class="khstt__icon khstt__icon--back" aria-hidden="true"><?php KHSTT_Icons::render( 'arrow-down' ); ?></span>
				<?php endif; ?>

				<?php if ( 'off' !== $percentage || 'off' !== $percentage_mobile ) : ?>
					<span class="khstt__pct khstt-p-rest" aria-hidden="true">0%</span>
				<?php endif; ?>

				<?php
				// Last child, so the choreography paints over the ring and the
				// percentage rather than behind them.
				KHSTT_Powers::render_stage( $power );
				?>
			</button>

			<?php if ( $s['hover_label'] ) : ?>
				<?php
				// A sibling after the button on purpose: hover and focus states
				// reach it through the sibling combinator, so showing and hiding
				// it needs no JavaScript at all.
				?>
				<span class="khstt__label" aria-hidden="true"><?php echo esc_html( $pill ); ?></span>
			<?php endif; ?>

			<?php if ( $s['smart_return'] ) : ?>
				<span class="khstt__status" role="status" aria-live="polite"></span>
			<?php endif; ?>
		</div>
		<?php
	}
}
