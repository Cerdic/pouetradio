<?php
/*
 * Plugin PouetRadio
 * (c) 2017
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Determiner si un pouet est playable, c'est a dire si il contient au moins un lien de jouable
 * @param array $item
 * @return bool
 */
function pouetradio_is_pouet_playable($item) {
	if (!function_exists('extraire_balises')) {
		include_spip('inc/filtres');
	}

	$links = extraire_balises($item['tags'], 'a');
	foreach ($links as $link) {
		if (pouetradio_is_sound_playable($link, true)) {
			return true;
		}
	}

	return false;
}


/**
 * Determiner si un lien est jouable et le mets en forme pour la lecture sur le front
 * @param string $link
 * @param bool $fast
 *   si true on se contente de retourner un flag sans preparer le son
 * @return bool
 */
function pouetradio_is_sound_playable($link, $fast = false) {

	// c'est un son, c'est non
	if ($type = extraire_attribut($link, 'type')
	  and strncmp($type,'image/', 6) == 0) {
		return false;
	}
	$texte = trim(strip_tags($link));
	// c'est un tag, c'est non
	if (strncmp($texte, '#', 1) == 0) {
		return false;
	}

	$href = extraire_attribut($link, 'href');
	$parts = parse_url($href);

	$functions_detection = array(
		'generic' => 'pouetradio_is_playable_generic',
		'peertube' => 'pouetradio_is_playable_peertube',
	);
	// est-ce qu'on a une fonction pour le domaine ou son parent ?
	$domain = explode('.', $parts['host']);
	while (count($domain)>1) {
		$d = preg_replace(',\W+,', '_', implode('_', $domain));
		$functions_detection[$d] = "pouetradio_is_playable_" . $d;
		array_shift($domain);
	}

	$functions_detection = array_reverse($functions_detection);
	foreach ($functions_detection as $type => $function_detection) {
		if (function_exists($function_detection)
		  and $playable_link = $function_detection($link, $fast, $parts)) {
			//var_dump("playable :$function_detection: $href");
			if (!$fast) {
				$t = explode('>', $playable_link);
				$class = extraire_attribut($t[0] . '>', 'class');
				$class = trim("$class playable $type");
				$t[0] = rtrim(inserer_attribut($t[0] . '>', 'class', $class), '>');
				$playable_link = implode('>', $t);
			}
			return $playable_link;
		}
	}

	// on a rien trouve, ce pouet n'est pas playable
	return false;
}


function pouetradio_is_playable_generic($link, $fast, $parts) {
	// TODO : les mp4 en lien direct ?
	return false;
}


/**
 * peertube dont on ne connait pas les noms d'instance
 * @param $link
 * @param $fast
 * @param $parts
 * @return bool
 */
function pouetradio_is_playable_peertube($link, $fast, $parts) {
	if (!preg_match(',^/videos/watch/([0-9a-f-]+)$,', $parts['path'], $m)) {
		return false;
	}
	if ($fast) {
		return true;
	}

	$video_id = $m[1];

	// TODO : utiliser l'API peertube pour recuperer la source mp4 sur une des instances peer ?
	// mais c'est lent si le serveur distant rame
	// --> on fait le remaping ici en dur et on laisse le JS charger ou gerer le fail si ca vient pas

	// $endpoint = "https://" . $parts['host'] . '/api/v1/';
	// include_spip('inc/distant');
	// $res = recuperer_url($endpoint . "videos/$video_id");
	// puis regarder dans file
	// https://xxxx/static/webseed/a5c7839a-3b09-474c-a7bb-825152bcbf9d-360.mp4"
	// https://xxxx/download/videos/a5c7839a-3b09-474c-a7bb-825152bcbf9d-360.mp4"
	// https://xxxx/static/thumbnails/a5c7839a-3b09-474c-a7bb-825152bcbf9d.jpg
	$src = extraire_attribut($link, 'href');
	$src = explode('/videos/watch/', $src);
	$host = reset($src);
	$src =  $host . '/static/webseed/' . $video_id . '-240.mp4';
	$thumb = $host . '/static/thumbnails/' . $video_id . '.jpg';

	$api_url = $host . '/api/v1/videos/' . $video_id;
	if (!function_exists('calculer_cle_action')) {
		include_spip("inc/securiser_action");
	}
	$cle = calculer_cle_action("peertube:" . $api_url);

	$api_url = url_absolue(_DIR_RACINE . "peertube.api/$cle/".base64_encode($api_url));
	// todo : lancer un curl async de mise en cache

	$link = inserer_attribut($link, 'data-api', $api_url);
	// en fallback si pas de reponse de l'api
	$link = inserer_attribut($link, 'data-src', $src);
	//$link = inserer_attribut($link, 'data-thumb', $thumb);
	return $link;
}


