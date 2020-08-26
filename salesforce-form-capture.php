<?php
/*
Plugin Name: Salesforce Form Capture
Description: Save WordPress form submissions to Salesforce. Compatible with Formidable Forms, Gravity Forms, and Contact Form 7.
Version:     2.0
Author:      CHIEF
Author URI:  http://www.agencychief.com
*/

define( 'CHIEF_SFC_PATH', plugin_dir_path( __FILE__ ) );
define( 'CHIEF_SFC_URL', plugin_dir_url( __FILE__ ) );

require_once( CHIEF_SFC_PATH . 'includes/form.php' );
require_once( CHIEF_SFC_PATH . 'includes/remote.php' );

require_once( CHIEF_SFC_PATH . 'public/capture.php' );

require_once( CHIEF_SFC_PATH . 'admin/captures.php' );
require_once( CHIEF_SFC_PATH . 'admin/list-table.php' );
require_once( CHIEF_SFC_PATH . 'admin/edit-form.php' );

require_once( CHIEF_SFC_PATH . 'admin/authorization.php' );

// introduce the error log portions
require_once( CHIEF_SFC_PATH . 'admin/log.php' );
require_once( CHIEF_SFC_PATH . 'admin/error-email.php' );
require_once( CHIEF_SFC_PATH . 'includes/export.php' );
CHIEF_SFC_Export::init();

function chief_sfc_boot() {

	// add hooks to capture forms
	CHIEF_SFC_Capture::init();

	// add form capture UI
	$captures = new CHIEF_SFC_Captures();
	$captures->add_actions();

	// add authorization settings
	CHIEF_SFC_Authorization::init();

	new CHIEF_SFC_Error_Email();

}

add_action( 'plugins_loaded', 'chief_sfc_boot' );

// begin adding db for log functionality

function form_capture_activate() {

	global $wpdb;

    $table_name = $wpdb->prefix . 'form_capture_data';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            fc_id INT NOT NULL AUTO_INCREMENT, -- every table needs one
            fc_form_id INT DEFAULT NULL, -- which form is used by ID
            fc_submission_id INT, -- unique submission ID (all submission are unique)
            fc_request_data LONGTEXT DEFAULT NULL, -- form content
            fc_response LONGTEXT DEFAULT NULL, -- response status code
            fc_submission_date DATETIME DEFAULT CURRENT_TIMESTAMP, -- submission date
            fc_failure TINYINT(1), -- did it work or nah?
            PRIMARY KEY  (fc_id),
            KEY fc_id (fc_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$result = dbDelta( $sql );

	// error handling
	// die ('<pre>' . print_r($result,1) );

}

// adding in demo data for log db (use for testing)

function log_demo_data() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'form_capture_data';

	$fc_form_id = '';
    $fc_submission_id = '';
    // below mocks the actual form output in an array
    $fc_request_data =  '{"method":"POST","body":"{\"LastName\":\"Bogard\",\"FirstName\":\"Tester\",\"Company\":\"(no information submitted)\",\"Country\":\"Zambia\",\"Email\":\"testit@teststuff.com\",\"LeadSource\":\"YALI\",\"Youth_Network_Add_Me__c\":\"Yes\"}","headers":{"content-type":"application\/json","Authorization":"Bearer 00D30000000mqyv!AQMAQCcomNX73OkdKJ.ty_bW6BuElkVpmjotVwhL4aXYE.WMCO.LVz2cnbAcoshF0stkWVhCfBrT1gUcNUBnEnY9Zxn3jF95"},"sslverify":true,"timeout":5}';
    // below mocks the actual form output in an array
	$fc_response = '{"headers":{},"body":"{\"id\":\"00Qt0000006ndlsEAA\",\"success\":true,\"errors\":[]}","response":{"code":201,"message":"Created"},"cookies":[{"name":"BrowserId","value":"unAI-YikEeqtEJdJXH6nTw","expires":1619541167,"path":"\/","domain":"salesforce.com","host_only":false}],"filename":null,"http_response":{"data":null,"headers":null,"status":null}}';
	$fc_submission_date = '2020-08-13 21:14:05';
	$fc_failure = '1';

	$wpdb->insert(
		$table_name,
		array(
			'fc_form_id' => $fc_form_id,
			'fc_submission_id' => $fc_submission_id,
			'fc_request_data' => $fc_request_data,
			'fc_response' => $fc_response,
			'fc_submission_date' => $fc_submission_date,
			'fc_failure' => $fc_failure
		)
	);
}

// drop table when unistalling plugin
register_uninstall_hook( __FILE__, 'form_capture_uninstall' );

// adding table when activating plugin
register_activation_hook( __FILE__, 'form_capture_activate' );

// add in demo data (use for testing)
register_activation_hook( __FILE__, 'log_demo_data' );

// drop table when deactivating (use for testing, comment out for prod)
// register_deactivation_hook( __FILE__, 'form_capture_uninstall' );

// And here goes the uninstallation function:
function form_capture_uninstall(){
	global $wpdb;
    $table_name = $wpdb->prefix . 'form_capture_data';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
}

