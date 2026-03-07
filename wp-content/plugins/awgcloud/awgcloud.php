<?php
/**
 * Plugin Name: AWG Cloud for WordPress and WooCommerce
 * Description: Whatsapp OTP and Notifications for WordPress.
 * Version: 1.0.0
 * Author: arrocy
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-awgcloud-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-awgcloud-events.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-awgcloud-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-awgcloud-otp.php';

new AwgCloud_Admin();
new AwgCloud_Events();
new AwgCloud_OTP();
