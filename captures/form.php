<?php

class CHIEF_SFC_Form {

	public $form_id;
	public $source;
	public $name;
	public $fields;

	public function __construct( $form_id = 0, $source = '' ) {
		$this->form_id = $form_id;
		$this->source  = $source;

		$form = $this->get_form();
		$this->name   = $form['name'];
		$this->fields = $form['fields'];

		$this->values = $this->get_values();
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
			'name'   => sanitize_text_field( $form->name ),
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
			'name'   => sanitize_text_field( $form->title() ),
			'fields' => $fields
		);
		return $form;
	}

	/**
	 * Get values for this form from wp_options.
	 */
	public function get_values() {
		$values = array();

		$all_forms = get_option( 'chief_sfc_captures', array() );

		// nothing saved yet
		if ( !$all_forms )
			return $values;

		// nothing saved for this form yet
		if ( !isset( $all_forms[$this->form_id . '_' . $this->source] ) )
			return $values;

		// get and normalize values
		$values = wp_parse_args( $all_forms[$this->form_id . '_' . $this->source], array(
			'object' => '',
			'fields' => array()
		) );

		return $values;
	}

	/**
	 * Get pretty name of source.
	 */
	public function source_label() {
		if ( $this->source === 'frm' )
			return 'Formidable';
		if ( $this->source === 'cf7' )
			return 'Contact Form 7';
		if ( $this->source === 'grv' )
			return 'Gravity Forms';
		return '';
	}

	public function display() {
		?>
		<div class="metabox-holder">
			<div id="postbox-container-1" class="postbox-container">
				<div class="meta-box-sortables">
					<div class="postbox">
						<h3 class="hndle ui-sortable-handle">
							<span>
								<?php echo esc_html( $this->name ); ?>
								(<?php echo esc_html( $this->source_label() ); ?>)
							</span>
						</h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th>Left Aligned</th>
									<td>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla at volutpat tellus. Suspendisse accumsan molestie sagittis. Cras id massa vitae leo placerat sodales ac non erat. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Praesent pharetra, nibh sit amet pretium tincidunt, tellus lorem posuere augue, ac lobortis orci lacus ac tellus. Donec vulputate sapien quis tincidunt fringilla. Phasellus viverra dignissim turpis, a hendrerit diam scelerisque nec. Phasellus vulputate, diam vel faucibus malesuada, odio magna maximus eros, et rutrum elit nulla nec nisi. Aliquam fermentum pellentesque nisi, ut venenatis felis rutrum id. Praesent egestas mollis maximus.</td>
								</tr>
								<tr>
									<th>Object</th>
									<td>
										<select name="chief_sfc_object">
											<option>&mdash; Select &mdash;</option>
											<?php foreach( $this->get_objects() as $object ) { ?>
												<option
													value="<?php echo esc_attr( $object ); ?>"
													<?php selected( $this->values['object'], $object ); ?>>
													<?php echo esc_html( $object ); ?>
												</option>
											<?php } ?>
										</select>
										<p class="howto">Select the Salesforce object in which to save this form's submissions.</p>
									</td>
								</tr>
								<?php if ( $this->values['object'] ) {
									$sf_fields = $this->get_object_fields( $this->values['object'] );
									if ( $sf_fields ) {
										foreach( $sf_fields as $field ) {
											$field = wp_parse_args( $field, array(
												'name'  => '',
												'label' => ''
											) ); ?>
											<tr>
												<th><?php echo esc_html( $field['label'] ); ?></th>
												<td>
													<select name="<?php echo esc_attr( $field['name'] ); ?>">
														<option>&mdash; Select &mdash;</option>
														<?php foreach( $this->fields as $name => $label ) { ?>
															<option
																value="<?php echo esc_attr( $name ); ?>"
																<?php // selected(); ?>>
																<?php echo esc_html( $label ); ?>
															</option>
														<?php } ?>
													</select>
												</td>
											</tr>
										<?php }
									}
								} ?>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div><?php

	}

	function get_objects() {
		return apply_filters( 'chief_sfc_objects', array(
			'Contact',
			'Lead'
		) );
	}

	function get_object_fields( $object = '' ) {
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
			if( $fieldobj->updateable ) {
				$fields[] = array(
					'name'  => $fieldobj->name,
					'label' => $fieldobj->label
				);
			}
		}

		return $fields;
	}

}