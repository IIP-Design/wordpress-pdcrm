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
		add_action( 'plugins_loaded', array( __CLASS__, 'retrieve_auth_code' ), 11 );
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

		// just finished authorizing. no need to continue
		if ( isset( $_GET['authorized'] ) )
			return;

		// get form settings
		$values = get_option( CHIEF_SFC_Settings::$setting, array() );

		// if empty form, continue on as normal
		if ( !array_filter( $values ) ) {
			return;
		}

		// check if we're already authorized
		$response = CHIEF_SFC_Remote::test( $attempt_refresh = false );

		// not authorized
		if ( is_wp_error( $response ) ) {

			// is either field empty? if so, get out early
			if ( $response->get_error_code() === 'missing_client_keys' ) {
				$url = 'admin.php?page=chief-sfc-settings';
				$url = esc_url_raw( add_query_arg( 'missing-required', 'true', $url ) );
				wp_redirect( $url );
				exit;
			}

			CHIEF_SFC_Remote::authorize( $values['client_id'] );

		// already authorized
		} else {
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'already-authorized', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

	}

	/**
	 * Check for an authorization code sent from Salesforce, and if it exists send
	 * a request for an access token.
	 */
	static public function retrieve_auth_code() {
		if ( !isset( $_GET['code'] ) || !isset( $_GET['state'] ) )
			return;

		// if the nonce doesn't pass, fail silently
		if ( !wp_verify_nonce( $_GET['state'], 'chief-sfc-authorize' ) )
			return;

		$code = sanitize_text_field( $_GET['code'] );
		$values = get_option( CHIEF_SFC_Settings::$setting, array() );
		$values = wp_parse_args( $values, array(
			'client_id'     => '',
			'client_secret' => ''
		) );

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
			'issued_at'     => '',
			'instance_url'  => ''
		) );
		update_option( self::$setting, $body, $autoload = false );

		// redirect to success
		$settings_url = admin_url( 'admin.php?page=chief-sfc-settings&settings-updated=true&authorized=true' );
		wp_redirect( $settings_url );
		exit;

	}

}