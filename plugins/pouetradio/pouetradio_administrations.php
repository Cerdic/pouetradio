<?php
/*
 * Plugin PouetRadio
 * (c) 2017
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * maj dede la table article
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function pouetradio_upgrade($nom_meta_base_version,$version_cible){

	$maj = array();
	$maj['create'] = array(
		array('sql_alter',"TABLE spip_syndic_articles ADD playable TINYINT DEFAULT 0 NOT NULL"),
		array('sql_updateq',"spip_syndic_articles", array('playable'=>-1), "playable=0"),
		array('pouetradio_make_playables'),
	);

	$maj['1.0.0'] = array(
		array('sql_alter',"TABLE spip_syndic_articles ADD playable TINYINT DEFAULT 0 NOT NULL"),
	);
	$maj['1.1.2'] = array(
		array('sql_updateq',"spip_syndic_articles", array('playable'=>-1), "playable=0"),
		array('pouetradio_make_playables'),
	);
	$maj['1.1.4'] = array(
		array('sql_updateq',"spip_syndic_articles", array('playable'=>-1), "tags like '%soundcloud%'"),
		array('pouetradio_make_playables'),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


function pouetradio_make_playables() {
	include_spip('inc/pouetradio');

	$n = sql_countsel('spip_syndic_articles', 'playable=-1');
	spip_log("pouetradio_make_playables : $n restants", 'maj');

	do {
		$items = sql_allfetsel('*', 'spip_syndic_articles', 'playable=-1', '', 'date DESC','0,100');

		if (count($items)) {

			foreach ($items as $item) {
				$set = array();
				if (pouetradio_is_pouet_playable($item)) {
					$set['playable'] = 1;
				}
				else {
					$set['playable'] = 0;
				}
				if ($set) {
					sql_updateq('spip_syndic_articles', $set, 'id_syndic_article=' . intval($item['id_syndic_article']));
				}

				if (time()>_TIME_OUT) {
					return;
				}
			}

		}

		if (time()>_TIME_OUT) {
			return;
		}

	} while (count($items));

}

/**
 * Desinstallation/suppression
 *
 * @param string $nom_meta_base_version
 */
function pouetradio_vider_tables($nom_meta_base_version) {
	effacer_meta($nom_meta_base_version);
}