/**
 * youtu.be
 * @param $link
 * @param $fast
 * @param $parts
 * @return bool|string
 */
function pouetradio_is_playable_youtu_be($link, $fast, $parts) {
	if ($fast) {
		return true;
	}
	// on expand le lien au passage, ca jouera plus vite
	$src = "https://www.youtube.com/watch?v=" . ltrim($parts['path'],'/');
	$link = inserer_attribut($link, 'data-src', $src);
	return $link;
}

/**
 * youtube.com
 * @param $link
 * @param $fast
 * @param $parts
 * @return bool|string
 */
function pouetradio_is_playable_youtube_com($link, $fast, $parts) {
	if ($parts['path'] !== '/watch') {
		return false;
	}
	if (!$v = parametre_url($parts['path'] . '?' . $parts['query'], 'v')) {
		return false;
	}
	if ($fast) {
		return true;
	}
	// on expand le lien au passage, ca jouera plus vite
	$src = "https://www.youtube.com/watch?v=" . $v;
	$link = inserer_attribut($link, 'data-src', $src);
	return $link;
}

/**
 * hooktube.com qui est un simple wrapper anonymisateur de youtube.com
 * @param $link
 * @param $fast
 * @param $parts
 * @return bool|string
 */
function pouetradio_is_playable_hooktube_com($link, $fast, $parts) {
	return pouetradio_is_playable_youtube_com($link, $fast, $parts);
}


/**
 * dai.ly
 * @param $link
 * @param $fast
 * @param $parts
 * @return bool|string
 */
function pouetradio_is_playable_dai_ly($link, $fast, $parts) {
	if ($fast) {
		return true;
	}
	// on expand le lien au passage, ca jouera plus vite
	$src = "https://www.dailymotion.com/video/" . ltrim($parts['path'],'/');
	$link = inserer_attribut($link, 'data-src', $src);
	return $link;
}


/**
 * dailymotion.com
 * @param $link
 * @param $fast
 * @param $parts
 * @return bool|string
 */
function pouetradio_is_playable_dailymotion_com($link, $fast, $parts) {
	if (strncmp($parts['path'], '/video/', 7) !== 0) {
		return false;
	}
	if ($fast) {
		return true;
	}
	$path = explode('_', $parts['path']);
	$path = ltrim(reset($path), '/');
	// on expand le lien au passage, ca jouera plus vite
	$src = "https://www.dailymotion.com/" . $path;
	$link = inserer_attribut($link, 'data-src', $src);
	return $link;
}

/**
 * vimeo.com
 * @param $link
 * @param $fast
 * @param $parts
 * @return bool|string
 */
function pouetradio_is_playable_vimeo_com($link, $fast, $parts) {

	//https://vimeo.com/217775903
	if (!preg_match(',^/\d+$,', $parts['path'])) {
		return false;
	}
	if ($fast) {
		return true;
	}
	// on expand le lien au passage, ca jouera plus vite
	$src = "https://vimeo.com/" . ltrim($parts['path'], '/');
	$link = inserer_attribut($link, 'data-src', $src);
	return $link;
}