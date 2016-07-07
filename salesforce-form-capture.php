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

// set up top-level dashboard page
require_once( CHIEF_SFC_PATH . '/admin/class-admin.php' );

// helper wrapper for settings api
require_once( CHIEF_SFC_PATH . '/admin/class-settings-abstract.php' );

// authorization page
require_once( CHIEF_SFC_PATH . '/admin/class-settings.php' );

// extra salesforce authorization logic
require_once( CHIEF_SFC_PATH . '/admin/class-authorization.php' );

// main UI page
require_once( CHIEF_SFC_PATH . '/admin/class-captures.php' );


// require_once( CHIEF_SFC_PATH . '/includes/controller.php' );

function chief_salesforce_form_capture() {

	CHIEF_SFC_Authorization::add_actions();

	// add admin container
	$admin = new CHIEF_SFC_Admin();
	$admin->add_actions();

	// add captures ui
	$captures = new CHIEF_SFC_Captures();
	$captures->add_actions();

	// add settings
	$settings = new CHIEF_SFC_Settings();
	$settings->add_actions();

}
add_action( 'plugins_loaded', 'chief_salesforce_form_capture' );


/**
 * Simple go-to function for Salesforce API requests.
 */
/* function chief_sfc_request( $uri = '', $params = array(), $method = 'GET', $attempt_refresh = true ) {

	$auth = get_option( CHIEF_SFC_Authorization::$setting, array() );
	$auth = wp_parse_args( $auth, array(
		'access_token' => '',
		'instance_url' => ''
	) );

	$headers = array(
		'content-type'  => 'application/json',
		'Authorization' => 'Bearer '. $auth['access_token']
	);

	$url = $auth['instance_url'] . '/services/data/v37.0/';

	if ( $uri )
		$url .= $uri;

	if ( ( $method === 'GET' ) && $params ) {
		$url .= urldecode( http_build_query( $params ) );
		$params = false;
	}

	$request = array(
		'method'    => 'GET',
		'body'      => 'sample body',
		'headers'   => $headers,
		'sslverify' => true
	);

	$response = wp_remote_request( $url, $request );

	// if error, try refreshing the token
	if ( is_wp_error( $response ) && $attempt_refresh ) {
		if ( $response[0]->errorCode === 'INVALID_SESSION_ID' ) {
			CHIEF_SFC_Authorization::refresh_token();
			// now try again (but don't keep trying)
			$response = chief_sfc_request( $uri, $params, $method, false );
		}
	}

	return $response;

} */