<?php
/**
 * Live preview panel.
 *
 * Everything here is a representation, not a working control: the preview
 * button is removed from the tab order and hidden from assistive technology so
 * the settings form has no duplicate or non-functional controls. None of the
 * inputs in this file carry a name attribute, so preview-only state such as the
 * simulated progress value is never submitted or saved.
 *
 * Injected from page-settings.php:
 *   $settings array Current settings merged with defaults.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$khstt_devices = array(
	'desktop' => __( 'Desktop', 'kinetichub-scroll-to-top' ),
	'tablet'  => __( 'Tablet', 'kinetichub-scroll-to-top' ),
	'mobile'  => __( 'Mobile', 'kinetichub-scroll-to-top' ),
);
?>
<div class="khstt-preview" id="khstt-preview" data-khstt-preview data-khstt-device="desktop">

	<div class="khstt-preview__head">
		<h2 class="khstt-preview__title"><?php esc_html_e( 'Live Preview', 'kinetichub-scroll-to-top' ); ?></h2>
		<p class="khstt-preview__hint"><?php esc_html_e( 'Updates as you type. Nothing here is saved until you press Save Changes.', 'kinetichub-scroll-to-top' ); ?></p>
	</div>

	<div class="khstt-preview__devices" role="group" aria-label="<?php esc_attr_e( 'Preview device', 'kinetichub-scroll-to-top' ); ?>">
		<?php
		$khstt_first_device = true;
		foreach ( $khstt_devices as $khstt_device_key => $khstt_device_label ) :
			?>
			<button type="button"
				class="khstt-preview__device<?php echo $khstt_first_device ? ' is-active' : ''; ?>"
				data-khstt-device-btn="<?php echo esc_attr( $khstt_device_key ); ?>"
				aria-pressed="<?php echo $khstt_first_device ? 'true' : 'false'; ?>">
				<?php echo esc_html( $khstt_device_label ); ?>
			</button>
			<?php
			$khstt_first_device = false;
		endforeach;
		?>
	</div>

	<div class="khstt-preview__stage" data-khstt-stage>
		<div class="khstt-preview__paper" aria-hidden="true">
			<span class="khstt-preview__bar khstt-preview__bar--head"></span>
			<span class="khstt-preview__bar"></span>
			<span class="khstt-preview__bar khstt-preview__bar--short"></span>
			<span class="khstt-preview__bar"></span>
			<span class="khstt-preview__bar"></span>
			<span class="khstt-preview__bar khstt-preview__bar--short"></span>
			<span class="khstt-preview__bar"></span>
			<span class="khstt-preview__footer"><?php esc_html_e( 'Footer', 'kinetichub-scroll-to-top' ); ?></span>
		</div>

		<div class="khstt-preview__control" data-khstt-control aria-hidden="true">
			<span class="khstt-preview__btn" data-khstt-btn>
				<svg class="khstt-preview__ring" data-khstt-ring focusable="false" aria-hidden="true">
					<rect data-khstt-ring-track fill="none"></rect>
					<rect data-khstt-ring-bar fill="none" stroke-linecap="round"></rect>
				</svg>
				<span class="khstt-preview__icon khstt-p-rest" data-khstt-icon></span>
				<span class="khstt-preview__pct khstt-p-rest" data-khstt-pct>0%</span>
				<?php
				// The same stage the front-end control gets, styled by the same
				// stylesheet, so the preview plays the real choreography rather
				// than an imitation of it. It is hidden until the script sets a
				// phase, and the whole preview is already aria-hidden.
				KHSTT_Powers::render_stage( KHSTT_Powers::ROCKET );
				?>
			</span>
			<?php
			// A sibling after the button, exactly as on the front end, so the
			// same hover and focus relationship holds here.
			?>
			<span class="khstt-preview__pill" data-khstt-pill hidden></span>
		</div>
	</div>

	<div class="khstt-preview__controls">
		<div class="khstt-preview__row">
			<label class="khstt-preview__label" for="khstt-preview-progress">
				<?php esc_html_e( 'Simulated progress', 'kinetichub-scroll-to-top' ); ?>
			</label>
			<input type="range"
				id="khstt-preview-progress"
				class="khstt-preview__range"
				min="0" max="100" step="1" value="35"
				data-khstt-progress-sim>
			<output class="khstt-preview__value" for="khstt-preview-progress" data-khstt-progress-out>35%</output>
		</div>

		<div class="khstt-preview__row khstt-preview__row--checks">
			<label class="khstt-preview__check">
				<input type="checkbox" data-khstt-sim="hover">
				<span><?php esc_html_e( 'Hover state', 'kinetichub-scroll-to-top' ); ?></span>
			</label>
			<label class="khstt-preview__check">
				<input type="checkbox" data-khstt-sim="return">
				<span><?php esc_html_e( 'Smart Return state', 'kinetichub-scroll-to-top' ); ?></span>
			</label>
		</div>
	</div>

	<p class="khstt-preview__note khstt-preview__note--power" data-khstt-power-hint hidden>
		<?php esc_html_e( 'Click the preview control to test Rocket Boost.', 'kinetichub-scroll-to-top' ); ?>
	</p>

	<p class="khstt-preview__note">
		<?php esc_html_e( 'The preview shows the control at its real pixel size. Offsets are drawn to scale inside this small stage, so they read larger here than on a full screen.', 'kinetichub-scroll-to-top' ); ?>
	</p>

</div>
