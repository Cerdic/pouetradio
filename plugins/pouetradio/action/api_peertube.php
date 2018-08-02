<?php
/*
 * Plugin PouetRadio
 * (c) 2017
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_api_peertube_dist() {

	$arg = _request('arg');
	$arg = explode('/', $arg, 2);


	// enlever une fausse extension .mp4
	$peertube_url = preg_replace(',\.\w+$,', '', end($arg));
	$peertube_url = base64_decode($peertube_url);

	if (!function_exists('calculer_cle_action')) {
		include_spip("inc/securiser_action");
	}
	$cle = calculer_cle_action("peertube:" . $peertube_url);

	if ($cle === reset($arg)) {

		// $endpoint = "https://" . $parts['host'] . '/api/v1/';
		// include_spip('inc/distant');
		// $res = recuperer_url($endpoint . "videos/$video_id");
		// puis regarder dans file
		// https://xxxx/static/webseed/a5c7839a-3b09-474c-a7bb-825152bcbf9d-360.mp4"
		// https://xxxx/download/videos/a5c7839a-3b09-474c-a7bb-825152bcbf9d-360.mp4"
		// https://xxxx/static/thumbnails/a5c7839a-3b09-474c-a7bb-825152bcbf9d.jpg
		$src = explode('/videos/watch/', $peertube_url, 2);
		$host = reset($src);
		$video_id = end($src);
		$api_url = $host . '/api/v1/videos/' . $video_id;
		//var_dump($api_url);

		include_spip('inc/distant');
		$res = recuperer_url_cache($api_url);
		if ($res and $res['page']){
			$json = json_decode($res['page'], true);
			if (isset($json['files'])
			  and $file_lowres = end($json['files'])
				and $src = $file_lowres['fileUrl']) {

				include_spip('inc/headers');
				redirige_par_entete($src);
			}
		}
	}

}