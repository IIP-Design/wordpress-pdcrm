<?php
/**
 * Add salesforce authorization processes.
 *
 * User path:
 * 1. User enters Consumer Key and Consumer Secret on Settings page and submits.
 * 2. Settings are saved in WordPress.
 * 3. Before redirecting to success message, user is sent to Salesforce.
 * 4. User is prompted to log in and give the plugin permission to access Salesforce.
 * 5. Salesforce redirects back to this site with an authorization code.
 * 6. The plugin grabs the authorization code and sends a POST request back to Salesforce.
 * 7. Salesforce sends back a final token which is saved in wp_options.
 * 8. The user is finally redirected back to the settings page with an updated Status.
 *
 * Failures within these steps will ideally kick the user back to the settings page with
 * an error message.
 */
class CHIEF_SFC_Authorization {

	static $setting;

	/**
	 * Hook methods into WP.
	 */
	static public function add_actions() {
		self::$setting = 'chief_sfc_authorization';

		add_action( 'current_screen', array( __CLASS__, 'authorize' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'get_auth_code' ), 11 );
	}

	/**
	 * After settings are saved, attempt to grab an authorization code from Salesforce.
	 */
	static public function authorize( $current_screen ) {

		// ensure we're on the right page and the form was just submitted
		if ( $current_screen->base !== 'salesforce_page_chief-sfc-settings' )
			return;
		if ( !isset( $_GET['settings-updated'] ) )
			return;

		// check if we're already authorized
		$response = self::check_authorization();
		if ( !is_wp_error( $response ) ) {
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'already-authorized', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

		// get form settings
		$values = get_option( CHIEF_SFC_Settings::$setting, array() );

		// if empty form, don't bother going further
		if ( !array_filter( $values ) ) {
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'empty-form', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

		// attempt authorization
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
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// anything except success, kick to error
		if ( (int) $response_code !== 200 ) {
			$error = isset( $body['error_description'] ) ? $body['error_description'] : 'unknown error.';
			set_transient( 'chief_sfc_error', $error, 5 * 60 );
			$settings_url = admin_url( 'admin.php?page=chief-sfc-settings&auth-error=true' );
			wp_redirect( $settings_url );
			exit;
		}

		// save retrieved tokens and info

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
	 * Revoke authorization with Salesforce.
	 */
	static public function revoke() {
		$settings = get_option( self::$setting, array() );
		$settings = wp_parse_args( $settings, array(
			'refresh_token' => ''
		) );

		$url = 'https://login.salesforce.com/services/oauth2/revoke?token=' . $settings['refresh_token'];
		$data = wp_remote_get( $url );
		$response = wp_remote_retrieve_body( $data );

		// even if the response fails, at least delete all the auth data
		delete_option( self::$setting );

		// if response is successful, client secret will have been reset, so the current one is useless
		$response_code = isset( $response['response']['code'] ) ? $response['response']['code'] : 400;
		if ( (int) $response_code === 200 ) {
			$client_keys = get_option( CHIEF_SFC_Settings::$setting, array() );
			if ( isset( $settings['client_secret'] ) ) {
				unset( $settings['client_secret'] );
				update_option( CHIEF_SFC_Settings::$setting );
			}
		}

		return $response;
	}

	/**
	 * Make a Salesforce API request.
	 */
	static public function request( $uri = '', $params = array(), $method = 'GET', $attempt_refresh = true ) {

		$auth = get_option( CHIEF_SFC_Authorization::$setting, array() );
		$auth = wp_parse_args( $auth, array(
			'access_token' => '',
			'instance_url' => ''
		) );

		$headers = array(
			'content-type'  => 'application/json',
			'Authorization' => 'Bearer '. $auth['access_token']
		);

		$url = $auth['instance_url'] . '/services/data/v37.0/';

		if ( $uri )
			$url .= $uri;

		if ( ( $method === 'GET' ) && $params ) {
			$url .= urldecode( http_build_query( $params ) );
			$params = false;
		}

		$request = array(
			'method'    => 'GET',
			'body'      => 'sample body',
			'headers'   => $headers,
			'sslverify' => true
		);

		$response = wp_remote_request( $url, $request );

		// if error, try refreshing the token
		if ( is_wp_error( $response ) && $attempt_refresh ) {
			if ( $response->get_error_code() === 'INVALID_SESSION_ID' ) {
				CHIEF_SFC_Authorization::refresh_token();
				// now try again (but don't keep trying)
				$response = chief_sfc_request( $uri, $params, $method, false );
			}
		}

		return $response;

	}

	/**
	 * Refresh the Salesforce access_token using the refresh_token and the client id/secret.
	 */
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

		// if something went wrong
		if ( empty( $body['access_token'] ) )
			return false;

		// refresh setting
		$body = wp_parse_args( $body, array(
			'access_token'  => '',
			'refresh_token' => '',
			// 'signature'     => '',
			// 'id'            => '',
			'issued_at'     => '',
			'instance_url'  => ''
		) );
		return update_option( self::$setting, $body, false );

	}

	/**
	 * Check to see if we're currently communicating with Salesforce by sending a bare-bones request.
	 */
	static public function check_authorization() {
		$client_keys = get_option( CHIEF_SFC_Settings::$setting, array() );
		$client_keys = array_filter( $client_keys );
		if ( count( $client_keys ) < 2 )
			return new WP_Error( 'no_client_keys', 'The Consumer Key and Consumer Secret are required.' );

		return self::request( '', array(), 'GET' );
	}

}