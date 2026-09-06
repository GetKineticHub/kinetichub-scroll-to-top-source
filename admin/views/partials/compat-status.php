<?php
/**
 * Compatibility status: what the frontend last reported.
 *
 * Injected from general.php:
 *   $settings array        Current settings merged with defaults.
 *   $compat   KHSTT_Compat Compatibility status service.
 *
 * The reading comes from the browser, because only the browser can see the
 * rendered page. Until an administrator has loaded the site's front end there
 * is genuinely nothing to show, and this partial says so rather than inventing
 * a server-side guess.
 *
 * Tone vocabulary, applied consistently across every row:
 *
 *   positive  a clear pass - normal page scrolling, a working custom
 *             container, no competing control found
 *   neutral   information, not a verdict - nothing checked yet, the browser
 *             window as a deliberate choice, the configured motion
 *   warning   worth a look, but nothing is broken - a competing control
 *             exists, Force Replace is actively hiding one, a container
 *             selector fell back, an unadapted scrolling library
 *   error     reserved for genuine failures
 *
 * A second back-to-top control existing is never an error, and it is never
 * green either: Force Replace hiding one is a situation the site owner should
 * know about, not a pass.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$khstt_status  = $compat->get_status();
$khstt_checked = null !== $khstt_status;

/**
 * Emits one status row.
 *
 * @param string $label Row label.
 * @param string $value Row value.
 * @param string $tone  "positive", "neutral", "warning", or "error".
 */
$khstt_row = static function ( $label, $value, $tone = 'neutral' ) {
	printf(
		'<div class="khstt-status__row"><span class="khstt-status__label">%1$s</span><span class="khstt-status__value khstt-status__value--%2$s">%3$s</span></div>',
		esc_html( $label ),
		esc_attr( $tone ),
		esc_html( $value )
	);
};

// ---- Scroll environment ----------------------------------------------------
if ( ! $khstt_checked ) {
	$khstt_env_text = __( 'Not checked yet', 'kinetichub-scroll-to-top' );
	$khstt_env_tone = 'neutral';
} else {
	$khstt_env_text = KHSTT_Compat::environment_label( $khstt_status['env'] );
	$khstt_env_tone = KHSTT_Compat::environment_is_adapted( $khstt_status['env'] ) ? 'positive' : 'warning';
}

// ---- Scroll container ------------------------------------------------------
$khstt_source = $khstt_checked ? $khstt_status['source'] : '';
$khstt_fell   = $khstt_checked && ! empty( $khstt_status['fallback'] );
$khstt_mixed  = $khstt_checked && ! empty( $khstt_status['mixed'] );

if ( 'window' === $settings['scroll_container'] ) {
	// A deliberate choice that is working as asked: information, not a verdict.
	$khstt_container_text = __( 'Browser window', 'kinetichub-scroll-to-top' );
	$khstt_container_tone = 'neutral';
} elseif ( 'custom' === $settings['scroll_container'] ) {
	if ( ! $khstt_checked ) {
		$khstt_container_text = __( 'Custom, not checked yet', 'kinetichub-scroll-to-top' );
		$khstt_container_tone = 'neutral';
	} elseif ( $khstt_fell ) {
		$khstt_container_text = __( 'Custom container not found, using the browser window', 'kinetichub-scroll-to-top' );
		$khstt_container_tone = 'warning';
	} else {
		$khstt_container_text = '' !== $settings['scroll_container_selector']
			? sprintf(
				/* translators: %s: the configured CSS selector. */
				__( 'Custom: %s', 'kinetichub-scroll-to-top' ),
				$settings['scroll_container_selector']
			)
			: __( 'Custom', 'kinetichub-scroll-to-top' );
		$khstt_container_tone = 'positive';
	}
} elseif ( ! $khstt_checked ) {
	$khstt_container_text = __( 'Automatic, not checked yet', 'kinetichub-scroll-to-top' );
	$khstt_container_tone = 'neutral';
} elseif ( 'custom' === $khstt_source ) {
	$khstt_container_text = __( 'Automatic: a page-level scroll container', 'kinetichub-scroll-to-top' );
	$khstt_container_tone = 'positive';
} else {
	$khstt_container_text = __( 'Automatic: the browser window', 'kinetichub-scroll-to-top' );
	$khstt_container_tone = 'neutral';
}

