<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Character Sync Interface
 * Contract that a game plugin implements to let the core character-sync
 * cron task (avathar\bbguild\cron\task\character_sync) delegate syncing
 * a single character to that plugin.
 *
 */

namespace avathar\bbguild\model\games;

/**
 * Interface character_sync_interface
 *
 * @package avathar\bbguild\model\games
 */
interface character_sync_interface
{
	/**
	 * Get the unique game identifier this handler syncs (e.g. 'wow').
	 * Matches bb_players.game_id.
	 *
	 * @return string
	 */
	public function get_game_id(): string;

	/**
	 * Sync a single character from this game's external data source.
	 *
	 * @param array $player_row The full bb_players row for this character
	 * @return bool True on success, false on failure
	 */
	public function sync_character(array $player_row): bool;
}
