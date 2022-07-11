<?php
/**
 * 
 * Plugin Name: BPal Cloud Gateway
 * Author: BPal Technologies
 * Author URI: http://bpalcloud.com
 * Version: 1.0
 * Description: Payments gateway .
 *
 *  text-domain: bpal
 * 
 * Class WC_BPal_payments_gateway file.
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;
}
wp_enqueue_style('styles', plugins_url( 'styles.css', __FILE__ ), array(),'all');
add_filter( 'woocommerce_payment_gateways', 'wmm_bpal_gateway' );
function wmm_bpal_gateway( $methods ) {
	$methods[] = 'WC_BPal_payments_gateway';
	return $methods;
}

// inserts class gateway
function woocommerce_bpal_init() {
	if (!class_exists('WC_BPal_payments_gateway')) {

		class WC_BPal_payments_gateway extends WC_Payment_Gateway {
		function __construct() {
			$this->id = "woo_bpal_payment";
			$this->method_title = __( "bpal_woo_mobile_money", 'bpal_payments' );
			$this->method_description = __( "bpal_woo_mobile_money - extends woocommerce to allow mtn and airtel mobile money payments", 'bpal_payments' );
			$this->title = __( "bpal_woo_mobile_money", 'bpal_payments' );
			// $this->icon = plugin_dir_url(__FILE__) . 'favicon.png';
			$this->has_fields = true;
			$this->init_form_fields();
			$this->init_settings();
			foreach ( $this->settings as $setting_key => $value ) {
				$this->$setting_key = $value;
			}
			if ( is_admin() ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			}
			add_action('woocommerce_receipt_woo_bpal_payment', array($this, 'bpal_receipt_page'));
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'		=> __( 'Enable / Disable', 'bpal_payments' ),
					'label'		=> __( 'Enable this payment gateway', 'bpal_payments' ),
					'type'		=> 'checkbox',
					'default'	=> 'no',
				),
				'title' => array(
					'title'		=> __( 'Title', 'bpal_payments' ),
					'type'		=> 'text',
					'desc_tip'	=> __( 'Payment title the customer will see during the checkout process.', 'bpal_payments' ),
					'default'	=> __( 'Pay with BPal payments Gateway', 'bpal_payments' ),
				),
				'description' => array(
					'title'		=> __( 'Description', 'bpal_payments' ),
					'type'		=> 'textarea',
					'desc_tip'	=> __( 'Payment description the customer will see during the checkout process.', 'bpal_payments' ),
					'default'	=> __( 'You will be redirected to bpal to complete the payment', 'bpal_payments' ),
					'css'		=> 'max-width:350px;'
				),
				'api_key' => array(
					'title'		=> __( 'API key', 'bpal_payments' ),
					'type'		=> 'text',
					'desc_tip'	=> __( 'This is the consumer Key provided by BPal client BPal technologies', 'bpal_payments'),
				)

			);
		}

		function process_payment( $order_id ) {
			global $woocommerce;
			$order = new WC_Order( $order_id );

			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
			);
		}

		function bpal_receipt_page( $order ) {
			echo $this->generate_bpal_form( $order );

		}

		/**
		 * 
		 * Register and enqueue a custom stylesheet in the WordPress admin.
		 * 
		 */ 
		function generate_bpal_form($order_id) {

			global $woocommerce;
			$order = wc_get_order( $order_id );
			$items = $order->get_items();

			//items 1

			$i= 0;
			$item_array=array();

			foreach($items as  $item){
				if( $item['type']=='line_item' ){
					$item_array[$i]['name']=$item['name'];
					$product = new WC_Product($item['product_id'] );
					$item_array[$i]['price']=$product->price;
					$item_array[$i]['qty']=$item['qty'];
					if($product->weight){
						$item_array[$i]['weight']=$product->weight;
					} else {
						$item_array[$i]['weight']=0;
					}
					if($product->sku){
						$item_array[$i]['pid']=$product->sku;
					} else {
						$item_array[$i]['pid']=$item['product_id'];
					}
					$i++;
				}
			}

			$amount 	= intval( $order->get_total());
			$buyer_name = $order->get_billing_first_name();
			$api_key 	= $this->api_key;

			$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$actual_link = explode("&message", $actual_link)[0];


			/**
			 * 
			 * Checkout form for inputing charges details
			 * 
			 */ 
			$output ="";

			$output.= "<div id='woocommerce_wmm_wrap'>
						<div class='bpal-container'>
						<form action='".plugin_dir_url( __FILE__ ) . "./bpal_collections.php' method='post'>
						<div class='logo'><img src='".plugin_dir_url(__FILE__)."favicon.png' style='width:60px;' alt='BPal'><div>";
				
			// $path = plugin_dir_url( __FILE__ );
			// echo $path;
			// echo $order_id;
			// exit();
			if(isset($_GET['message'])){

			

			

				if($_GET['message'] == "Request submited. Please confirm on your phone to complete the transaction."){
					
					$output.="<div > <p class='message-sucess'>".$_GET['message']."</p> <div>";
					
					if($order_id > 0){
						$order = new WC_Order( $order_id );
						$order->update_status('Processing', __( 'Processing payment', 'woocommerce' ));  
						// $order->reduce_order_stock();
						wc()->cart->empty_cart();
						// $order->payment_complete();
					}
				}else{
					$output.="<div > <p class='message-fail'>".$_GET['message']."</p> <div>";
				}							
			}		

			$output .= "
						<p>
						<label for='phone'>Phone Number for payment charges</label> <br >
						<input type='tel' name='phone' required style='width:250px' placeholder='07...'>
						<p>
						<input type='hidden' name='redirect_url' value='".$actual_link."'>
						<input type='hidden' name='amount' value='".$amount."'>
						<input type='hidden' name='order_id' value='".$order_id."'>
						<input type='hidden' name='name' value='".$buyer_name."'>
						<input type='hidden' name='reason' value='test'>
						<input type='hidden' name='api_key' value='".$api_key."'>
						<input type='submit' name='BPal_payments_gateway' value='Place Order'/>
						<div class='footer-txt'> <p>powerd by <a href='http://bpaltech.com' target='_blank' >BPal Technologies<a><p></div>
					</form>
					</div>
				</div>";

			return $output;

			}
		}
	}

}



add_action('woocommerce_init', 'woocommerce_bpal_init');


if (isset($_POST)){
	add_action('init','woo_check_bpal_response');
	
	function woo_check_bpal_response(){
		$bpal_post = json_decode(file_get_contents('php://input'), true);

		if(isset($bpal_post['network_ref'])){			
		
			$network_ref = $bpal_post['network_ref'];
			$vendor_tran_ref = $bpal_post["vendor_tran_ref"];
			$collection = $bpal_post["collection_id"];
			$status = $bpal_post["status"];
			$status_desc = $bpal_post["status_desc"];

			if($status == 1000 ){
				$order_id =  $vendor_tran_ref;
				if($order_id > 0){
					$order = new WC_Order( $order_id );
					$order->update_status('completed', __( 'Completed payment', 'woocommerce' ));  
					// $order->reduce_order_stock();
					// wc()->cart->empty_cart();
					$order->payment_complete();
					
				}
			}

		}
	}
}