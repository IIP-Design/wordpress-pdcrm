<?php
/**
 * The Form Capture object model.
 *
 * Each instance links a WordPress form to a Salesforce object by matching fields between
 * the form and the object.
 */
class CHIEF_SFC_Form {

	public $form_id;
	public $source;
	public $name;
	public $fields;

	/**
	 * Constructor. Set up properties and normalize form data.
	 */
	public function __construct( $form_id = 0, $source = '' ) {

		// must-have info
		$this->form_id = (int) $form_id;
		$this->source  = sanitize_key( $source );

		// normalize form data from supported plugins
		$form = $this->get_form();
		$this->name   = $form['name'];
		$this->fields = $form['fields'];

		// get existing values
		$this->values = $this->get_values();

	}

	/**
	 * Get normalized form data from supported plugins.
	 */
	private function get_form() {
		$form = false;
		switch( $this->source ) {
			case 'frm' :
				if ( is_callable( array( 'FrmForm', 'getOne' ) ) )
					$form = FrmForm::getOne( $this->form_id );
				break;
			case 'cf7' :
				if ( is_callable( array( 'WPCF7_ContactForm', 'get_instance' ) ) )
					$form = WPCF7_ContactForm::get_instance( $this->form_id );
				break;
			case 'grv' :
				if ( is_callable( array( 'GFFormsModel', 'get_form_meta' ) ) )
					$form = GFFormsModel::get_form_meta( $this->form_id );
				break;
		}
		if ( is_callable( array( $this, 'normalize_' . $this->source . '_form' ) ) )
			$form = call_user_func( array( $this, 'normalize_' . $this->source . '_form' ), $form );

		return $form;
	}

	/**
	 * Return the form capture's unique key. This is how it's stored in the settings array.
	 * Instead of giving each one a new ID to keep track of, we just use the ID_SOURCE pair.
	 */
	public function get_unique_key() {
		return $this->form_id . '_' . $this->source;
	}

	/**
	 * Get fields and pertinent info from a Formidable form.
	 */
	private function normalize_frm_form( $form ) {
		$fields = array();
		$frm_fields = FrmField::get_all_for_form( $this->form_id );

		foreach( $frm_fields as $field ) {
			$fields[] = array(
				'name'  => $field->id, // formidable fields get stored in item_meta[fieldid] format. we just grab field id.
				'label' => $field->name ? $field->name : '(no label)'
			);
		}

		$new_args = array(
			'name'   => sanitize_text_field( $form->name ),
			'fields' => $fields
		);
		return $new_args;
	}

	/**
	 * Get fields and pertinent info from a Contact Form 7 form.
	 */
	private function normalize_cf7_form( $form ) {
		$fields = array();

		if ( is_callable( array( 'WPCF7_ShortcodeManager', 'get_instance' ) ) ) {
			$manager = WPCF7_ShortcodeManager::get_instance();
			$scanned_fields = $manager->scan_shortcode( $form->prop( 'form' ) );
			foreach( $scanned_fields as $field ) {
				$field = wp_parse_args( $field, array(
					'name' => '',
					'type' => ''
				) );
				if ( $field['type'] !== 'submit' )
					$fields[] = array(
						'name'  => $field['name'],
						'label' => $field['name']
					);
			}
		}

		$new_args = array(
			'name'   => sanitize_text_field( $form->title() ),
			'fields' => $fields
		);
		return $new_args;
	}

	/**
	 * Get fields and pertinent info from a Gravity Forms form.
	 */
	private function normalize_grv_form( $form ) {
		$fields = array();

		$form = wp_parse_args( $form, array(
			'title'  => '',
			'fields' => array()
		) );

		foreach( $form['fields'] as $field ) {

			// support for Gravity Form's multi-part name field
			$nameFormat = isset( $field->nameFormat ) ? $field->nameFormat : '';
			if ( $field->type === 'name' && $nameFormat !== 'simple' ) {
				foreach( $field->inputs as $name_part ) {
					$fields[] = array(
						'name'  => $name_part['id'],
						'label' => $name_part['label']
					);
				}

			// normal fields
			} else {
				$fields[] = array(
					'name'  => $field->id,
					'label' => $field->label
				);
			}
		}

		$new_args = array(
			'name'   => $form['title'],
			'fields' => $fields
		);
		return $new_args;
	}

