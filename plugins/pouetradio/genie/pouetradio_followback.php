<?php
/*
 * Plugin PouetRadio
 * (c) 2017
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


/**
 * Alerte sur les articles publies post-dates
 *
 * @param int $last
 * @return int
 */
function genie_pouetradio_followback_dist($last) {

	include_spip('inc/mastodon');
	mastodon_followback(array());

	return 1;
}