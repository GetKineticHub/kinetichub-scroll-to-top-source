<?php
/**
 * Runs when the plugin is deleted through the WordPress admin.
 *
 * Removes only the two options this plugin owns: the settings, and the
 * compatibility status the frontend reports back. Deactivating the plugin
 * leaves both untouched; they are removed here and nowhere else.
 *
 * @package KineticHub_Scroll_To_Top
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'khstt_settings' );
delete_option( 'khstt_compat_status' );

// Multisite: the options are per site, so clear them on every site in the network.
if ( is_multisite() ) {
	$khstt_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $khstt_site_ids as $khstt_site_id ) {
		switch_to_blog( $khstt_site_id );
		delete_option( 'khstt_settings' );
		delete_option( 'khstt_compat_status' );
		restore_current_blog();
	}
}
