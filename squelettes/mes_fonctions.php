<?php

function filtre_oembed_embarquer_lien_dist($lien) {
	include_spip('inc/oembed');
	return oembed_embarquer_lien($lien);

}

function masto_tag2rssitems($url) {
	static $deja = array();
	include_spip('inc/distant');

	$xml = "";
	if ($res = recuperer_url($url.".json")
	  and $d = json_decode($res['page'], true)) {

		$links = $d['orderedItems'];
		foreach ($links as $link) {
			if (strpos($link,'://') !== false) {
				$href = $link;
				if (!isset($deja[$href])) {
					$xml .= masto_url2item($href);
					$deja[$href] = true;
				}
			}
		}
	}
	return $xml;
}


function masto_url2item($url) {
	$item = "";

	if ($res = recuperer_url_cache($url,array('delai_cache'=>86400))) {
		$links = extraire_balises($res['page'], 'link');
		foreach ($links as $link) {
			if (extraire_attribut($link, 'rel') == 'alternate'
			  and extraire_attribut($link, 'type') == 'application/atom+xml') {
				$url = extraire_attribut($link, 'href');
				if ($res = recuperer_url_cache($url,array('delai_cache'=>86400))) {
					$item = extraire_balise($res['page'], 'entry');
					// supprimer <author> qui perturbe la syndication
					$item = preg_replace(",<author.*</author>,Uims", "", $item);
					$item .= "\n\n";
					return $item;
				}
			}
		}
	}

	return $item;
}

/**
 * Critere pour recuperer les pouets apres le dernier de la page precedente
 * en se basant sur son id et sa date
 * sachant que des items ont pu etre ajoutes a la base entre temps
 * (on ne peut donc pas se fier au nombre de resultats dans la boucle, qui peut changer d'une fois a l'autre)
 * @param $idb
 * @param $boucles
 * @param $crit
 */
function critere_apres_pouet_dist($idb, &$boucles, $crit) {
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id = $boucle->primary;

	$_id_syndic_article = "''";
	if (isset($crit->param[0][0])) {
		$_id_syndic_article = calculer_liste(array($crit->param[0][0]), array(), $boucles, $boucle->id_parent);
	}

	$champ_date = $boucle->id_table.'.date';

	$_id_syndic_article = "(\$zid=$_id_syndic_article)";
	$_date = "(\$zd=sql_getfetsel('date','spip_syndic_articles','id_syndic_article='.intval(\$zid)))";
	$where = "'($champ_date<'.sql_quote(\$zd).' OR ($champ_date='.sql_quote(\$zd).' AND id_syndic_article<'.intval(\$zid).'))'";
	$where = "(($_id_syndic_article and $_date)?($where):'')";

	$boucle->where[] = $where;
}

/**
 * Critere pour recuperer les pouets apres le dernier de la page precedente
 * en se basant sur son id et sa date
 * sachant que des items ont pu etre ajoutes a la base entre temps
 * (on ne peut donc pas se fier au nombre de resultats dans la boucle, qui peut changer d'une fois a l'autre)
 * @param $idb
 * @param $boucles
 * @param $crit
 */
function critere_avant_pouet_dist($idb, &$boucles, $crit) {
	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id = $boucle->primary;

	$_id_syndic_article = "''";
	if (isset($crit->param[0][0])) {
		$_id_syndic_article = calculer_liste(array($crit->param[0][0]), array(), $boucles, $boucle->id_parent);
	}

	$champ_date = $boucle->id_table.'.date';

	$_id_syndic_article = "(\$zid=$_id_syndic_article)";
	$_date = "(\$zd=sql_getfetsel('date','spip_syndic_articles','id_syndic_article='.intval(\$zid)))";
	$where = "'($champ_date>'.sql_quote(\$zd).' OR ($champ_date='.sql_quote(\$zd).' AND id_syndic_article>'.intval(\$zid).'))'";
	$where = "(($_id_syndic_article and $_date)?($where):'')";

	$boucle->where[] = $where;
}

function critere_filtre_tags($idb, &$boucles, $crit) {

	$not = $crit->not;
	$boucle = &$boucles[$idb];
	$id = $boucle->primary;
	$champ_tags = $boucle->id_table.'.tags';
	$w = array();
	$w[] = "($champ_tags LIKE '.sql_quote('%youtu%').')";
	$w[] = "($champ_tags LIKE '.sql_quote('%hooktube%').')";
	$w[] = "($champ_tags LIKE '.sql_quote('%vimeo%').')";
	$w[] = "($champ_tags LIKE '.sql_quote('%dailymotion.com%').')";
	$w[] = "($champ_tags LIKE '.sql_quote('%dai.ly%').')";

	$w = "'(".implode(' OR ', $w).")'";
	$boucle->where[] = $w;
}


function pouetradio_extraire_first_enclosure($tags) {
	$links = extraire_balises($tags, 'a');
	foreach ($links as $link) {
		if (extraire_attribut($link, 'rel') == 'enclosure'
		  and $type = extraire_attribut($link, 'type')
		  and strncmp($type, 'image/', 6) == 0) {
			$href = extraire_attribut($link, 'href');

			return "<a href=\"$href\" type=\"$type\" class=\"toot-enclosure\"><span class=\"toot-enclosure-inner\" style=\"background-image:url('$href');\"></span></a>";
		}
	}
	return '';
}
