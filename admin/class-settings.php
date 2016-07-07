<?php
/**
 * The Salesforce > Settings page.
 */
class CHIEF_SFC_Settings extends CHIEF_SFC_Settings_Abstract {

	public function __construct() {
		self::$setting    = 'chief_sfc_settings';
		$this->parent     = 'chief-sfc-captures';
		$this->slug       = 'chief-sfc-settings';
		$this->page_title = 'Salesforce Form Capture Settings';
		$this->menu_title = 'Settings';
		$this->intro      = $this->get_intro();
		$this->fields = array(
			'chief-sfc-client-id-field' => array(
				'title' => 'Salesforce Consumer Key',
				'type'  => 'text',
				'args'  => array( 'name' => 'client_id' )
			),
			'chief-sfc-client-secret-field' => array(
				'title' => 'Salesforce Consumer Secret',
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

			<?php // successfully authorized
			if ( !empty( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) { ?>
			    <div class="updated notice is-dismissible"><p>Settings updated.</p></div>
			<?php } ?>

			<?php // successfully revoked authorization
			if ( !empty( $_GET['revoked'] ) && $_GET['revoked'] === 'true' ) { ?>
				<div class="notice notice-info is-dismissible"><p>Authorization revoked and Consumer Secret reset.</p></div>
			<?php } ?>

			<?php // tried submitting an empty form
			if ( !empty( $_GET['empty-form'] ) && $_GET['empty-form'] === 'true' ) { ?>
			    <div class="notice notice-error is-dismissible"><p>The form is empty.</p></div>
			<?php } ?>

			<?php // tried authenticating but got an error
			if ( !empty( $_GET['auth-error'] ) && $_GET['auth-error'] === 'true' ) {
				$error = get_transient( 'chief_sfc_error' );
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

					$response = CHIEF_SFC_Authorization::check_authorization();
					$submit_value = is_wp_error( $response ) ? 'Save Settings &amp; Authorize' : 'Save Settings';
					submit_button( esc_attr( $submit_value ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Intro HTML.
	 */
	public function get_intro() {
		return '';
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

		$response = CHIEF_SFC_Authorization::check_authorization();

		if ( is_wp_error( $response ) ) {
			?><span>Not connected.</span><?php
		} else {
			$auth = get_option( 'chief_sfc_authorization' );
			$auth = wp_parse_args( $auth, array(
				'issued_at' => 0
			) );
			$date = get_option( 'date_format', 'F j, Y' );
			$time = get_option( 'time_format', 'g:i a' );
			$issued_at = date( $date . ' \a\t ' . $time, $auth['issued_at'] / 1000 ) . ' UTC';
			?>
			<span style="color:green;">Connected</span>
			(since <?php echo $issued_at; ?>)
			<span style="display:inline-block;vertical-align:middle;">
				<?php submit_button( esc_attr( 'Revoke Authorization' ), 'secondary', 'revoke', false ); ?>
			</span>
			<?php
		}

	}

	/**
	 * Sanitize our fields. Values are saved only if they match an existing field.
	 */
	public function sanitize_settings( $values ) {

		// if we're trying to revoke, hijack the process
		$revoke = isset( $_POST['revoke'] ) ? (bool) $_POST['revoke'] : false;
		if ( $revoke ) {
			$response = CHIEF_SFC_Authorization::revoke();
			$url = 'admin.php?page=chief-sfc-settings';
			$url = esc_url_raw( add_query_arg( 'revoked', 'true', $url ) );
			wp_redirect( $url );
			exit;
		}

		$sanitized = array();
		foreach( $this->fields as $field ) {
			$field = wp_parse_args( $field, array(
				'type' => '',
				'args' => ''
			) );
			$name = isset( $field['args']['name'] ) ? $field['args']['name'] : '';
			if ( !$name )
				continue;

			// if a value isn't passed which matches this field
			if ( !isset( $values[$name] ) )
				continue;

			switch( $field['type'] ) {
				case 'text' :
					$sanitized[$name] = sanitize_text_field( $values[$name] );
					break;
			}
		}
		return $sanitized;
	}

}