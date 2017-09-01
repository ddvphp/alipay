<?php
namespace DdvPhp\Alipay;
class Gateway {
	public function verifygw($is_sign_success) {
		$biz_content = HttpRequest::getRequest ( "biz_content" );
		

		$xml = simplexml_load_string ( $biz_content );
		// print_r($xml);
		$EventType = ( string ) $xml->EventType;
		// echo $EventType;
		if ($EventType == "verifygw") {
			require 'config.php';
			require_once 'AopSdk.php';
			require_once 'function.inc.php';
			$as = new AopClient();
			$as->rsaPrivateKey=$config['merchant_private_key'];
			// global $config;
			// print_r ( $config );
			if ($is_sign_success) {
				$response_xml = "<success>true</success><biz_content>" . $config ['merchant_public_key'] . "</biz_content>";

			} else { // echo $response_xml;
				$response_xml = "<success>false</success><error_code>VERIFY_FAILED</error_code><biz_content>" . $config ['merchant_public_key'] . "</biz_content>";
			}
			
			$mysign=$as->alonersaSign($response_xml,$config['merchant_private_key'],$config['sign_type']);
			$return_xml = "<?xml version=\"1.0\" encoding=\"".$config['charset']."\"?><alipay><response>".$response_xml."</response><sign>".$mysign."</sign><sign_type>".$config['sign_type']."</sign_type></alipay>";

			writeLog ( "response_xml: " . $return_xml );
			echo $return_xml;
			exit ();
		}
	}
}