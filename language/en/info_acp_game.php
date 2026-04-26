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
	'ACP_BBGUILD_GAME'            => 'Game Settings',
	'ACP_BBGUILD_FACTION_ADD'        => 'Add Faction',
	'ACP_BBGUILD_RACE_ADD'        => 'Add Race',
	'ACP_BBGUILD_ROLE_ADD'        => 'Add Role',
	'ACP_BBGUILD_CLASS_ADD'        => 'Add Class',
	'ACP_BBGUILD_SPEC_ADD'         => 'Add Specialization',
	'ACP_BBGUILD_SPEC_EDIT'        => 'Edit Specialization',
	'ACP_BBGUILD_SPEC_EXPLAIN'     => 'A specialization (subclass) pins a class to a specific role. For example, a Frost Mage is a Mage with the DPS role.',
	'ACP_LISTSPEC'                 => 'Specializations',
	'ACP_LISTSPEC_EXPLAIN'         => 'Specializations seeded by the active game plugin. Each spec belongs to one class and one role.',
	'LISTSPEC_FOOTCOUNT'           => '%d specialization(s)',
	'SPEC_ID'                      => 'Spec ID',
	'SPEC_NAME'                    => 'Specialization name',
	'SPEC_ICON'                    => 'Spec icon (filename without extension)',
	'SPEC_ORDER'                   => 'Display order',
	'ACP_BBGUILD_GAME_LIST'        => 'Game List',
	'ACP_BBGUILD_GAME_EDIT'        => 'Edit Game',
	'ADMIN_ADD_SPEC_SUCCESS'       => 'Specialization "%s" added.',
	'ADMIN_UPDATE_SPEC_SUCCESS'    => 'Specialization "%s" updated.',
	'FV_REQUIRED_SPEC_NAME'        => 'Specialization name is required.',
	)
);
