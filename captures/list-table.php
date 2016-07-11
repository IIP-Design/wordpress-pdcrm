<?php

if( !class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class CHIEF_SFC_List_Table extends WP_List_Table {

	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $this->get_items();
	}

	public function get_views() {
		$count = count( $this->items );
		$count = '<span class="count">(' . $count . ')</span>';
		$url   = admin_url( 'admin.php?page=chief-sfc-captures' );
		$views = array(
			'all' => '<a class="current" href="' . $url . '">All ' . $count . '</a>'
		);
		return $views;
	}

	public function get_columns() {
		$columns = array(
			'form'   => 'Form',
			'source' => 'Source',
			'status' => 'Status'
		);
		return $columns;
	}

	public function column_default( $item, $column_name ) {
		return isset( $item[$column_name] ) ? $item[$column_name] : print_r( $item, true );
	}

	public function column_form( $item ) {
		ob_start();
		$form = wp_parse_args( $item['form'], array(
			'form'   => '',
			'id'     => '',
			'source' => ''
		) );
		$url = admin_url( 'admin.php?page=chief-sfc-captures' );
		$url = esc_url( add_query_arg( array(
			'form'   => $form['id'],
			'source' => $form['source']
		) ), $url );
		?>
		<strong>
			<a class="row-title" href="<?php echo $url; ?>"><?php echo $form['title']; ?></a>
		</strong>
		<?php
		return ob_get_clean();
	}

	public function column_status( $item ) {
		return $item['status'] ? 'Active' : 'Inactive';
	}

	public function get_items() {
		$forms = array();

		// check for compatible plugins
		if ( is_callable( array( 'FrmForm', 'getAll' ) ) ) {
			$formidable_forms = FrmForm::getAll( array(
				'is_template' => false
			) );
			foreach( $formidable_forms as $form ) {
				$form_args = array(
					'title'  => $form->name,
					'id'     => $form->id,
					'source' => 'frm'
				);
				$forms[] = array(
					'form'   => $form_args,
					'source' => 'Formidable',
					'status' => true
				);
			}

		}
		if ( is_callable( array( 'WPCF7_ContactForm', 'find' ) ) ) {
			$contact_form_7s = WPCF7_ContactForm::find();
			foreach( $contact_form_7s as $form ) {
				$form_args = array(
					'title'  => $form->title(),
					'id'     => $form->id(),
					'source' => 'cf7'
				);
				$forms[] = array(
					'form'   => $form_args,
					'source' => 'Contact Form 7',
					'status' => false
				);
			}
		}

		return $forms;
	}

}