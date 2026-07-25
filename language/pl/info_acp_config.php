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
	'ACP_BBGUILD_MAINPAGE'        => 'Ustawienia ogólne',
	'ACP_BBGUILD_GAMESETTINGS'  => 'Ustawienia gry',
	'ACP_BBGUILD_PANEL'           => 'Panel',
	'ACP_BBGUILD_CONFIG'          => 'Ustawienia',
	'ACP_BBGUILD_LOGS'            => 'Dziennik aktywności',
	'ACP_BBGUILD_INDEX'           => 'Ustawienia portalu',
	)
);
