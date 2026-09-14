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
	'ACP_BBGUILD_GAME'            => 'Impostazioni Giochi',
	'ACP_BBGUILD_FACTION_ADD'        => 'Aggiungi Fazione',
	'ACP_BBGUILD_RACE_ADD'        => 'Aggiungi Razza',
	'ACP_BBGUILD_ROLE_ADD'        => 'Aggiungi Ruolo',
	'ACP_BBGUILD_CLASS_ADD'        => 'Aggiungi Classe',
	'ACP_BBGUILD_SPEC_ADD'         => 'Aggiungi Specializzazione',
	'ACP_BBGUILD_SPEC_EDIT'        => 'Modifica Specializzazione',
	'ACP_BBGUILD_SPEC_EXPLAIN'     => 'Una specializzazione (sottoclasse) associa una classe a un ruolo specifico. Ad esempio, un Mago del Gelo è un Mago con ruolo DPS.',
	'ACP_LISTSPEC'                 => 'Specializzazioni',
	'ACP_LISTSPEC_EXPLAIN'         => 'Specializzazioni fornite dal plugin di gioco attivo. Ogni specializzazione appartiene a una classe e a un ruolo.',
	'LISTSPEC_FOOTCOUNT'           => '%d specializzazione/i',
	'SPEC_ID'                      => 'ID specializzazione',
	'SPEC_NAME'                    => 'Nome della specializzazione',
	'SPEC_ICON'                    => 'Icona specializzazione (nome file senza estensione)',
	'SPEC_ORDER'                   => 'Ordine di visualizzazione',
	'ACP_BBGUILD_GAME_LIST'        => 'Lista Giochi',
	'ACP_BBGUILD_GAME_EDIT'        => 'Edita Gioco',
	'ADMIN_ADD_SPEC_SUCCESS'       => 'Specializzazione "%s" aggiunta.',
	'ADMIN_UPDATE_SPEC_SUCCESS'    => 'Specializzazione "%s" aggiornata.',
	'FV_REQUIRED_SPEC_NAME'        => 'Il nome della specializzazione è obbligatorio.',
	)
);
