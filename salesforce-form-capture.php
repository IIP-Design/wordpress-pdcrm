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
define( 'CHIEF_SFC_VERSION', '1.0' );

require_once( CHIEF_SFC_PATH . 'includes/form.php' );
require_once( CHIEF_SFC_PATH . 'includes/remote.php' );

require_once( CHIEF_SFC_PATH . 'public/capture.php' );

require_once( CHIEF_SFC_PATH . 'admin/captures.php' );
require_once( CHIEF_SFC_PATH . 'admin/list-table.php' );
require_once( CHIEF_SFC_PATH . 'admin/edit-form.php' );

require_once( CHIEF_SFC_PATH . 'admin/authorization.php' );

function chief_sfc_boot() {

	// add hooks to capture forms
	CHIEF_SFC_Capture::init();

	// add form capture UI
	$captures = new CHIEF_SFC_Captures();
	$captures->add_actions();

	// add authorization settings
	CHIEF_SFC_Authorization::init();


}
add_action( 'plugins_loaded', 'chief_sfc_boot' );