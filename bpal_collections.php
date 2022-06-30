<?php

	$path = preg_replace('/wp-content.*$/','',__DIR__);
	require_once($path."wp-load.php");
	require_once("BPalGateway.php");		

	if(isset($_POST['BPal_payments_gateway']))
	{
		$serviceId = sanitize_text_field($_POST['service_id']);
		$phone = sanitize_text_field($_POST['phone']);
		$amount = sanitize_text_field($_POST['amount']);
		$reason = sanitize_text_field($_POST['reason']);
		$name = sanitize_text_field($_POST['name']);
		$tranId = rand(1111,9999);
		$api_key = sanitize_text_field($_POST['api_key']);
		$redirect_url = sanitize_text_field($_POST['redirect_url']);

		$bpal = new BPalGateway();	
		$result = $bpal->CollectFunds($serviceId, $tranId, $amount, $phone, $name, $reason,$api_key);						
			
		if($result->status == 1100 || $result->status == 1000){
		// if(true){
			$message='Request submited. Please confirm on your phone to complete the transaction.';
		}else{
			$message = "";
			$message .= $result->status_desc;
			$message .= " Check details and try again.";
			// $message ='Try again';			
		}	
		// wp_redirect($redirect_url);
		
		wp_redirect($redirect_url.'&message='.$message);
		
	}


	if(isset($_POST['netwrk_ref'])){

		$_POST = file_get_contents('php://input');
	
		$bpal_post = $_POST;
		$path = plugin_dir_url( __FILE__ ).'/bpal_woocomerce_plugin.php';
		
		$ch = curl_init($path);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $bpal_post);
		
		// execute!
		// $response = curl_exec($ch);

		curl_exec($ch);
		// close the connection, release resources used
		curl_close($ch);
		
		// do anything you want with your response
		// var_dump($response);
		// var_dump($bpal_post);
	}