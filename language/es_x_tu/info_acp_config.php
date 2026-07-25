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
	'ACP_BBGUILD_MAINPAGE'        => 'Configuración general',
	'ACP_BBGUILD_GAMESETTINGS'  => 'Configuración del juego',
	'ACP_BBGUILD_PANEL'           => 'Panel de control',
	'ACP_BBGUILD_CONFIG'          => 'Configuración',
	'ACP_BBGUILD_LOGS'            => 'Registro de actividad',
	'ACP_BBGUILD_INDEX'           => 'Configuración del portal',
	)
);
