<?php
/**
 * Abstract settings class. This handles the oddities of the WordPress Settings API so
 * our main class doesn't have to.
 */
abstract class CHIEF_SFC_Settings_Abstract {

	public $parent;
	public $slug;
	public $page_title;
	public $menu_title;
	public $intro;
	public $setting;
	public $sections;
	public $fields;
	public $values;

	/**
	 * Hook in our methods to the WordPress to make everything work.
	 */
	public function add_actions() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_sections' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'register_fields' ) );
	}

	/**
	 * Register a submenu page.
	 */
	public function register_page() {
		add_submenu_page( $this->parent, $this->page_title, $this->menu_title, 'manage_options', $this->slug, array( $this, 'view_page' ) );
	}

	/**
	 * Register this page's settings.
	 *
	 * The Settings API is vastly simplified when only one setting group and only
	 * one setting record is stored per page, so that's the approach we take here.
	 * It also allows us to better automate sanitization.
	 */
	public function register_settings() {
		register_setting( $this->slug, $this->setting, array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Register sections within the settings page. These are for arbitrary grouping.
	 * If you don't register any, a 'default' section will be registered.
	 */
	public function register_sections() {
		if ( empty( $this->sections ) ) {
			$this->sections = array( 'default' => '' );
		}
		foreach( $this->sections as $slug => $section ) {
			$section = wp_parse_args( $section, array(
				'title'    => '',
				'callback' => ''
			) );
			$func = array( $this, $section['callback'] );
			if ( !is_callable( $func ) ) {
				$func = '';
			}
			add_settings_section( $slug, $section['title'], $func, $this->slug );
		}
	}

	/**
	 * Add actual HTML form fields. This triggers a callback that the child class handles.
	 */
	public function register_fields() {
		foreach( $this->fields as $id => $field ) {
			$field = wp_parse_args( $field, array(
				'title'   => '',
				'section' => 'default',
				'type'    => '',
				'args'    => array()
			) );
			$field['args']['id'] = $id;
			$func = array( $this, 'view_field_' . $field['type'] );
			if ( is_callable( $func ) ) {
				add_settings_field( $id, $field['title'], $func, $this->slug, $field['section'], $field['args'] );
			}
		}
	}

	/**
	 * Add wrapping HTML for the settings page.
	 */
	public function view_page() {
		// get values to have them handy for field callbacks
		$this->values = get_option( $this->setting, array() );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->page_title ); ?></h1>
			<?php if ( isset( $_GET['settings-updated'] ) ) { ?>
			    <div class="updated notice is-dismissible"><p>Settings updated.</p></div>
			<?php } ?>
			<?php echo $this->intro; ?>
			<form action="options.php" method="post">
				<?php
					settings_fields( $this->slug );
					do_settings_sections( $this->slug );
					submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Ensure the child class includes sanitization for its fields.
	 */
	abstract function sanitize_settings( $values );

}