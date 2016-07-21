<?php
/**
 * Display the Authorization settings page.
 */
?>

<div class="wrap">
	<h1><?php echo esc_html( self::$title ); ?></h1>
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

		<p>Before using this plugin, you must authorize this website with Salesforce:</p>
		<ol>
			<li>Log into Salesforce and create a new Connected App. (Setup > Create > Apps > Connected Apps)</li>
			<li>Enter an App Name and Contact Email.</li>
			<li>
				Under API (Enable OAuth Settings):
				<p>
					<ol>
						<li>Select "Enable Oauth Settings".</li>
						<li>Enter this site's URL. This should exactly match the <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">Site Address field in Settings > General</a> and must use HTTPS.</li>
						<li>Under "Selected Oauth Scopes", add "Full access" and "Perform requests on your behalf at any time".</li>
					</ol>
				</p>
			</li>
			<li>Save the Connected App. The app will be assigned a Consumer Key and Consumer Secret. Add those here.</li>
		</ol>

		<form action="options.php" method="post">
			<?php
				settings_fields( self::$slug );
				do_settings_sections( self::$slug );
				submit_button( 'Authorize with Salesforce' );
			?>
		</form>
	</div><!-- .chief-sfc-settings -->
</div><!-- .wrap -->
