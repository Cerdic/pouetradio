<?php

define('_POUET_RADIO_VERSION','1.4.1');
define('_DIR_PLUGINS_SUPPL',_DIR_RACINE . 'squelettes/plugins/');
define('_PERIODE_SYNDICATION', 3);
define('_YOUTUBE_AUTO_EXPAND_MP4', true);


$GLOBALS['spip_pipeline']['post_syndication'] .= '|pouet_post_syndication';

/**
 * Chaque syndication invalide le cache
 */
function pouet_post_syndication($flux) {
	static $reloaded = false;

	// on a de la syndication, recalculer la home a la fin pour charger les oembed
	if (!$reloaded) {
		register_shutdown_function('pouet_reload_home');
	}

	return $flux;
}

/**
 * Calculer content/home pour generer les vignettes des users
 */
function pouet_reload_home() {
	include_spip('inc/invalideur');
	suivre_invalideur('syndication');
	chdir(_ROOT_CWD);
	recuperer_fond('content/home',array());
}


