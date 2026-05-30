<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link  https://weal.cloud
 * @since 1.0.0
 *
 * @package    Weal_Profile
 * @subpackage Weal_Profile/includes
 */

	use WealProfile\Admin\Admin_Settings;
	use WealProfile\Includes\Comment_Votes\Comment_Votes;
	use WealProfile\Includes\Manager\Settings_Manager;
	use WealProfile\Includes\Ratings\Weal_Profile_Rating;
	use WealProfile\Includes\Routes;

/**
 * The core plugin class.
 *
 * This is used to define admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Weal_Profile
 * @subpackage Weal_Profile/includes
 * @author     leouix <nsht22sola@gmail.com>
 */
class Weal_Profile {



	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Weal_Profile_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $version The current version of the plugin.
	 */
	protected $version;
	/**
	 * Admin settings.
	 *
	 * @var array
	 */
	private $admin_settings;
	/**
	 * Settings manager.
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( defined( 'WEAL_PROFILE_VERSION' ) ) {
			$this->version = WEAL_PROFILE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'weal-profile';

		$this->load_dependencies();

		$this->settings_manager = new Settings_Manager();
		$this->admin_settings   = $this->settings_manager->get_settings();

		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_update_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Weal_Profile_Loader. Orchestrates the hooks of the plugin.
	 * - Weal_Profile_Admin. Defines all hooks for the admin area.
	 * - Weal_Profile_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/weal-profile-module-singleton-interface.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/class-weal-profile-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		include_once WEAL_PROFILE_PLUGIN_DIR . 'admin/class-weal-profile-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		include_once WEAL_PROFILE_PLUGIN_DIR . 'public/class-weal-profile-public.php';

		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/manager/class-settings-manager.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/manager/class-public-page-manager.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/class-routes.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/class-weal-profile-avatar.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'admin/class-admin-settings.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'public/class-info-tab-manager.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/comment-votes/class-comment-votes.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/comment-votes/class-comments-service.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/comment-votes/class-likes-vote-service.php';
		include_once WEAL_PROFILE_PLUGIN_DIR . 'includes/ratings/class-weal-profile-rating.php';

		$this->loader = Weal_Profile_Loader::get_instance();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Weal_Profile_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'localize_admin_script_data' );

		$this->loader->add_action( 'admin_menu', $this, 'add_menu_page_weal_profile' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__, 1 ) . '/weal-profile.php' ), $this, 'my_plugin_settings' );
	}


	/**
	 * Add plugin settings link.
	 *
	 * @param array $settings Plugin settings.
	 */
	public function my_plugin_settings( $settings ) {
		$settings[] = '<a href="' . get_admin_url( null, 'admin.php?page=weal-profile-admin' ) . '">' . esc_html__( 'Settings', 'weal-profile' ) . '</a>';
		return $settings;
	}

