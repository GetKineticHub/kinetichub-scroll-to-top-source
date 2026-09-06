<?php
/**
 * Plugin bootstrap: singleton entry point.
 *
 * Loads the settings schema and the compatibility status service
 * unconditionally, then hands off to the admin or frontend controller depending
 * on the current request context. Admin and frontend classes are required
 * lazily so a frontend request never parses the admin UI code and vice versa.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the plugin and hands off to the admin or frontend controller.
 */
final class KHSTT_Plugin {

	/**
	 * The single instance.
	 *
	 * @var KHSTT_Plugin|null
	 */
	private static $instance = null;

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
	 * Returns the single plugin instance, creating it on first call.
	 * Hooked to plugins_loaded from the main plugin file.
	 *
	 * @return KHSTT_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wires the settings registration, then the context-appropriate controller.
	 */
	private function __construct() {
		$this->settings = new KHSTT_Settings();
		$this->compat   = new KHSTT_Compat();

		// Settings registration must run in both contexts: options.php handles
		// the save request and needs the registered sanitize callback, and the
		// option default is read on the frontend too.
		add_action( 'admin_init', array( $this->settings, 'register' ) );

		if ( is_admin() ) {
			// admin-ajax.php is an admin request, so the compatibility write
			// endpoint is registered here rather than on the frontend, where
			// nothing would ever dispatch it.
			$this->compat->register();

			require_once KHSTT_PATH . 'includes/class-khstt-ui.php';
			require_once KHSTT_PATH . 'includes/class-khstt-admin.php';
			new KHSTT_Admin( $this->settings, $this->compat );
		} else {
			require_once KHSTT_PATH . 'includes/class-khstt-frontend.php';
			new KHSTT_Frontend( $this->settings, $this->compat );
		}
	}

	/** Prevent cloning the singleton. */
	private function __clone() {}
}
