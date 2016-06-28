<?php

class CHIEF_SFC_Admin {

	/**
	 * Register events to create admin pages.
	 */
	static public function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings_fields' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_pages' ) );
	}

	/**
	 * Register admin pages.
	 */
	static public function register_admin_pages() {

		// placeholder top page
		add_menu_page(
			'',
			'Salesforce',
			'manage_options',
			'chief-sfc-integrations',
			'',
			'dashicons-feedback'
		);

		// actual main page
		add_submenu_page(
			'chief-sfc-integrations',
			'Integrations | Salesforce Form Capture',
			'Integrations',
			'manage_options',
			'chief-sfc-integrations',
			array( __CLASS__, 'load_main' )
		);

		// misc settings page
		add_submenu_page(
			'chief-sfc-integrations',
			'Settings | Salesforce Form Capture',
			'Settings',
			'manage_options',
			'chief-sfc-settings',
			array( __CLASS__, 'load_settings' )
		);

	}

	/**
	 * Set up the main page and load the view.
	 */
	static public function load_main() {

	}

	/**
	 * Register settings with the WP settings API.
	 */
	static public function register_settings() {
		register_setting( 'chief_sfc_settings_group', 'chief_sfc_settings', 'sanitize_settings' );
	}

	/**
	 * Register settings fields with the WP settings API.
	 */
	static public function register_settings_fields() {
		add_settings_section(
			'chief_sfc_settings_section',
			'', // no section title
			'', // no section intro html
			'chief-sfc-settings'
		);
		add_settings_field(
			'chief_sfc_salesforce_client_id',
			'Client ID',
			array( __CLASS__, 'load_field' ),
			'chief-sfc-settings',
			'chief_sfc_settings_section',
			array(
				'name' => 'client_id',
				'type' => 'text'
			)
		);
		add_settings_field(
			'chief_sfc_salesforce_client_secret',
			'Client Secret',
			array( __CLASS__, 'load_field' ),
			'chief-sfc-settings',
			'chief_sfc_settings_section',
			array(
				'name' => 'client_secret',
				'type' => 'text'
			)
		);
		add_settings_field(
			'chief_sfc_salesforce_client_status',
			'Status',
			array( __CLASS__, 'load_field' ),
			'chief-sfc-settings',
			'chief_sfc_settings_section',
			array(
				'name' => 'client_status',
				'type' => 'status'
			)
		);
	}

	/**
	 * Generic function to generate a settings field's HTML. Set up the data, then
	 * load the view.
	 *
	 * @param  [type] $args [description]
	 * @return [type]       [description]
	 */
	static public function load_field( $args ) {
		echo '<pre>';
		print_r( $args );
		echo '</pre>';
	}

	/**
	 * Load the settings page content. Since we registered all the settings previously,
	 * all we need to do is call do_settings_sections().
	 */
	static public function load_settings() {
		do_settings_sections( 'chief-sfc-settings' );
	}

}