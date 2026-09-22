<?php
/**
 * bbGuild Extension — 2.1.0 squashed migration
 *
 * Combines every 2.1.x-line migration (v210b1 through v210b3) into the
 * single final schema/data state 2.1.0 ships with: character-sync
 * scheduler support (#361), page-level portal tabs (#360), and the
 * guild_id=0 default-template fix (#374).
 *
 * Canonical version lives in ext::BBGUILD_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v210;

class release_2_1_0 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild\migrations\v200\release_2_0_0'];
	}

	/**
	 * The guild_id=0 template tab is the last artifact seeded by this
	 * chain (former v210b3) — checking for it is equivalent to checking
	 * the whole chain ran.
	 */
	public function effectively_installed()
	{
		$sql = 'SELECT tab_id FROM ' . $this->table_prefix . 'bb_portal_tabs WHERE guild_id = 0';
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
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
				$this->table_prefix . 'bb_players' => [
					'player_last_synced' => ['UINT', 0],
				],
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
				$this->table_prefix . 'bb_players' => ['player_last_synced'],
			],
			'drop_tables' => [
				$this->table_prefix . 'bb_portal_tabs',
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['bbguild_sync_last_run', 0]],

			// 1. Create one default tab per existing guild_id already present
			//    in bb_portal_modules (on a fresh install: just guild_id=1,
			//    the sample "Test Guild" seeded by the 2.0.0 migration) and
			//    reassign that guild's existing modules onto it.
			['custom', [[$this, 'backfill_default_tabs']]],

			// 2. Seed the guild_id=0 template that seed_guild_layout() reads
			//    from when creating a brand new guild (nothing seeds this on
			//    its own -- #374), then backfill any other real guild that
			//    still has zero tabs. Guild 1 is already covered by step 1,
			//    so this only ever matters for guilds created between #360
			//    shipping and this fix landing.
			['custom', [[$this, 'seed_template_and_backfill']]],
		];
	}

	/**
	 * Create one default tab per existing guild_id in bb_portal_modules
	 * (including the guild_id=0 template row, if already present) and
	 * reassign that guild's existing modules onto it.
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
				SET module_tab = ' . (int) $tab_id . '
				WHERE guild_id = ' . (int) $guild_id;
			$this->db->sql_query($sql);
		}
	}

	protected function default_modules()
	{
		return [
			['module_classname' => '\avathar\bbguild\portal\modules\motd',        'module_column' => 1, 'module_order' => 1, 'module_name' => 'BBGUILD_PORTAL_MOTD'],
			['module_classname' => '\avathar\bbguild\portal\modules\roster',      'module_column' => 2, 'module_order' => 1, 'module_name' => 'BBGUILD_PORTAL_ROSTER'],
			['module_classname' => '\avathar\bbguild\portal\modules\recruitment', 'module_column' => 3, 'module_order' => 1, 'module_name' => 'BBGUILD_PORTAL_RECRUITMENT'],
		];
	}

	protected function add_default_layout($tabs_table, $modules_table, $guild_id)
	{
		$sql = 'INSERT INTO ' . $tabs_table . ' ' . $this->db->sql_build_array('INSERT', [
			'guild_id'   => $guild_id,
			'tab_name'   => 'Overview',
			'tab_slug'   => 'welcome',
			'tab_order'  => 0,
			'tab_status' => 1,
		]);
		$this->db->sql_query($sql);
		$tab_id = (int) $this->db->sql_nextid();

		foreach ($this->default_modules() as $module)
		{
			$sql_ary = array_merge($module, [
				'guild_id'            => $guild_id,
				'module_tab'          => $tab_id,
				'module_image_src'    => '',
				'module_icon'         => '',
				'module_icon_size'    => 16,
				'module_image_width'  => 16,
				'module_image_height' => 16,
				'module_group_ids'    => '',
				'module_status'       => 1,
			]);

			$sql = 'INSERT INTO ' . $modules_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}

	public function seed_template_and_backfill()
	{
		$tabs_table = $this->table_prefix . 'bb_portal_tabs';
		$modules_table = $this->table_prefix . 'bb_portal_modules';
		$guild_table = $this->table_prefix . 'bb_guild';

		// 1. Seed the guild_id=0 template that seed_guild_layout() reads from.
		$this->add_default_layout($tabs_table, $modules_table, 0);

		// 2. Backfill any existing real guild that still has zero tabs
		// (created after #360 shipped, before this fix landed).
		$sql = 'SELECT g.id
			FROM ' . $guild_table . ' g
			LEFT JOIN ' . $tabs_table . ' t ON t.guild_id = g.id
			WHERE g.id > 0
				AND t.tab_id IS NULL';
		$result = $this->db->sql_query($sql);
		$guild_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$guild_ids[] = (int) $row['id'];
		}
		$this->db->sql_freeresult($result);

		foreach ($guild_ids as $guild_id)
		{
			$this->add_default_layout($tabs_table, $modules_table, $guild_id);
		}
	}
}
