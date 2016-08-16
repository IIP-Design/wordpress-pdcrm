<?php
/**
 * The Authorization admin page. Build with the Settings API.
 */
class CHIEF_SFC_Authorization {

	static $slug;
	static $title;

	static $client_setting;
	static $tokens_setting;

	static $values;

	/**
	 * Init. Add static vars and load actions.
	 */
	static public function init() {

		// page vars
		self::$title = 'Salesforce Authorization';
		self::$slug = 'chief-sfc-settings';

		// the names of our stored settings arrays
		self::$client_setting = 'chief_sfc_settings';
		self::$tokens_setting = 'chief_sfc_authorization';

		self::add_actions();

	}

	/**
	 * Hook in methods to WordPress.
	 */
	static public function add_actions() {

		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_sections' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_fields' ) );

		add_action( 'load-form-captures_page_' . self::$slug, array( __CLASS__, 'load_page' ) );

		add_action( 'current_screen', array( __CLASS__, 'authorize' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'retrieve_auth_code' ), 11 );

	}

	/**
	 * Register a submenu page.
	 */
	static public function register_page() {
		add_submenu_page(
			'chief-sfc-captures',
			self::$title,
			'Authorization',
			'manage_options',
			self::$slug,
			array( __CLASS__, 'view_page' )
		);
	}

	/**
	 * Register this page's settings.
	 *
	 * The Settings API is vastly simplified when only one setting group and only
	 * one setting record is stored per page, so that's the approach we take here.
	 */
	static public function register_settings() {
		register_setting( self::$slug, self::$client_setting, array( __CLASS__, 'sanitize_settings' ) );
	}

	/**
	 * Register a "default" section to put our settings in.
	 */
	static public function register_sections() {
		add_settings_section( 'default', '', '', self::$slug );
	}

	/**
	 * Add actual HTML form fields. This triggers a callback that the child class handles.
	 */
	static public function register_fields() {
		$fields = array(
			array(
				'title' => 'Consumer Key',
				'type'  => 'text',
				'args'  => array(
					'name' => 'client_id',
					'id'   => 'chief-sfc-client-id-field'
				)
			),
			array(
				'title' => 'Consumer Secret',
				'type'  => 'text',
				'args'  => array(
					'name' => 'client_secret',
					'id'   => 'chief-sfc-client-secret-field'
				)
			),
			array(
				'title' => 'Login URL',
				'type'  => 'text',
				'args'  => array(
					'name'    => 'client_url',
					'id'      => 'chief-sfc-client-url',
					'default' => 'https://login.salesforce.com'
				)
			),
			array(
				'title' => 'Status',
				'type'  => 'status',
				'args'  => array(
					'id' => 'status'
				)
			)
		);

		foreach( $fields as $field ) {
			$func = array( __CLASS__, 'view_field_' . $field['type'] );
			if ( is_callable( $func ) ) {
				add_settings_field( $field['args']['id'], $field['title'], $func, self::$slug, 'default', $field['args'] );
			}
		}
	}

	/**
	 * Now that we're on the right page, set up the enqueue hook and load existing values.
	 */
	static public function load_page() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		self::$values = get_option( self::$client_setting, array() );
	}

	/**
	 * Enqueue scripts/styles.
	 */
	static public function enqueue() {
		wp_enqueue_style( 'chief-sfc-style', CHIEF_SFC_URL . 'admin/css/style.css' );
	}

	/**
	 * Output the HTML structure of the full settings page.
	 */
	static public function view_page() {
		include( CHIEF_SFC_PATH . 'admin/partials/settings-page.php' );
	}

	/**
	 * Output the HTML for a text field.
	 */
	static public function view_field_text( $args ) {

		$args = wp_parse_args( $args, array(
			'name'    => '',
			'id'      => '',
			'default' => ''
		) );

		$value = isset( self::$values[$args['name']] ) ? self::$values[$args['name']] : '';

		if ( !$value && $args['default'] )
			$value = $args['default'];

		include( CHIEF_SFC_PATH . 'admin/partials/settings-text-field.php' );
	}

	/**
	 * Output the HTML for the status field.
	 */
	static public function view_field_status() {
		$status = CHIEF_SFC_Remote::test();
		include( CHIEF_SFC_PATH . 'admin/partials/settings-status-field.php' );
	}

	/**
	 * Return the Salesforce timestamp in a readable format according to current
	 * WordPress date/time settings. Helper function for the Status field display.
	 */
	static public function get_readable_time( $salesforce_time = 0 ) {
		$date_format = get_option( 'date_format', 'F j, Y' );
		$time_format = get_option( 'time_format', 'g:i a' );

		$local_time = ( $salesforce_time / 1000 ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		$readable_time = date( $date_format . ' \a\t ' . $time_format, $local_time );

		return $readable_time;
	}

	/**
	 * Sanitize fields.
	 */
	static public function sanitize_settings( $values ) {

		/**
		 * If we're trying to revoke, hijack and redirect.
		 */
		$revoke = isset( $_POST['revoke'] ) ? (bool) $_POST['revoke'] : false;
		if ( $revoke ) {
			$response = CHIEF_SFC_Remote::revoke();
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'revoked', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

		// sanitize
		$client_id     = isset( $values['client_id'] )     ? sanitize_text_field( $values['client_id'] )     : '';
		$client_secret = isset( $values['client_secret'] ) ? sanitize_text_field( $values['client_secret'] ) : '';
		$client_url    = isset( $values['client_url'] )    ? esc_url_raw( $values['client_url'] )            : '';

		// remove possible url trailing slash
		$client_url = rtrim( $client_url, '/' );

		/**
		 * If either the id/secret is empty, hijack and redirect.
		 */
		if ( !$client_id || !$client_secret ) {
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'missing-required', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

		// send along
		$sanitized = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'client_url'    => $client_url
		);
		return $sanitized;

	}

	/**
	 * After settings are successfully saved, attempt to grab an authorization code from Salesforce.
	 */
	static public function authorize( $current_screen ) {

		// ensure we're on the right page and the form was just submitted
		if ( $current_screen->base !== 'form-captures_page_' . self::$slug )
			return;
		if ( !isset( $_GET['settings-updated'] ) )
			return;

		// if we just finished authorizing, no need to continue
		if ( isset( $_GET['authorized'] ) )
			return;

		// get form settings
		$values = get_option( self::$client_setting, array() );

		// if empty form, continue on as normal
		if ( !array_filter( $values ) )
			return;

		// check if we're already authorized
		$response = CHIEF_SFC_Remote::test( $attempt_refresh = false );

		// not authorized
		if ( is_wp_error( $response ) || !is_object( $response ) ) {

			// is either field empty? if so, get out early
			if ( is_wp_error( $response ) ) {
				if ( $response->get_error_code() === 'missing_client_keys' ) {
					$url = 'admin.php?page=chief-sfc-settings';
					$url = esc_url_raw( add_query_arg( 'missing-required', 'true', $url ) );
					wp_redirect( $url );
					exit;
				}
			}

			$values = wp_parse_args( $values, array(
				'client_id'  => '',
				'client_url' => ''
			) );

			CHIEF_SFC_Remote::authorize( $values['client_id'], $values['client_url'] );

		// already authorized
		} else {
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'already-authorized', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

	}

	/**
	 * On any page, check for an authorization code sent from Salesforce, and if it exists, send
	 * a request for an access token. After receiving a successful response, save the tokens and
	 * redirect back to the settings page (or, redirect back with an error message).
	 */
	static public function retrieve_auth_code() {

		// get out early unless the Salesforce query vars exist
		if ( !isset( $_GET['code'] ) || !isset( $_GET['state'] ) )
			return;

		// if the nonce doesn't pass, fail silently
		// (this way nothing will go wrong if there's other 'code' and 'state' query vars for some reason)
		if ( !wp_verify_nonce( $_GET['state'], 'chief-sfc-authorize' ) )
			return;

		$code = sanitize_text_field( $_GET['code'] );
		$values = get_option( self::$client_setting, array() );
		$values = wp_parse_args( $values, array(
			'client_id'     => '',
			'client_secret' => '',
			'client_url'    => 'https://login.salesforce.com'
		) );

		$url = $values['client_url'] . '/services/oauth2/token';
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
		$body['original_issue'] = $body['issued_at'];
		update_option( self::$tokens_setting, $body, $autoload = false );

		// redirect to success
		$settings_url = admin_url( 'admin.php?page=chief-sfc-settings&settings-updated=true&authorized=true' );
		wp_redirect( $settings_url );
		exit;

	}

}