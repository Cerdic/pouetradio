<?php
/*
 * Plugin PouetRadio
 * (c) 2017
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_api_peertube_dist() {

	$arg = _request('arg');
	$arg = explode('/', $arg);

	$api_url = base64_decode(end($arg));
	if (!function_exists('calculer_cle_action')) {
		include_spip("inc/securiser_action");
	}

	$cle = calculer_cle_action("peertube:" . $api_url);
	if ($cle === reset($arg)) {
		var_dump($api_url);

		if (!function_exists('curl_init')){
			include_spip('inc/distant');
			$res = recuperer_url_cache($api_url);
			var_dump('recuperer_url_cache',$res);
		} else {
			//setting the curl parameters.
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

			//turning off the server and peer verification(TrustManager Concept).
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, false);

			$response = curl_exec($ch);
			$erreur = curl_errno($ch);
			$erreur_msg = curl_error($ch);
			if (!$erreur){
				//closing the curl
				curl_close($ch);
			}
			var_dump('curl',$response);
		}


		var_dump($res);
	}

	die();

}