<?php
/**
 * KineticHub Scroll to Top - Playground showcase template.
 *
 * A standalone page: the demo deliberately owns the whole viewport rather than
 * sitting inside theme chrome, because it is the product showcase. It still
 * calls wp_head() / wp_body_open() / wp_footer(), so the real Scroll to Top
 * control is printed over it exactly as it would be on any site, and the admin
 * bar keeps working.
 *
 * The page is long on purpose. Every feature this plugin has is a response to
 * scrolling, so a short page would demonstrate nothing: the control needs a
 * document tall enough to reveal itself in, fill a progress ring against, and
 * make a Smart Return worth offering.
 *
 * The closing <footer> is not decoration either - Smart Footer Dock looks for
 * a footer element, and this is the one it finds.
 *
 * @package KineticHub_Scroll_To_Top_Demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$khsttd_variations = khsttd_variations();
$khsttd_current    = khsttd_current_variation();

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'khsttd' ); ?>>
<?php wp_body_open(); ?>

<main class="khsttd-main">

	<!-- ===================================================== 1. HERO -->
	<section class="khsttd-hero">
		<div class="khsttd-shell">
			<p class="khsttd-hero__eyebrow">KineticHub</p>
			<h1 class="khsttd-hero__title">Scroll to Top</h1>
			<p class="khsttd-hero__sub">
				A smart scroll companion for WordPress. It returns the reader to the
				top, shows how far through the page they are, and stays out of the
				way while they read.
			</p>

			<p class="khsttd-hero__note">
				The control in the corner of this page is the real plugin, running
				the real settings. Nothing on this page is a mock-up of it.
			</p>

			<div class="khsttd-hero__cue" aria-hidden="true">
				<span class="khsttd-hero__cue-text">Start scrolling</span>
				<span class="khsttd-hero__cue-line"></span>
			</div>
		</div>
	</section>

	<!-- ===================================================== 2. GUIDANCE -->
	<section class="khsttd-guide" aria-labelledby="khsttd-guide-title" data-khsttd-reveal>
		<div class="khsttd-shell">
			<h2 class="khsttd-guide__title" id="khsttd-guide-title">Four things to try</h2>
			<p class="khsttd-guide__note">
				Each one ticks itself off as it happens. The demo watches the
				control's own state to do that - it never drives it for you.
			</p>

			<ol class="khsttd-steps" id="khsttd-steps">
				<li class="khsttd-step" data-khsttd-step="scrolled">
					<span class="khsttd-step__tick" aria-hidden="true"><?php khsttd_icon( 'check' ); ?></span>
					<span class="khsttd-step__text">
						<strong>Scroll down.</strong>
						The control appears once you have genuinely left the first screen.
					</span>
				</li>
				<li class="khsttd-step" data-khsttd-step="progress">
					<span class="khsttd-step__tick" aria-hidden="true"><?php khsttd_icon( 'check' ); ?></span>
					<span class="khsttd-step__text">
						<strong>Watch the ring.</strong>
						It fills as you move through the page, and the number inside is
						the same value.
					</span>
				</li>
				<li class="khsttd-step" data-khsttd-step="used">
					<span class="khsttd-step__tick" aria-hidden="true"><?php khsttd_icon( 'check' ); ?></span>
					<span class="khsttd-step__text">
						<strong>Press it.</strong>
						The page travels back to the top, and Rocket Boost plays while
						it does.
					</span>
				</li>
				<li class="khsttd-step" data-khsttd-step="returned">
					<span class="khsttd-step__tick" aria-hidden="true"><?php khsttd_icon( 'check' ); ?></span>
					<span class="khsttd-step__text">
						<strong>Press it again.</strong>
						For a few seconds the same control offers to take you back to
						where you were reading.
					</span>
				</li>
			</ol>

			<p class="khsttd-guide__hint" id="khsttd-hint" role="status" aria-live="polite"></p>
		</div>
	</section>

	<!-- ===================================================== 3. READING PROGRESS -->
	<?php
	khsttd_section_open(
		'progress',
		'Reading progress',
		'How far through the page am I?',
		'A thin ring wraps the control and fills as the reader moves. The exact number can sit inside the button too.'
	);
	?>
		<p class="khsttd-p">
			A progress indicator is usually a bar pinned across the top of the
			screen: a second piece of furniture, competing for the same edge as
			the site header. This one is drawn around a control that already had
			to be somewhere, so it costs no extra space on the page.
		</p>
		<p class="khsttd-p">
			The ring is one rounded rectangle whose corner radius follows whatever
			button shape you chose, and its length is calculated rather than
			estimated - so it reads correctly on a circle, a rounded square and a
			square alike. It is drawn as inline SVG, so there is nothing extra to
			download and nothing to load late.
		</p>
		<p class="khsttd-p">
			What it measures is a setting. <strong>Page</strong> tracks the whole
			document, which is what this demo is doing. <strong>Smart Content</strong>
			tracks the article itself, so the ring reaches one hundred per cent at
			the end of the writing rather than at the end of the comments, the
			related posts and the footer.
		</p>

		<div class="khsttd-grid">
			<?php
			khsttd_card( 'ring', 'The ring', 'Whole page, or the article only. Colour, thickness and track opacity are all yours.' );
			khsttd_card( 'eye', 'The number', 'Off, always, or on hover. The icon comes back the moment you hover or focus the control.' );
			khsttd_card( 'devices', 'On phones', 'The ring can be thinner and the percentage can be off, without touching the desktop setting.' );
			?>
		</div>
	<?php khsttd_section_close(); ?>

	<!-- ===================================================== 4. SMART NAVIGATION -->
	<?php
	khsttd_section_open(
		'smart',
		'Smart navigation',
		'A control that knows when to be there',
		'Four behaviours, all of them about staying useful without becoming clutter.'
	);
	?>
		<div class="khsttd-rows">
			<div class="khsttd-row">
				<h3 class="khsttd-row__title">Smart Reveal</h3>
				<p class="khsttd-row__body">
					While you read downward the control gets out of the way. Scroll up
					- the gesture of someone who wants to go back - and it returns at
					once. On this page it is set to <em>Peek</em>, the middle setting:
					instead of disappearing it shrinks to a quiet marker, so you can
					watch the behaviour rather than wonder where the button went.
				</p>
			</div>
			<div class="khsttd-row">
				<h3 class="khsttd-row__title">Smart Return</h3>
				<p class="khsttd-row__body">
					Jumping to the top of a long page normally means losing your place.
					Here the control remembers where you were, and for twelve seconds
					after you arrive it offers to take you back. No second button
					appears; the same one changes what it does. Scroll about half a
					screen on your own, or press Escape while it has focus, and the
					offer clears itself.
				</p>
			</div>
			<div class="khsttd-row">
				<h3 class="khsttd-row__title">Smart Motion</h3>
				<p class="khsttd-row__body">
					The travel time follows the distance. A short hop stays snappy; a
					very long page still arrives promptly instead of crawling for four
					seconds. Both ends are capped. If you would rather not have any of
					that, Smooth and Instant are the other two choices.
				</p>
			</div>
			<div class="khsttd-row">
				<h3 class="khsttd-row__title">Smart Footer Dock</h3>
				<p class="khsttd-row__body">
					Scroll to the bottom of this page and watch the control lift clear
					of the footer rather than sitting on top of it. It looks for the
					usual footer elements and does nothing at all if none of them
					exist.
				</p>
			</div>
		</div>
	<?php khsttd_section_close(); ?>

	<!-- ===================================================== 5. LONG READ -->
	<?php
	khsttd_section_open(
		'reading',
		'A longer read',
		'Something to actually scroll through',
		'Six paragraphs about why a scroll control is worth thinking about - and enough page height to make the features above worth trying.'
	);
	?>
		<div class="khsttd-prose">
			<p class="khsttd-p">
				A back-to-top button is one of the oldest patterns on the web, and one
				of the least examined. It appeared when pages first grew past a couple
				of screens, it has barely changed since, and most implementations still
				do exactly one thing: they sit in a corner and they scroll you to
				position zero.
			</p>
			<p class="khsttd-p">
				That is fine, as far as it goes. The trouble is what it ignores. A
				reader who presses it has told you something specific - that they have
				finished with where they were, or that they want to get back to
				navigation, or that they overshot and want to start again. Those are
				three different intentions, and a control that treats them identically
				is throwing away the only signal it was given.
			</p>
			<p class="khsttd-p">
				Consider the overshoot case, which is the most common on long pages. A
				reader is two thirds of the way down an article, jumps to the top to
				check the heading or the date, and now has to find their place again by
				scrolling and skimming. The information needed to spare them that was
				available the whole time: the page knew exactly where they were when
				they pressed. Remembering it costs one number.
			</p>
			<p class="khsttd-p">
				Then there is the question of when a control should be visible at all.
				The usual answer is "after four hundred pixels", which is a rule about
				the document rather than about the reader. Someone moving steadily down
				a page is reading; they have not asked to go anywhere. Someone scrolling
				back up is looking for something. The second is when a return control
				earns its place on screen, and the first is when it is just a shape in
				the corner of the eye.
			</p>
			<p class="khsttd-p">
				Progress is the other half of it. A reader deciding whether to keep
				going is making a judgement about how much is left, and on a long page
				the scrollbar is a poor guide - it counts the footer, the comments and
				the three related posts underneath as though they were part of the
				article. Measuring the content itself gives an honest answer, and
				putting that answer around a control that already exists means the
				answer costs nothing in layout.
			</p>
			<p class="khsttd-p">
				None of this needs to be heavy. Everything on this page runs from one
				passive scroll listener and one passive resize listener, both feeding a
				single animation frame that reads the scroll position once. There is no
				jQuery, no framework, no icon font and no external request of any kind.
				The whole thing is a small amount of code that pays attention.
			</p>
		</div>
	<?php khsttd_section_close(); ?>

	<!-- ===================================================== 6. ROCKET BOOST -->
	<?php
	khsttd_section_open(
		'powers',
		'KineticPowers',
		'Rocket Boost',
		'A short piece of motion when someone actually uses the control. Decoration, never behaviour.'
	);
	?>
		<p class="khsttd-p">
			Press the control on this page and three things happen inside the button:
			a small recoil as it ignites, a rocket that climbs with a short thrust
			trail, and a soft spark marking the arrival. Then it goes back to being a
			button. Nothing repeats, and nothing loops.
		</p>
		<p class="khsttd-p">
			It is decoration in the strict sense. The page scrolls exactly where it
			scrolled before, at exactly the speed it did before, and the sequence
			simply follows along. The rocket is drawn in the icon colour you already
			chose, so there is no new colour to configure. Your chosen icon stays the
			resting icon - Rocket is not a ninth entry in the icon picker.
		</p>
		<p class="khsttd-p">
			It costs nothing when it is off, which is the default. The choreography
			lives in a separate stylesheet that is downloaded only when a power is
			selected, no listener or animation loop is added either way, and readers
			who ask for reduced motion never see it at all - the sequence is switched
			off rather than made smaller.
		</p>

		<div class="khsttd-grid">
			<?php
			khsttd_card( 'rocket', 'Three intensities', 'Subtle, Balanced and Strong change how far the sequence travels. They never touch the scroll.' );
			khsttd_card( 'feather', 'Nothing when off', 'No stylesheet is requested, no listener is added, and the script does no power work at all.' );
			khsttd_card( 'accessible', 'Never announced', 'The button stays a real button, its accessible name never changes because of a sequence, and no tab stop is added.' );
			?>
		</div>
	<?php khsttd_section_close(); ?>

	<!-- ===================================================== 7. LOOKS -->
	<?php
	khsttd_section_open(
		'looks',
		'Appearance',
		'Try a different configuration',
		'Each of these reloads the page with different settings for this preview only. Nothing is saved, and the Settings screen keeps showing the stored values.'
	);
	?>
		<div class="khsttd-looks">
			<?php foreach ( $khsttd_variations as $khsttd_key => $khsttd_look ) : ?>
				<a class="khsttd-look<?php echo $khsttd_key === $khsttd_current ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( khsttd_variation_url( $khsttd_key ) ); ?>"
					<?php echo $khsttd_key === $khsttd_current ? 'aria-current="true"' : ''; ?>>
					<span class="khsttd-look__name">
						<span class="khsttd-look__tick" aria-hidden="true"><?php khsttd_icon( 'check' ); ?></span>
						<?php echo esc_html( $khsttd_look['label'] ); ?>
					</span>
					<span class="khsttd-look__note"><?php echo esc_html( $khsttd_look['note'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<p class="khsttd-p khsttd-p--tight">
			The real settings screen has five tabs and a live preview beside them:
			five style presets, eight icons, three shapes, size, colours, opacity,
			border, shadow, position with per-device overrides, and the progress
			ring. Everything here was configured with it.
		</p>
	<?php khsttd_section_close(); ?>

	<!-- ===================================================== 8. THE REST -->
	<?php
	khsttd_section_open(
		'rest',
		'The rest of it',
		'What else is in the box',
		'The parts that are less fun to demonstrate and more likely to decide whether the plugin survives on a real site.'
	);
	?>
		<div class="khsttd-grid">
			<?php
			khsttd_card( 'puzzle', 'Works with your theme', 'If your theme already prints a back-to-top control, the plugin tells you it found one - and can hide it for you without touching the theme.' );
			khsttd_card( 'sliders', 'Custom scroll containers', 'Sites that scroll inside an element rather than the window are supported: Auto, Window, or a selector you name.' );
			khsttd_card( 'devices', 'Per-device everything', 'Position, offsets, size, ring thickness and the percentage can all differ on tablet and phone. Safe-area insets are automatic.' );
			khsttd_card( 'accessible', 'Accessible by construction', 'A real button, never smaller than 44 by 44, fully keyboard operable, with a name that updates when Smart Return is offered.' );
			khsttd_card( 'feather', 'Light on purpose', 'No jQuery, no framework, no icon font, no build step, and one database row for all the settings.' );
			khsttd_card( 'shield', 'Nothing is collected', 'No tracking, no analytics, no remote requests, no cookies, and nothing written to browser storage.' );
			?>
		</div>
	<?php khsttd_section_close(); ?>

	<!-- ===================================================== 9. CLOSE -->
	<section class="khsttd-close" data-khsttd-reveal>
		<div class="khsttd-shell">
			<h2 class="khsttd-h2">You are at the bottom of the page</h2>
			<p class="khsttd-lead">
				Which is the interesting part: the control has just lifted clear of the
				footer below. Press it, then press it again when it offers to bring you
				back here.
			</p>

			<div class="khsttd-actions">
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a class="khsttd-btn khsttd-btn--primary" href="<?php echo esc_url( admin_url( 'options-general.php?page=kinetichub-scroll-to-top' ) ); ?>">
						<?php khsttd_icon( 'sliders' ); ?>
						Open the settings
					</a>
				<?php endif; ?>
				<a class="khsttd-btn" href="<?php echo esc_url( 'https://wordpress.org/plugins/kinetichub-scroll-to-top/' ); ?>" target="_blank" rel="noopener noreferrer">
					View on WordPress.org
				</a>
				<a class="khsttd-btn" href="<?php echo esc_url( 'https://getkinetichub.com/kinetic-scroll-to-top/' ); ?>" target="_blank" rel="noopener noreferrer">
					Documentation
				</a>
			</div>
		</div>
	</section>

	<footer class="khsttd-foot">
		<div class="khsttd-shell">
			<p class="khsttd-foot__line">
				KineticHub Scroll to Top &mdash; a free WordPress plugin.
			</p>
			<p class="khsttd-foot__small">
				This is a real WordPress site running the real plugin. The page around
				the control is a demo written by the plugin's Playground blueprint; the
				control itself is not.
			</p>
		</div>
	</footer>

</main>

<?php wp_footer(); ?>
</body>
</html>
