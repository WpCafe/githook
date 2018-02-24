<?php
/**
 * The GitHook plugin
 *
 * This is a simple plugin to automatically sync plugins and themes
 * using GitHub API.
 *
 * @link              https://anspress.io
 * @since             1.0.0
 * @package           GitHook
 *
 * @wordpress-plugin
 * Plugin Name:       GitHook
 * Description:       Plugin to auto sync with github repo.
 * Version:           1.0.0
 * Author:            Rahul Aryan
 * Author URI:        https://anspress.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       githook
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Load plugin class .
 */
function load_githook() {
	// Return if not GitHook action.
	if ( ! isset( $_GET['githook_action'] ) ) {
		return;
	}

	$options = get_option( 'githook_settings', [] );

	/**
	 * Filter allows changing default GitHook secret key.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	$secret = apply_filters( 'githook_secret', $options['webhook_secret'] );

	// Define GitHook secret so that it can be used after this point.
	define( 'GITHOOK_SECRET', $secret );

	// Check secret key.
	if ( empty( $_GET['secret'] ) || $_GET['secret'] !== $secret ) {
		wp_die( esc_attr__( 'Trying to cheat?', 'githook' ), 401 );
	}

	require_once plugin_dir_path( __FILE__ ) . '/class-githook.php';

	// Get the instance.
	GitHook::get_instance();

	die( esc_attr__( 'End of the life!', 'githook' ) );
}
add_action( 'template_redirect', 'load_githook' );

/**
 * Register GitHook settings page.
 *
 * @return void
 */
function githook_admin() {
	// Require admin settings.
	require_once plugin_dir_path( __FILE__ ) . '/admin/class-admin.php';

	add_options_page( __( 'GitHook settings page', 'githook' ), __( 'GitHook', 'githook' ), 'manage_options', 'githook', [ 'GitHook\Admin', 'page' ] );
}
add_action( 'admin_menu', 'githook_admin' );

/**
 * Save GitHook settings
 *
 * @return void
 */
function githook_settings() {
	// Require admin settings.
	require_once plugin_dir_path( __FILE__ ) . '/admin/class-admin.php';
	GitHook\Admin::get_instance()->save_settings();
	die();
}
add_action( 'admin_action_githook_settings', 'githook_settings' );
