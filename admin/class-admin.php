<?php

class CHIEF_SFC_Admin {

	/**
	 * Register events to create admin pages.
	 */
	public function add_actions() {
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
	}

	/**
	 * Register admin pages.
	 */
	public function register_admin_pages() {

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
			array( $this, 'load_main' )
		);

	}

	/**
	 * Set up the main page and load the view.
	 */
	public function load_main() {

	}

}