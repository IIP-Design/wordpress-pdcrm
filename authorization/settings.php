<?php
/**
 * The Salesforce > Settings page.
 */
class CHIEF_SFC_Settings extends CHIEF_SFC_Settings_Abstract {

	public function __construct() {
		self::$setting    = 'chief_sfc_settings';
		$this->parent     = 'chief-sfc-captures';
		$this->slug       = 'chief-sfc-settings';
		$this->page_title = 'Salesforce Authorization';
		$this->menu_title = 'Authorization';
		$this->intro      = $this->get_intro();
		$this->fields = array(
			'chief-sfc-client-id-field' => array(
				'title' => 'Consumer Key',
				'type'  => 'text',
				'args'  => array( 'name' => 'client_id' )
			),
			'chief-sfc-client-secret-field' => array(
				'title' => 'Consumer Secret',
				'type'  => 'text',
				'args'  => array( 'name' => 'client_secret' )
			),
			'status' => array(
				'title' => 'Status',
				'type'  => 'status'
			)
		);

	}

	/**
	 * Page wrapper HTML. Output custom error messages, custom submit buttons, etc.
	 */
	public function view_page() {

		// get values to have them handy for field callbacks
		$this->values = get_option( self::$setting, array() );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->page_title ); ?></h1>
			<div class="chief-sfc-settings">

				<?php if ( !empty( $_GET['authorized'] ) && $_GET['authorized'] === 'true' ) { ?>
				    <div class="updated notice is-dismissible"><p>Authorization successful.</p></div>
				<?php } ?>

				<?php // successfully revoked authorization
				if ( !empty( $_GET['revoked'] ) && $_GET['revoked'] === 'true' ) { ?>
					<div class="notice notice-info is-dismissible"><p>Authorization revoked.</p></div>
				<?php } ?>

				<?php // tried submitting an empty form
				if ( !empty( $_GET['missing-required'] ) && $_GET['missing-required'] === 'true' ) { ?>
				    <div class="notice notice-error is-dismissible"><p>The Consumer Key and Consumer Secret are both required for authorization.</p></div>
				<?php } ?>

				<?php // tried authenticating but got an error
				if ( !empty( $_GET['auth-error'] ) && $_GET['auth-error'] === 'true' ) {
					$error = get_transient( 'chief_sfc_error' );
					if ( $error === 'invalid client credentials' )
						$error = 'Salesforce did not accept the Consumer Key or Consumer Secret.';
					if ( !$error ) $error = 'unknown error.'; ?>
				    <div class="notice notice-error is-dismissible"><p>Error during authorization: <?php echo esc_html( $error ); ?></p></div>
				<?php } ?>

				<?php // tried authorizing but already authorized
				if ( !empty( $_GET['already-authorized'] ) && $_GET['already-authorized'] === 'true' ) { ?>
				    <div class="notice notice-info is-dismissible"><p>You're already authorized.</p></div>
				<?php } ?>

				<?php echo $this->intro; ?>

				<form action="options.php" method="post">
					<?php
						settings_fields( $this->slug );
						do_settings_sections( $this->slug );

						$response = CHIEF_SFC_Remote::test();
						$submit_value = 'Authorize with Salesforce';
						submit_button( esc_attr( $submit_value ) );
					?>
				</form>
			</div><!-- .chief-sfc-settings -->
		</div><!-- .wrap -->
		<?php
	}

	/**
	 * Intro HTML.
	 */
	public function get_intro() {
		ob_start();
		?>
		<p>Before using this plugin, you must authorize this website with Salesforce. An HTTPS connection is required.</p>
		<ol>
			<li>Log into Salesforce and create a new Connected App. (Setup > Create > Apps > Connected Apps)</li>
			<li>Enter an App Name and Contact Email.</li>
			<li>
				Under API (Enable OAuth Settings):
				<p>
					<ol>
						<li>Select "Enable Oauth Settings".</li>
						<li>Enter this site's URL.</li>
						<li>Under "Selected Oauth Scopes", add "Full access" and "Perform requests on your behalf at any time".</li>
					</ol>
				</p>
			</li>
			<li>Save the Connected App. The app will be assigned a Consumer Key and Consumer Secret. Add those here.</li>
		</ol>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build a custom text field.
	 */
	public function view_field_text( $args ) {
		$args = wp_parse_args( $args, array(
			'id'   => '',
			'name' => ''
		) );
		$value = isset( $this->values[$args['name']] ) ? $this->values[$args['name']] : '';
		?>
		<input
			id="<?php echo esc_attr( $args['id'] ); ?>"
			type="text"
			name="<?php echo self::$setting; ?>[<?php echo esc_attr( $args['name'] ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			class="widefat"
		/>
		<?php
	}

	/**
	 * Build a custom status field.
	 */
	public function view_field_status() {

		$response = CHIEF_SFC_Remote::test();

		if ( is_wp_error( $response ) ) {
			?><p class="unauthorized">Not authorized.</p><?php
		} else {
			?>
			<p class="authorized">Authorized with Salesforce</p>
			<?php

			$auth = get_option( CHIEF_SFC_Authorization::$setting );
			$auth = wp_parse_args( $auth, array(
				'issued_at'      => 0,
				'original_issue' => 0
			) );

			$original_issue = $this->get_readable_time( $auth['original_issue'] );
			?>
			<p>Issued at <?php echo esc_html( $original_issue ); ?></p>
			<?php

			if ( $auth['issued_at'] !== $auth['original_issue'] ) {

				$issued_at = $this->get_readable_time( $auth['issued_at'] );
				?>
				<p>Last refreshed at <?php echo esc_html( $issued_at ); ?></p>
				<?php

			}
			?>
			<p style="display:inline-block;vertical-align:middle;">
				<?php submit_button( esc_attr( 'Revoke Authorization' ), 'secondary', 'revoke', false ); ?>
			</p>
			<?php
		}

	}

	/**
	 * Return the Salesforce timestamp in a readable format according to current
	 * WordPress date/time settings.
	 */
	public function get_readable_time( $salesforce_time = 0 ) {
		$date_format = get_option( 'date_format', 'F j, Y' );
		$time_format = get_option( 'time_format', 'g:i a' );

		$local_time = ( $salesforce_time / 1000 ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		$readable_time = date( $date_format . ' \a\t ' . $time_format, $local_time );

		return $readable_time;
	}

	/**
	 * Sanitize our fields. Values are saved only if they match an existing field.
	 */
	public function sanitize_settings( $values ) {

		// if we're trying to revoke, hijack the process
		$revoke = isset( $_POST['revoke'] ) ? (bool) $_POST['revoke'] : false;
		if ( $revoke ) {
			$response = CHIEF_SFC_Remote::revoke();
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'revoked', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

		// sanitize
		$client_id = isset( $values['client_id'] ) ? sanitize_text_field( $values['client_id'] ) : '';
		$client_secret = isset( $values['client_secret'] ) ? sanitize_text_field( $values['client_secret'] ) : '';

		// if either field is empty, hijack the process
		if ( !$client_id || !$client_secret ) {
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'missing-required', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

		// send along
		$sanitized = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret
		);
		return $sanitized;

	}

}