<?php

class CHIEF_SFC_Edit_Form {

	public $form;
	public $url;
	public $list_url;

	/**
	 * Constructor.
	 *
	 * Get a new CHIEF_SFC_Form object and set the admin URLs for easy access.
	 */
	public function __construct( $form_id, $source ) {

		$this->form = new CHIEF_SFC_Form( $form_id, $source );

		$this->list_url = admin_url( 'admin.php?page=chief-sfc-captures' );

		$this->url = esc_url_raw( add_query_arg( array(
			'form'   => $form_id,
			'source' => $source
		), $this->list_url ) );

	}

	/**
	 * Add actions. This runs during the load-{slug} hook, right before HTTP headers are sent.
	 */
	public function add_actions() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue scripts/styles.
	 */
	public function enqueue() {
		// enqueue in footer
		wp_enqueue_script( 'chief-sfc-scripts', CHIEF_SFC_URL . 'admin/js/scripts.min.js', array(), CHIEF_SFC_VERSION, false );
	}

	/**
	 * Output the page HTML.
	 */
	public function view_page() {
		include( CHIEF_SFC_PATH . 'admin/partials/edit-form-page.php' );
	}

	/**
	 * Output the field-matching portion of the page.
	 */
	public function view_fields( $object ) {
		include( CHIEF_SFC_PATH . 'admin/partials/edit-form-fields.php' );
	}

	/**
	 * Sanitize the form capture data and save to an option.
	 */
	public function save() {

		// don't do a thing unless the nonce passes
		$nonce = isset( $_POST['_chief_sfc_form'] ) ? $_POST['_chief_sfc_form'] : false;
		if ( !$nonce || !wp_verify_nonce( $nonce, 'chief-sfc-form' ) )
			$this->fail_update( 'save' );

		// sanitize
		$sanitized_object = isset( $_POST['object'] ) ? sanitize_text_field( $_POST['object'] ) : '';
		$fields = isset( $_POST['field'] ) ? $_POST['field'] : array();
		$sanitized_fields = array();
		foreach( $fields as $key => $field ) {
			$new_key   = sanitize_text_field( $key );
			$new_field = sanitize_text_field( $field );
			$sanitized_fields[$new_key] = $new_field;
		}

		$this->form->save( $sanitized_object, $sanitized_fields );

		// redirect
		$url = esc_url_raw( add_query_arg( 'updated', 'true', $this->url ) );
		wp_redirect( $url );
		exit;

	}

	/**
	 * Clear the object field cache, then reload the page.
	 */
	public function clear_object_cache() {

		// don't do a thing unless the nonce passes
		$nonce = isset( $_GET['_chief_sfc_objcache'] ) ? $_GET['_chief_sfc_objcache'] : false;
		if ( !$nonce || !wp_verify_nonce( $nonce, 'chief-sfc-objcache' ) )
			$this->fail_update( 'objcache' );

		// get object
		$object = isset( $_GET['chief_sfc_object'] ) ? sanitize_key( $_GET['chief_sfc_object'] ) : $this->form->values['object'];

		// clear
		$this->form->clear_object_cache( $object );

		// redirect
		$url = esc_url_raw( add_query_arg( 'message', 'objcache', $this->url ) );
		wp_redirect( $url );
		exit;

	}

	/**
	 * Generate a Clear Cache url.
	 */
	public function get_clear_cache_url( $object ) {
		return esc_url_raw( add_query_arg( array(
			'chief_sfc_action'    => 'objcache',
			'chief_sfc_object'    => sanitize_key( $object ),
			'_chief_sfc_objcache' => wp_create_nonce( 'chief-sfc-objcache' )
		), $this->url ) );
	}

	/**
	 * Disable the current form.
	 */
	public function disable() {

		// don't do a thing unless the nonce passes
		$nonce = isset( $_GET['_chief_sfc_disable'] ) ? $_GET['_chief_sfc_disable'] : false;
		if ( !$nonce || !wp_verify_nonce( $nonce, 'chief-sfc-disable' ) )
			$this->fail_update( 'disable' );

		// back up for one hour
		$option = get_option( 'chief_sfc_captures', array() );
		$key    = $this->form->get_unique_key();
		set_transient( "chief_sfc_disabled_{$key}", $this->form->values, 60 * 60 );

		$this->form->disable();

		// redirect to success/failure
		$url = esc_url_raw( add_query_arg( array(
			'disabled' => 'true',
			'key'      => $key
		), $this->list_url ) );
		wp_redirect( $url );
		exit;

	}

	/**
	 * Generate a disable url with a nonce included.
	 */
	public function get_disable_url() {
		return esc_url_raw( add_query_arg( array(
			'chief_sfc_action'   => 'disable',
			'_chief_sfc_disable' => wp_create_nonce( 'chief-sfc-disable' )
		), $this->url ) );
	}

	/**
	 * Run when an updated is attempted but it doesn't pass the nonce check. Redirect and provide
	 * an error message.
	 */
	public function fail_update( $context = 'save' ) {
		if ( !in_array( $context, array( 'save', 'disable', 'objcache'  ) ) )
			return;

		$url = ( $context === 'disable' ) ? $this->list_url : $this->url;
		$url = esc_url_raw( add_query_arg( 'skipped', $context, $url ) );
		wp_redirect( $url );
		exit;
	}

}