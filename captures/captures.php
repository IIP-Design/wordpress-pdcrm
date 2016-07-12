<?php
/**
 * The Salesforce > Form Captures page.
 */
class CHIEF_SFC_Captures {

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
	 * Set up the main page and load the view.
	 */
	public function view_page() {
		?>
		<div class="wrap">
			<?php
				// send to the list page or the individual form screen
				$form   = isset( $_GET['form'] ) ? (int) $_GET['form'] : false;
				$source = isset( $_GET['source'] ) ? sanitize_key( $_GET['source'] ) : false;
				if ( $form && $source ) {
					?>
					<h2>
						Salesforce Form Captures
						<a class="page-title-action" href="<?php echo admin_url( 'admin.php?page=chief-sfc-captures' ); ?>">View All</a>
					</h2>
					<?php
					$form_screen = new CHIEF_SFC_Form( $form, $source );
					$form_screen->display();
				} else {
					?>
					<h2>Salesforce Form Captures</h2>
					<?php
					$table = new CHIEF_SFC_List_Table();
					$table->prepare_items();
					$table->views();
					$table->display();
				}
			?>
		</div>
		<?php


	}

}

