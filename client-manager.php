<?php 
/*
Plugin Name:  Client Manager
Description:  Manage clients and track hours via the capability of The Events Calendar
Version:	  1.0.8
Author:		  Gregg Hogan
Author URI:   https://mynameisgregg.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  rbc
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CM_PLUGIN_DIR', dirname(__FILE__));
if(!defined('CM_PLUGIN_URL')) {
    define('CM_PLUGIN_URL',plugins_url() . '/client-manager');
}

add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
    $plugin_data = get_file_data(__FILE__, [
        'Version' => 'Version',
        'TextDomain' => 'cm-plugin'
    ], 'plugin');
    
    $plugin_version = $plugin_data['Version'];
    wp_enqueue_style( 'cm-plugin', CM_PLUGIN_URL . '/css/client-manager.css',array(), $plugin_version);
    wp_enqueue_script( 'cm-plugin', CM_PLUGIN_URL . '/js/client-manager.js', array('jquery'),$plugin_version,true );
    wp_localize_script( 'cm-plugin', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'plugins_loaded', 'cm_required_files' );
function cm_required_files() {
    //customizing functions for WP
	require_once( CM_PLUGIN_DIR . '/includes/core.php' );
    require_once( CM_PLUGIN_DIR . '/includes/ajax.php' );
}