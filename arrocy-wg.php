<?php
/**
 * Plugin Name: Arrocy Whatsapp Gateway
 * Description: Whatsapp OTP and Notifications for WordPress.
 * Version: 0.1.0
 * Author: arrocy
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-arrocywg-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-arrocywg-events.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-arrocywg-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-arrocywg-otp.php';

new ArrocyWG_Admin();
new ArrocyWG_Events();
new ArrocyWG_OTP();
