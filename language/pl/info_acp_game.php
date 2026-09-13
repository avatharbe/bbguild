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
	'ACP_BBGUILD_GAME'            => 'Ustawienia gry',
	'ACP_BBGUILD_FACTION_ADD'        => 'Dodaj frakcję',
	'ACP_BBGUILD_RACE_ADD'        => 'Dodaj rasę',
	'ACP_BBGUILD_ROLE_ADD'        => 'Dodaj rolę',
	'ACP_BBGUILD_CLASS_ADD'        => 'Dodaj klasę',
	'ACP_BBGUILD_SPEC_ADD'         => 'Dodaj specjalizację',
	'ACP_BBGUILD_SPEC_EDIT'        => 'Edytuj specjalizację',
	'ACP_BBGUILD_SPEC_EXPLAIN'     => 'Specjalizacja (podklasa) przypisuje klasie konkretną rolę. Na przykład Mag Mrozu to Mag pełniący rolę DPS.',
	'ACP_LISTSPEC'                 => 'Specjalizacje',
	'ACP_LISTSPEC_EXPLAIN'         => 'Specjalizacje dostarczane przez aktywną wtyczkę gry. Każda specjalizacja należy do jednej klasy i jednej roli.',
	'LISTSPEC_FOOTCOUNT'           => '%d specjalizacja/e/i',
	'SPEC_ID'                      => 'ID specjalizacji',
	'SPEC_NAME'                    => 'Nazwa specjalizacji',
	'SPEC_ICON'                    => 'Ikona specjalizacji (nazwa pliku bez rozszerzenia)',
	'SPEC_ORDER'                   => 'Kolejność wyświetlania',
	'ACP_BBGUILD_GAME_LIST'        => 'Lista gier',
	'ACP_BBGUILD_GAME_EDIT'        => 'Edytuj grę',
	'ADMIN_ADD_SPEC_SUCCESS'       => 'Dodano specjalizację "%s".',
	'ADMIN_UPDATE_SPEC_SUCCESS'    => 'Zaktualizowano specjalizację "%s".',
	'FV_REQUIRED_SPEC_NAME'        => 'Nazwa specjalizacji jest wymagana.',
	)
);
