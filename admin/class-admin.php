<?php
/**
 * The GitHook admin class.
 *
 * @package GitHook
 * @author  Rahul Aryan <rah12@live.com>
 * @since   1.0.0
 */

namespace GitHook;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The GitHook admin class.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * The instance.
	 *
	 * @var null|\GitHook\Admin
	 */
	private static $instance;

	/**
	 * Return the instance of this class.
	 *
	 * @return void
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 */
	private function __construct() {
	}

	/**
	 * Output page.
	 */
	public function page() {
		include dirname( __FILE__ ) . '/page.php';
	}

	/**
	 * Save GitHook settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		check_admin_referer( 'githook-settings' );

		// Die if user don't have enough permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			die( esc_attr__( 'Trying to cheat?', 'githook' ) );
		}

		$options = [];

		// Update GitHook access token
		if ( false === strpos( $_POST['access_token'], '**' ) ) {
			update_option( 'githook_access_token', sanitize_text_field( $_POST['access_token'] ) );
		}

		$options['webhook_secret'] = sanitize_text_field( $_POST['webhook_secret'] );

		if ( ! empty( $_POST['repository'] ) ) {
			$options['repos'] = [];
			$repos            = $_POST['repository'];

			unset( $repos['#'] );

			foreach ( $_POST['repository'] as $repo ) {
				$full_name = ! empty( $repo['full_name'] ) ? sanitize_text_field( $repo['full_name'] ) : false;
				$type      = $repo['is_plugin'] ? 'plugin': 'theme';

				if ( ! empty( $full_name ) && ! empty( $repo['dir'] ) ) {
					$options[ 'repos' ][ $full_name ] = array(
						"type" => $type,
						'dir'  => sanitize_text_field( $repo['dir'] ),
					);
				}
			}

			// Update settings.
			update_option( 'githook_settings', $options );

			// Redirect to settings page.
			wp_redirect( admin_url( 'options-general.php?page=githook' ) );
			exit;
		}

	}

}
