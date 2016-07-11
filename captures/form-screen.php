<?php

class CHIEF_SFC_Form_Screen {

	public $form_id;
	public $source;
	public $form;

	public function __construct( $form_id = 0, $source = '' ) {
		$this->form_id = $form_id;
		$this->source  = $source;
		$this->form    = $this->get_form();
	}

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
		}
		if ( is_callable( array( $this, 'normalize_' . $this->source . '_form' ) ) )
			$form = call_user_func( array( $this, 'normalize_' . $this->source . '_form' ), $form );

		return $form;
	}

	/**
	 * Get fields and pertinent info from a Formidable form.
	 */
	private function normalize_frm_form( $form ) {
		$fields = array();
		$frm_fields = FrmField::get_all_for_form( $this->form_id );

		foreach( $frm_fields as $field ) {
			$fields[] = array(
				'name'  => 'item_meta[' . $field->id . ']',
				'label' => $field->name ? $field->name : '(no label)'
			);
		}

		$form = array(
			'title'  => $form->name,
			'fields' => $fields
		);
		return $form;
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
				$fields[] = array(
					'name'  => $field['name'],
					'label' => $field['name']
				);
			}
		}

		$form = array(
			'title'  => $form->title(),
			'fields' => $fields
		);
		return $form;
	}

	public function display() {
		$response = CHIEF_SFC_Remote::get( 'sobjects/Contact/describe' );

		$fields = array();
		if( is_object( $response ) ) {
			foreach ( $response->fields as $fieldobj ) {
				if( $fieldobj->updateable ) {
					$fields[] = array(
						'label' => $fieldobj->label,
						'name' => $fieldobj->name
					);
				}
			}
		 }

		echo '<pre>';
		print_r( $fields );
		echo '</pre>';

		?>
		<?php
	}

}