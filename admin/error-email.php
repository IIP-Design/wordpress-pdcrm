<?php

class CHIEF_SFC_Error_Email {
	public static $option = 'chief_sfc_error_email';
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	public function register_page() {
		add_submenu_page(
			'chief-sfc-captures',
			'Error Email',
			'Error Email',
			'manage_options',
			'chief-sfc-error-email',
			array( $this, 'view_page' )
		);
	}

	public function view_page() {
		$message = '';
		$error = false;
		if ( array_key_exists( 'address', $_POST ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$error_email = $_POST['address'];
				update_option( CHIEF_SFC_Error_Email::$option, $error_email );
				$message = 'Email updated';
			} else {
				$message = 'Unauthorized';
				$error = true;
			}
		}

		$error_email = get_option( CHIEF_SFC_Error_Email::$option, '' );
		include_once CHIEF_SFC_PATH . 'admin/partials/error-email.php';
	}
}