<?php

function filtre_oembed_embarquer_lien_dist($lien) {
	include_spip('inc/oembed');
	return oembed_embarquer_lien($lien);

}

function masto_tag2rssitems($url) {
	static $deja = array();
	include_spip('inc/distant');

	$xml = "";
	if ($res = recuperer_url($url)) {
		$links = extraire_balises($res['page'], 'a');
		foreach ($links as $link) {
			$class = extraire_attribut($link,'class');
			if (strpos($class, 'u-uid') !== false) {
				$href = extraire_attribut($link,'href');
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
