<?php

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Get settings option.
 *
 * @param string $option Option name.
 * @param mixed  $default_value Default value.
 *
 * @since 1.0.0
 * @retun mixed|null
 */
function smsm_get_settings( $option, $default_value = null ) {
	$options = get_option( 'smsm_sms_manager', array() );

	return isset( $options[ $option ] ) ? $options[ $option ] : $default_value;
}
