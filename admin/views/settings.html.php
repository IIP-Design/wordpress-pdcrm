<div class="wrap">
	<h1><?php echo esc_html( $name ); ?></h1>
	<p>
		These settings link this WordPress site to your Salesforce account.
	</p>
	<p>
		<a href="https://www.webholics.in/knowledgebase/get-client-id-client-secret-salesforce/" target="_blank">Follow these instructions to get your Client ID and Client Secret.</a>
	</p>
	<form action="options.php" method="post">
		<?php settings_fields( $group ); ?>
		<?php do_settings_sections( $section ); ?>
		<?php submit_button( 'Save Settings' ); ?>
	</form>
</div>