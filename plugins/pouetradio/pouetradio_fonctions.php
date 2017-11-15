<?php
/*
 * Plugin PouetRadio
 * (c) 2017
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Ajouter la tache cron pour tweeter les articles post-dates, chaque heure
 * @param $taches_generales
 * @return mixed
 */
function pouetradio_taches_generales_cron($taches_generales){

	$taches_generales['pouetradio_followback'] = 3333;

	return $taches_generales;
}
