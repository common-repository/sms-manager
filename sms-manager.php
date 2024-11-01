<?php
/**
 * Plugin Name:       SMS Manager
 * Plugin URI:        https://beautifulplugins.com/sms-manager/
 * Description:       SMS Manager for WooCommerce allows you to send SMS notifications to your customers.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            BeautifulPlugins
 * Author URI:        https://beautifulplugins.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sms-manager
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * @package SMSManager
 *
 * SMS Manager for WooCommerce is a free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * SMS Manager for WooCommerce is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SMS Manager for WooCommerce. If not, see https://www.gnu.org/licenses/gpl-2.0.html
 */

use SMSManager\Plugin;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Autoload classes.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Get the plugin instance.
 *
 * @since 1.0.0
 * @return Plugin plugin initialize class.
 */
function sms_manager() {
	return Plugin::create( __FILE__, '1.0.0' );
}

// Initialize the plugin.
sms_manager();
