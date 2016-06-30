<?php
/**
 * Add the top-level Salesforce menu item. It doesn't have its own page, it just acts
 * as a shell for the two pages underneath it.
 */
class CHIEF_SFC_Admin {

	/**
	 * Register events to create admin pages.
	 */
	public function add_actions() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	/**
	 * Register admin pages.
	 */
	public function register_page() {

		// placeholder top page
		add_menu_page(
			'',
			'Salesforce',
			'manage_options',
			'chief-sfc-captures',
			'',
			'dashicons-feedback'
		);

	}

}