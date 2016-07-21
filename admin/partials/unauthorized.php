<?php
/**
 * HTML when the user visits the Form Captures main screen but hasn't authorized the website
 * with Salesforce yet.
 */
?>
<div class="wrap">
	<h2>Salesforce Form Captures</h2>
	<div class="notice-info notice">
		<p>
			Before using this plugin you must authenticate with Salesforce.
			Follow the instructions at <a href="<?php echo esc_url( admin_url( 'admin.php?page=chief-sfc-settings' ) ); ?>">Form Captures > Authorization</a>.
		</p>
	</div>
</div>