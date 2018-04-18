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
 * Decoder le champ raw_data d'un article syndique en tableau de donnees utilisable
 * @param string $methode_syndication
 * @param string $raw_data
 * @param string $raw_format
 * @return array
 */
function syndic_article_raw_data_to_array($methode_syndication, $raw_data, $raw_format) {
	$data = array();
	if ($methode_syndication
	  and $syndic = charger_fonction($methode_syndication, 'syndic', true)
		and $methode_row_data_to_array = charger_fonction($methode_syndication . "_raw_data_to_array", 'syndic', true)) {
		$data = $methode_row_data_to_array($raw_data, $raw_format);
	}

	return $data;
}

/**
 * Compile la balise `#RAW_DATA` retournant le champ `raw_data`
 *
 * Utile dans une boucle SYNDIC_ARTICLES pour retourner les donnees brutes de syndication.
 *
 * @balise
 * @see table_valeur()
 * @example
 *     ```
 *     #RAW_DATA* renvoie le champ raw_data brut, au format texte
 *     #RAW_DATA renvoie le champ raw_data au format tableau structure si il a pu etre decode par la fonction fournie par la methode de syndication
 *     #RAW_DATA{x} renvoie #RAW_DATA|table_valeur{x},
 *     #RAW_DATA{a/b} renvoie #RAW_DATA|table_valeur{a/b}
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_RAW_DATA_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->id_boucle;
	$_raw_data = index_pile($p->id_boucle, 'raw_data', $p->boucles, $b);

	if ($p->etoile) {
		$p->code = $_raw_data;
	}
	else {
		$_raw_format = index_pile($p->id_boucle, 'raw_format', $p->boucles, $b);
		$_raw_methode = index_pile($p->id_boucle, 'raw_methode', $p->boucles, $b);

		$p->code = "syndic_article_raw_data_to_array($_raw_methode, $_raw_data, $_raw_format)";
		if (($v = interprete_argument_balise(1, $p)) !== null) {
			$p->code = 'table_valeur(' . $p->code . ', ' . $v . ')';
		}
	}
	$p->interdire_scripts = true;

	return $p;
}
