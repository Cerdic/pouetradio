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

/**
 * Gestion de syndication (RSS,...)
 *
 * @package SPIP\Sites\Syndication
 **/

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

// ATTENTION
// Cette inclusion charge executer_une_syndication pour compatibilite,
// mais cette fonction ne doit plus etre invoquee directement:
// il faut passer par cron() pour avoir un verrou portable
// Voir un exemple dans action/editer/site
include_spip('genie/syndic');
include_spip('syndic/atomrss');

