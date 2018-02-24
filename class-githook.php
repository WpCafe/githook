<?php
/**
 * The GitHook class.
 *
 * @package GitHook
 * @author Rahul Aryan <rah12@live.com>
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The GitHook class.
 *
 * @since 1.0.0
 */
class GitHook {

	/**
	 * The instance.
	 *
	 * @var null|GitHook
	 */
	private static $instance;

	/**
	 * List of the repos.
	 *
	 * @var array
	 */
	private $repos = [];

	/**
	 * Current repo slug.
	 *
	 * @var string
	 */
	private $current_repo = '';

	/**
	 * The githook upload dir.
	 *
	 * @var string
	 */
	private $githook_dir;

	/**
	 * The payload
	 *
	 * @var array
	 */
	private $payload;

	/**
	 * Return the instance of this class.
	 *
	 * @return void
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance    = new self;

			if ( ! empty( self::$instance->repos ) ) {
				self::$instance->process_webhook();
			}
		}

		return self::$instance;
	}

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		$options = get_option( 'githook_settings', [] );

		if ( ! empty( $options['repos'] ) ) {
			$this->repos = $options['repos'];
		}

		$this->githook_dir = wp_normalize_path( ABSPATH . '/wp-content/uploads/githook/' );
	}

	/**
	 * Optional function to check if HMAC hex digest of the payload matches GitHub's.
	 *
	 * @param string $payload GitHub payload.
	 * @return bool
	 * @throws Exception If signature is missing.
	 * @since 0.0.1
	 */
	public static function validate_sign( $payload ) {
		if ( ! array_key_exists( 'HTTP_X_HUB_SIGNATURE', $_SERVER ) ) {
			wp_die( __( 'Missing X-Hub-Signature header. Did you configure secret token in hook settings?', 'githook' ) );
		}

		return 'sha1=' . hash_hmac( 'sha1', $payload, GITHOOK_SECRET, false ) === $_SERVER['HTTP_X_HUB_SIGNATURE'];
	}

	/**
	 * Process webhook.
	 *
	 * @return void
	 */
	public function process_webhook() {
		$payload = file_get_contents( 'php://input' );

		if ( self::validate_sign( $payload ) ) {
			$event = sanitize_title_with_dashes( $_SERVER['HTTP_X_GITHUB_EVENT'] );

			try {
				// Decode JSON data from Github.
				$this->payload = json_decode( stripslashes( $payload ), true );
			} catch ( Exception $e ) {
				var_dump( $e->getMessage() );
				exit(0);
			}

			// Check for repo.
			if ( ! $this->is_valid_repo() ) {
				http_response_code( 400 );
				print( __( 'Not a valid repository', 'githook' ) );
				exit;
			}

			$method_name = 'event_' . $event;

			// Call event method.
			if ( method_exists( $this, 'event_' . $event ) ) {
				$this->$method_name();
			}
		}
	}

	/**
	 * Download a zipball archive from GitHub api.
	 *
	 * @param string  $repo_fullname Repository owner name.
	 * @param string  $slug          Repository slug.
	 * @param string  $branch        Branch or tag. Default is `master`.
	 * @param integer $timeout       Curl timeout value. Default is 300.
	 * @return string|WP_Error
	 * @since 1.0.0
	 */
	private function download_url( $repo_fullname, $branch = 'master', $timeout = 300 ) {
		global $wp_filesystem;

		// Initialize the file system.
		if ( empty( $wp_filesystem ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();
		}

		$url          = "https://api.github.com/repos/{$repo_fullname}/zipball/{$branch}";
		print_r( $url );
		$url_filename = basename( parse_url( $url, PHP_URL_PATH ) );

		// Create githook dir if not exists.
		if ( ! file_exists( $this->githook_dir ) ) {
			$wp_filesystem->mkdir( $this->githook_dir, FS_CHMOD_DIR );
		}

		$tmpfname = wp_tempnam( $url_filename, $this->githook_dir );

		if ( ! $tmpfname ) {
			return new WP_Error( 'http_no_file', __( 'Could not create Temporary file.', 'hithook' ) );
		}

		$headers = [ 'User-Agent' => 'GitHook' ];

		$access_token = get_option( 'githook_access_token', '' );
		if ( ! empty( $access_token ) ) {
			$headers['Authorization'] = 'token ' . $access_token;
		}

		$response = wp_remote_get( $url, array(
			'headers'  => $headers,
			'timeout'  => $timeout,
			'stream'   => true,
			'filename' => $tmpfname,
		) );

		if ( is_wp_error( $response ) ) {
			unlink( $tmpfname );
			return $response;
		}

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			unlink( $tmpfname );
			return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
		}

		$content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );

