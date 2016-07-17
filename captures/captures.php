<?php
/**
 * The Salesforce > Form Captures section.
 */
class CHIEF_SFC_Captures {

	public $context;
	public $form_screen;
	public $list_screen;
	public $authorized;

	/**
	 * Register events to create admin pages.
	 */
	public function add_actions() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'load-toplevel_page_chief-sfc-captures', array( $this, 'set_context' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_chief_sfc_object', array( $this, 'ajax_object' ) );
	}

	/**
	 * Enqueue scripts/styles.
	 */
	public function enqueue() {
		wp_enqueue_style( 'chief-sfc-style', CHIEF_SFC_URL . 'css/style.css' );
	}

	/**
	 * Register admin pages.
	 */
	public function register_page() {
		add_submenu_page(
			'chief-sfc-captures',
			'Salesforce Form Captures',
			'Form Captures',
			'manage_options',
			'chief-sfc-captures',
			array( $this, 'view_page' )
		);
	}

	/**
	 * Run before the page headers are sent. Set the context (list page or edit form screen),
	 * and checks for any save/disable attempts.
	 */
	public function set_context() {

		// check authorization
		$response = CHIEF_SFC_Remote::test();
		if ( is_wp_error( $response ) || !is_object( $response ) )
			$this->authorized = false;
		else
			$this->authorized = true;

		if ( !$this->authorized )
			return;

		$form   = isset( $_GET['form'] ) ? (int) $_GET['form'] : false;
		$source = isset( $_GET['source'] ) ? sanitize_key( $_GET['source'] ) : false;

		if ( $form && $source ) {
			$this->context = 'form';
			$this->form_screen = new CHIEF_SFC_Form( $form, $source );

			// check for actions
			$action = isset( $_REQUEST['chief_sfc_action'] ) ? sanitize_key( $_REQUEST['chief_sfc_action'] ) : false;
			if ( $action === 'disable' ) {
				$this->form_screen->disable();
			} else if ( $action === 'save' ) {
				$this->form_screen->save();
			}

			$this->form_screen->add_actions();

		} else {
			$this->context = 'list';
			$this->list_screen = new CHIEF_SFC_List_Table();
		}
	}

	/**
	 * Set up the main page and load the view.
	 */
	public function view_page() {
		?>
		<div class="wrap">
			<?php
				if ( $this->authorized ) {
					if ( $this->context === 'form' ) {
						$this->form_screen->display();
					} elseif ( $this->context === 'list' ) {
						?>
						<h2>Salesforce Form Captures</h2>
						<?php if ( !empty( $_GET['disabled'] ) && $_GET['disabled'] === 'true' ) { ?>
							<div class="updated notice is-dismissible"><p>Form disabled successfully.</p></div>
						<?php } ?>
						<?php if ( !empty( $_GET['skipped'] ) && $_GET['skipped'] === 'disable' ) { ?>
							<div class="error notice is-dismissible"><p>Form could not be disabled. Please try again.</p></div>
						<?php } ?>
						<div class="chief-sfc-list"><?php
							$this->list_screen->prepare_items();
							$this->list_screen->views();
							$this->list_screen->display();
						?></div><?php
					}
				} else {
					?>
					<h2>Salesforce Form Captures</h2>
					<div class="notice-info notice"><p>Before using this plugin you must authenticate with Salesforce. Follow the instructions at <a href="<?php echo esc_url( admin_url( 'admin.php?page=chief-sfc-settings' ) ); ?>">Form Captures > Authorization</a>.</p></div>
					<?php
				}
			?>
		</div>
		<?php

	}

	/**
	 * Callback for an ajax object request (i.e. the user selected Contact or Lead on the form
	 * screen). Instantiate CHIEF_SFC_Form and echo the correct form content in response.
	 */
	public function ajax_object() {
		$value  = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : '';
		$form   = isset( $_POST['form'] ) ? (int) $_POST['form'] : false;
		$source = isset( $_POST['source'] ) ? sanitize_key( $_POST['source'] ) : false;
		$form = new CHIEF_SFC_Form( $form, $source );
		$form->view_field_matching( $value );
		exit();
	}

}