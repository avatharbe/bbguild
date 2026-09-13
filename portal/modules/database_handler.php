<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @copyright (c) 2023 Board3 Group (www.board3.de) — original design
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace avathar\bbguild\portal\modules;

use phpbb\db\driver\driver_interface;

/**
 * Database operations for portal modules and tabs.
 * All queries are scoped by guild_id.
 */
class database_handler
{
	const MOVE_DIRECTION_UP = -1;
	const MOVE_DIRECTION_DOWN = 1;
	const MOVE_DIRECTION_RIGHT = 1;
	const MOVE_DIRECTION_LEFT = -1;
	const MODULE_ENABLED = 1;
	const DEFAULT_ICON_SIZE = 16;

	protected driver_interface $db;
	protected string $modules_table;
	protected string $tabs_table;

	public function __construct(driver_interface $db, string $modules_table, string $tabs_table)
	{
		$this->db = $db;
		$this->modules_table = $modules_table;
		$this->tabs_table = $tabs_table;
	}

	/**
	 * Get the modules table name.
	 */
	public function get_table_name(): string
	{
		return $this->modules_table;
	}

	/**
	 * Get the tabs table name.
	 */
	public function get_tabs_table_name(): string
	{
		return $this->tabs_table;
	}

	/**
	 * Get module data from database.
	 *
	 * @return array|false
	 */
	public function get_module_data(int $module_id)
	{
		$sql = 'SELECT *
			FROM ' . $this->modules_table . '
			WHERE module_id = ' . (int) $module_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$module_data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $module_data;
	}

