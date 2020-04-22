<?php
/**
 * Display the status field.
 *
 * Available vars: $status.
 */
if ( $status['wp_error'] || !is_object( $status['body'] ) ) { ?>

	<p class="unauthorized">Not authorized.</p>

<?php } else { ?>

	<p class="authorized">Authorized with Salesforce</p>

	<?php
		$auth = get_option( CHIEF_SFC_Authorization::$tokens_setting );
		$auth = wp_parse_args( $auth, array(
			'issued_at'      => 0,
			'original_issue' => 0
		) );
		$original_issue = self::get_readable_time( $auth['original_issue'] );
	?>

	<p>Issued at <?php echo esc_html( $original_issue ); ?></p>

	<?php if ( $auth['issued_at'] !== $auth['original_issue'] ) {
		$issued_at = self::get_readable_time( $auth['issued_at'] ); ?>

		<p>Last refreshed at <?php echo esc_html( $issued_at ); ?></p>

	<?php } ?>

	<p class="revoke-container">
		<?php submit_button( esc_attr( 'Revoke Authorization' ), 'secondary', 'revoke', false ); ?>
	</p>

<?php } ?>