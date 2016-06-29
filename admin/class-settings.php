<?php

class CHIEF_SFC_Settings extends CHIEF_SFC_Settings_Abstract {

	public function __construct() {
		$this->parent     = 'chief-sfc-integrations';
		$this->slug       = 'chief-sfc-settings';
		$this->page_title = 'Salesforce Form Capture Settings';
		$this->menu_title = 'Settings';
		$this->setting    = 'chief_sfc_settings';

		$this->fields = array(
			'chief-sfc-client-id-field' => array(
				'title' => 'Salesforce Client ID',
				'type'  => 'text',
				'args'  => array( 'name' => 'client_id' )
			),
			'chief-sfc-client-secret-field' => array(
				'title' => 'Salesforce Client Secret',
				'type'  => 'text',
				'args'  => array( 'name' => 'client_secret' )
			),
			'status' => array(
				'title' => 'Status',
				'type'  => 'status'
			)
		);

	}

	/**
	 * Build a custom text field.
	 */
	public function view_field_text( $args ) {
		$args = wp_parse_args( $args, array(
			'id'   => '',
			'name' => ''
		) );
		$value = isset( $this->values[$args['name']] ) ? $this->values[$args['name']] : '';
		?>
		<input
			id="<?php echo esc_attr( $args['id'] ); ?>"
			type="text"
			name="<?php echo $this->setting; ?>[<?php echo esc_attr( $args['name'] ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			class="widefat"
		/>
		<?php
	}

	/**
	 * Build a custom status field.
	 */
	public function view_field_status() {
		echo 'This is the status';
	}

	/**
	 * Sanitize our fields. Values are saved only if they match an existing field.
	 */
	public function sanitize_settings( $values ) {
		$sanitized = array();
		foreach( $this->fields as $field ) {
			$field = wp_parse_args( $field, array(
				'type' => '',
				'args' => ''
			) );
			$name = isset( $field['args']['name'] ) ? $field['args']['name'] : '';
			if ( !$name )
				continue;

			// if a value isn't passed which matches this field
			if ( !isset( $values[$name] ) )
				continue;

			switch( $field['type'] ) {
				case 'text' :
					$sanitized[$name] = sanitize_text_field( $values[$name] );
					break;
			}
		}
		return $sanitized;
	}

}