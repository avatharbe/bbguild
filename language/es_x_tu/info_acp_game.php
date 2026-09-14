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
	'ACP_BBGUILD_GAME'            => 'Configuración del juego',
	'ACP_BBGUILD_FACTION_ADD'        => 'Añadir facción',
	'ACP_BBGUILD_RACE_ADD'        => 'Añadir raza',
	'ACP_BBGUILD_ROLE_ADD'        => 'Añadir rol',
	'ACP_BBGUILD_CLASS_ADD'        => 'Añadir clase',
	'ACP_BBGUILD_SPEC_ADD'         => 'Añadir especialización',
	'ACP_BBGUILD_SPEC_EDIT'        => 'Editar especialización',
	'ACP_BBGUILD_SPEC_EXPLAIN'     => 'Una especialización (subclase) fija una clase a un rol específico. Por ejemplo, un Mago de Escarcha es un Mago con el rol de daño (DPS).',
	'ACP_LISTSPEC'                 => 'Especializaciones',
	'ACP_LISTSPEC_EXPLAIN'         => 'Especializaciones proporcionadas por el plugin de juego activo. Cada especialización pertenece a una clase y un rol.',
	'LISTSPEC_FOOTCOUNT'           => '%d especialización(es)',
	'SPEC_ID'                      => 'ID de especialización',
	'SPEC_NAME'                    => 'Nombre de la especialización',
	'SPEC_ICON'                    => 'Icono de especialización (nombre de archivo sin extensión)',
	'SPEC_ORDER'                   => 'Orden de visualización',
	'ACP_BBGUILD_GAME_LIST'        => 'Lista de juegos',
	'ACP_BBGUILD_GAME_EDIT'        => 'Editar juego',
	'ADMIN_ADD_SPEC_SUCCESS'       => 'Especialización "%s" añadida.',
	'ADMIN_UPDATE_SPEC_SUCCESS'    => 'Especialización "%s" actualizada.',
	'FV_REQUIRED_SPEC_NAME'        => 'El nombre de la especialización es obligatorio.',
	)
);
