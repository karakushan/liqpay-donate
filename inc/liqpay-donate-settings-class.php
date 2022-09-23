<?php
/**
 * @class LiqPayDonateSettings 
 */

class LiqPayDonateSettings {
	private $liqpay_donate_settings_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'liqpay_donate_settings_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'liqpay_donate_settings_page_init' ) );
	}

	public function liqpay_donate_settings_add_plugin_page() {
		add_options_page(
			'LiqPay Donate Settings', // page_title
			'LiqPay Donate Settings', // menu_title
			'manage_options', // capability
			'liqpay-donate-settings', // menu_slug
			array( $this, 'liqpay_donate_settings_create_admin_page' ) // function
		);
	}

	public function liqpay_donate_settings_create_admin_page() {
		$this->liqpay_donate_settings_options = get_option( 'liqpay_donate_settings_option_name' ); ?>

		<div class="wrap">
			<h2>LiqPay Donate Settings</h2>
			<p></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'liqpay_donate_settings_option_group' );
					do_settings_sections( 'liqpay-donate-settings-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function liqpay_donate_settings_page_init() {
		register_setting(
			'liqpay_donate_settings_option_group', // option_group
			'liqpay_donate_settings_option_name', // option_name
			array( $this, 'liqpay_donate_settings_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'liqpay_donate_settings_setting_section', // id
			'Settings', // title
			array( $this, 'liqpay_donate_settings_section_info' ), // callback
			'liqpay-donate-settings-admin' // page
		);

		add_settings_field(
			'public_key_0', // id
			'Public Key', // title
			array( $this, 'public_key_0_callback' ), // callback
			'liqpay-donate-settings-admin', // page
			'liqpay_donate_settings_setting_section' // section
		);

		add_settings_field(
			'private_key_1', // id
			'Private Key', // title
			array( $this, 'private_key_1_callback' ), // callback
			'liqpay-donate-settings-admin', // page
			'liqpay_donate_settings_setting_section' // section
		);

		add_settings_field(
			'payment_description', // id
			'Payment Description', // title
			array( $this, 'payment_description_callback' ), // callback
			'liqpay-donate-settings-admin', // page
			'liqpay_donate_settings_setting_section' // section
		);
	}

	public function liqpay_donate_settings_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['public_key_0'] ) ) {
			$sanitary_values['public_key_0'] = sanitize_text_field( $input['public_key_0'] );
		}

		if ( isset( $input['private_key_1'] ) ) {
			$sanitary_values['private_key_1'] = sanitize_text_field( $input['private_key_1'] );
		}	

		if ( isset( $input['payment_description'] ) ) {
			$sanitary_values['payment_description'] = sanitize_text_field( $input['payment_description'] );
		}

		return $sanitary_values;
	}

	public function liqpay_donate_settings_section_info() {
		
	}

	public function public_key_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="liqpay_donate_settings_option_name[public_key_0]" id="public_key_0" value="%s">',
			isset( $this->liqpay_donate_settings_options['public_key_0'] ) ? esc_attr( $this->liqpay_donate_settings_options['public_key_0']) : ''
		);
	}

	public function private_key_1_callback() {
		printf(
			'<input class="regular-text" type="text" name="liqpay_donate_settings_option_name[private_key_1]" id="private_key_1" value="%s">',
			isset( $this->liqpay_donate_settings_options['private_key_1'] ) ? esc_attr( $this->liqpay_donate_settings_options['private_key_1']) : ''
		);
	}	


	public function payment_description_callback() {
		printf(
			'<input class="regular-text" type="text" name="liqpay_donate_settings_option_name[payment_description]" id="payment_description" value="%s">',
			isset( $this->liqpay_donate_settings_options['payment_description'] ) ? esc_attr( $this->liqpay_donate_settings_options['payment_description']) : ''
		);
	}

}
if ( is_admin() )
	$liqpay_donate_settings = new LiqPayDonateSettings();

/* 
 * Retrieve this value with:
 * $liqpay_donate_settings_options = get_option( 'liqpay_donate_settings_option_name' ); // Array of All Options
 * $public_key_0 = $liqpay_donate_settings_options['public_key_0']; // Public Key
 * $private_key_1 = $liqpay_donate_settings_options['private_key_1']; // Private Key
 */
