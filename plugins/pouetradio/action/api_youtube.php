<?php
/*
 * Plugin PouetRadio
 * (c) 2017
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_api_youtube_dist() {

	$arg = _request('arg');
	$arg = explode('/', $arg, 2);

	// enlever une fausse extension .mp4
	$video_id = preg_replace(',\.\w+$,', '', end($arg));

	if (!function_exists('calculer_cle_action')) {
		include_spip("inc/securiser_action");
	}
	$cle = calculer_cle_action("youtube:" . $video_id);

	if ($cle === reset($arg)
	  and $getvideo = find_in_path('lib/yt/getvideo.php')) {

		$getvideo = realpath($getvideo);
		$dir = dirname($getvideo);
		$_GET['videoid'] = $video_id;
		$_GET['format'] = 'ipad';

		chdir($dir);
		include_once $getvideo;

	}

}