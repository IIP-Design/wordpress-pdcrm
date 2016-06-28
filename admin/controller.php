<?php

class CHIEF_SFC_Admin {

	/**
	 * Register events to create admin pages.
	 */
	static public function init() {
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

	}

	/**
	 * Set up the main page and load the view.
	 */
	static public function load_main() {

	}

}



/**
 * Create the Salesforce > Settings page.
 */
class CHIEF_SFC_Settings {

	public $page_slug;
	public $page_name;
	public $settings_name;
	public $settings_group;
	public $settings_section;

	/**
	 * @var array The settings fields in name/value pairs.
	 */
	public $fields;

	/**
	 * Grab the settings and set up variables.
	 */
	public function __construct() {

		$this->page_slug = 'chief-sfc-settings';
		$this->page_name = 'Salesforce Form Capture Settings';
		$this->settings_name = 'chief_sfc_settings';

		// get fields
		$whitelist = array(
			'client_id'     => '',
			'client_secret' => ''
		);
		$fields = wp_parse_args( get_option( $this->settings_name ), $whitelist );

		// only include whitelist in our sanitized array
		$this->fields = array_intersect_key( $fields, $whitelist );

	}

	/**
	 * Register events to get the ball rolling.
	 */
	public function add_actions() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'register_settings_fields' ) );
	}

	/**
	 * Register admin pages.
	 */
	public function register_page() {
		add_submenu_page(
			'chief-sfc-integrations',
			$this->page_name,
			'Settings',
			'manage_options',
			$this->page,
			array( $this, 'load_settings' )
		);
	}

	/**
	 * Register settings with the WP settings API.
	 */
	public function register_settings() {
		register_setting( $this->settings_name, $this->settings_name, array( __CLASS__, 'sanitize_settings' ) );
	}

	/**
	 * Register settings fields with the WP settings API.
	 */
	public function register_settings_fields() {
		add_settings_section(
			$this->settings_name,
			'', // no section title
			'', // no section intro html
			$this->page
		);
		add_settings_field(
			'chief_sfc_salesforce_client_id',
			'Client ID',
			array( __CLASS__, 'load_field' ),
			$this->page,
			$this->settings_name,
			array(
				'name' => 'client_id',
				'type' => 'text'
			)
		);
		add_settings_field(
			'chief_sfc_salesforce_client_secret',
			'Client Secret',
			array( __CLASS__, 'load_field' ),
			$this->page,
			$this->settings_name,
			array(
				'name' => 'client_secret',
				'type' => 'text'
			)
		);
		add_settings_field(
			'chief_sfc_salesforce_client_status',
			'Status',
			array( __CLASS__, 'load_field' ),
			$this->page,
			$this->settings_name,
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
	public function load_field( $args ) {
		$args = wp_parse_args( $args, array(
			'name' => '',
			'type' => ''
		) );
		$name  = sanitize_key( $args['name'] );
		$type  = sanitize_key( $args['type'] );
		$value = isset( $this->fields[$name] ) ? $this->fields[$name] : '';

		include( CHIEF_SFC_PATH . "/admin/views/settings-field-{$type}.html.php" );
	}

	/**
	 * Load the settings page content.
	 */
	public function load_settings() {
		$name    = $this->page_name;
		$group   = $this->settings_name;
		$section = $this->settings_name;
		include( CHIEF_SFC_PATH . '/admin/views/settings.html.php' );
	}

	/**
	 * Sanitize the settings upon save.
	 */
	public function sanitize_settings( $values ) {

		if ( empty( $values ) )
			return '';

		foreach( $values as $key => $value ) {
			switch( $key ) {
				case 'client_id' :
				case 'client_secret' :
					$values[$key] = sanitize_text_field( $value );
					break;
				default:
					unset( $values[$key] );
					break;
			}
		}

		return $value;

	}

}