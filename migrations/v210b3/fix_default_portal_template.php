<?php
/**
 * bbGuild Extension — seed the guild_id=0 portal template (#374)
 *
 * database_handler::seed_guild_layout() copies its default portal layout
 * (MOTD, Roster, Recruitment) from guild_id=0, per its own docblock — but
 * no migration ever populated that template. The only layout ever seeded
 * lives at guild_id=1 (the removable demo guild from v200b3, written before
 * the guild_id=0 template convention existed). Net effect: every guild
 * created since #360 shipped got an empty portal (no roster, nothing).
 *
 * This migration seeds the guild_id=0 template directly (not copied from
 * guild_id=1, since that demo guild/its modules may already have been
 * removed via remove_sample_data() on any given install), then backfills
 * the same default layout onto any existing real guild (guild_id > 0)
 * that still has zero tabs — fixing already-affected guilds without
 * requiring manual ACP configuration.
 *
 * Canonical version lives in ext::BBGUILD_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v210b3;

class fix_default_portal_template extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild\migrations\v210b2\add_portal_tabs'];
	}

	public function effectively_installed()
	{
		$sql = 'SELECT tab_id FROM ' . $this->table_prefix . 'bb_portal_tabs WHERE guild_id = 0';
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'seed_template_and_backfill']]],
		];
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
