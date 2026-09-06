<?php
/**
 * Plugin Name:       KineticHub Scroll to Top
 * Description:       A lightweight smart scroll companion for WordPress. Scroll to top, reading progress, and smart visibility - without the bloat.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            KineticHub
 * Author URI:        https://getkinetichub.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kinetichub-scroll-to-top
 * Domain Path:       /languages
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KHSTT_VERSION', '1.0.0' );
define( 'KHSTT_FILE', __FILE__ );
define( 'KHSTT_PATH', plugin_dir_path( __FILE__ ) );
define( 'KHSTT_URL', plugin_dir_url( __FILE__ ) );
define( 'KHSTT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Where the Documentation link in the settings header points.
 *
 * Declared here, once, so the destination is changed in exactly one place
 * rather than hunted for in a view. It is the only outbound link the plugin
 * renders anywhere in wp-admin.
 */
define( 'KHSTT_DOC_URL', 'https://getkinetichub.com/kinetic-scroll-to-top/' );

require_once KHSTT_PATH . 'includes/class-khstt-settings.php';
require_once KHSTT_PATH . 'includes/class-khstt-compat.php';
require_once KHSTT_PATH . 'includes/class-khstt-icons.php';
require_once KHSTT_PATH . 'includes/class-khstt-powers.php';
require_once KHSTT_PATH . 'includes/class-khstt-styles.php';
require_once KHSTT_PATH . 'includes/class-khstt-plugin.php';

add_action( 'plugins_loaded', array( 'KHSTT_Plugin', 'instance' ) );
