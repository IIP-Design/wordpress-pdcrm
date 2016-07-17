<?php
/**
 * This class does what the plugin promises to do: intercept form submissions of the
 * supported plugins and push them to Salesforce.
 */
class CHIEF_SFC_Capture {

	/**
	 * Hook into the form-submission actions of each supported plugin.
	 */
	static public function add_actions() {
		add_action( 'frm_after_create_entry', array( __CLASS__, 'capture_frm' ), 30, 2 );
		add_action( 'wpcf7_mail_sent', array( __CLASS__, 'capture_cf7' ) );
		add_action( 'gform_after_submission', array( __CLASS__, 'capture_grv' ), 10, 2 );
	}

	/**
	 * Get the current Formidable submission's form object.
	 */
	static public function capture_frm( $entry_id, $form_id ) {
		$form = new CHIEF_SFC_Form( $form_id, 'frm' );
		self::send_to_salesforce( $form );
	}

	/**
	 * Get the current Contact Form 7 submission's form object.
	 */
	static public function capture_cf7( $contact_form ) {
		$form = new CHIEF_SFC_Form( $contact_form->id(), 'cf7' );
		self::send_to_salesforce( $form );
	}

	/**
	 * Get the current Gravity Form submission's form object.
	 */
	static public function capture_grv( $entry, $form ) {
		$form = new CHIEF_SFC_Form( $form->id, 'grv' );
		self::send_to_salesforce( $form );
	}

	/**
	 * Given a form object, grab the object and field values, sanitize, and create a new record
	 * in Salesforce.
	 */
	static public function send_to_salesforce( $form ) {

		if ( !$form->is_enabled() )
			return;

		// get synced fields
		$object = $form->values['object'];
		$fields = $form->values['fields'];

		// compile field data to send
		$data = array();
		foreach( $fields as $sf_field => $wp_field ) {

			// if no matched field was selected for the Salesforce field, move on
			if ( !$wp_field )
				continue;

			// formidable stores its field values in item_meta
			if ( $form->source === 'frm' ) {

				$value = isset( $_POST['item_meta'][$wp_field] ) ? sanitize_text_field( $_POST['item_meta'][$wp_field] ) : '';

			} else {

				// @todo test
				$value = isset( $_POST[$wp_field] ) ? sanitize_text_field( $_POST[$wp_field] ) : '';

			}


			$data[$sf_field] = $value;

		}


		CHIEF_SFC_Remote::post( 'sobjects/' . $object, $data );
	}

}