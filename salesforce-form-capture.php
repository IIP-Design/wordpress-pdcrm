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

// model
require_once( CHIEF_SFC_PATH . 'includes/remote.php' );

// authorization settings
require_once( CHIEF_SFC_PATH . 'authorization/settings-abstract.php' );
require_once( CHIEF_SFC_PATH . 'authorization/settings.php' );
require_once( CHIEF_SFC_PATH . 'authorization/authorization.php' );

// form captures - main ui
require_once( CHIEF_SFC_PATH . 'captures/list-table.php' );
require_once( CHIEF_SFC_PATH . 'captures/form-screen.php' );
require_once( CHIEF_SFC_PATH . 'captures/captures.php' );

function chief_sfc_boot() {

	CHIEF_SFC_Authorization::add_actions();

	// register top-level admin menu
	add_action( 'admin_menu', function() {
		add_menu_page(
			'',
			'Salesforce',
			'manage_options',
			'chief-sfc-captures',
			'',
			'dashicons-feedback'
		);
	} );

	// add captures ui
	$captures = new CHIEF_SFC_Captures();
	$captures->add_actions();

	// add settings
	$settings = new CHIEF_SFC_Settings();
	$settings->add_actions();

}
add_action( 'plugins_loaded', 'chief_sfc_boot' );