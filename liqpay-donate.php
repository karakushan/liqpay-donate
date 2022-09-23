<?php
/*
Plugin Name: LiqPay Donate
Description: Allows you to receive donations with LiyPay
Version: 1.0
Author: Vitaliy Karakushan
Author URI: https://github.com/karakushan
Text Domain: liqpay-donate
License: GPL2
*/

/*  
Copyright 2012 Metaphor Creations  (email : joe@metaphorcreations.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once "inc/liqpay.php"; 
require_once "inc/liqpay-donate-settings-class.php";

// робимо щось при активації плагіну
register_activation_hook( __FILE__, 'ld_actication_action' );
function ld_actication_action() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'liqpay_payments';

	$sql = "CREATE TABLE $table_name (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`order_id` INT(10) NULL DEFAULT NULL,
	`project_id` BIGINT(20) NULL DEFAULT NULL, 
	`status` VARCHAR(50) NULL DEFAULT NULL,
	`sender_phone` VARCHAR(50) NULL DEFAULT NULL,
	`amount` DECIMAL(20,2) NULL DEFAULT NULL,
	`data` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	PRIMARY KEY (`id`) USING BTREE
) $charset_collate;"; 

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	return maybe_create_table($table_name, $sql);
}

/**
 * 
 */
class LiqPayDonate
{
	private $liqpay;
	private $data;
	private $settings;
	public $payment_description='Пожертвування';


	function __construct()
	{
		$this->settings =get_option( 'liqpay_donate_settings_option_name' );
		if(empty($this->settings['public_key_0']) || empty($this->settings['private_key_1'])) return;

		$this->liqpay =new LiqPay($this->settings['public_key_0'] , $this->settings['private_key_1']);
		$server_url=site_url('/wp-json/liqpay/callback');
		$this->data = [ 
			'order_id'=>uniqid(),
			'amount'=>1, 
			'description'=>$this->settings['payment_description'] ?? $this->payment_description,
			'version'=>3,
			'currency'=>'UAH', 
			'action'=>'pay',
			'server_url'=>$server_url.'?project_id='.(get_the_ID()),  
			'result_url'=>home_url('/')
		]; 

		add_action( 'wp_ajax_lg_refresh_data', [$this,'ld_refresh_form_data'] );
		add_action( 'wp_ajax_nopriv_lg_refresh_data', [$this,'ld_refresh_form_data']);
		add_action('wp_footer', [$this,'ld_footer_scripts']);
		add_action('wp_enqueue_scripts', [$this,'ld_theme_styles']);
		add_action('liqpay_button',[$this,'show_liypay_button']);
		
		// Реєструємо апі урл де будуть оброблятися платежі
		add_action( 'rest_api_init', function () {
		  register_rest_route( 'liqpay', '/callback', array(
		    'methods' => 'POST', 
		    'callback' => [$this, 'ld_liqpay_callback']
		  ) );
		} );

		 
	}


	// Цей хук допоможе вивести кнопку для оплати
	function show_liypay_button(){ 
		echo '<button class="privat_btn btn_pay lg-pay-button" data-project-id='.get_the_ID().'><img src="http://symvolviry.com/wp-content/themes/hello-elementor/assets/images/pr.svg" alt="placeholder+image"></button>';
	}  

	function ld_refresh_form_data(){
		$this->data['amount'] = $_POST['amount'] ?? 0; 
		$this->data['product_name'] = $_POST['project_id'] ?? 0; 
	 	$params    = $this->liqpay->cnb_params($this->data);
		wp_send_json_success( [
			'data' => $this->liqpay->encode_params($params),
			'signature'=>$this->liqpay->cnb_signature($params)
		]);
	} 


	function ld_footer_scripts(){ 
	 ?>

		<div class="ld-modal">
			<div class="ld-modal-content">
				<button class=ld-modal-close>&#10006</button>
				<h4>Вкажіть суму в гривні:</h4>
				<?php echo $this->liqpay->cnb_form($this->data);  ?>
			</div>    
		</div>

	<?php }

	function ld_theme_styles() { 
	   wp_enqueue_script('lg-script', plugins_url( 'assets/ld-script.js' , __FILE__ ),['jquery'], 1.0); 
	   wp_localize_script( 'lg-script', 'lgData',
	            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	   wp_enqueue_style('lg-style', plugins_url( 'assets/ld-style.css' , __FILE__ )); 
	}

	

	// Тут проходить сама обробка відповіді від серверу платіжної системи
	function ld_liqpay_callback(WP_REST_Request $request ){
		global $wpdb;
		
		$data=$this->liqpay->decode_params($request->get_param('data'));
		 if(!empty($data['product_name']) && $data['status']=='success'){
		 	$parse_sum=(preg_replace("/[^0-9]/", "",get_post_meta( (int) $data['product_name'], 'зібрано', 1 )));
		 	$parse_sum=floatval($parse_sum) + floatval($data['amount']);
		 	update_post_meta( (int) $data['product_name'], 'зібрано', number_format($parse_sum,0,',',' ') );
		 }
		 $wpdb->insert($wpdb->prefix . 'liqpay_payments',[
			'order_id'=>$data['order_id'],
			'project_id'=> $data['product_name'] ?? 0, 
			'status'=>$data['status'],
			'sender_phone'=>$data['sender_phone'],
			'amount'=>$data['amount'],
			'data' => json_encode($data)
		 ]);  
	} 
}

new LiqPayDonate();