<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * DO NOT CHANGE
 */
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge(
	$lang, array(

	// General
	'ACTIVE' => 'Active',
	'ALL' => 'All',
	'ARMORY' => 'Armory',
	'GAME' => 'Game',
	'INACTIVE' => 'Inactive',
	'PLUGIN_DISABLED' => 'Disabled (plugin not enabled)',
	'LINKED_USER' => 'Forum Account',
	'NO_PLAYER' => 'Player not found.',
	'ROLE' => 'Role',
	'BBGUILDDISABLED' => 'bbGuild is currently disabled.',
	'FOOTERBBGUILD' => 'bbGuild',

	// Portal Blocks
	'PORTAL' => 'Portal',
	'REMEMBERME' => 'Remember me',
	'INFORUM' => 'Posted in',
	'BBGUILD' => 'Guild',
	'NEWS' => 'News',
	'COMMENT' => 'Comment',
	'LIST_NEWS' => 'List News',
	'NO_NEWS' => 'No news entries found.',
	'NEWS_PER_PAGE' => 'News Entries per Page',
	'ERROR_INVALID_NEWS_PROVIDED' => 'A valid news id was not provided.',
	'BOSSPROGRESS' => 'Bossprogress',
	'WELCOME' => 'Welcome',
	'RECENT_LENGTH' => 'Number of chars retrieved',
	'NUMTOPICS' => 'Number of topics retrieved',
	'SHOW_RT_BLOCK' => 'Show Recent Topics',
	'RECENT_TOPICS_SETTING' => 'Recent Topics Settings',
	'RECENT_TOPICS' => 'Recent Topics',
	'NO_RECENT_TOPICS' => 'No recent topics',
	'POSTED_BY_ON' => 'by %1$s on %2$s',
	'LATESTPLAYERS' => 'Latest players',

	// Main Menu
	'MENU' => 'Navigation',
	'MENU_WELCOME' => 'Welcome',
	'MENU_ROSTER' => 'Roster',
	'MENU_NEWS' => 'News',
	'MENU_RAIDS' => 'Raids',
	'MENU_STATS' => 'Statistics',
	'MENU_PLAYER' => 'Player',
	'MENU_ACHIEVEMENTS' => 'Achievements',

	// Games
	'WOW'        => 'World of Warcraft',
	'EQ'         => 'EverQuest',
	'EQ2'        => 'EverQuest II',
	'FFXI'       => 'Final Fantasy XI',
	'LINEAGE2'   => 'Lineage 2',
	'LOTRO'      => 'Lord of the Rings Online',
	'SWTOR'      => 'Starwars : The old Republic',
	'FFXIV'      => 'Final Fantasy XIV',
	'PREINSTALLED' => 'Available game plugins: %s',

	// Recruitment
	'RECRUITMENT_BLOCK' => 'Recruitment Status',
	'RECRUIT_CLOSED' => 'Closed',
	'RECRUIT_OPEN' => 'Open',
	'TANK' => 'Tank',
	'DPS' => 'Dps',
	'HEAL' => 'Heal',
	'HEALER' => 'Healer',
	'RECRUIT_MESSAGE' => 'We are currently looking for new players for the following classes:',

	// Roster
	'GUILDROSTER' => 'Guild Roster',
	'RANK'   => 'Rank',
	'CLASS'   => 'Class',
	'SPEC'    => 'Specialization',
	'PLAYER_SPEC_EXPLAIN' => 'Optional. Pick a specialization within the selected class.',
	'LVL'   => 'Level',
	'REALM'  => 'Realm',
	'REGION'  => 'Region',
	'ACHIEV'  => 'Achievements',
	'PROFFESSION' => 'Proffessions',

	// Player List
	'FILTER' => 'Filter',
	'LASTRAID' => 'Last Raid',
	'LEVEL' => 'Level',
	'LISTPLAYERS_TITLE' => 'Leaderboard',
	'MNOTFOUND' => 'Could not obtain player information',
	'RNOTFOUND' => 'Could not obtain Raid information',
	'EMPTYRAIDNAME' => 'Raidname Not Found',
	'NAME' => 'Name',
	'SURNAME' => 'Surname/Title',
	'LISTPLAYERS_FOOTCOUNT' => '... found %d players',
	'LOGIN_TITLE' => 'Login',
	'NOUCPACCESS' => 'You are not authorised to claim characters',
	'NOUCPADDCHARS' => 'You are not authorised to add characters',
	'NOUCPUPDCHARS' => 'You are not authorised to update your characters',
	'NOUCPDELCHARS' => 'You are not authorised to delete your characters',

	// Common Labels
	'ACCOUNT' => 'Account',
	'ACTION' => 'Action',
	'ACHIEVED' => 'earned the achievement ',
	'ADD' => 'Add',
	'ADDED_BY' => 'Added by %s',
	'ADMINISTRATION' => 'Administration',
	'ADMINISTRATIVE_OPTIONS' => 'Administrative Options',
	'ADMIN_INDEX' => 'Admin Index',
	'ATTENDED' => 'Attended',
	'ATTENDEES' => 'Attendees',
	'ATTENDANCE' => 'Attendance',
	'ATT' => 'Att.',
	'AVERAGE' => 'Average',
	'BOSS' => 'Boss',
	'ARMOR' => 'Armor',
	'STATS_SOCIAL' => '< 20% Attendance',
	'STATS_RAIDER' => '< 50% Attendance',
	'STATS_CORERAIDER' => '> 70% Attendance',

	// Armor Types
	'CLOTH' => 'Very light / Cloth',
	'ROBE' => 'Robes',
	'LEATHER' => 'Light / Leather',
	'AUGMENTED' => 'Augmented Suit',
	'MAIL' =>  'Medium / Chain Mail',
	'HEAVY' => 'Heavy Armor',
	'PLATE' => 'Heavy / Plate',

	// Class & Race Labels
	'CLASSID' => 'Class ID',
	'CLASS_FACTOR' => 'Class Factor',
	'CLASSARMOR' => 'Class Armor',
	'CLASSIMAGE' => 'Image',
	'CLASSMIN' => 'Min level',
	'CLASSMAX' => 'Max level',
	'CLASS_DISTRIBUTION' => 'Class Distribution',
	'CLASS_SUMMARY' => 'Class Summary: %s to %s',
	'CONFIGURATION' => 'Configuration',
	'DATE' => 'Date',
	'DELETE' => 'Delete',
	'DELETE_CONFIRMATION' => 'Delete Confirmation',

	// Character Management
	'NO_CHARACTERS' => 'No characters in database',
	'STATUS' => 'Status Y/N',
	'CHARACTER' => 'Here is a list of all your Characters. ',
	'CHARACTER_EXPLAIN' => 'Choose an unclaimed Character to claim it and press submit.',
	'CHARACTERS_UPDATED' => 'The Charactername %s was assigned to your account. ',
	'CLAIM_PLAYER' => 'Claim Character',
	'CLAIM' => 'Claim',
	'UNCLAIM' => 'Unclaim',
	'CHARACTER_UNCLAIMED' => 'The character %s has been removed from your account.',
	'CHARACTER_UNCLAIM_FAILED' => 'Could not unclaim this character. It may not be assigned to your account.',
	'CONFIRM_UNCLAIM_PLAYER' => 'Are you sure you want to unclaim %s?',
	'MY_CHARACTERS' => 'My Characters',
	'NO_PLAYERS_FOUND' => 'No characters found.',
	'NO_CHARACTERS_BOUND' => 'No characters bound to your Account.',

	// Entity Labels
	'EVENT' => 'Event',
	'EVENTNAME' => 'Event Name',
	'EVENTS' => 'Events',
	'FACTION' => 'Faction',
	'FACTIONID' => 'Faction ID',
	'FIRST' => 'First',
	'HIGH' => 'High',
	'JOINDATE' => 'Guild Join date',
	'LAST' => 'Last',
	'LAST_VISIT' => 'Last Visit',
	'LAST_UPDATE' => 'Last Update',
	'LOG_DATE_TIME' => 'Date/Time of this Log',
	'LOW' => 'Low',
	'MANAGE' => 'Manage',
	'MEDIUM' => 'Medium',
	'MEMBERS' => 'Members',
	'PLAYER' => 'Player',
	'PLAYERS' => 'Players',
	'NA' => 'N/A',
	'NO_DATA' => 'No Data',
	'MAX_CHARS_EXCEEDED' => 'Sorry, you can only have %s Characters bound to your phpBB account.',
	'MISCELLANEOUS' => 'Miscellaneous',
	'NEWEST' => 'Newest raid',
	'NOTE' => 'Note',
	'OLDEST' => 'Oldest raid',
	'OPEN' => 'Open',
	'OPTIONS' => 'Options',
	'OUTDATE' => 'Guild leave date',
	'PERCENT' => 'Percent',
	'PERMISSIONS' => 'Permissions',
	'PREFERENCES' => 'Preferences',
	'QUOTE' => 'Quote',
	'RACE' => 'Race',
	'RACEID' => 'Race ID',
	'RAIDSTART' => 'Raid Start',
	'RAIDEND' => 'Raid End',
	'RAIDDURATION' => 'Duration',
	'RAID' => 'Raid',
	'RAIDCOUNT' => 'Raidcount',
	'RAIDS' => 'Raids',
	'RAID_ID' => 'Raid ID',
	'RANK_DISTRIBUTION' => 'Rank Distribution',
	'REASON' => 'Reason',
	'RESULT' => 'Result',
	'SESSION_ID' => 'Session ID',
	'SUMMARY_DATES' => 'Raid Summary: %s to %s',
	'TIME' => 'Time',
	'TOTAL' => 'Total',
	'TYPE' => 'Type',
	'UPDATE' => 'Update',
	'UPDATED_BY' => 'Updated by %s',
	'USER' => 'User',
	'USERNAME' => 'Username',
	'VALUE' => 'Value',
	'VIEW' => 'View',
	'VIEW_ACTION' => 'View Action',
	'VIEW_LOGS' => 'View Logs',
	'APPLICANTS' => 'Applicants',
	'POSITIONS' => 'Positions',

	// Form Elements
	'ENDING_DATE' => 'Ending Date',
	'GUILD_TAG' => 'Guild Tag',
	'LANGUAGE' => 'Language',
	'STARTING_DATE' => 'Starting Date',
	'TO' => 'To',
	'ENTER_NEW' => 'Enter New Name',

	// Pagination
	'NEXT_PAGE' => 'Next Page',
	'PAGE' => 'Page',
	'PREVIOUS_PAGE' => 'Previous Page',

	// Permission Messages
	'NOAUTH_DEFAULT_TITLE' => 'Permission Denied',
	'NOAUTH_U_PLAYER_LIST' => 'You do not have permission to view player standings.',
	'NOAUTH_U_PLAYER_VIEW' => 'You do not have permission to view player history.',
	'NOAUTH_U_RAID_LIST' => 'You do not have permission to list raids.',
	'NOAUTH_U_RAID_VIEW' => 'You do not have permission to view raids.',

	// Miscellaneous
	'DEACTIVATED_BY_USR' => 'Deactivated by User',
	'ADDED' => 'Added',
	'CLOSED' => 'Closed',
	'DELETED' => 'Deleted',
	'FEMALE' => 'Female',
	'GENDER' => 'Gender',
	'GUILD' => 'Guild',
	'LIST' => 'List',
	'LIST_PLAYERS' => 'List Players',
	'MALE' => 'Male',
	'NOT_AVAILABLE' => 'Not Available',
	'NORAIDS' => 'No Raids',
	'OR' => 'or',
	'REQUIRED_FIELD_NOTE' => 'Items marked with a * are required fields.',
	'UPDATED' => 'Updated',
	'NOVIEW' => 'Unknown Viewname %s',

	// About Page
	'ABOUT' => 'About',
	'MAINIMG' => 'bbguild.png',
	'IMAGE_ALT' => 'Logo',
	'REPOSITORY_IMAGE' => 'Google.jpg',
	'TCOPYRIGHT' => 'Copyright',
	'TCREDITS' => 'Credits',
	'TEAM' => 'Dev Team',
	'TSPONSORS' => 'Donators',
	'TPLUGINS' => 'PlugIns',
	'CREATED' => 'Created by',
	'DEVELOPEDBY' => 'Developed by',
	'DEVTEAM' => 'bbGuild Development Team',
	'AUTHNAME' => 'Ippeh',
	'WEBNAME' =>'Website',
	'SVNNAME' => 'Repository',
	'SVNURL' => 'https://github.com/avatharbe/bbguild',
	'WEBURL' => 'http://www.avathar.be/bbdkp',
	'AUTHWEB' => 'http://www.avathar.be/bbdkp/',
	'LICENSE1' => 'bbGuild is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   bbGuild is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with bbGuild.  If not, see http://www.gnu.org/licenses',
	'LICENSE2' => 'Powered by bbDKP (c) 2009 The bbDKP Project Team. If you use this software and find it to be useful, we ask that you retain the copyright notice below. While not required for free use, it will help build interest in the bbDKP project and is <strong>required for obtaining support</strong>.',
	'COPYRIGHT3' => 'bbDKP (c) 2010 Sajaki, Malfate, Blazeflack <br />
bbDKP (c) 2008, 2009 Sajaki, Malfate, Kapli, Hroar',
	'COPYRIGHT2' => 'bbDKP (c) 2007 Ippeh, Teksonic, Monkeytech, DWKN',
	'COPYRIGHT1' => 'EQDkp (c) 2003 The EqDkp Project Team ',
	'PRODNAME' => 'Product',
	'VERSION' => 'Version',
	'DEVELOPER' => 'Developer',
	'JOB' => 'Job',
	'DEVLINK' => 'Link',
	'PROD' => 'bbGuild',
	'DEVELOPERS' => '<a href=mailto:sajaki@avathar.be>Sajaki</a>',
	'PHPBB' => 'phpBB',
	'PHPBBGR' => 'phpBB Group',
	'PHPBBLINK' => 'http://www.phpbb.com',
	'EQDKP' => 'Original EQDKP',
	'EQDKPVERS' => '1.3.2',
	'EQDKPDEV' => 'Tsigo',
	'EQDKPLINK' => 'http://www.eqdkp.com/',
	'PLUGINS' => 'Plugins',
	'PLUGINVERS' => 'Version',
	'AUTHOR' => 'Author',
	'MAINT' => 'Maintainer',
	'DONATION' => 'Donation',
	'DONA_NAME' => 'Name',
	'ADDITIONS' => 'Code Additions',
	'CONTRIB' => 'Contributions',

	)
);
