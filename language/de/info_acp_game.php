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
	'ACP_BBGUILD_GAME'            => 'Spieleinstellungen',
	'ACP_BBGUILD_FACTION_ADD'        => 'Faktion hinzufügen',
	'ACP_BBGUILD_RACE_ADD'        => 'Rasse hinzufügen',
	'ACP_BBGUILD_ROLE_ADD'        => 'Rolle hinzufügen',
	'ACP_BBGUILD_CLASS_ADD'        => 'Klasse hinzufügen',
	'ACP_BBGUILD_SPEC_ADD'         => 'Spezialisierung hinzufügen',
	'ACP_BBGUILD_SPEC_EDIT'        => 'Spezialisierung bearbeiten',
	'ACP_BBGUILD_SPEC_EXPLAIN'     => 'Eine Spezialisierung (Unterklasse) legt für eine Klasse eine bestimmte Rolle fest. Ein Frost-Magier zum Beispiel ist ein Magier mit der Rolle Schaden (DPS).',
	'ACP_LISTSPEC'                 => 'Spezialisierungen',
	'ACP_LISTSPEC_EXPLAIN'         => 'Spezialisierungen, die vom aktiven Spiel-Plugin vorgegeben werden. Jede Spezialisierung gehört zu genau einer Klasse und einer Rolle.',
	'LISTSPEC_FOOTCOUNT'           => '%d Spezialisierung(en)',
	'SPEC_ID'                      => 'Spezialisierungs-ID',
	'SPEC_NAME'                    => 'Name der Spezialisierung',
	'SPEC_ICON'                    => 'Spezialisierungssymbol (Dateiname ohne Erweiterung)',
	'SPEC_ORDER'                   => 'Anzeigereihenfolge',
	'ACP_BBGUILD_GAME_LIST'        => 'Spielliste',
	'ACP_BBGUILD_GAME_EDIT'        => 'Spiel bearbeiten',
	'ADMIN_ADD_SPEC_SUCCESS'       => 'Spezialisierung "%s" hinzugefügt.',
	'ADMIN_UPDATE_SPEC_SUCCESS'    => 'Spezialisierung "%s" aktualisiert.',
	'FV_REQUIRED_SPEC_NAME'        => 'Der Name der Spezialisierung ist erforderlich.',
	)
);
