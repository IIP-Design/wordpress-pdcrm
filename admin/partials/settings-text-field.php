<?php
/**
 * Display a text field.
 *
 * Available vars: $args, $value.
 */
?>
<input
	id="<?php echo esc_attr( $args['id'] ); ?>"
	type="text"
	name="<?php echo self::$client_setting; ?>[<?php echo esc_attr( $args['name'] ); ?>]"
	value="<?php echo esc_attr( $value ); ?>"
	class="widefat"
/>
