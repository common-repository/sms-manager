<?php

namespace SMSManager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Plugin class.
 *
 * @since 1.0.0
 * @package SMSManager
 */
class Plugin {
	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var self
	 */
	public static $instance;

	/**
	 * Gets the single instance of the class.
	 * This method is used to create a new instance of the class.
	 *
	 * @param string $file The plugin file path.
	 * @param string $version The plugin version.
	 *
	 * @since 1.0.0
	 * @return static
	 */
	final public static function create( $file, $version = '1.0.0' ) {
		if ( null === self::$instance ) {
			self::$instance = new static( $file, $version );
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param string $file The plugin file path.
	 * @param string $version The plugin version.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $file, $version ) {
		$this->file    = $file;
		$this->version = $version;
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define plugin constants.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function define_constants() {
		if ( ! defined( 'SMSM_VERSION' ) ) {
			define( 'SMSM_VERSION', $this->version );
		}

		if ( ! defined( 'SMSM_FILE' ) ) {
			define( 'SMSM_FILE', $this->file );
		}

		if ( ! defined( 'SMSM_PATH' ) ) {
			define( 'SMSM_PATH', plugin_dir_path( $this->file ) );
		}

		if ( ! defined( 'SMSM_URL' ) ) {
			define( 'SMSM_URL', plugin_dir_url( $this->file ) );
		}

		if ( ! defined( 'SMSM_ASSETS_URL' ) ) {
			define( 'SMSM_ASSETS_URL', SMSM_URL . 'assets/' );
		}
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function includes() {
		require_once __DIR__ . '/functions.php';
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function init_hooks() {
		register_activation_hook( SMSM_FILE, array( $this, 'activate' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_notices', array( $this, 'display_flash_notices' ), 12 );
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
		add_action( 'woocommerce_init', array( $this, 'init' ), 0 );
	}

	/**
	 * Declare compatibility with WooCommerce.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function declare_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SMSM_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', SMSM_FILE, true );
		}
	}

	/**
	 * Plugin activation hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate() {
		// Set plugin version.
		update_option( 'smsm_version', SMSM_VERSION );

		// Set default settings.
		$default_settings = apply_filters(
			'smsm_default_settings',
			array(
				'sms_is_enabled'      => 'no',
				'twilio_sid'          => '',
				'twilio_token'        => '',
				'twilio_phone_number' => '',
				'text_message'        => __( 'Your order#{order_number} is {order_status}. Thank you for shopping with us.', 'sms-manager' ),
			)
		);

		// Update the settings.
		update_option( 'smsm_sms_manager', $default_settings );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'sms-manager', false, dirname( plugin_basename( SMSM_FILE ) ) . '/languages' );
	}

	/**
	 * Add a flash notice.
	 *
	 * @param string  $notice Notice message.
	 * @param string  $type This can be "info", "warning", "error" or "success", "success" as default.
	 * @param boolean $dismissible Whether the notice is-dismissible or not.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function flash_notice( $notice = '', $type = 'success', $dismissible = true ) {
		$notices          = get_option( 'smsm_flash_notices', array() );
		$dismissible_text = ( $dismissible ) ? 'is-dismissible' : '';

		// Add new notice.
		array_push(
			$notices,
			array(
				'notice'      => $notice,
				'type'        => $type,
				'dismissible' => $dismissible_text,
			)
		);

		// Update the notices array.
		update_option( 'smsm_flash_notices', $notices );
	}

	/**
	 * Display flash notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_flash_notices() {
		$notices = get_option( 'smsm_flash_notices', array() );

		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			printf(
				'<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
				esc_attr( $notice['type'] ),
				esc_attr( $notice['dismissible'] ),
				esc_html( $notice['notice'] )
			);
		}

		// Reset options to prevent notices being displayed forever.
		if ( ! empty( $notices ) ) {
			delete_option( 'smsm_flash_notices', array() );
		}
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Load the admin class.
		if ( is_admin() ) {
			new Admin\Admin();
		}

		// Load the notifications class.
		new Handlers\Notifications();

		/**
		 * Fires when the plugin is initialized.
		 *
		 * @param Plugin $this The plugin instance.
		 *
		 * @since 1.0.0
		 */
		do_action( 'smsm_sms_manager_init', $this );
	}
}
