<?php
/**
 * Settings screen shell: hero, tablist, panels, live preview, action bar.
 *
 * Injected from KHSTT_Admin::render_page():
 *   $settings array Current settings merged with defaults.
 *
 * Every field lives inside the single options.php form, including the hero's
 * master enable switch, so one Save button commits the whole screen.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$khstt_tabs = array(
	'general'    => __( 'General', 'kinetichub-scroll-to-top' ),
	'position'   => __( 'Position', 'kinetichub-scroll-to-top' ),
	'appearance' => __( 'Appearance', 'kinetichub-scroll-to-top' ),
	'progress'   => __( 'Progress', 'kinetichub-scroll-to-top' ),
	'visibility' => __( 'Visibility', 'kinetichub-scroll-to-top' ),
);
?>
<div class="wrap khstt-wrap">

	<?php
	// There is no screen-reader-only <h1> here any more: the hero's own title
	// is a real <h1>, so the visible heading and the accessible one are the
	// same string rather than two that have to be kept in step.
	?>

	<?php
	// settings_errors() is deliberately not called here. This screen is
	// registered with add_options_page(), so its $parent_file is
	// options-general.php, and wp-admin/admin-header.php already requires
	// options-head.php for those screens - which calls settings_errors()
	// itself. Calling it again printed "Settings saved." a second time after
	// every save, because the first call leaves the messages in the
	// $wp_settings_errors global after clearing their transient.

	// Read-only display flag set by the reset redirect. No data is processed
	// from it, and the write itself is nonce-checked in KHSTT_Admin::handle_reset().
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['khstt-reset'] ) ) :
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'All settings were reset to their defaults.', 'kinetichub-scroll-to-top' ); ?></p>
		</div>
		<?php
	endif;

	// Read-only display flag set by the Check Again redirect. The write it
	// reports on is nonce-checked in KHSTT_Admin::handle_recheck().
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['khstt-recheck'] ) ) :
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php esc_html_e( 'The stored compatibility reading was cleared.', 'kinetichub-scroll-to-top' ); ?>
				<?php
				printf(
					/* translators: %s: link to the site's front page. */
					esc_html__( 'Open %s to record a fresh one, then return here.', 'kinetichub-scroll-to-top' ),
					'<a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">'
						. esc_html__( 'your site', 'kinetichub-scroll-to-top' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	endif;
	?>

	<?php
	// novalidate: the panels for inactive tabs are display:none, and browsers
	// refuse to submit a form containing an out-of-range control they cannot
	// focus to report on. Every value is clamped in the browser as it is typed
	// and clamped again by the server sanitizer, so native validation here would
	// only ever turn a recoverable value into a silently failed save.
	?>
	<form method="post" action="options.php" id="khstt-form" class="khstt-form" novalidate>
		<?php settings_fields( KHSTT_Settings::OPTION_GROUP ); ?>

		<header class="khstt-hero">
			<span class="khstt-hero__mark" aria-hidden="true">
				<?php
				/*
				 * The official KineticHub Scroll to Top logo - the same artwork
				 * prepared for the plugin's WordPress.org icon, shipped
				 * unmodified rather than redrawn, so the dashboard and the
				 * directory listing show one identical mark.
				 *
				 * It is a local file: no remote request and no CDN. The source
				 * is 256px square while the frame renders at 68px (56px on
				 * small screens), which keeps it sharp on 2x and 3x displays.
				 * Width and height are declared so the header never reflows
				 * while it loads, and the circular crop is CSS only.
				 *
				 * The hero names the product in text immediately below, so the
				 * mark is decorative and hidden from assistive technology.
				 */
				?>
				<img class="khstt-hero__logo"
					src="<?php echo esc_url( KHSTT_URL . 'admin/images/khstt-logo.png' ); ?>"
					alt=""
					width="68"
					height="68"
					decoding="async" />
			</span>

			<div class="khstt-hero__text">
				<?php
				/*
				 * "KineticHub" is the product name and is deliberately not sent
				 * through the translation layer - brand names are not
				 * translated, and splitting a translatable string across spans
				 * would make the string untranslatable in practice. The
				 * descriptive half of the title stays a normal translatable
				 * string.
				 *
				 * This is the page's only <h1>, so the visible title and the
				 * accessible one are the same text.
				 */
				?>
				<h1 class="khstt-hero__title">
					<span class="khstt-brand-word">Kinetic<span class="khstt-brand-word__hub">Hub</span></span>
					<span class="khstt-hero__title-rest"><?php esc_html_e( 'Scroll to Top', 'kinetichub-scroll-to-top' ); ?></span>
				</h1>
				<p class="khstt-hero__subtitle">
					<?php esc_html_e( 'A lightweight smart scroll companion: navigate, show reading progress, and stay out of the way.', 'kinetichub-scroll-to-top' ); ?>
				</p>
			</div>

			<div class="khstt-hero__aside">
				<div class="khstt-hero__actions">
					<span class="khstt-hero__badge">
						<span class="khstt-hero__badge-dot" aria-hidden="true"></span>
						<?php
						printf(
							/* translators: %s: plugin version number. */
							esc_html__( 'Version %s', 'kinetichub-scroll-to-top' ),
							esc_html( KHSTT_VERSION )
						);
						?>
					</span>
					<?php
					// Ecosystem navigation, not promotion: one link, to this
					// plugin's own documentation. The destination is the
					// KHSTT_DOC_URL constant in the plugin's main file.
					?>
					<a class="button khstt-hero__link"
						href="<?php echo esc_url( KHSTT_DOC_URL ); ?>"
						target="_blank"
						rel="noopener noreferrer">
						<?php esc_html_e( 'Documentation', 'kinetichub-scroll-to-top' ); ?>
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'kinetichub-scroll-to-top' ); ?></span>
					</a>
				</div>

				<div class="khstt-hero__switch">
					<label class="khstt-hero__switch-label" for="<?php echo esc_attr( KHSTT_UI::id( 'enabled' ) ); ?>">
						<span class="khstt-hero__switch-title"><?php esc_html_e( 'Scroll to Top', 'kinetichub-scroll-to-top' ); ?></span>
						<span class="khstt-hero__switch-state" data-khstt-enabled-label>
							<?php echo $settings['enabled'] ? esc_html__( 'Enabled', 'kinetichub-scroll-to-top' ) : esc_html__( 'Disabled', 'kinetichub-scroll-to-top' ); ?>
						</span>
					</label>
					<?php KHSTT_UI::toggle( 'enabled', $settings['enabled'], 'large' ); ?>
				</div>
			</div>
		</header>

		<?php
		// WordPress relocates every admin notice on the screen to just after
		// this marker. Without it the anchor would be the first heading, which
		// is now the hero title, and notices would be injected inside the hero
		// itself. Core already hides the element; the stylesheet zeroes its box
		// so it opens no gap between the hero and the tab strip.
		?>
		<hr class="wp-header-end">

		<?php
		// The tab strip sits outside the two-column layout so it spans the full
		// width of the panel directly under the hero, the way the rest of the
		// KineticHub dashboards are built. It is still the same tablist: the
		// script finds it, and every panel, by data attribute.
		?>
		<div class="khstt-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'kinetichub-scroll-to-top' ); ?>">
			<?php
			$khstt_first = true;
			foreach ( $khstt_tabs as $khstt_tab_id => $khstt_tab_label ) :
				?>
				<button type="button"
					class="khstt-tab<?php echo $khstt_first ? ' is-active' : ''; ?>"
					id="khstt-tab-<?php echo esc_attr( $khstt_tab_id ); ?>"
					role="tab"
					aria-selected="<?php echo $khstt_first ? 'true' : 'false'; ?>"
					aria-controls="khstt-panel-<?php echo esc_attr( $khstt_tab_id ); ?>"
					tabindex="<?php echo $khstt_first ? '0' : '-1'; ?>"
					data-khstt-tab="<?php echo esc_attr( $khstt_tab_id ); ?>">
					<?php KHSTT_Icons::render( 'tab-' . $khstt_tab_id, 'khstt-tab__icon' ); ?>
					<?php
					// The label is only ever hidden visually, never removed, so
					// the button keeps its accessible name on narrow screens
					// where the strip falls back to icons alone.
					?>
					<span class="khstt-tab__label"><?php echo esc_html( $khstt_tab_label ); ?></span>
				</button>
				<?php
				$khstt_first = false;
			endforeach;
			?>
		</div>

		<?php
		// Without JavaScript the tab controls cannot switch panels, so
		// every panel is shown stacked instead. This block sits after
		// the stylesheet in source order, so it wins on equal
		// specificity without needing !important.
		?>
		<noscript>
			<style>.khstt-tabs{display:none}.khstt-panel[data-khstt-inactive]{display:block}.khstt-side{display:none}</style>
		</noscript>

		<div class="khstt-layout">

			<div class="khstt-main">

				<div class="khstt-panels">
					<?php
					$khstt_first = true;
					foreach ( array_keys( $khstt_tabs ) as $khstt_tab_id ) :
						?>
						<div class="khstt-panel"
							id="khstt-panel-<?php echo esc_attr( $khstt_tab_id ); ?>"
							role="tabpanel"
							aria-labelledby="khstt-tab-<?php echo esc_attr( $khstt_tab_id ); ?>"
							data-khstt-panel="<?php echo esc_attr( $khstt_tab_id ); ?>"
							<?php echo $khstt_first ? '' : 'data-khstt-inactive="1"'; ?>>
							<?php require KHSTT_PATH . 'admin/views/tabs/' . $khstt_tab_id . '.php'; ?>
						</div>
						<?php
						$khstt_first = false;
					endforeach;
					?>
				</div>

			</div>

			<aside class="khstt-side">
				<?php require KHSTT_PATH . 'admin/views/partials/preview.php'; ?>
			</aside>

		</div>

		<div class="khstt-actionbar">
			<?php submit_button( __( 'Save Changes', 'kinetichub-scroll-to-top' ), 'primary khstt-save', 'submit', false ); ?>
			<button type="submit"
				form="khstt-reset-form"
				class="button button-link khstt-reset"
				data-khstt-reset>
				<?php esc_html_e( 'Reset to Defaults', 'kinetichub-scroll-to-top' ); ?>
			</button>
		</div>

	</form>

	<?php
	// Rendered outside the settings form because HTML forms cannot nest. The
	// Reset button above targets it through its form attribute so both actions
	// still appear together in the action bar.
	?>
	<form id="khstt-reset-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="khstt-offscreen-form">
		<input type="hidden" name="action" value="<?php echo esc_attr( KHSTT_Admin::RESET_ACTION ); ?>">
		<?php
		// A distinct field name is required: settings_fields() above already
		// emits a _wpnonce field, and two elements sharing that id would be
		// invalid markup and ambiguous to getElementById().
		wp_nonce_field( KHSTT_Admin::RESET_ACTION, KHSTT_Admin::RESET_NONCE );
		?>
	</form>

	<?php
	// The Check Again button inside the Compatibility card targets this form
	// the same way, for the same reason: it sits inside the settings form and
	// forms cannot nest.
	?>
	<form id="khstt-recheck-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="khstt-offscreen-form">
		<input type="hidden" name="action" value="<?php echo esc_attr( KHSTT_Admin::RECHECK_ACTION ); ?>">
		<?php wp_nonce_field( KHSTT_Admin::RECHECK_ACTION, KHSTT_Admin::RECHECK_NONCE ); ?>
	</form>

</div>