	/**
	 * Get the full name of supported form plugins.
	 */
	public function get_source_label() {
		switch( $this->source ) {
			case 'frm' : return 'Formidable';     break;
			case 'cf7' : return 'Contact Form 7'; break;
			case 'grv' : return 'Gravity Forms';  break;
		}
		return '';
	}

	/**
	 * Get values for this form from wp_options.
	 */
	public function get_values() {
		$values = array();

		$all_forms = get_option( 'chief_sfc_captures', array() );
		$key = $this->get_unique_key();

		// nothing saved for this form yet
		if ( !isset( $all_forms[$key] ) )
			$all_forms[$key] = array();

		// normalize values
		$values = wp_parse_args( $all_forms[$key], array(
			'object' => '',
			'fields' => array()
		) );

		return $values;
	}

	/**
	 * Get whether or not the current form is actively syncing with Salesforce.
	 * True for enabled, false for disabled.
	 */
	public function is_enabled() {
		$values = array_filter( $this->values ); // do any values exist
		return (bool) $values;
	}

	/**
	 * Get the human-readable status of the form capture.
	 */
	public function get_status_label() {
		ob_start();
		if ( $this->is_enabled() ) {
			$object = isset( $this->values['object'] ) ? $this->values['object'] : '';
			?><span class="enabled">
				Saving to Salesforce
				<?php if ( $object ) { ?>
					(as <?php echo $object; ?>)
				<?php } ?>
			</span><?php
		} else {
			?><span class="disabled">Not saving to Salesforce</span><?php
		}
		return ob_get_clean();
	}

	/**
	 * Get supported Salesforce objects.
	 */
	public function get_objects() {
		return apply_filters( 'chief_sfc_objects', array(
			'Contact',
			'Lead'
		) );
	}

	/**
	 * Get Salesforce object fields. Once we grab them once they are cached onto
	 * the current site to avoid an API call. The cache can be refreshed on any
	 * form edit screen.
	 */
	public function get_object_fields( $object = '' ) {
		// debug: delete_option( 'chief_sfc_object_fields_' . $object );
		$fields = get_option( 'chief_sfc_object_fields_' . sanitize_key( $object ), array() );
		if ( empty( $fields ) ) {
			$fields = $this->get_remote_object_fields( $object );
			update_option( 'chief_sfc_object_fields_' . sanitize_key( $object ), $fields );
		}
		return $fields;
	}

	/**
	 * Use the Salesforce API to grab all the fields associated with the given object.
	 */
	public function get_remote_object_fields( $object = '' ) {
		if ( !in_array( $object, $this->get_objects() ) )
			return array();

		$object = sanitize_text_field( $object );

		$response = CHIEF_SFC_Remote::get( 'sobjects/' . $object . '/describe' );

		if ( is_wp_error( $response ) )
			return array();

		if ( !is_object( $response ) )
			return array();

		if ( !isset( $response->fields ) )
			return array();

		$fields = array();
		foreach ( $response->fields as $fieldobj ) {
			if( $fieldobj->updateable && $fieldobj->createable && !$fieldobj->defaultedOnCreate ) {

				$required = false;
				if ( !$fieldobj->nillable )
					$required = true;

				$fields[$fieldobj->name] = array(
					'label'    => $fieldobj->label,
					'required' => $required
				);

			}
		}

		return $fields;
	}

	/**
	 * Delete the cache of the current object's fields.
	 */
	public function clear_object_cache( $object ) {
		$object = sanitize_key( $object );
		delete_option( 'chief_sfc_object_fields_' . $object );
	}

	/**
	 * Save form capture data.
	 */
	public function save( $object, $fields ) {
		$option = get_option( 'chief_sfc_captures', array() );
		$key    = $this->get_unique_key();
		$option[$key] = array(
			'object' => $object,
			'fields' => $fields
		);
		update_option( 'chief_sfc_captures', $option );
	}

	/**
	 * Disable a form capture by deleting its data.
	 */
	public function disable() {
		$option = get_option( 'chief_sfc_captures', array() );
		$key    = $this->get_unique_key();

		unset( $option[$key] );

		update_option( 'chief_sfc_captures', $option );
	}

}