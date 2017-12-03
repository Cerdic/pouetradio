<?php

define('_POUET_RADIO_VERSION','0.7.2');
define('_DIR_PLUGINS_SUPPL',_DIR_RACINE . 'squelettes/plugins/');


$GLOBALS['spip_pipeline']['post_syndication'] .= '|pouet_post_syndication';

/**
 * Chaque syndication invalide le cache
 */
function pouet_post_syndication($flux) {
	static $reloaded = false;
	include_spip('inc/invalideur');
	suivre_invalideur('syndication');

	// on a de la syndication, recalculer la home a la fin pour charger les oembed
	if (!$reloaded) {
		register_shutdown_function('pouet_reload_home');
	}

	return $flux;
}

/**
 * Calculer content/home pour charger les oembed
 */
function pouet_reload_home() {
	chdir(_ROOT_CWD);
	recuperer_fond('content/home',array());
}


define('_PERIODE_SYNDICATION', 3);
