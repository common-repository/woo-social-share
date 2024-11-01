<?php
/**
 * Plugin Name: Woo Social Share
 * Plugin URI: http://www.wpfruits.com 
 * Description: The Woocommerce product social share.
 * Version: 1.0.1
 * Author: WPFruits
 * Author URI: http://www.wpfruits.com
 */

/******************
	 GLOBALS
******************/
define('WSS_VERSION', '1.0.0');
define('WSS_PATH', plugin_dir_path(__FILE__));
define('WSS_PLUGIN_URL', plugins_url() . '/woo-social-share');

session_start();
ob_start();

/******************
	 OPTIONS
******************/
require_once WSS_PATH . 'options/woo-social-options.php';
/******************
	 FACEBOOK
******************/
require_once WSS_PATH . 'inc/facebook.php';
WooSocialShare::wss_construct();