// ---- Existing back-to-top --------------------------------------------------
$khstt_count      = $khstt_checked ? (int) $khstt_status['count'] : 0;
$khstt_suppressed = $khstt_checked ? (int) $khstt_status['suppressed'] : 0;
$khstt_conflicts  = ( $khstt_checked && ! empty( $khstt_status['conflicts'] ) ) ? $khstt_status['conflicts'] : array();
$khstt_managed    = $khstt_count > 0 && $khstt_suppressed >= $khstt_count;

if ( ! $settings['conflict_detection'] ) {
	$khstt_conflict_text = __( 'Detection is off', 'kinetichub-scroll-to-top' );
	$khstt_conflict_tone = 'neutral';
} elseif ( ! $khstt_checked ) {
	$khstt_conflict_text = __( 'Not checked yet', 'kinetichub-scroll-to-top' );
	$khstt_conflict_tone = 'neutral';
} elseif ( 0 === $khstt_count ) {
	$khstt_conflict_text = __( 'No competing back-to-top detected', 'kinetichub-scroll-to-top' );
	$khstt_conflict_tone = 'positive';
} elseif ( $khstt_managed ) {
	$khstt_conflict_text = sprintf(
		/* translators: %d: number of competing controls Force Replace is hiding. */
		_n(
			'%d found, hidden by Force Replace',
			'%d found, hidden by Force Replace',
			$khstt_suppressed,
			'kinetichub-scroll-to-top'
		),
		$khstt_suppressed
	);
	// Something is being actively managed. That is working as configured, but
	// it is a situation to know about rather than a clean pass.
	$khstt_conflict_tone = 'warning';
} else {
	$khstt_conflict_text = sprintf(
		/* translators: %d: number of competing controls found. */
		_n( '%d possible conflict detected', '%d possible conflicts detected', $khstt_count, 'kinetichub-scroll-to-top' ),
		$khstt_count
	);
	$khstt_conflict_tone = 'warning';
}

