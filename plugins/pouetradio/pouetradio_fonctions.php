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
function pouetradio_make_content_playable($content, $tags) {
	if (!function_exists('extraire_balises')) {
		include_spip('inc/filtres');
	}
	if (!function_exists('pouetradio_is_sound_playable')) {
		include_spip('inc/pouetradio');
	}

	$links = extraire_balises($tags, 'a');
	foreach ($links as $link) {
		if ($playable_link = pouetradio_is_sound_playable($link)) {

			$content = str_replace($link, $playable_link, $content);
		}
	}

	return $content;
}


/**
 * Ajouter la tache cron pour tweeter les articles post-dates, chaque heure
 * @param $taches_generales
 * @return mixed
 */
function pouetradio_taches_generales_cron($taches_generales){

	$taches_generales['pouetradio_followback'] = 3333;

	return $taches_generales;
}


/**
 * Chaque syndication invalide le cache
 */
function pouetradio_post_syndication($flux) {

	if ($flux['args']['ajout']
		and isset($flux['data']['raw_data']) and $flux['data']['raw_data']
	  and isset($flux['data']['raw_methode']) and $flux['data']['raw_methode']=='mastodon') {

		$raw = json_decode($flux['data']['raw_data'], true);
		include_spip('inc/mastodon');

		$account = mastodon_url2account($raw['account']['url']);
		spip_log("pouet_post_syndication : follow $account", 'pouetradio');
		mastodon_follow_if_not_already($account, array());

	}

	if ($flux['args']['table'] == 'spip_syndic_articles'
	  and $id_syndic_article = $flux['args']['id_objet']) {

		$item = sql_fetsel('*', 'spip_syndic_articles', 'id_syndic_article=' . intval($id_syndic_article));
		if (!function_exists('pouetradio_is_sound_playable')) {
			include_spip('inc/pouetradio');
		}
		if (pouetradio_is_pouet_playable($item)) {
			sql_updateq('spip_syndic_articles', array('playable'=>1), 'id_syndic_article=' . intval($item['id_syndic_article']));
		}
	}

	return $flux;
}