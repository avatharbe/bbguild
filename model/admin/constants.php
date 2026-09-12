<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace avathar\bbguild\model\admin;

class constants {

	const BBGUILD_VERSIONURL = 'https://www.avathar.be/versioncheck/';

	const URI_LOG = 'log';
	const URI_NAME = 'name';
	const URI_NAMEID = 'player_id';
	const URI_NEWS =  'news';
	const URI_ORDER = 'o';
	const URI_PAGE = 'pag';
	const URI_GUILD = 'guild_id';
	const URI_GAME = 'game_id';
	const USER_LLIMIT = 20;

	/**
	 * dkp system added
	 */
	const DKPSYS_ADDED = 1;
	/**
	 * dkp system updated
	 */
	const DKPSYS_UPDATED = 2;
	/**
	 * dkp system deleted
	 */
	const DKPSYS_DELETED = 3;
	/**
	 * event added
	 */
	const EVENT_ADDED = 4;
	/**
	 * event updated
	 */
	const EVENT_UPDATED = 5;
	/**
	 * event deleted
	 */
	const EVENT_DELETED = 6;
	/**
	 * history transferred
	 */
	const HISTORY_TRANSFER = 7;
	/**
	 * individual adjustment added
	 */
	const INDIVADJ_ADDED = 8;
	/**
	 * individual adjustment updated
	 */
	const INDIVADJ_UPDATED = 9;
	/**
	 * individual adjustment deleted
	 */
	const INDIVADJ_DELETED = 10;
	/**
	 * item added
	 */
	const ITEM_ADDED = 11;
	/**
	 * item updated
	 */
	const ITEM_UPDATED = 12;
	/**
	 * item deleted
	 */
	const ITEM_DELETED= 13;
	/**
	 * new player was added
	 */
	const PLAYER_ADDED = 14;
	/**
	 * player file was updated
	 */
	const PLAYER_UPDATED = 15;
	/**
	 * player was removed
	 */
	const PLAYER_DELETED = 16;
	/**
	 * rank was added
	 */
	const RANK_ADDED = 17;
	/**
	 * rank was updated
	 */
	const RANK_UPDATED = 18;
	/**
	 * rank was deleted
	 */
	const RANK_DELETED = 19;
	/**
	 * news was added
	 */
	const NEWS_ADDED = 20;
	/**
	 * news was updated
	 */
	const NEWS_UPDATED = 21;
	/**
	 * news was deleted
	 */
	const NEWS_DELETED = 22;
	/**
	 * a new raid was added
	 */
	const RAID_ADDED = 23;
	/**
	 * raid was updated
	 */
	const RAID_UPDATED = 24;
	/**
	 * raid was deleted
	 */
	const RAID_DELETED = 25;
	/**
	 * an action was completed
	 */
	const ACTION_DELETED = 26;
	/**
	 * raidtracker config was updated
	 */
	const RT_CONFIG_UPDATED = 27;
	/**
	 * the decay was synchronised
	 */
	const DECAYSYNC = 28;
	/**
	 * decay was switched off
	 */
	const DECAYOFF = 29;
	/**
	 * zero sum dkp was synced
	 */
	const ZSYNC = 30;
	/**
	 * dkp was synchronised
	 */
	const DKPSYNC = 31;
	/**
	 * default pool was changed
	 */
	const DEFAULT_DKP_CHANGED = 32;
	/**
	 * new guild was added
	 */
	const GUILD_ADDED = 33;
	/**
	 * new player points account was opened
	 */
	const PLAYERDKP_UPDATED = 34;
	/**
	 * points account was deleted
	 */
	const PLAYERDKP_DELETED = 35;
	/**
	 * a game was added
	 */
	const GAME_ADDED = 36;
	/**
	 * a game was deleted
	 */
	const GAME_DELETED = 37;
	/**
	 * settings were updated
	 */
	const SETTINGS_CHANGED = 38;
	/**
	 * portal settings were changed
	 */
	const PORTAL_CHANGED = 39;
	/**
	 * a faction was deleted
	 */
	const FACTION_DELETED = 40;
	/**
	 * bbguild logs were purged
	 */
	const LOG_DELETED = 41;
	/**
	 * a faction was added
	 */
	const FACTION_ADDED = 42;
	/**
	 * a race was added
	 */
	const RACE_ADDED = 43;
	/**
	 * a race was deleted
	 */
	const RACE_DELETED = 44;
	/**
	 * a class was added
	 */
	const CLASS_ADDED = 45;
	/**
	 * a class was deleted
	 */
	const CLASS_DELETED = 46;
	/**
	 * a race was updated
	 */
	const RACE_UPDATED = 47;
	/**
	 * a class was updated
	 */
	const CLASS_UPDATED = 48;
	/**
	 * a previously inactive player was reactivated
	 */
	const PLAYER_DEACTIVATED = 49;
	/**
	 * a guild was updated
	 */
	const GUILD_UPDATED = 50;
	/**
	 * battle.NET is down
	 */
	const ARMORY_DOWN = 51;
	/**
	 * a faction was updated
	 */
	const FACTION_UPDATED = 52;
	/**
	 * A role was added
	 */
	const ROLE_ADDED = 53;
	/**
	 * a role was updated
	 */
	const ROLE_UPDATED = 54;
	/**
	 * a role was updated
	 */
	const ROLE_DELETED = 55;
	/**
	 * inactive account
	 */
	const  BATTLENET_ACCOUNT_INACTIVE = 56;
	/**
	 * a guild was deleted
	 */
	const GUILD_DELETED = 57;
	/**
	 * guild roster synced from API
	 */
	const ROSTER_SYNCED = 58;
	/**
	 * character specializations synced from API
	 */
	const SPECS_SYNCED = 59;
	/**
	 * character portraits synced from API
	 */
	const PORTRAITS_SYNCED = 60;
	/**
	 * character-sync cron task failed to sync a character (#361)
	 */
	const CHARACTER_SYNC_FAILED = 61;

}
