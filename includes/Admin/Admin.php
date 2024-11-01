<?php

namespace SMSManager\Admin;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Admin class.
 *
 * @since 1.0.0
 * @package SMSManager
 */
class Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Create admin settings page under WordPress settings menu.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page under WordPress settings menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'SMS Manager', 'sms-manager' ),
			__( 'SMS Manager', 'sms-manager' ),
			'manage_options',
			'sms-manager',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			sms_manager()->flash_notice( __( 'You do not have sufficient permissions to access this page.', 'sms-manager' ), 'error' );
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'SMS Manager', 'sms-manager' ); ?></h1>
			<p><?php esc_html_e( 'Configure the SMS settings to enable SMS notifications. Currently, only Twilio is supported.', 'sms-manager' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( 'sms_manager' );
				do_settings_sections( 'sms_manager' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'sms_manager', 'smsm_sms_manager', array( $this, 'sanitize_settings' ) );

		// Add settings section.
		add_settings_section(
			'smsm_general_settings',
			__( 'SMS Settings', 'sms-manager' ),
			array( $this, 'general_settings' ),
			'sms_manager'
		);

		// Add settings field to enable SMS notifications.
		add_settings_field(
			'smsm_sms_is_enabled',
			__( 'Enable SMS Notifications', 'sms-manager' ),
			array( $this, 'sms_is_enabled' ),
			'sms_manager',
			'smsm_general_settings'
		);

		// Twilio SID field.
		add_settings_field(
			'smsm_twilio_sid',
			__( 'Twilio Account SID', 'sms-manager' ),
			array( $this, 'twilio_sid' ),
			'sms_manager',
			'smsm_general_settings'
		);

		// Twilio token field.
		add_settings_field(
			'smsm_twilio_token',
			__( 'Twilio Auth Token', 'sms-manager' ),
			array( $this, 'twilio_token' ),
			'sms_manager',
			'smsm_general_settings'
		);

		// Twilio phone number field.
		add_settings_field(
			'smsm_twilio_phone_number',
			__( 'Twilio Phone Number', 'sms-manager' ),
			array( $this, 'twilio_phone_number' ),
			'sms_manager',
			'smsm_general_settings'
		);

		// Text message field.
		add_settings_field(
			'smsm_text_message',
			__( 'Text Message', 'sms-manager' ),
			array( $this, 'text_message' ),
			'sms_manager',
			'smsm_general_settings'
		);
	}

	/**
	 * Display general settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function general_settings() {
		echo '<p>' . esc_html__( 'Configure the SMS settings to enable SMS notifications.', 'sms-manager' ) . '</p>';
	}

	/**
	 * Display the SMS is enabled field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function sms_is_enabled() {
		$sms_is_enabled = smsm_get_settings( 'sms_is_enabled' );
		?>
		<label for="smsm_sms_manager[sms_is_enabled]">
			<input type="checkbox" name="smsm_sms_manager[sms_is_enabled]" id="smsm_sms_manager[sms_is_enabled]" value="yes" <?php checked( $sms_is_enabled, 'yes' ); ?> />
			<?php esc_html_e( 'Enable to send SMS notifications.', 'sms-manager' ); ?>
		</label>
		<?php
	}

	/**
	 * Display Twilio SID field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function twilio_sid() {
		$twilio_sid = smsm_get_settings( 'twilio_sid' );
		?>
		<input type="text" name="smsm_sms_manager[twilio_sid]" id="smsm_sms_manager[twilio_sid]" value="<?php echo esc_attr( $twilio_sid ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Enter your Twilio Account SID.', 'sms-manager' ); ?></p>
		<?php
	}

	/**
	 * Display Twilio token field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function twilio_token() {
		$twilio_token = smsm_get_settings( 'twilio_token' );
		?>
		<input type="text" name="smsm_sms_manager[twilio_token]" id="smsm_sms_manager[twilio_token]" value="<?php echo esc_attr( $twilio_token ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Enter your Twilio Auth Token.', 'sms-manager' ); ?></p>
		<?php
	}

	/**
	 * Display Twilio phone number field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function twilio_phone_number() {
		$twilio_phone_number = smsm_get_settings( 'twilio_phone_number' );
		?>
		<input type="text" name="smsm_sms_manager[twilio_phone_number]" id="smsm_sms_manager[twilio_phone_number]" value="<?php echo esc_attr( $twilio_phone_number ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Enter your Twilio Phone Number.', 'sms-manager' ); ?></p>
		<?php
	}

	/**
	 * Display text message field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function text_message() {
		$text_message = smsm_get_settings( 'text_message' );

		// Set default text message if empty.
		$default_text = __( 'Your order#{order_number} is {order_status}. Thank you for shopping with us.', 'sms-manager' );
		$text_message = ! empty( $text_message ) ? $text_message : $default_text;
		?>
		<textarea name="smsm_sms_manager[text_message]" id="smsm_sms_manager[text_message]" class="regular-text" rows="4"><?php echo esc_textarea( $text_message ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Enter the text message to be sent. Use the following placeholders: {total_amount}, {order_number}, {order_date}, {order_status}.', 'sms-manager' ); ?></p>
		<?php
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $settings Settings to sanitize.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		$sanitized_settings = array();

		// Sanitize SMS is enabled.
		$sanitized_settings['sms_is_enabled'] = isset( $settings['sms_is_enabled'] ) ? 'yes' : 'no';

		// Sanitize Twilio SID.
		$sanitized_settings['twilio_sid'] = isset( $settings['twilio_sid'] ) ? sanitize_text_field( $settings['twilio_sid'] ) : '';

		// Sanitize Twilio token.
		$sanitized_settings['twilio_token'] = isset( $settings['twilio_token'] ) ? sanitize_text_field( $settings['twilio_token'] ) : '';

		// Sanitize Twilio phone number.
		$sanitized_settings['twilio_phone_number'] = isset( $settings['twilio_phone_number'] ) ? sanitize_text_field( $settings['twilio_phone_number'] ) : '';

		// Sanitize text message.
		$sanitized_settings['text_message'] = isset( $settings['text_message'] ) ? sanitize_textarea_field( $settings['text_message'] ) : '';

		return $sanitized_settings;
	}
}
