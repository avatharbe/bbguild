<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

// Create the lang array if it does not already exist
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// Merge the following language entries into the lang array
$lang = array_merge(
	$lang, array(
	'ACP_BBGUILD_GAME'            => 'Spelinstellingen',
	'ACP_BBGUILD_FACTION_ADD'        => 'Factie toevoegen',
	'ACP_BBGUILD_RACE_ADD'        => 'Ras toevoegen',
	'ACP_BBGUILD_ROLE_ADD'        => 'Rol toevoegen',
	'ACP_BBGUILD_CLASS_ADD'        => 'Klasse toevoegen',
	'ACP_BBGUILD_SPEC_ADD'         => 'Specialisatie toevoegen',
	'ACP_BBGUILD_SPEC_EDIT'        => 'Specialisatie bewerken',
	'ACP_BBGUILD_SPEC_EXPLAIN'     => 'Een specialisatie (subklasse) koppelt een klasse aan een specifieke rol. Een Vorstmagiër is bijvoorbeeld een Magiër met de rol DPS.',
	'ACP_LISTSPEC'                 => 'Specialisaties',
	'ACP_LISTSPEC_EXPLAIN'         => 'Specialisaties aangeleverd door de actieve spel-plugin. Elke specialisatie hoort bij één klasse en één rol.',
	'LISTSPEC_FOOTCOUNT'           => '%d specialisatie(s)',
	'SPEC_ID'                      => 'Specialisatie-ID',
	'SPEC_NAME'                    => 'Naam van de specialisatie',
	'SPEC_ICON'                    => 'Specialisatie-icoon (bestandsnaam zonder extensie)',
	'SPEC_ORDER'                   => 'Weergavevolgorde',
	'ACP_BBGUILD_GAME_LIST'        => 'Spellenlijst',
	'ACP_BBGUILD_GAME_EDIT'        => 'Spel bewerken',
	'ADMIN_ADD_SPEC_SUCCESS'       => 'Specialisatie "%s" toegevoegd.',
	'ADMIN_UPDATE_SPEC_SUCCESS'    => 'Specialisatie "%s" bijgewerkt.',
	'FV_REQUIRED_SPEC_NAME'        => 'Naam van de specialisatie is verplicht.',
	)
);
