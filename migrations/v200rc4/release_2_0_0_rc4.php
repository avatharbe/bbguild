<?php
/**
 * bbGuild Extension — 2.0.0-rc4 ACP restructure migration
 *
 * Adds a new "Game settings" category (ACP_BBGUILD_GAMESETTINGS) under the
 * bbGuild top category and moves the "Game List" module (game_module) into it,
 * out of "General Settings" (ACP_BBGUILD_MAINPAGE). The companion bbguildwow
 * migration moves its "BattleNet API" module into the same category (it
 * depends_on this one so the category exists first).
 *
 * Runs on both fresh and existing installs: on a fresh install v200b3 first
 * creates game_module under MAINPAGE, then this migration moves it, so no
 * in-place edit of the base migration is needed.
 *
 * Canonical version lives in ext::BBGUILD_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v200rc4;

class release_2_0_0_rc4 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild\migrations\v200rc2\release_2_0_0_rc2'];
	}

	public function effectively_installed()
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
				AND module_langname = 'ACP_BBGUILD_GAMESETTINGS'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function update_data()
	{
		return [
			// New "Game settings" category under the bbGuild top category
			['module.add', ['acp', 'ACP_CAT_BBGUILD', 'ACP_BBGUILD_GAMESETTINGS']],

			// Move "Game List" out of General Settings into Game settings
			['module.remove', ['acp', 'ACP_BBGUILD_MAINPAGE', [
				'module_basename' => '\avathar\bbguild\acp\game_module',
			]]],
			['module.add', ['acp', 'ACP_BBGUILD_GAMESETTINGS', [
				'module_basename' => '\avathar\bbguild\acp\game_module',
				'modes'           => ['listgames', 'editgames', 'addfaction', 'addrace', 'addclass', 'addrole'],
			]]],
		];
	}

	public function revert_data()
	{
		return [
			// Move "Game List" back to General Settings
			['module.remove', ['acp', 'ACP_BBGUILD_GAMESETTINGS', [
				'module_basename' => '\avathar\bbguild\acp\game_module',
			]]],
			['module.add', ['acp', 'ACP_BBGUILD_MAINPAGE', [
				'module_basename' => '\avathar\bbguild\acp\game_module',
				'modes'           => ['listgames', 'editgames', 'addfaction', 'addrace', 'addclass', 'addrole'],
			]]],

			// Remove the Game settings category
			['module.remove', ['acp', 'ACP_CAT_BBGUILD', 'ACP_BBGUILD_GAMESETTINGS']],
		];
	}
}
