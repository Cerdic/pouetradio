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
	$json = "{}";
	if ($cle === reset($arg)) {
		//var_dump($api_url);

		include_spip('inc/distant');
		$res = recuperer_url_cache($api_url);
		if ($res){
			$json = $res['page'];
		}
	}

	include_spip('inc/actions');
	ajax_retour($json,"application/json");

}