// ---- Mode ------------------------------------------------------------------
$khstt_modes = array(
	'smart'   => __( 'Smart', 'kinetichub-scroll-to-top' ),
	'smooth'  => __( 'Smooth', 'kinetichub-scroll-to-top' ),
	'instant' => __( 'Instant', 'kinetichub-scroll-to-top' ),
);
?>
<div class="khstt-status">

	<p class="khstt-status__title"><?php esc_html_e( 'Scroll Compatibility', 'kinetichub-scroll-to-top' ); ?></p>

	<?php
	$khstt_row( __( 'Scroll Environment', 'kinetichub-scroll-to-top' ), $khstt_env_text, $khstt_env_tone );
	$khstt_row( __( 'Scroll Container', 'kinetichub-scroll-to-top' ), $khstt_container_text, $khstt_container_tone );
	$khstt_row( __( 'Existing Back-to-Top', 'kinetichub-scroll-to-top' ), $khstt_conflict_text, $khstt_conflict_tone );
	$khstt_row( __( 'Scroll Motion', 'kinetichub-scroll-to-top' ), $khstt_modes[ $settings['scroll_motion'] ], 'neutral' );
	?>

	<p class="khstt-status__note">
		<?php esc_html_e( 'Compatibility is checked automatically when an administrator visits the front end.', 'kinetichub-scroll-to-top' ); ?>
		<?php if ( $khstt_checked ) : ?>
			<?php
			printf(
				/* translators: %s: human readable time difference, e.g. "5 mins". */
				esc_html__( 'This reading was taken %s ago.', 'kinetichub-scroll-to-top' ),
				esc_html( human_time_diff( (int) $khstt_status['time'] ) )
			);
			?>
		<?php else : ?>
			<?php esc_html_e( 'Nothing has been recorded yet.', 'kinetichub-scroll-to-top' ); ?>
		<?php endif; ?>
	</p>

	<p class="khstt-status__actions">
		<button type="submit" form="khstt-recheck-form" class="button button-secondary khstt-status__recheck">
			<?php esc_html_e( 'Check Again', 'kinetichub-scroll-to-top' ); ?>
		</button>
		<a class="khstt-status__visit" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
			<?php esc_html_e( 'Open your site', 'kinetichub-scroll-to-top' ); ?>
		</a>
		<span class="khstt-status__hint">
			<?php esc_html_e( 'Check Again clears this reading; opening your site records a new one.', 'kinetichub-scroll-to-top' ); ?>
		</span>
	</p>

	<?php if ( $khstt_mixed ) : ?>
		<div class="khstt-alert khstt-alert--notice">
			<p>
				<?php
				if ( '' !== $settings['scroll_container_selector'] ) {
					printf(
						/* translators: %s: the configured CSS selector. */
						esc_html__( 'Mixed page and container scrolling detected. KineticHub Scroll to Top follows %s while that scroll area is in view, and steps aside while it is not.', 'kinetichub-scroll-to-top' ),
						esc_html( $settings['scroll_container_selector'] )
					);
				} else {
					esc_html_e( 'Mixed page and container scrolling detected. KineticHub Scroll to Top follows the scroll area while it is in view, and steps aside while it is not.', 'kinetichub-scroll-to-top' );
				}
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $khstt_checked && ! KHSTT_Compat::environment_is_adapted( $khstt_status['env'] ) ) : ?>
		<div class="khstt-alert khstt-alert--warning">
			<p>
				<?php
				printf(
					/* translators: %s: the detected scrolling environment, e.g. "Lenis detected". */
					esc_html__( '%s. KineticHub Scroll to Top still reads the page\'s normal scroll position, which is usually correct, but it does not drive that library. Test Smart Motion and Smart Return on this site before relying on them.', 'kinetichub-scroll-to-top' ),
					esc_html( KHSTT_Compat::environment_label( $khstt_status['env'] ) )
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $khstt_conflicts ) : ?>
		<div class="khstt-alert khstt-alert--warning">
			<?php if ( $khstt_managed ) : ?>
				<p>
					<strong>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of competing controls Force Replace is hiding. */
								_n(
									'Force Replace is hiding %d competing control.',
									'Force Replace is hiding %d competing controls.',
									$khstt_suppressed,
									'kinetichub-scroll-to-top'
								),
								$khstt_suppressed
							)
						);
						?>
					</strong>
					<?php esc_html_e( 'The theme or plugin itself is untouched: only its front-end control is hidden, and it returns as soon as you turn this off.', 'kinetichub-scroll-to-top' ); ?>
				</p>
			<?php else : ?>
				<p>
					<strong>
						<?php
						echo esc_html(
							_n(
								'Another back-to-top control appears to be active on this site.',
								'Other back-to-top controls appear to be active on this site.',
								$khstt_count,
								'kinetichub-scroll-to-top'
							)
						);
						?>
					</strong>
					<?php esc_html_e( 'Running two controls may create duplicate buttons or inconsistent behaviour. Turning the existing control off in your theme or plugin settings is the cleanest fix; Force Replace below can hide it instead.', 'kinetichub-scroll-to-top' ); ?>
				</p>
			<?php endif; ?>

			<ul class="khstt-status__list">
				<?php foreach ( $khstt_conflicts as $khstt_conflict ) : ?>
					<li>
						<code><?php echo esc_html( KHSTT_Compat::descriptor( $khstt_conflict ) ); ?></code>
						<span class="khstt-status__via"><?php echo esc_html( KHSTT_Compat::reason_label( $khstt_conflict['via'] ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

</div>
