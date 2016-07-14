<?php

if( !class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class CHIEF_SFC_List_Table extends WP_List_Table {

	/**
	 * Basic setup required by WP_List_Table to get column and row info.
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $this->get_items();
	}

	/**
	 * Show "All" so we get a quick glance at the amount.
	 */
	public function get_views() {
		$count = count( $this->items );
		$count = '<span class="count">(' . $count . ')</span>';
		$url   = admin_url( 'admin.php?page=chief-sfc-captures' );
		$views = array(
			'all' => '<a class="current" href="' . $url . '">All ' . $count . '</a>'
		);
		return $views;
	}

	/**
	 * No bulk actions needed, so don't return default markup.
	 */
	protected function display_tablenav( $which ) {
		return '';
	}

	/**
	 * List table columns.
	 */
	public function get_columns() {
		$columns = array(
			'form'   => 'Form',
			'source' => 'Source',
			'status' => 'Status'
		);
		return $columns;
	}

	/**
	 * Covering our bases.
	 */
	public function column_default( $item, $column_name ) {
		return '';
	}

	/**
	 * Form name with contextual Edit/Disable links underneath.
	 */
	public function column_form( $item ) {
		ob_start();
		?>
		<strong>
			<a class="row-title" href="<?php echo esc_url( $item->url ); ?>"><?php echo $item->name; ?></a>
		</strong>
		<?php

		$actions = array(
			'edit' => '<a href="' . esc_url( $item->url ) . '">Edit</a>',
		);

		if ( $item->is_enabled() )
			$actions['delete'] = '<a href="' . esc_url( $item->get_disable_url() ) . '">Disable</a>';

		echo $this->row_actions( $actions );

		return ob_get_clean();
	}

	/**
	 * Get the human-readable label.
	 */
	public function column_source( $item ) {
		return $item->source_label();
	}

	/**
	 * Get the human-readable status. Will show if the form is saving to Salesforce and what
	 * object it's saving as.
	 */
	public function column_status( $item ) {
		return $item->get_status_label();
	}

	/**
	 * Find all available forms. Look in compatible plugins and build a CHIEF_SFC_Form object
	 * for each one.
	 */
	public function get_items() {
		$forms = array();

		// check for compatible plugins
		if ( is_callable( array( 'FrmForm', 'getAll' ) ) ) {
			$formidable_forms = FrmForm::getAll( array(
				'is_template' => false
			) );
			foreach( $formidable_forms as $form )
				$forms[] = new CHIEF_SFC_Form( $form->id, 'frm' );
		}
		if ( is_callable( array( 'WPCF7_ContactForm', 'find' ) ) ) {
			$contact_form_7s = WPCF7_ContactForm::find();
			foreach( $contact_form_7s as $form )
				$forms[] = new CHIEF_SFC_Form( $form->id(), 'cf7' );
		}

		return $forms;
	}

}