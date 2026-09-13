<?php
/**
 * bbGuild Extension — Portal page-level guild tabs (#360)
 *
 * Adds the bb_portal_tabs table and a module_tab column on bb_portal_modules,
 * so a guild's portal page can have more than one tab, each with its own
 * independently column-organized set of modules.
 *
 * Backward compat: every existing guild_id present in bb_portal_modules
 * (including guild_id=0, the template layout seed_guild_layout() copies for
 * new guilds) gets one auto-created default tab ("Overview" / slug "welcome"
 * — the current de-facto default page), and every existing module row is
 * backfilled onto that tab. Net effect: zero visual change for any existing
 * guild until a second tab is added in ACP.
 *
 * Canonical version lives in ext::BBGUILD_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v210b2;

class add_portal_tabs extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild\migrations\v210b1\release_2_1_0_b1'];
	}

	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'bb_portal_tabs');
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'bb_portal_tabs' => [
					'COLUMNS' => [
						'tab_id'     => ['UINT', null, 'auto_increment'],
						'guild_id'   => ['USINT', 0],
						'tab_name'   => ['VCHAR_UNI:100', ''],
						'tab_slug'   => ['VCHAR:100', ''],
						'tab_order'  => ['USINT', 0],
						'tab_status' => ['TINT:1', 1],
					],
					'PRIMARY_KEY' => 'tab_id',
					'KEYS' => [
						'guild_order' => ['INDEX', ['guild_id', 'tab_order']],
						'UQ01'        => ['UNIQUE', ['guild_id', 'tab_slug']],
					],
				],
			],
			'add_columns' => [
				$this->table_prefix . 'bb_portal_modules' => [
					'module_tab' => ['UINT', 0],
				],
			],
			'drop_keys' => [
				$this->table_prefix . 'bb_portal_modules' => ['guild_col_order'],
			],
			'add_index' => [
				$this->table_prefix . 'bb_portal_modules' => [
					'guild_col_order' => ['guild_id', 'module_tab', 'module_column', 'module_order'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_keys' => [
				$this->table_prefix . 'bb_portal_modules' => ['guild_col_order'],
			],
			'add_index' => [
				$this->table_prefix . 'bb_portal_modules' => [
					'guild_col_order' => ['guild_id', 'module_column', 'module_order'],
				],
			],
			'drop_columns' => [
				$this->table_prefix . 'bb_portal_modules' => ['module_tab'],
			],
			'drop_tables' => [
				$this->table_prefix . 'bb_portal_tabs',
			],
		];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'backfill_default_tabs']]],
		];
	}

	/**
	 * Create one default tab per existing guild_id in bb_portal_modules
	 * (including the guild_id=0 template row) and reassign that guild's
	 * existing modules onto it.
	 */
	public function backfill_default_tabs()
	{
		$modules_table = $this->table_prefix . 'bb_portal_modules';
		$tabs_table = $this->table_prefix . 'bb_portal_tabs';

		$sql = 'SELECT DISTINCT guild_id FROM ' . $modules_table;
		$result = $this->db->sql_query($sql);
		$guild_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$guild_ids[] = (int) $row['guild_id'];
		}
		$this->db->sql_freeresult($result);

		foreach ($guild_ids as $guild_id)
		{
			$sql_ary = [
				'guild_id'   => $guild_id,
				'tab_name'   => 'Overview',
				'tab_slug'   => 'welcome',
				'tab_order'  => 0,
				'tab_status' => 1,
			];
			$sql = 'INSERT INTO ' . $tabs_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
			$tab_id = (int) $this->db->sql_nextid();

			$sql = 'UPDATE ' . $modules_table . '
				SET module_tab = ' . $tab_id . '
				WHERE guild_id = ' . $guild_id;
			$this->db->sql_query($sql);
		}
	}
}
