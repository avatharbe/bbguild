<?php
/**
 * bbGuild Extension — 2.1.0-b1 migration
 *
 * Adds character-sync scheduler support (#361):
 *   - player_last_synced column on bb_players, tracking when a character
 *     was last synced by the character-sync cron task. Deliberately
 *     separate from the existing generic last_update column, which is
 *     bumped on manual ACP edits too and would otherwise let a manual
 *     edit make a genuinely-stale character look freshly synced.
 *   - bbguild_sync_last_run config key, tracking when the cron task itself
 *     last ran (independent of any individual character's sync time).
 *
 * Canonical version lives in ext::BBGUILD_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v210b1;

class release_2_1_0_b1 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild\migrations\v200rc4\release_2_0_0_rc4'];
	}

	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'bb_players', 'player_last_synced');
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'bb_players' => [
					'player_last_synced' => ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'bb_players' => ['player_last_synced'],
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['bbguild_sync_last_run', 0]],
		];
	}
}
