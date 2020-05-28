<?php
/**
 * Communicate with Salesforce's REST API over HTTPS.
 */
class CHIEF_SFC_Remote {

	/**
	 * Make a Salesforce API request.
	 */
	static public function request( $uri = '', $params = array(), $method = 'GET', $attempt_refresh = true ) {

		$auth = get_option( CHIEF_SFC_Authorization::$tokens_setting, array() );
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

		// if get, put params into the url and discard the array
		if ( ( $method === 'GET' ) && $params ) {
			$url .= urldecode( http_build_query( $params ) );
			$params = false;
		}

		$request = array(
			'method'    => $method,
			'body'      => $params ? json_encode( $params ) : false,
			'headers'   => $headers,
			'sslverify' => true,
			'timeout' => 30
		);

		$response = wp_remote_request( $url, $request );

		$return = [
			'request' => $request,
			'response' => $response,
			'body' => null,
			'code' => null,
			'wp_error' => is_wp_error( $response )
		];

		if ( $return['wp_error'] )
			return $return;

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		$code = isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 400;

		$return['body'] = $body;
		$return['code'] = $code;

		if ( ( $code < 200 || $code > 299 ) && $attempt_refresh ) {
			$error = isset( $body[0]->errorCode ) ? sanitize_text_field( $body[0]->errorCode ) : '';
			if ( $error === 'INVALID_SESSION_ID' ) {
				self::refresh_token();
				// now try again (but don't keep trying)
				$return = self::request( $uri, $params, $method, false );
			}
		}

		return $return;

	}

	/**
	 * Make a basic GET request.
	 */
	static public function get( $uri = '', $params = array() ) {
		return self::request( $uri, $params, 'GET', true );
	}

	/**
	 * Make a basic POST request.
	 */
	static public function post( $uri = '', $params = array() ) {
		return self::request( $uri, $params, 'POST', true );
	}

	/**
	 * Refresh the Salesforce access_token using the refresh_token and the client id/secret.
	 */
	static public function refresh_token() {

		$keys = get_option( CHIEF_SFC_Authorization::$client_setting, array() );
		$auth = get_option( CHIEF_SFC_Authorization::$tokens_setting, array() );

		$keys = wp_parse_args( $keys, array(
			'client_id'     => '',
			'client_secret' => '',
			'client_url'    => 'https://login.salesforce.com'
		) );

		$url = $keys['client_url'] . '/services/oauth2/token';

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
			'access_token'   => '',
			'refresh_token'  => $auth['refresh_token'], // no new refresh token is sent
			'issued_at'      => '',
			'instance_url'   => '',
			'original_issue' => $auth['original_issue']
		) );
		return update_option( CHIEF_SFC_Authorization::$tokens_setting, $body, false );

	}

	/**
	 * Authorize with Salesforce. This isn't actually a remote request,
	 * just a redirect to Salesforce where the user is asked to authorize
	 * the app.
	 */
	static public function authorize( $client_id = '', $url = '' ) {
		$url = $url . '/services/oauth2/authorize';
		$url = esc_url_raw( add_query_arg( array(
			'response_type' => 'code',
			'client_id'     => $client_id,
			'redirect_uri'  => site_url(),
			'state'         => wp_create_nonce( 'chief-sfc-authorize' )
		), $url ) );
		wp_redirect( $url );
		exit;
	}

	/**
	 * Revoke authorization with Salesforce.
	 */
	static public function revoke() {
		$settings = get_option( CHIEF_SFC_Authorization::$tokens_setting, array() );
		$settings = wp_parse_args( $settings, array(
			'refresh_token' => ''
		) );

		$keys = get_option( CHIEF_SFC_Authorization::$client_setting, array() );
		$keys = wp_parse_args( $keys, array(
			'client_url' => 'https://login.salesforce.com'
		) );

		$url = $keys['client_url'] . '/services/oauth2/revoke?token=' . $settings['refresh_token'];
		$data = wp_remote_get( esc_url_raw( $url ) );

		// even if the response fails, at least delete all the auth data
		delete_option( CHIEF_SFC_Authorization::$tokens_setting );

		return $data;
	}

	/**
	 * Check to see if we're currently communicating with Salesforce by sending a bare-bones request.
	 */
	static public function test( $attempt_refresh = true ) {

		// fail early if no client keys exist
		$client_keys = get_option( CHIEF_SFC_Authorization::$client_setting, array() );
		$client_keys = array_filter( $client_keys );
		if ( count( $client_keys ) < 2 )
			return new WP_Error( 'missing_client_keys', 'The Consumer Key and Consumer Secret are required.' );

		// send a basic request
		return self::request( '', array(), 'GET', $attempt_refresh );

	}

}