		if ( $content_md5 ) {
			$md5_check = verify_file_md5( $tmpfname, $content_md5 );
			if ( is_wp_error( $md5_check ) ) {
				unlink( $tmpfname );
				return $md5_check;
			}
		}

		return $tmpfname;
	}

	/**
	 * Update a theme or plugin from a temporary ZIPball file.
	 *
	 * @param string $slug      Slug of theme or plugin (directory name).
	 * @param string $temp_file Temporary zip file which will be extracted.
	 * @param string $type      Type of package.
	 * @return void
	 * @since 1.0.0
	 */
	private function update( $slug, $temp_file, $type = 'theme' ) {
		global $wp_filesystem;

		// Check if temp file exists.
		if ( empty( $slug ) || is_wp_error( $temp_file ) || ! file_exists( $temp_file ) ) {
			http_response_code( 400 );
			print( esc_attr__( 'Not a valid temporary zip file or plugin or theme dir empty.', 'githook' ) );
			exit;
		}

		$type_dir = 'theme' === $type ? 'themes' : 'plugins';
		$dest     = wp_normalize_path( ABSPATH . '/wp-content/' . $type_dir . '/' . $slug );

		$wp_filesystem->delete( $dest, true );
		$wp_filesystem->mkdir( $dest, FS_CHMOD_DIR );

		$unzip_file = unzip_file( $temp_file, $dest );

		// Check if unzipping done properly.
		if ( is_wp_error( $unzip_file ) ) {
			http_response_code( 400 );
			print( esc_attr__( 'Failed to unzip theme', 'hithook' ) );
			exit;
		}

		$dirs     = array_keys( $wp_filesystem->dirlist( $dest ) );
		$temp_dir = $dest . '/' . $dirs[0];
		$copy     = copy_dir( $temp_dir, $dest );

		if ( ! $wp_filesystem->delete( $temp_dir, true ) ) {
			http_response_code( 400 );
			print_r( "\n\rUnable to delete temporary source directory.\n\r" . $temp_dir );
			exit;
		}

		if ( ! $wp_filesystem->delete( $temp_file ) ) {
			http_response_code( 400 );
			print_r( "\n\rUnable to delete temporary zip file.\n\r" . $temp_file );
			exit;
		}

		print_r( "\n\rSuccessfully updated {$type}.\n\r" );
	}

	/**
	 * Check if payload has a valid repository.
	 *
	 * @return boolean
	 */
	private function is_valid_repo() {
		$defined_repos = array_keys( $this->repos );

		if ( ! $this->get_repo_fullname() ) {
			return false;
		}

		if ( in_array( $this->get_repo_fullname(), $defined_repos, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the full name of repository from current payload.
	 *
	 * @return string|false
	 */
	private function get_repo_fullname() {
		if ( empty( $this->payload['repository'] ) ) {
			return false;
		}

		return $this->payload['repository']['full_name'];
	}

	/**
	 * Check if repo is for a theme.
	 *
	 * @return boolean
	 */
	private function is_theme() {
		$type = $this->repos[ $this->get_repo_fullname() ]['type'];

		if ( 'plugin' !== $type ) {
			return true;
		}

		return false;
	}

	/**
	 * Process GitHub push event.
	 *
	 * Update theme or plugin based on repo type set in options.
	 *
	 * @return void
	 */
	private function event_push() {
		$downloaded = $this->download_url( $this->get_repo_fullname() );
		$dir = $this->repos[ $this->get_repo_fullname() ]['dir'];

		// Update theme.
		if ( $this->is_theme() ) {
			$this->update( $dir, $downloaded );
		} else {
			$this->update( $dir, $downloaded, 'plugin' );
		}
	}
}
