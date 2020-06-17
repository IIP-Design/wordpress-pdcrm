<div class="wrap">
	<?php if ( $message && !$error ): ?>
		<div class="updated notice is-dismissible"><p><?=$message?></p></div>
	<?php elseif ( $message ): ?>
		<div class="notice notice-error is-dismissible"><p><?=$message?></p></div>
	<?php endif; ?>
	<h2>Error Email</h2>
	<div>Enter an email address to receive notifications whenever an error occurs in sending data to Salesforce.</div>
	<br/>
	<form method="post" action="">
		<label>Email: <input type="email" name="address" value="<?=$error_email?>" /></label>
		<input type="submit" class="button" value="Submit" />
	</form>
</div>