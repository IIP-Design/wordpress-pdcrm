<?php
/**
 * This class does what the plugin promises to do: intercept form submissions of the
 * supported plugins and push them to Salesforce.
 */
class CHIEF_SFC_Capture {

	/**
	 * Hook into the supported forms and attempt to capture them and send to Salesforce.
	 */
	static public function init() {
		add_action( 'frm_after_create_entry', array( __CLASS__, 'capture_frm' ), 30, 2 );
		add_action( 'wpcf7_mail_sent',        array( __CLASS__, 'capture_cf7' ) );
		add_action( 'gform_after_submission', array( __CLASS__, 'capture_grv' ), 10, 2 );
	}

	/**
	 * Get the current Formidable submission's form object.
	 */
	static public function capture_frm( $entry_id, $form_id ) {
		$form = new CHIEF_SFC_Form( $form_id, 'frm' );

		// get values in a flat array and sanitize
		$item_meta = isset( $_POST['item_meta'] ) ? (array) $_POST['item_meta'] : array();
		$values = array_map( 'sanitize_text_field', $item_meta );

		self::send_to_salesforce( $form, $values );
	}

	/**
	 * Get the current Contact Form 7 submission's form object.
	 */
	static public function capture_cf7( $contact_form ) {
		$form = new CHIEF_SFC_Form( $contact_form->id(), 'cf7' );

		// get values in a flat array. cf7 already sanitized.
		$submission = WPCF7_Submission::get_instance();
		$values = $submission->get_posted_data();

		self::send_to_salesforce( $form, $values );
	}

	/**
	 * Get the current Gravity Form submission's form object.
	 *
	 * The params here are sent as arrays. The Gravity Forms documentation calls them objects; they're not.
	 */
	static public function capture_grv( $entry, $form ) {
		$form = new CHIEF_SFC_Form( $form['id'], 'grv' );

		// get values in a flat array. gravity forms already sanitized them.
		// there's extra info in $entry too but it doesn't matter
		$values = $entry;

		self::send_to_salesforce( $form, $values );
	}

	/**
	 * Create a new record in Salesforce.
	 *
	 * @param  object $form  A CHIEF_SFC_Form object.
	 * @param  array $values A flat associative array of pre-sanitized form submission values.
	 */
	static public function send_to_salesforce( $form, $values = array() ) {

		if ( !$form->is_enabled() )
			return;

		// get synced fields
		$object = $form->values['object'];
		$fields = $form->values['fields'];

		// get "required" fields
		$all_sf_fields = $form->get_object_fields( $object );
		$required_fields = array();
		foreach( $all_sf_fields as $sf_name => $sf_field ) {
			$sf_field = wp_parse_args( $sf_field, array(
				'required' => false
			) );
			if ( $sf_field['required'] )
				$required_fields[] = $sf_name;
		}

		// compile field data to send
		$data = array();
		foreach( $fields as $sf_field => $wp_field ) {
			$value = '';

			// match with the field from $values
			if ( $wp_field )
				$value = isset( $values[$wp_field] ) ? $values[$wp_field] : '';

			// if we don't have anything for a required field, add a dummy value
			if ( !$value && in_array( $sf_field, $required_fields ) )
				$value = '(no information submitted)';

			// only include fields with values
			if ( $value )
				$data[$sf_field] = $value;

		}

		$result = CHIEF_SFC_Remote::post( "sobjects/{$object}", $data );

		// if ( !empty( $result ) ) {
		// 	file_put_contents( 'php://stderr', print_r( json_encode( $result ) . PHP_EOL, TRUE) );
		// }

		// debug:
		// echo '<pre>';
		// print_r( $data );
		// print_r( $result );
		// echo '</pre>';
		// exit;

	}

}