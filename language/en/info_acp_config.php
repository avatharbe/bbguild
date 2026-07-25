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
	'ACP_BBGUILD_MAINPAGE'        => 'General Settings',
	'ACP_BBGUILD_GAMESETTINGS'  => 'Game settings',
	'ACP_BBGUILD_PANEL'           => 'Dashboard',
	'ACP_BBGUILD_CONFIG'          => 'Settings',
	'ACP_BBGUILD_LOGS'            => 'Activity Log',
	'ACP_BBGUILD_INDEX'           => 'Portal Settings',
	)
);
