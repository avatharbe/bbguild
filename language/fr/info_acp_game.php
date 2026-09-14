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
	'ACP_BBGUILD_GAME'            => 'Factions, Races, Classes',
	'ACP_BBGUILD_FACTION_ADD'        => 'Ajouter Faction',
	'ACP_BBGUILD_RACE_ADD'        => 'Ajouter Race',
	'ACP_BBGUILD_ROLE_ADD'        => 'Ajouter Rôle',
	'ACP_BBGUILD_CLASS_ADD'        => 'Ajouter Classe',
	'ACP_BBGUILD_SPEC_ADD'         => 'Ajouter Spécialisation',
	'ACP_BBGUILD_SPEC_EDIT'        => 'Modifier Spécialisation',
	'ACP_BBGUILD_SPEC_EXPLAIN'     => 'Une spécialisation (sous-classe) rattache une classe à un rôle précis. Par exemple, un Mage Givre est un Mage avec le rôle DPS.',
	'ACP_LISTSPEC'                 => 'Spécialisations',
	'ACP_LISTSPEC_EXPLAIN'         => 'Spécialisations fournies par le plugin de jeu actif. Chaque spécialisation appartient à une classe et un rôle.',
	'LISTSPEC_FOOTCOUNT'           => '%d spécialisation(s)',
	'SPEC_ID'                      => 'ID de spécialisation',
	'SPEC_NAME'                    => 'Nom de la spécialisation',
	'SPEC_ICON'                    => 'Icône de spécialisation (nom de fichier sans extension)',
	'SPEC_ORDER'                   => 'Ordre d\'affichage',
	'ACP_BBGUILD_GAME_LIST'        => 'Réglages Jeux',
	'ACP_BBGUILD_GAME_EDIT'        => 'Edition Jeux',
	'ADMIN_ADD_SPEC_SUCCESS'       => 'Spécialisation "%s" ajoutée.',
	'ADMIN_UPDATE_SPEC_SUCCESS'    => 'Spécialisation "%s" mise à jour.',
	'FV_REQUIRED_SPEC_NAME'        => 'Le nom de la spécialisation est requis.',
	)
);
