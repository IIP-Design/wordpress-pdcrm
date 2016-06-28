<?php
/*
Plugin Name: Salesforce Form Capture
Description: Save WordPress form submissions to Salesforce. Compatible with Formidable Forms, Gravity Forms, and Contact Form 7.
Version:     1.0
Author:      CHIEF
Author URI:  http://www.agencychief.com
*/

define( 'CHIEF_SFC_PATH', plugin_dir_path( __FILE__ ) );
define( 'CHIEF_SFC_URL', plugin_dir_url( __FILE__ ) );

require_once( CHIEF_SFC_PATH . '/admin/controller.php' );
require_once( CHIEF_SFC_PATH . '/includes/controller.php' );

CHIEF_SFC_Admin::init();

$settings = new CHIEF_SFC_Settings();
$settings->add_actions();


// CHIEF_SFC_Logic::init();