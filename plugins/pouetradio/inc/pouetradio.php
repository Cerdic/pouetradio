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

	if (!function_exists('calculer_cle_action')) {
		include_spip("inc/securiser_action");
	}
	$src = extraire_attribut($link, 'href');
	$cle = calculer_cle_action("peertube:" . $src);
	$src = url_absolue(_DIR_RACINE . "peertube.api/$cle/".base64_encode($src));
	$link = inserer_attribut($link, 'data-src', $src);
	return $link;
}


/**
 * soundcloud.com
 * @param $link
 * @param $fast
 * @param $parts
 * @return bool|string
 */
function pouetradio_is_playable_soundcloud_com($link, $fast, $parts) {
	if (!defined('_SOUNDCLOUD_CLIENT_ID')) {
		return false;
	}
	if (count(explode('/', trim($parts['path'],'/'))) !== 2) {
		return false;
	}
	if ($fast) {
		return true;
	}

	if (!function_exists('calculer_cle_action')) {
		include_spip("inc/securiser_action");
	}
	$src = extraire_attribut($link, 'href');
	$cle = calculer_cle_action("soundcloud:" . $src);
	$src = url_absolue(_DIR_RACINE . "soundcloud.api/$cle/".base64_encode($src));
	$link = inserer_attribut($link, 'data-src', $src);
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
	if (!defined('_YOUTUBE_AUTO_EXPAND_MP4') or !_YOUTUBE_AUTO_EXPAND_MP4) {
		$src = "https://www.youtube.com/watch?v=" . ltrim($parts['path'],'/');
	}
	else {
		// branchement sur la lib https://github.com/jeckman/YouTube-Downloader qui redirige vers le mp4 final
		$src = url_absolue(_DIR_RACINE . 'youtube.api/'. ltrim($parts['path'],'/') .'.mp4');
	}
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
	if (!defined('_YOUTUBE_AUTO_EXPAND_MP4') or !_YOUTUBE_AUTO_EXPAND_MP4) {
		$src = "https://www.youtube.com/watch?v=" . $v;
	}
	else {
		// branchement sur la lib https://github.com/jeckman/YouTube-Downloader qui redirige vers le mp4 final
		$src = url_absolue(_DIR_RACINE . 'youtube.api/' . $v . '.mp4');
	}
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