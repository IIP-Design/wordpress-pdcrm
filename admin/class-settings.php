<?php
/**
 * The Salesforce > Settings page.
 */
class CHIEF_SFC_Settings extends CHIEF_SFC_Settings_Abstract {

	public function __construct() {
		$this->parent     = 'chief-sfc-captures';
		$this->slug       = 'chief-sfc-settings';
		$this->page_title = 'Salesforce Form Capture Settings';
		$this->menu_title = 'Settings';
		$this->setting    = 'chief_sfc_settings';
		$this->intro      = $this->get_intro();
		$this->submit_value = 'Save & Authenticate';
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
	 * Run the default actions in addition to a hook which allows us to authenticate with Salesforce.
	 */
	public function add_actions() {
		parent::add_actions();
		add_action( 'current_screen', array( $this, 'authenticate' ) );
	}

	/**
	 * Authenticate with Salesforce.
	 */
	public function authenticate( $current_screen ) {
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

		$this->values = get_option( $this->setting, array() );
		$url = 'https://login.salesforce.com/services/oauth2/authorize';
		$url = esc_url_raw( add_query_arg( array(
			'response_type' => 'code',
			'client_id'     => $this->values['client_id'],
			'redirect_uri'  => site_url()
		), $url ) );
		wp_redirect( $url );
		exit;
	}

	/**
	 * Intro HTML.
	 */
	public function get_intro() {
		ob_start();
		?>
		<p>Instructions here.</p>
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
			name="<?php echo $this->setting; ?>[<?php echo esc_attr( $args['name'] ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			class="widefat"
		/>
		<?php
	}

	/**
	 * Build a custom status field.
	 */
	public function view_field_status() {

		/* $url = 'https://login.salesforce.com';
		$token_url = $url . '/services/oauth2/token';
		$post = array(
			'body' => array(
				'client_id'     => $this->values['client_id'],
				'client_secret' => $this->values['client_secret']
			)
		); */

		/* $post['body']["code"] = $grantCode;
		$post['body']["redirect_uri"] = home_url();
		$post['body']["grant_type"] = "authorization_code"; */

		// $response = wp_remote_post( $token_url, $post );

		// authenticate with Salesforce
		$url = 'https://login.salesforce.com/services/oauth2/authorize';
		$url = esc_url( add_query_arg( array(
			'response_type' => 'code',
			'client_id'     => $this->values['client_id'],
			'redirect_uri'  => home_url()
		), $url ) );
		echo '<p><a href="' . $url . '">What happens</a></p>';

		echo '<pre>';
		// print_r( $response );
		echo '</pre>';

	}

	/**
	 * Sanitize our fields. Values are saved only if they match an existing field.
	 */
	public function sanitize_settings( $values ) {
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

		// authenticate with Salesforce
		$url = 'https://login.salesforce.com/services/oauth2/authorize';
		$url = esc_url( add_query_arg( array(
			'response_type' => 'code',
			'client_id'     => $sanitized['client_id'],
			'redirect_uri'  => home_url()
		), $url ) );

		return $sanitized;
	}

}