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
	'ACP_CAT_BBGUILD'            => 'bbGuild',
	'ACP_BBGUILD_MAINPAGE'        => 'Paramètres généraux',
	'ACP_BBGUILD_GAMESETTINGS'  => 'Paramètres du jeu',
	'ACP_BBGUILD_PANEL'                => 'Statistiques',
	'ACP_BBGUILD_CONFIG'        => 'Réglages bbguild',
	'ACP_BBGUILD_INDEX'            => 'Réglages portail',
	'ACP_BBGUILD_LOGS'            => 'Consulter les Logs',
	)
);
