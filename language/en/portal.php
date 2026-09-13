<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Portal language keys (English)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	// ACP heading
	'ACP_BBGUILD_PORTAL'             => 'Portal',

	// Module names
	'BBGUILD_PORTAL_MOTD'            => 'Message of the Day',
	'BBGUILD_PORTAL_RECRUITMENT'     => 'Recruitment',
	'BBGUILD_PORTAL_ROSTER'          => 'Guild Roster',
	'BBGUILD_PORTAL_STATISTICS'      => 'Guild Statistics',

	// Module content
	'NO_RECRUITS'                    => 'No open recruitment positions.',
	'BBGUILD_NO_PORTAL_MODULES'      => 'No portal modules configured for this guild.',

	// Statistics module
	'STAT_CLASS_DISTRIBUTION'        => 'Classes',
	'STAT_RACE_DISTRIBUTION'         => 'Races',
	'STAT_LEVEL_DISTRIBUTION'        => 'Levels',
	'STAT_RANK_DISTRIBUTION'         => 'Ranks',
	'STAT_TOTAL_MEMBERS'             => 'Total active members: %d',
	'STAT_RECENT_JOINS'              => 'Recently Joined',
	'STAT_RECENT_DEPARTURES'         => 'Recently Departed',
	'STAT_NO_DATA'                   => 'No data yet.',

	// ACP portal management
	'ACP_PORTAL_EXPLAIN'             => 'Manage portal modules for each guild. Add, remove, reorder, and enable/disable blocks.',
	'ACP_PORTAL_SELECT_GUILD'        => 'Select Guild',
	'ACP_PORTAL_MODULES'             => 'Portal Modules',
	'ACP_PORTAL_MODULE_NAME'         => 'Module',
	'ACP_PORTAL_COLUMN'              => 'Column',
	'ACP_PORTAL_ORDER'               => 'Order',
	'ACP_PORTAL_COLUMN_TOP'          => 'Top',
	'ACP_PORTAL_COLUMN_CENTER'       => 'Center',
	'ACP_PORTAL_COLUMN_RIGHT'        => 'Right',
	'ACP_PORTAL_ADD_MODULE'          => 'Add Module',
	'ACP_PORTAL_NO_MODULES'          => 'No portal modules configured for this guild. Add one below.',
	'ACP_PORTAL_MODULE_ADDED'        => 'Portal module has been added.',
	'ACP_PORTAL_MODULE_ADD_FAILED'   => 'Could not add portal module. The module may not be allowed in the selected column.',
	'ACP_PORTAL_MODULE_DELETED'      => 'Portal module has been removed.',
	'ACP_PORTAL_MOVE_LEFT'           => 'Move to previous column',
	'ACP_PORTAL_MOVE_RIGHT'          => 'Move to next column',
	'ACP_PORTAL_COLUMN_BOTTOM'       => 'Bottom',

	'ACP_PORTAL_TABS'                => 'Tabs',
	'ACP_PORTAL_TAB'                 => 'Tab',
	'ACP_PORTAL_TAB_NAME'            => 'Tab name',
	'ACP_PORTAL_TAB_SLUG'            => 'Slug',
	'ACP_PORTAL_TAB_SLUG_EXPLAIN'    => 'Used in the page URL, e.g. <samp>raids</samp>. Letters, numbers, hyphens and underscores only; cannot start with a number.',
	'ACP_PORTAL_ADD_TAB'             => 'Add Tab',
	'ACP_PORTAL_TAB_ADDED'           => 'Tab has been added.',
	'ACP_PORTAL_TAB_ADD_FAILED'      => 'Could not add tab. A tab with that slug already exists for this guild.',
	'ACP_PORTAL_TAB_UPDATED'         => 'Tab has been updated.',
	'ACP_PORTAL_TAB_UPDATE_FAILED'   => 'Could not update tab. A tab with that slug already exists for this guild.',
	'ACP_PORTAL_TAB_DELETED'         => 'Tab has been removed.',
	'ACP_PORTAL_TAB_DELETE_FAILED'   => 'Could not remove tab. A guild must always have at least one tab.',

	// Module configuration
	'ACP_PORTAL_MODULE_CONFIG'        => 'Module Configuration',
	'ACP_PORTAL_MODULE_CONFIG_EXPLAIN' => 'Configure the display name, icon, visibility and module-specific settings for this portal block. Changes apply to the guild welcome page.',
	'ACP_PORTAL_MODULE_SETTINGS'      => 'General Settings',
	'ACP_PORTAL_MODULE_SETTINGS_EXPLAIN' => 'Set the display name and enable or disable this module. Disabled modules are hidden from the portal but retain their configuration.',
	'ACP_PORTAL_ICON_SETTINGS'        => 'Icon Settings',
	'ACP_PORTAL_ICON_SETTINGS_EXPLAIN' => 'Choose an icon displayed next to the module title on the portal. You can use either an image file or a Font Awesome icon. If both are set, the Font Awesome icon takes priority.',
	'ACP_PORTAL_IMAGE_SRC'            => 'Image file',
	'ACP_PORTAL_IMAGE_SRC_EXPLAIN'    => 'Relative path to an image file, e.g. <samp>ext/avathar/bbguild/images/icon.png</samp>.',
	'ACP_PORTAL_FA_ICON'              => 'Font Awesome icon',
	'ACP_PORTAL_FA_ICON_EXPLAIN'      => 'Enter a Font Awesome 4.7 CSS class, e.g. <samp>fa-star</samp>, <samp>fa-shield</samp>, <samp>fa-users</samp>. Takes priority over the image file. Leave empty to use the image instead.',
	'ACP_PORTAL_ICON_SIZE'            => 'Icon size',
	'ACP_PORTAL_ICON_SIZE_EXPLAIN'    => 'Size of the Font Awesome icon in pixels (8–64). Only applies when a Font Awesome icon is set.',
	'ACP_PORTAL_GROUP_ACCESS'         => 'Group Access',
	'ACP_PORTAL_GROUP_ACCESS_EXPLAIN' => 'Restrict which user groups can see this module on the portal page.',
	'ACP_PORTAL_GROUP_IDS'            => 'Allowed groups',
	'ACP_PORTAL_GROUP_IDS_EXPLAIN'    => 'Select one or more groups. Only members of the selected groups will see this module. Leave empty to show the module to all users.',
	'ACP_PORTAL_MODULE_SPECIFIC'      => 'Module Settings',
	'ACP_PORTAL_MODULE_SPECIFIC_EXPLAIN' => 'Settings specific to this module type. Available options depend on the module (e.g. custom block content, feed limits).',
	'ACP_PORTAL_MODULE_UPDATED'       => 'Module configuration has been saved.',
]);
