<?php
/**
 * Send requests to Salesforce.
 */
class CHIEF_SFC_Authorization {

	static $setting;

	/**
	 * Hook methods into WP.
	 */
	static public function add_actions() {
		self::$setting = 'chief_sfc_authorization';

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

		$auth = get_option( self::$setting, array() );
		$values = get_option( CHIEF_SFC_Settings::$setting, array() );

		// check for existing authentication
		$response = self::check_authentication();
		if ( is_array( $response ) ) {
			if ( $response[0]->errorCode === 'INVALID_SESSION_ID' ) {
				// attempt to refresh token
				CHIEF_SFC_Authorization::refresh_token();
				$response = CHIEF_SFC_Authorization::check_authentication();
			} else {
				echo '<p>An unexpected error occurred.</p>';
			}
		}

		// already authenticated
		if ( is_object( $response ) ) {
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'already-authenticated', 'true', $url ) );
			wp_redirect( $url );
			exit;

		// attempt authentication
		} else {
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

		// anything except success, kick to error
		if ( (int) $response_code !== 200 ) {
			$settings_url = admin_url( 'admin.php?page=chief-sfc-settings&chief-sfc-error' );
			wp_redirect( $settings_url );
			exit;
		}

		// save retrieved tokens and info
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$body = wp_parse_args( $body, array(
			'access_token'  => '',
			'refresh_token' => '',
			// 'signature'     => '',
			// 'id'            => '',
			'issued_at'     => '',
			'instance_url'  => ''
		) );
		update_option( self::$setting, $body, $autoload = false );

		// redirect to success
		$settings_url = admin_url( 'admin.php?page=chief-sfc-settings&settings-updated=true' );
		wp_redirect( $settings_url );
		exit;

	}

	/**
	 * Check to see if we're currently authenticating with Salesforce.
	 *
	 * If not, grab a new token.
	 */
	static public function check_authentication() {
		$auth = get_option( self::$setting, array() );
		$auth = wp_parse_args( $auth, array(
			'access_token' => '',
			'instance_url' => ''
		) );

		$headers = array(
			'content-type'  => 'application/json',
			'Authorization' => 'Bearer '. $auth['access_token']
		);

		$url = $auth['instance_url'] . '/services/data/v37.0/';

		$request = array(
			'method'    => 'GET',
			'body'      => 'sample body',
			'headers'   => $headers,
			'sslverify' => true
		);

		$result = wp_remote_request( $url, $request );

		if ( !is_wp_error( $result ) ) {
			$response = json_decode( wp_remote_retrieve_body( $result ) );
			return $response;
		} else {
			return $result->get_error_message();
		}

	}

	static public function refresh_token() {

		$keys = get_option( CHIEF_SFC_Settings::$setting, array() );
		$auth = get_option( self::$setting, array() );

		$success = true;

		$url  = 'https://login.salesforce.com/services/oauth2/token';
		$args = array(
			'body' => array(
				'client_id'     => $keys['client_id'],
				'client_secret' => $keys['client_secret'],
				'refresh_token' => $auth['refresh_token'],
				'grant_type'    => 'refresh_token'
			)
		);

		$response = wp_remote_post( $url, $args );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) )
			return false;

		$body = wp_parse_args( $body, array(
			'access_token'  => '',
			'refresh_token' => '',
			// 'signature'     => '',
			// 'id'            => '',
			'issued_at'     => '',
			'instance_url'  => ''
		) );

		// refresh auth setting
		update_option( self::$setting, $body, false );
		// refresh_token, access_token, issued_at, instance_url
		// not: signature, id

	}

}