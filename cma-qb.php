<?php 
/*
Plugin Name:  Client Manager Quickbooks Add-On
Description:  This is an add-on to Client Manager to create and summarize invoices from within WordPress
Version:	  1.0.0
Author:		  Gregg Hogan
Author URI:   https://mynameisgregg.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  rbc
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CMA_PLUGIN_DIR', dirname(__FILE__));
if(!defined('CMA_PLUGIN_URL')) {
    define('CMA_PLUGIN_URL',plugins_url() . '/client-manager');
}

add_action( 'plugins_loaded', 'cma_required_files' );
function cma_required_files() {
	require CMA_PLUGIN_DIR .'/vendor/autoload.php';
	require_once( CMA_PLUGIN_DIR . '/includes/core.php' );
}