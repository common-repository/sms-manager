<?php

namespace SMSManager\Handlers;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Notifications class.
 * This class is used to sending SMS notifications with Twilio.
 *
 * @since 1.0.0
 * @package SMSManager
 */
class Notifications {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register woocommerce order action to send SMS notification.
		add_filter( 'woocommerce_order_actions', array( __CLASS__, 'add_sms_order_actions' ) );

		// Handle woocommerce order action to send SMS notification.
		add_action( 'woocommerce_order_action_smsm_send_order_sms', array( __CLASS__, 'send_order_sms' ) );

		// Add action to send order SMS notification when order status is completed.
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'send_notification' ), PHP_INT_MAX );
	}

	/**
	 * Register woocommerce order action to send SMS.
	 *
	 * @param array $actions The order actions.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function add_sms_order_actions( $actions ) {
		$actions['smsm_send_order_sms'] = __( 'Send order SMS to customer', 'sms-manager' );

		return $actions;
	}

	/**
	 * Send SMS notification when order action is triggered.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function send_order_sms( $order ) {

		// Return & display admin notice if the SMS settings are not enabled.
		if ( 'yes' !== smsm_get_settings( 'sms_is_enabled', 'no' ) ) {
			sms_manager()->flash_notice( __( 'SMS notification is not enabled. Please configure the SMS settings to send the SMS notifications.', 'sms-manager' ), 'error' );
			return;
		}

		// Send SMS notification.
		self::send_notification( $order );
	}

	/**
	 * Handle woocommerce order action to send SMS.
	 *
	 * @param \WC_Order|int $order The order object or the order ID.
	 *
	 * @since 1.0.0
	 */
	public static function send_notification( $order ) {

		// Return if the SMS settings are not enabled.
		if ( 'yes' !== smsm_get_settings( 'sms_is_enabled', 'no' ) ) {
			return;
		}

		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order );
		}

		// Get the customer phone number.
		$customer_phone = $order->get_billing_phone();

		// Get the customer country.
		$customer_country = $order->get_billing_country();

		// Get the SMS content.
		$content = smsm_get_settings( 'text_message', __( 'Your order#{order_number} is {order_status}. Thank you for shopping with us.', 'sms-manager' ) );

		// If keys, customer phone number, customer country or content is empty, return.
		if ( empty( $customer_phone ) || empty( $customer_country ) || empty( $content ) ) {
			return;
		}

		// Format the phone number.
		$to = self::format_phone_number( $customer_phone, $customer_country );

		// Get the order total.
		$total = $order->get_total();

		// Get the order status.
		$status = $order->get_status();

		// Get the order number.
		$order_number = $order->get_order_number();

		// Get the order date.
		$date = $order->get_date_created()->date( 'Y-m-d H:i:s' );

		// Replacements for the available content placeholders.
		$replacements = array(
			'{order_number}' => $order_number,
			'{order_status}' => $status,
			'{total_amount}' => $total,
			'{order_date}'   => $date,
		);

		// Replace the content with the replacements.
		$message = wp_kses_post( str_replace( array_keys( $replacements ), array_values( $replacements ), $content ) );

		// Send the SMS.
		$result = self::send_sms( $to, $message );

		// Based on the result add a note to the order.
		if ( is_wp_error( $result ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: error message */
					__( 'Failed to send SMS notification. Error: %s', 'sms-manager' ),
					esc_html( $result->get_error_message() )
				)
			);
		} else {
			$order->add_order_note(
				sprintf(
					/* translators: 1: customer phone number */
					__( 'SMS notification sent successfully. Customer phone number: %s', 'sms-manager' ),
					esc_html( $to )
				)
			);
		}
	}

	/**
	 * Send SMS notification.
	 *
	 * @param string $to The phone number.
	 * @param string $message The message.
	 *
	 * @since 1.0.0
	 * @return bool|\WP_Error
	 */
	public static function send_sms( $to, $message ) {
		// Get Twilio SID, auth token and phone number.
		$twilio_sid    = smsm_get_settings( 'twilio_sid' );
		$twilio_token  = smsm_get_settings( 'twilio_token' );
		$twilio_number = smsm_get_settings( 'twilio_phone_number' );

		// If any of the required fields are empty return.
		if ( empty( $twilio_sid ) || empty( $twilio_token ) || empty( $twilio_number ) ) {
			return new \WP_Error( 'twilio_error', __( 'Twilio settings are not configured properly.', 'sms-manager' ) );
		}

		// Twilio API URL.
		$url = 'https://api.twilio.com/2010-04-01/Accounts/' . $twilio_sid . '/Messages.json';

		// Body for the POST request.
		$body = array(
			'From' => $twilio_number,
			'To'   => $to,
			'Body' => $message,
		);

		// Authentication.
		$auth = base64_encode( $twilio_sid . ':' . $twilio_token ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		// Twilio API arguments.
		$args = array(
			'body'    => $body,
			'headers' => array(
				'Authorization' => 'Basic ' . $auth,
			),
		);

		// Send the POST request.
		$response = wp_remote_post( $url, $args );
		// If there is an error return the error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		// Get the response code.
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 201 !== $response_code ) {
			return new \WP_Error(
				'twilio-error',
				sprintf( /* translators: 1: response code */
					__( 'Failed to send SMS notification. Twilio API returned an error with response code: %s', 'sms-manager' ),
					esc_html( $response_code )
				)
			);
		}

		return true;
	}

	/**
	 * Format phone number.
	 *
	 * @param string $phone_number Phone number.
	 * @param string $country Country code.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function format_phone_number( $phone_number, $country ) {
		// If phone number starts with +, return the phone number.
		if ( 0 === strpos( $phone_number, '+' ) ) {
			return $phone_number;
		}

		// Get the country code.
		$country_code = self::get_country_calling_code( $country );

		// If country code is empty, return the phone number.
		if ( empty( $country_code ) ) {
			return $phone_number;
		}

		// Return the formatted phone number.
		return '+' . $country_code . $phone_number;
	}

	/**
	 * Get country calling code.
	 *
	 * @param string $country Country code.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_country_calling_code( $country ) {
		$country = strtoupper( $country );
		$code    = array(
			'AC' => '247',
			'AD' => '376',
			'AE' => '971',
			'AF' => '93',
			'AG' => '1268',
			'AI' => '1264',
			'AL' => '355',
			'AM' => '374',
			'AO' => '244',
			'AQ' => '672',
			'AR' => '54',
			'AS' => '1684',
			'AT' => '43',
			'AU' => '61',
			'AW' => '297',
			'AX' => '358',
			'AZ' => '994',
			'BA' => '387',
			'BB' => '1246',
			'BD' => '880',
			'BE' => '32',
			'BF' => '226',
			'BG' => '359',
			'BH' => '973',
			'BI' => '257',
			'BJ' => '229',
			'BL' => '590',
			'BM' => '1441',
			'BN' => '673',
			'BO' => '591',
			'BQ' => '599',
			'BR' => '55',
			'BS' => '1242',
			'BT' => '975',
			'BW' => '267',
			'BY' => '375',
			'BZ' => '501',
			'CA' => '1',
			'CC' => '61',
			'CD' => '243',
			'CF' => '236',
			'CG' => '242',
			'CH' => '41',
			'CI' => '225',
			'CK' => '682',
			'CL' => '56',
			'CM' => '237',
			'CN' => '86',
			'CO' => '57',
			'CR' => '506',
			'CU' => '53',
			'CV' => '238',
			'CW' => '599',
			'CX' => '61',
			'CY' => '357',
			'CZ' => '420',
			'DE' => '49',
			'DJ' => '253',
			'DK' => '45',
			'DM' => '1767',
			'DO' => '1809',
			'DZ' => '213',
			'EC' => '593',
			'EE' => '372',
			'EG' => '20',
			'EH' => '212',
			'ER' => '291',
			'ES' => '34',
			'ET' => '251',
			'EU' => '388',
			'FI' => '358',
			'FJ' => '679',
			'FK' => '500',
			'FM' => '691',
			'FO' => '298',
			'FR' => '33',
			'GA' => '241',
			'GB' => '44',
			'GD' => '1473',
			'GE' => '995',
			'GF' => '594',
			'GG' => '44',
			'GH' => '233',
			'GI' => '350',
			'GL' => '299',
			'GM' => '220',
			'GN' => '224',
			'GP' => '590',
			'GQ' => '240',
			'GR' => '30',
			'GT' => '502',
			'GU' => '1671',
			'GW' => '245',
			'GY' => '592',
			'HK' => '852',
			'HN' => '504',
			'HR' => '385',
			'HT' => '509',
			'HU' => '36',
			'ID' => '62',
			'IE' => '353',
			'IL' => '972',
			'IM' => '44',
			'IN' => '91',
			'IO' => '246',
			'IQ' => '964',
			'IR' => '98',
			'IS' => '354',
			'IT' => '39',
			'JE' => '44',
			'JM' => '1',
			'JO' => '962',
			'JP' => '81',
			'KE' => '254',
			'KG' => '996',
			'KH' => '855',
			'KI' => '686',
			'KM' => '269',
			'KN' => '1869',
			'KP' => '850',
			'KR' => '82',
			'KW' => '965',
			'KY' => '1345',
			'KZ' => '7',
			'LA' => '856',
			'LB' => '961',
			'LC' => '1758',
			'LI' => '423',
			'LK' => '94',
			'LR' => '231',
			'LS' => '266',
			'LT' => '370',
			'LU' => '352',
			'LV' => '371',
			'LY' => '218',
			'MA' => '212',
			'MC' => '377',
			'MD' => '373',
			'ME' => '382',
			'MF' => '590',
			'MG' => '261',
			'MH' => '692',
			'MK' => '389',
			'ML' => '223',
			'MM' => '95',
			'MN' => '976',
			'MO' => '853',
			'MP' => '1670',
			'MQ' => '596',
			'MR' => '222',
			'MS' => '1664',
			'MT' => '356',
			'MU' => '230',
			'MV' => '960',
			'MW' => '265',
			'MX' => '52',
			'MY' => '60',
			'MZ' => '258',
			'NA' => '264',
			'NC' => '687',
			'NE' => '227',
			'NF' => '672',
			'NG' => '234',
			'NI' => '505',
			'NL' => '31',
			'NO' => '47',
			'NP' => '977',
			'NR' => '674',
			'NU' => '683',
			'NZ' => '64',
			'OM' => '968',
			'PA' => '507',
			'PE' => '51',
			'PF' => '689',
			'PG' => '675',
			'PH' => '63',
			'PK' => '92',
			'PL' => '48',
			'PM' => '508',
			'PR' => '1787',
			'PS' => '970',
			'PT' => '351',
			'PW' => '680',
			'PY' => '595',
			'QA' => '974',
			'QN' => '374',
			'QS' => '252',
			'QY' => '90',
			'RE' => '262',
			'RO' => '40',
			'RS' => '381',
			'RU' => '7',
			'RW' => '250',
			'SA' => '966',
			'SB' => '677',
			'SC' => '248',
			'SD' => '249',
			'SE' => '46',
			'SG' => '65',
			'SH' => '290',
			'SI' => '386',
			'SJ' => '47',
			'SK' => '421',
			'SL' => '232',
			'SM' => '378',
			'SN' => '221',
			'SO' => '252',
			'SR' => '597',
			'SS' => '211',
			'ST' => '239',
			'SV' => '503',
			'SX' => '1721',
			'SY' => '963',
			'SZ' => '268',
			'TA' => '290',
			'TC' => '1649',
			'TD' => '235',
			'TG' => '228',
			'TH' => '66',
			'TJ' => '992',
			'TK' => '690',
			'TL' => '670',
			'TM' => '993',
			'TN' => '216',
			'TO' => '676',
			'TR' => '90',
			'TT' => '1868',
			'TV' => '688',
			'TW' => '886',
			'TZ' => '255',
			'UA' => '380',
			'UG' => '256',
			'UK' => '44',
			'US' => '1',
			'UY' => '598',
			'UZ' => '998',
			'VA' => '39',
			'VC' => '1784',
			'VE' => '58',
			'VG' => '1284',
			'VI' => '1340',
			'VN' => '84',
			'VU' => '678',
			'WF' => '681',
			'WS' => '685',
			'XC' => '991',
			'XD' => '888',
			'XG' => '881',
			'XL' => '883',
			'XN' => '857',
			'XP' => '878',
			'XR' => '979',
			'XS' => '808',
			'XT' => '800',
			'XV' => '882',
			'YE' => '967',
			'YT' => '262',
			'ZA' => '27',
			'ZM' => '260',
			'ZW' => '263',
		);

		return isset( $code[ $country ] ) ? $code[ $country ] : '';
	}
}
