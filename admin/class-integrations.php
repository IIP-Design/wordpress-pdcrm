<?php
/**
 * The Salesforce > Integrations page.
 */
class CHIEF_SFC_Integrations {

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
			<h2>Salesforce Form Captures</h2>
			<?php
				$table = new CHIEF_SFC_Integrations_List_Table();
				$table->prepare_items();
				$table->display();
			?>
		</div>
		<?php


	}

}

if( !class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class CHIEF_SFC_Integrations_List_Table extends WP_List_Table {

	public function get_columns() {
		$columns = array(
			'form'   => 'Form',
			'source' => 'Source',
			'active' => 'Connected'
		);
		return $columns;
	}

	public function column_default( $item, $column_name ) {
		return isset( $item[$column_name] ) ? $item[$column_name] : print_r( $item, true );
	}

	public function column_form( $item ) {
		ob_start();
		?>
		<strong>
			<a class="row-title" href="#"><?php echo $item['form']; ?></a>
		</strong>
		<?php
		return ob_get_clean();
	}

	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $this->get_items();
	}

	public function get_items() {
		$forms = array();

		// check for compatible plugins
		if ( is_callable( array( 'FrmForm', 'getAll' ) ) ) {
			$formidable_forms = FrmForm::getAll();
			foreach( $formidable_forms as $form ) {
				$forms[] = array(
					'form'   => $form->name,
					'source' => 'Formidable',
					'active' => ''
				);
			}

		}
		if ( is_callable( array( 'WPCF7_ContactForm', 'find' ) ) ) {
			$contact_form_7s = WPCF7_ContactForm::find();
			foreach( $contact_form_7s as $form ) {
				$forms[] = array(
					'form'   => $form->title(),
					'source' => 'Contact Form 7',
					'active' => ''
				);
			}
		}

		return $forms;
	}

}