	/**
	 * Get all modules for a guild's tab, ordered by column and order.
	 */
	public function get_modules(int $guild_id, int $tab_id): array
	{
		$modules = [];
		$sql = 'SELECT *
			FROM ' . $this->modules_table . '
			WHERE guild_id = ' . (int) $guild_id . '
				AND module_tab = ' . (int) $tab_id . '
			ORDER BY module_column ASC, module_order ASC';
		$result = $this->db->sql_query($sql, 3600);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$modules[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $modules;
	}

	/**
	 * Reset module settings to defaults.
	 */
	public function reset_module(module_interface $module, int $module_id): int
	{
		$sql_ary = [
			'module_name'         => $module->get_name(),
			'module_image_src'    => $module->get_image(),
			'module_group_ids'    => '',
			'module_image_height' => self::DEFAULT_ICON_SIZE,
			'module_image_width'  => self::DEFAULT_ICON_SIZE,
			'module_status'       => self::MODULE_ENABLED,
		];
		$sql = 'UPDATE ' . $this->modules_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE module_id = ' . (int) $module_id;
		$this->db->sql_query($sql);

		return (int) $this->db->sql_affectedrows();
	}

	/**
	 * Move module vertically (up/down) within its column and tab.
	 */
	public function move_module_vertical(int $module_id, array $module_data, int $direction, int $step = 1): bool
	{
		$direction = (int) $direction;
		$step = (int) $step;

		if ($direction === self::MOVE_DIRECTION_DOWN)
		{
			$current_increment = ' + ' . $step;
			$other_increment = ' - ' . $step;
		}
		else
		{
			$current_increment = ' - ' . $step;
			$other_increment = ' + ' . $step;
		}

		$sql = 'UPDATE ' . $this->modules_table . '
			SET module_order = module_order' . $other_increment . '
			WHERE module_order = ' . ($module_data['module_order'] + ($direction * $step)) . '
				AND module_column = ' . (int) $module_data['module_column'] . '
				AND module_tab = ' . (int) $module_data['module_tab'] . '
				AND guild_id = ' . (int) $module_data['guild_id'];
		$this->db->sql_query($sql);
		$updated = (bool) $this->db->sql_affectedrows();

		if ($updated)
		{
			$sql = 'UPDATE ' . $this->modules_table . '
				SET module_order = module_order' . $current_increment . '
				WHERE module_id = ' . (int) $module_id;
			$this->db->sql_query($sql);
		}

		return $updated;
	}

	/**
	 * Move module horizontally (between columns), staying on the same tab.
	 */
	public function move_module_horizontal(int $module_id, array $module_data, int $move_action): void
	{
		$guild_id = (int) $module_data['guild_id'];
		$tab_id = (int) $module_data['module_tab'];
		$new_column = (int) ($module_data['module_column'] + $move_action);

		// Make room in target column
		$sql = 'UPDATE ' . $this->modules_table . '
			SET module_order = module_order + 1
			WHERE module_order >= ' . (int) $module_data['module_order'] . '
				AND module_column = ' . $new_column . '
				AND module_tab = ' . $tab_id . '
				AND guild_id = ' . $guild_id;
		$this->db->sql_query($sql);
		$updated = $this->db->sql_affectedrows();

		// Move module to target column
		$sql = 'UPDATE ' . $this->modules_table . '
			SET module_column = ' . $new_column . '
			WHERE module_id = ' . (int) $module_id;
		$this->db->sql_query($sql);

		// Close gap in source column
		$sql = 'UPDATE ' . $this->modules_table . '
			SET module_order = module_order - 1
			WHERE module_order >= ' . (int) $module_data['module_order'] . '
				AND module_column = ' . (int) $module_data['module_column'] . '
				AND module_tab = ' . $tab_id . '
				AND guild_id = ' . $guild_id;
		$this->db->sql_query($sql);

		// If module was appended at the end
		if (!$updated)
		{
			$sql = 'SELECT MAX(module_order) as new_order
				FROM ' . $this->modules_table . '
				WHERE module_order < ' . (int) $module_data['module_order'] . '
					AND module_column = ' . $new_column . '
					AND module_tab = ' . $tab_id . '
					AND guild_id = ' . $guild_id;
			$this->db->sql_query($sql);
			$new_order = (int) $this->db->sql_fetchfield('new_order') + 1;

			$sql = 'UPDATE ' . $this->modules_table . '
				SET module_order = ' . $new_order . '
				WHERE module_id = ' . (int) $module_id;
			$this->db->sql_query($sql);
		}
	}

	/**
	 * Move a module to a different tab (same column, appended at the end).
	 */
	public function move_module_to_tab(int $module_id, array $module_data, int $target_tab): void
	{
		$guild_id = (int) $module_data['guild_id'];
		$column = (int) $module_data['module_column'];

		$sql = 'SELECT MAX(module_order) as new_order
			FROM ' . $this->modules_table . '
			WHERE module_column = ' . $column . '
				AND module_tab = ' . (int) $target_tab . '
				AND guild_id = ' . $guild_id;
		$this->db->sql_query($sql);
		$new_order = (int) $this->db->sql_fetchfield('new_order') + 1;

		$sql = 'UPDATE ' . $this->modules_table . '
			SET module_tab = ' . (int) $target_tab . ',
				module_order = ' . $new_order . '
			WHERE module_id = ' . (int) $module_id;
		$this->db->sql_query($sql);

		// Close gap in source tab's column
		$sql = 'UPDATE ' . $this->modules_table . '
			SET module_order = module_order - 1
			WHERE module_order > ' . (int) $module_data['module_order'] . '
				AND module_column = ' . $column . '
				AND module_tab = ' . (int) $module_data['module_tab'] . '
				AND guild_id = ' . $guild_id;
		$this->db->sql_query($sql);
	}

	/**
	 * Add a new module to a guild's portal tab.
	 */
	public function add_module(string $classname, int $column, int $order, int $guild_id, int $tab_id, string $name, string $image = ''): int
	{
		$sql_ary = [
			'guild_id'            => $guild_id,
			'module_tab'          => $tab_id,
			'module_classname'    => $classname,
			'module_column'       => $column,
			'module_order'        => $order,
			'module_name'         => $name,
			'module_image_src'    => $image,
			'module_image_width'  => self::DEFAULT_ICON_SIZE,
			'module_image_height' => self::DEFAULT_ICON_SIZE,
			'module_group_ids'    => '',
			'module_status'       => self::MODULE_ENABLED,
		];

		$sql = 'INSERT INTO ' . $this->modules_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return (int) $this->db->sql_nextid();
	}

	/**
	 * Delete a module.
	 */
	public function delete_module(int $module_id, array $module_data): void
	{
		$sql = 'DELETE FROM ' . $this->modules_table . '
			WHERE module_id = ' . (int) $module_id;
		$this->db->sql_query($sql);

		// Close the gap
		$sql = 'UPDATE ' . $this->modules_table . '
			SET module_order = module_order - 1
			WHERE module_column = ' . (int) $module_data['module_column'] . '
				AND module_order > ' . (int) $module_data['module_order'] . '
				AND module_tab = ' . (int) $module_data['module_tab'] . '
				AND guild_id = ' . (int) $module_data['guild_id'];
		$this->db->sql_query($sql);
	}

	/**
	 * Copy default layout (guild_id=0) to a new guild, including its default tab.
	 */
	public function seed_guild_layout(int $guild_id): void
	{
		$source_tab = $this->get_default_tab(0);

		// Always create a default tab for the new guild, even when the
		// guild_id=0 template has none — otherwise a brand-new guild ends
		// up with zero tabs and its ACP "Add Module" action silently
		// no-ops (module_tab stays 0).
		$tab_name = $source_tab['tab_name'] ?? 'Overview';
		$tab_slug = $source_tab['tab_slug'] ?? 'welcome';
		$tab_order = $source_tab !== null ? (int) $source_tab['tab_order'] : 0;

		$new_tab_id = $this->add_tab($guild_id, $tab_name, $tab_slug, $tab_order);

		if ($source_tab === null)
		{
			return;
		}

		$defaults = $this->get_modules(0, (int) $source_tab['tab_id']);
		foreach ($defaults as $row)
		{
			$this->add_module(
				$row['module_classname'],
				(int) $row['module_column'],
				(int) $row['module_order'],
				$guild_id,
				$new_tab_id,
				$row['module_name'],
				$row['module_image_src'] ?? ''
			);
		}
	}

	/**
	 * Get tab data from database.
	 *
	 * @return array|false
	 */
	public function get_tab_data(int $tab_id)
	{
		$sql = 'SELECT *
			FROM ' . $this->tabs_table . '
			WHERE tab_id = ' . (int) $tab_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$tab_data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $tab_data;
	}

	/**
	 * Get all tabs for a guild, ordered by tab_order.
	 */
	public function get_tabs(int $guild_id): array
	{
		$tabs = [];
		$sql = 'SELECT *
			FROM ' . $this->tabs_table . '
			WHERE guild_id = ' . (int) $guild_id . '
			ORDER BY tab_order ASC';
		$result = $this->db->sql_query($sql, 3600);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$tabs[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $tabs;
	}

	/**
	 * Get a guild's tab by slug, or null if not found.
	 *
	 * @return array|null
	 */
	public function get_tab_by_slug(int $guild_id, string $slug)
	{
		$sql = 'SELECT *
			FROM ' . $this->tabs_table . '
			WHERE guild_id = ' . (int) $guild_id . "
				AND tab_slug = '" . $this->db->sql_escape($slug) . "'";
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row !== false ? $row : null;
	}

	/**
	 * Get a guild's default tab (lowest tab_order), or null if it has no tabs.
	 *
	 * @return array|null
	 */
	public function get_default_tab(int $guild_id)
	{
		$tabs = $this->get_tabs($guild_id);

		return $tabs[0] ?? null;
	}

	/**
	 * Add a new tab to a guild.
	 */
	public function add_tab(int $guild_id, string $name, string $slug, int $order, int $status = self::MODULE_ENABLED): int
	{
		$sql_ary = [
			'guild_id'   => $guild_id,
			'tab_name'   => $name,
			'tab_slug'   => $slug,
			'tab_order'  => $order,
			'tab_status' => $status,
		];

		$sql = 'INSERT INTO ' . $this->tabs_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return (int) $this->db->sql_nextid();
	}

	/**
	 * Rename a tab / change its slug.
	 */
	public function edit_tab(int $tab_id, string $name, string $slug): bool
	{
		$sql_ary = [
			'tab_name' => $name,
			'tab_slug' => $slug,
		];

		$sql = 'UPDATE ' . $this->tabs_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE tab_id = ' . (int) $tab_id;
		$this->db->sql_query($sql);

		return (bool) $this->db->sql_affectedrows();
	}

	/**
	 * Delete a tab, reassigning its modules to the guild's next remaining tab
	 * and closing the order gap left behind.
	 */
	public function delete_tab(int $tab_id, int $guild_id): void
	{
		$tabs = $this->get_tabs($guild_id);
		$deleted_order = null;
		$fallback_id = 0;

		foreach ($tabs as $tab)
		{
			if ((int) $tab['tab_id'] === $tab_id)
			{
				$deleted_order = (int) $tab['tab_order'];
				continue;
			}
			if (!$fallback_id)
			{
				$fallback_id = (int) $tab['tab_id'];
			}
		}

		if ($fallback_id)
		{
			// Reassign each module individually, appended at the end of its
			// column in the fallback tab (mirroring move_module_to_tab()),
			// instead of a bare bulk UPDATE that would leave modules with
			// their old module_order and risk colliding with modules
			// already in the same column of the fallback tab.
			$modules = $this->get_modules($guild_id, $tab_id);
			$column_max_order = [];

			foreach ($modules as $module)
			{
				$column = (int) $module['module_column'];

				if (!isset($column_max_order[$column]))
				{
					$sql = 'SELECT MAX(module_order) as max_order
						FROM ' . $this->modules_table . '
						WHERE module_column = ' . $column . '
							AND module_tab = ' . $fallback_id . '
							AND guild_id = ' . (int) $guild_id;
					$this->db->sql_query($sql);
					$column_max_order[$column] = (int) $this->db->sql_fetchfield('max_order');
				}

				$column_max_order[$column]++;

				$sql = 'UPDATE ' . $this->modules_table . '
					SET module_tab = ' . $fallback_id . ',
						module_order = ' . $column_max_order[$column] . '
					WHERE module_id = ' . (int) $module['module_id'];
				$this->db->sql_query($sql);
			}
		}

		$sql = 'DELETE FROM ' . $this->tabs_table . '
			WHERE tab_id = ' . (int) $tab_id . '
				AND guild_id = ' . (int) $guild_id;
		$this->db->sql_query($sql);

		if ($deleted_order !== null)
		{
			$sql = 'UPDATE ' . $this->tabs_table . '
				SET tab_order = tab_order - 1
				WHERE tab_order > ' . $deleted_order . '
					AND guild_id = ' . (int) $guild_id;
			$this->db->sql_query($sql);
		}
	}

	/**
	 * Move a tab vertically (up/down) within a guild's tab list.
	 */
	public function move_tab_vertical(int $tab_id, array $tab_data, int $direction): bool
	{
		$direction = (int) $direction;

		if ($direction === self::MOVE_DIRECTION_DOWN)
		{
			$current_increment = ' + 1';
			$other_increment = ' - 1';
		}
		else
		{
			$current_increment = ' - 1';
			$other_increment = ' + 1';
		}

		$sql = 'UPDATE ' . $this->tabs_table . '
			SET tab_order = tab_order' . $other_increment . '
			WHERE tab_order = ' . ((int) $tab_data['tab_order'] + $direction) . '
				AND guild_id = ' . (int) $tab_data['guild_id'];
		$this->db->sql_query($sql);
		$updated = (bool) $this->db->sql_affectedrows();

		if ($updated)
		{
			$sql = 'UPDATE ' . $this->tabs_table . '
				SET tab_order = tab_order' . $current_increment . '
				WHERE tab_id = ' . (int) $tab_id;
			$this->db->sql_query($sql);
		}

		return $updated;
	}
}
