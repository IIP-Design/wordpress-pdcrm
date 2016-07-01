<?php
/**
 * Add salesforce authorization processes.
 */
class CHIEF_SFC_Authorization {

	/**
	 * Hook into WP.
	 */
	static public function add_actions() {
		add_action( 'current_screen', array( __CLASS__, 'authenticate' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'get_auth_code' ), 11 );
	}

	/**
	 * After settings are saved, attempt to grab an authorization code from Salesforce.
	 */
	static public function authenticate( $current_screen ) {
		if ( $current_screen->base !== 'salesforce_page_chief-sfc-settings' )
			return;

		if ( !isset( $_GET['settings-updated'] ) )
			return;

		// if already authenticated
		if ( 1 === 0 ) {
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'already-authenticated', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

		$values = get_option( CHIEF_SFC_Settings::$setting, array() );
		$url = 'https://login.salesforce.com/services/oauth2/authorize';
		$url = esc_url_raw( add_query_arg( array(
			'response_type' => 'code',
			'client_id'     => $values['client_id'],
			'redirect_uri'  => site_url(),
			'state'         => wp_create_nonce( 'chief-sfc-authorize' )
		), $url ) );
		wp_redirect( $url );
		exit;
	}

	/**
	 * Check for an authorization code sent from Salesforce, and if it exists send
	 * a request for an access token.
	 */
	static public function get_auth_code() {
		if ( !isset( $_GET['code'] ) || !isset( $_GET['state'] ) )
			return;

		// if the nonce doesn't pass, fail silently
		if ( !wp_verify_nonce( $_GET['state'], 'chief-sfc-authorize' ) )
			return;

		$code = sanitize_text_field( $_GET['code'] );
		$values = get_option( CHIEF_SFC_Settings::$setting, array() );

		$url = 'https://login.salesforce.com/services/oauth2/token';
		$post = array(
			'body' => array(
				'grant_type'    => 'authorization_code',
				'client_id'     => $values['client_id'],
				'client_secret' => $values['client_secret'],
				'redirect_uri'  => site_url(),
				'code'          => $code
			)
		);

		$response = wp_remote_post( $url, $post );
		$response_code = isset( $response['response']['code'] ) ? $response['response']['code'] : 400;

		if ( (int) $response_code !== 200 ) {
			$settings_url = admin_url( 'admin.php?page=chief-sfc-settings&chief-sfc-error' );
			wp_redirect( $settings_url );
			exit;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$body = wp_parse_args( $body, array(
			'access_token'  => '',
			'refresh_token' => '',
			'signature'     => '',
			'id'            => '',
			'issued_at'     => '',
			'signature'     => ''
		) );

		update_option( 'chief_sfc_authorization', $body, $autoload = false );






		echo '<pre>';
		print_r( $response );
		print_r( $authObj );
		echo '</pre>';

		exit;

	}

}