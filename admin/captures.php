<?php
/**
 * The Salesforce > Form Captures section.
 */
class CHIEF_SFC_Captures {

	public $context;
	public $form_screen;
	public $list_table;
	public $authorized;

	/**
	 * Register events to create admin pages.
	 */
	public function add_actions() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'load-toplevel_page_chief-sfc-captures', array( $this, 'set_context' ) );
		add_action( 'wp_ajax_chief_sfc_object', array( $this, 'ajax_object' ) );
	}

	/**
	 * Register admin pages.
	 */
	public function register_page() {
		add_menu_page(
			'Salesforce Form Captures',
			'Form Captures',
			'manage_options',
			'chief-sfc-captures',
			array( $this, 'view_page' ),
			'dashicons-feedback'
		);
	}

	/**
	 * Run before the page headers are sent. Set the context (list page or edit form screen),
	 * and checks for any save/disable attempts.
	 */
	public function set_context() {

		// add styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

		// check authorization
		$response = CHIEF_SFC_Remote::test();
		if ( is_wp_error( $response ) || !is_object( $response ) )
			$this->authorized = false;
		else
			$this->authorized = true;

		if ( !$this->authorized )
			return;

		// determine context
		$form   = isset( $_GET['form'] ) ? (int) $_GET['form'] : false;
		$source = isset( $_GET['source'] ) ? sanitize_key( $_GET['source'] ) : false;

		if ( $form && $source ) {

			$this->context = 'form';
			$this->form_screen = new CHIEF_SFC_Edit_Form( $form, $source );

			// check for actions
			$action = isset( $_REQUEST['chief_sfc_action'] ) ? sanitize_key( $_REQUEST['chief_sfc_action'] ) : false;
			if ( $action === 'disable' ) {
				$this->form_screen->disable();
			} else if ( $action === 'save' ) {
				$this->form_screen->save();
			} else if ( $action === 'objcache' ) {
				$this->form_screen->clear_object_cache();
			}

			$this->form_screen->add_actions();

		} else {
			$this->context = 'list';
			$this->list_table = new CHIEF_SFC_List_Table();
		}

	}

	/**
	 * Enqueue styles.
	 */
	public function enqueue() {
		wp_enqueue_style( 'chief-sfc-style', CHIEF_SFC_URL . 'admin/css/style.css' );
	}

	/**
	 * Set up the main page and load the view.
	 */
	public function view_page() {
		if ( $this->authorized ) {
			if ( $this->context === 'form' ) {
				$this->form_screen->view_page();
			} elseif( $this->context === 'list' ) {
				include CHIEF_SFC_PATH . 'admin/partials/list-page.php';
			}
		} else {
			include CHIEF_SFC_PATH . 'admin/partials/unauthorized.php';
		}
	}

	/**
	 * Callback for an ajax object request (i.e. the user selected Contact or Lead on the form
	 * screen). Instantiate CHIEF_SFC_Form and echo the correct form content in response.
	 */
	public function ajax_object() {
		$value  = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : '';
		$form   = isset( $_POST['form'] ) ? (int) $_POST['form'] : false;
		$source = isset( $_POST['source'] ) ? sanitize_key( $_POST['source'] ) : false;
		$form = new CHIEF_SFC_Edit_Form( $form, $source );
		$form->view_fields( $value );
		exit();
	}

}