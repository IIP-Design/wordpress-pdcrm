<?php
/**
 * Display the field matching portion of the edit form page.
 */
?>

<tr>
	<th></th>
	<td><a class="button-secondary" href="<?php echo $this->get_clear_cache_url(); ?>">Clear cache</a></td>
</tr>

<?php $sf_fields = $this->form->get_object_fields( $object );
if ( $sf_fields ) {
	foreach( $sf_fields as $sf_field_name => $sf_field ) {
		$sf_field = wp_parse_args( $sf_field, array(
			'label'    => '',
			'required' => false
		) ); ?>

		<tr>
			<th>
				<?php echo esc_html( $sf_field['label'] ); ?>
			</th>
			<td>

				<select name="field[<?php echo esc_attr( $sf_field_name ); ?>]">
					<option value="">&mdash; Select field &mdash;</option>

					<?php foreach( $this->form->fields as $field ) {
						$field = wp_parse_args( $field, array(
							'name' => '',
							'label' => ''
						) ); ?>

						<option
							value="<?php echo esc_attr( $field['name'] ); ?>"
							<?php selected( $field['name'], $this->form->values['fields'][$sf_field_name]); ?>>
							<?php echo esc_html( $field['label'] ); ?>
						</option>

					<?php } ?>

				</select>

				<?php if ( $sf_field['required'] ) { ?>
					<span class="required">required&nbsp;by&nbsp;Salesforce</span>
				<?php } ?>

			</td>
		</tr>

	<?php }
}