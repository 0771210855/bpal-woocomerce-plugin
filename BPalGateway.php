<?php

class BPalGateway{
	var $url = 'https://api.bpalcloud.com';
	// var $api_key = '';
		
	public function CollectFunds($serviceId, $tranId, $amount, $phone, $name, $reason,$api_key,){

		$endPoint = $this->url.'/collections/create';
		$payload = ['request'=>[ 
			'api_key' => $api_key, 
			'vendor_tran_ref' => $tranId, 
			'service_id' => $serviceId, 
			'post_data'=>[
				['key'=>'amount', 'value'=>$amount],
				['key'=>'telephone', 'value'=>$phone],
				['key'=>'name', 'value'=>$name],
				['key'=>'reason', 'value'=>$reason]
			]
		]];  
		return $this->postJson($endPoint, $payload);
	}

	public function GiveFunds($serviceId, $tranId, $amount, $phone, $name, $reason){
		$endPoint = $this->url.'/payments/create';
		$payload = ['request'=>[ 
			'api_key' => $this->api_key, 
			'vendor_tran_ref' => $tranId, 
			'service_id' => $serviceId, 
			'post_data'=>[
				['key'=>'amount', 'value'=>$amount],
				['key'=>'telephone', 'value'=>$phone],
				['key'=>'name', 'value'=>$name],
				['key'=>'reason', 'value'=>$reason]
			]
		]]; 
		return $this->postJson($endPoint, $payload);
	}	
	
	function postJson($endPoint, $payload){
		//Initiate cURL.
		$ch = curl_init($endPoint);		 
		//Encode the array into JSON.
		$jsonDataEncoded = json_encode($payload);		 
		//Tell cURL that we want to send a POST request.
		curl_setopt($ch, CURLOPT_POST, 1);		 
		//Attach our encoded JSON string to the POST fields.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);		 
		//Set the content type to application/json
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 		
		//return response instead of outputting
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		 $result = curl_exec($ch);		  
		 //close connection 
		 curl_close($ch); 	
		 
		 $result = json_decode($result);		 
		 return $result->response_data;
	}
}	