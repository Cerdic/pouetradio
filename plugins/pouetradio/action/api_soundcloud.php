<?php
/*
 * Plugin PouetRadio
 * (c) 2017
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_api_soundcloud_dist() {

	$arg = _request('arg');
	$arg = explode('/', $arg);

	// enlever une fausse extension .mp3
	$soundcloud_url = preg_replace(',\.\w+$,', '', end($arg));
	$soundcloud_url = base64_decode($soundcloud_url);
	if (!function_exists('calculer_cle_action')) {
		include_spip("inc/securiser_action");
	}

	$cle = calculer_cle_action("soundcloud:" . $soundcloud_url);
	if ($cle === reset($arg) and defined('_SOUNDCLOUD_CLIENT_ID')) {
		$api_url = "http://api.soundcloud.com/resolve?client_id="._SOUNDCLOUD_CLIENT_ID;
		$api_url = parametre_url($api_url, "url", $soundcloud_url);
		include_spip('inc/distant');
		$res = recuperer_url($api_url, array('methode' => 'HEAD', 'follow_location' => 0));
		if ($res and $res['location']) {
			$tracks_url = $res['location'];
			$stream_url = str_replace("?", "/stream?", $tracks_url);
			$res = recuperer_url($stream_url, array('methode' => 'HEAD', 'follow_location' => 0));
			$media_url = $res['location'];
			if ($res and $res['location']) {
				include_spip('inc/headers');
				redirige_par_entete($media_url);
			}
		}
	}


}