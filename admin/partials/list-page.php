<?php
/**
 * HTML structure for the admin list page.
 */
?>
<div class="wrap">
	<h2>Salesforce Form Captures</h2>

	<?php if ( !empty( $_GET['disabled'] ) && $_GET['disabled'] === 'true' ) { ?>
		<div class="updated notice is-dismissible">
			<p>
				Form disabled successfully.
				<?php if ( !empty( $_GET['key'] ) ) {
					$key = sanitize_key( $_GET['key'] ); ?>
					<a href="<?php echo $this->get_undo_url( $key ); ?>">Undo</a>
				<?php } ?>
			</p>
		</div>
	<?php } ?>

	<?php if ( !empty( $_GET['skipped'] ) && $_GET['skipped'] === 'disable' ) { ?>
		<div class="error notice is-dismissible"><p>Form could not be disabled. Please try again.</p></div>
	<?php } ?>

	<?php if ( !empty( $_GET['skipped'] ) && $_GET['skipped'] === 'undo' ) { ?>
		<div class="error notice is-dismissible"><p>The disable could not be undone.</p></div>
	<?php } ?>

	<?php if ( !empty( $_GET['untrashed'] ) && $_GET['untrashed'] === 'true' ) { ?>
		<div class="updated notice is-dismissible"><p>Disable undone.</p></div>
	<?php } ?>

	<div class="chief-sfc-list">
		<?php
			$this->list_table->prepare_items();
			$this->list_table->views();
			$this->list_table->display();
		?>
	</div>
</div>