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

require_once( CHIEF_SFC_PATH . '/admin/class-settings-abstract.php' );
require_once( CHIEF_SFC_PATH . '/admin/class-admin.php' );
require_once( CHIEF_SFC_PATH . '/admin/class-settings.php' );

// require_once( CHIEF_SFC_PATH . '/includes/controller.php' );

function chief_salesforce_form_capture() {

	// add admin container
	$admin = new CHIEF_SFC_Admin();
	$admin->add_actions();

	// add integrations ui

	// add settings
	$settings = new CHIEF_SFC_Settings();
	$settings->add_actions();

}
add_action( 'plugins_loaded', 'chief_salesforce_form_capture' );