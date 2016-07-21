<?php
/**
 * Display the edit form page.
 */
?>
<div class="wrap">
	<h2>
		<?php echo $this->form->name; ?>
		<a class="page-title-action" href="<?php echo esc_url( $this->list_url ); ?>">View All</a>
	</h2>
	<?php if ( !empty( $_GET['updated'] ) && $_GET['updated'] === 'true' ) { ?>
		<div class="updated notice is-dismissible"><p>Form saved successfully.</p></div>
	<?php } ?>
	<?php if ( !empty( $_GET['skipped'] ) && $_GET['skipped'] === 'save' ) { ?>
		<div class="error notice is-dismissible"><p>Form could not be saved. Please try again.</p></div>
	<?php } ?>
	<?php if ( !empty( $_GET['message'] ) && $_GET['message'] === 'objcache' ) { ?>
		<div class="updated notice is-dismissible"><p>Cache cleared.</p></div>
	<?php } ?>
	<?php if ( !empty( $_GET['skipped'] ) && $_GET['skipped'] === 'objcache' ) { ?>
		<div class="error notice is-dismissible"><p>The cache failed to clear.</p></div>
	<?php } ?>
	<div class="chief-sfc-form-page">
		<form class="chief-sfc-form" action="<?php echo esc_url( $this->url ); ?>" method="post">
			<input type="hidden" name="chief_sfc_action" value="save" />
			<?php wp_nonce_field( 'chief-sfc-form', '_chief_sfc_form' ); ?>
			<div class="metabox-holder">
				<div id="postbox-container-1" class="postbox-container">
					<div class="meta-box-sortables">
						<div class="postbox">
							<h3 class="hndle ui-sortable-handle">
								<span>Save to Salesforce</span>
							</h3>
							<div class="inside">
								<table class="form-table">
									<tr class="object-row">
										<th>Object</th>
										<td>
											<select
												id="chief-sfc-object"
												name="object"
												data-form="<?php echo esc_attr( $this->form->form_id ); ?>"
												data-source="<?php echo esc_attr( $this->form->source ); ?>">
												<option value="">&mdash; Select object &mdash;</option>
												<?php foreach( $this->form->get_objects() as $object ) { ?>
													<option
														value="<?php echo esc_attr( $object ); ?>"
														<?php selected( $this->form->values['object'], $object ); ?>>
														<?php echo esc_html( $object ); ?>
													</option>
												<?php } ?>
											</select>
											<span class="spinner object-spinner"></span>
											<p class="howto">
												Select the Salesforce object in which to save this form's submissions.
											</p>
										</td>
									</tr>
									<?php if ( $this->form->values['object'] ) {
										$this->view_fields( $this->form->values['object'] );
									} ?>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div id="postbox-container-2" class="postbox-container">
					<div class="meta-box-sortables">
						<div class="postbox">
							<h3 class="hndle ui-sortable-handle">
								<span>Status</span>
							</h3>
							<div class="inside">
								<p>Form: <strong><?php echo $this->form->name; ?></strong></p>
								<p>Source: <strong><?php echo $this->form->get_source_label(); ?></strong></p>
								<p>Status: <strong><?php echo $this->form->get_status_label(); ?></strong></p>
							</div>
							<div class="submit-container">
								<?php if ( $this->form->is_enabled() ) { ?>
									<a class="submitdisable" href="<?php echo esc_url( $this->get_disable_url() ); ?>">Disable</a>
								<?php } ?>
								<?php submit_button( 'Save', 'primary', 'submit', false ); ?>
								<div class="spinner submit-spinner"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div><!-- .chief-sfc-form-page -->
</div>