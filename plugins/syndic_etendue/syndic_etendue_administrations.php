<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2018                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * maj dede la table article
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function syndic_etendue_upgrade($nom_meta_base_version,$version_cible){

	$maj = array();
	$maj['create'] = array(
		array('sql_alter',"TABLE spip_syndic_articles ADD raw_data TEXT DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_syndic_articles ADD raw_format TINYTEXT DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_syndic_articles ADD raw_methode TINYTEXT DEFAULT '' NOT NULL"),
	);

	$maj['1.0.0'] = array(
		array('sql_alter',"TABLE spip_syndic_articles ADD raw_data TEXT DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_syndic_articles ADD raw_format TINYTEXT DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_syndic_articles ADD raw_methode TINYTEXT DEFAULT '' NOT NULL"),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Desinstallation/suppression
 *
 * @param string $nom_meta_base_version
 */
function syndic_etendue_vider_tables($nom_meta_base_version) {
	effacer_meta($nom_meta_base_version);
}