	/**
	 * Add menu page.
	 */
	public function add_menu_page_weal_profile() {
		$admin_settings = new Admin_Settings();
		add_menu_page(
			__( 'Weal Profile', 'weal-profile' ),
			__( 'Weal Profile', 'weal-profile' ),
			'manage_options',
			'weal-profile-admin',
			array( $admin_settings, 'get_my_account_settings_page' ),
			'dashicons-admin-users'
		);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {

		$plugin_public = new Weal_Profile_Public( $this->get_plugin_name(), $this->get_version() );

		if ( $this->is_public_plugin_page() ) {
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_class_user_data' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
			$this->loader->add_action( 'wp_enqueue_scripts', $this, 'localize_script_data' );
			$this->loader->add_action( 'init', $this, 'my_custom_add_user_id_to_query_vars' );
		}

		$routes_class = Routes::instance( $this->admin_settings );
		$this->loader->add_action( 'rest_api_init', $routes_class, 'route_reg' );

		Comment_Votes::instance();
		Weal_Profile_Rating::instance();

		$profile_avatar_service = new Weal_Profile_Avatar();
		$this->loader->add_action( 'template_include', $this, 'show_plugin_content' );
		$this->loader->add_action( 'init', $this, 'handle_avatar_actions' );
		$this->loader->add_action( 'delete_user', $this, 'cleanup_user_avatar' );
		$this->loader->add_filter( 'get_avatar', $profile_avatar_service, 'filter_get_avatar', 10, 5 );
		$this->loader->add_filter( 'get_comment_author_url', $profile_avatar_service, 'filter_comment_author_url', 75, 2 );
	}

	/**
	 * Check if current page is public plugin page.
	 */
	public function is_public_plugin_page() {
		global $wp;
		$current_url = home_url( add_query_arg( null, null ) );
		$path        = trim( wp_parse_url( $current_url, PHP_URL_PATH ), '/' );
		$page_url    = $this->get_public_page_url();

		return ! empty( $page_url ) && $page_url === $path;
	}

	/**
	 * Encode user ID into a compact URL-safe token (14-16 chars).
	 *
	 * Format: 4 bytes user_id (big-endian) + 6 bytes HMAC-SHA256.
	 *
	 * @param int $user_id User ID.
	 * @return string URL-safe token.
	 */
	public static function encode_user_token( $user_id ) {
		$uid_bin = pack( 'N', (int) $user_id );
		$hmac    = substr( hash_hmac( 'sha256', $uid_bin, AUTH_KEY, true ), 0, 6 );
		return rtrim( strtr( base64_encode( $uid_bin . $hmac ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decode a compact URL-safe token and extract user ID.
	 *
	 * @param string $token Encoded token.
	 * @return int User ID or 0 on failure.
	 */
	private function decode_user_token( $token ) {
		$decoded = base64_decode( strtr( $token, '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( strlen( $decoded ) !== 10 ) {
			return 0;
		}
		$uid      = (int) unpack( 'N', substr( $decoded, 0, 4 ) )[1];
		$hmac     = substr( $decoded, 4, 6 );
		$expected = substr( hash_hmac( 'sha256', pack( 'N', $uid ), AUTH_KEY, true ), 0, 6 );
		if ( ! hash_equals( $hmac, $expected ) ) {
			return 0;
		}
		return $uid > 0 ? $uid : 0;
	}

	/**
	 * Extract profile user ID from URL query param.
	 *
	 * @return int 0 if own profile, positive int if viewing another user.
	 */
	private function get_profile_user_id_from_url() {
		if ( ! isset( $_GET['u'] ) || ! is_string( $_GET['u'] ) || '' === $_GET['u'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 0;
		}
		return $this->decode_user_token( sanitize_text_field( wp_unslash( $_GET['u'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get public page URL.
	 */
	public function get_public_page_url() {
		return $this->admin_settings['user_page_url'];
	}

	/**
	 * Check if current page is admin page URL.
	 */
	public function is_current_admin_page_url() {
		$screen = get_current_screen();
		return $screen && 'toplevel_page_weal-profile-admin' === $screen->id;
	}

	/**
	 * Show plugin content.
	 *
	 * @param string $template Template path.
	 */
	public function show_plugin_content( $template ) {
		if ( $this->is_public_plugin_page() ) {
			if ( ! is_user_logged_in() ) {
				auth_redirect();
			}

			$profile_user_id = $this->get_profile_user_id_from_url();
			$had_u_param     = $profile_user_id > 0;

			if ( $profile_user_id > 0 ) {
				$user = get_user_by( 'ID', $profile_user_id );
				if ( ! $user ) {
					global $wp_query;
					$wp_query->set_404();
					status_header( 404 );
					return get_404_template();
				}
			} else {
				$profile_user_id = get_current_user_id();
			}

			$is_own_profile = get_current_user_id() === (int) $profile_user_id;

			if ( $had_u_param && $is_own_profile ) {
				wp_safe_redirect( remove_query_arg( 'u' ) );
				exit;
			}

			global $weal_profile_user_id;
			$weal_profile_user_id = $profile_user_id;

			$template_path = $is_own_profile
				? WEAL_PROFILE_PLUGIN_DIR . 'public/partials/weal-profile-public-display.php'
				: WEAL_PROFILE_PLUGIN_DIR . 'public/partials/other-user-profile.php';

			load_template( $template_path, false );
			return null;
		}
		return $template;
	}

	/**
	 * Handle avatar upload and removal actions.
	 */
	public function handle_avatar_actions() {
		if ( ! isset( $_POST['weal_profile_avatar_action'] ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', get_current_user_id() ) ) {
			return;
		}

		$profile_user_id = $this->get_profile_user_id_from_url();
		if ( $profile_user_id > 0 && get_current_user_id() !== $profile_user_id ) {
			return;
		}

		if ( ! isset( $_POST['weal_profile_avatar_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['weal_profile_avatar_nonce'] ) ), 'weal_profile_avatar_action' ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['weal_profile_avatar_action'] ) );

		$redirect_url = remove_query_arg( array( 'avatar_updated', 'avatar_removed' ) );

		if ( 'upload' === $action ) {
			Weal_Profile_Avatar::handle_upload();
		} elseif ( 'remove' === $action ) {
			$user_id = get_current_user_id();
			Weal_Profile_Avatar::remove_avatar( $user_id );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Clean up user avatar on user deletion.
	 *
	 * @param int $user_id User ID.
	 */
	public function cleanup_user_avatar( $user_id ) {
		Weal_Profile_Avatar::cleanup_on_user_delete( $user_id );
	}
	/**
	 * Add user ID to query vars.
	 */
	public function my_custom_add_user_id_to_query_vars() {
		global $wp_query;
		$user_id = $this->get_profile_user_id_from_url();
		$wp_query->set( 'weal_profile_current_user_id', $user_id > 0 ? $user_id : get_current_user_id() );
	}

	/**
	 * Localize script data.
	 */
	public function localize_script_data() {
		$profile_user_id = $this->get_profile_user_id_from_url();
		if ( ! $profile_user_id ) {
			$profile_user_id = get_current_user_id();
		}

		wp_localize_script(
			$this->plugin_name,
			'wealProfilePageData',
			array(
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'root'            => esc_url_raw( rest_url() ),
				'profile_user_id' => $profile_user_id,
				'is_own_profile'  => get_current_user_id() === (int) $profile_user_id,
			)
		);
	}

	/**
	 * Localize admin script data.
	 */
	public function localize_admin_script_data() {
		if ( ! $this->is_current_admin_page_url() ) {
			return;
		}

		wp_localize_script(
			$this->plugin_name,
			'wealProfileAdminData',
			array(
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'root'  => esc_url_raw( rest_url() ),
			)
		);
	}

	/**
	 * Register hooks for plugin updates.
	 */
	private function define_update_hooks() {
		$this->loader->add_action( 'plugins_loaded', $this, 'maybe_update_database' );
	}

	/**
	 * Check and run database updates if needed.
	 */
	public function maybe_update_database() {
		$current_db_version = get_option( 'weal_profile_db_version', '' );

		if ( WEAL_PROFILE_DB_VERSION !== $current_db_version ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			if ( ! class_exists( '\WealProfile\Includes\Comment_Votes\Comment_Votes' ) ) {
				require_once WEAL_PROFILE_PLUGIN_DIR . 'includes/comment-votes/class-comment-votes.php';
			}

			\WealProfile\Includes\Comment_Votes\Comment_Votes::create_table();

			update_option( 'weal_profile_db_version', WEAL_PROFILE_DB_VERSION );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  1.0.0
	 * @return string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Weal_Profile_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
