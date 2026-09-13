<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @copyright (c) 2023 Board3 Group (www.board3.de) — original design
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace avathar\bbguild\portal\modules;

use avathar\bbguild\portal\columns;
use avathar\bbguild\portal\module_helper;
use avathar\bbguild\portal\module_registry;
use avathar\bbguild\portal\portal_config;
use phpbb\cache\service as cache_service;
use phpbb\db\driver\driver_interface;
use phpbb\request\request_interface;
use phpbb\user;

/**
 * Module and tab management: add, delete, move, reset modules and tabs
 * in a guild's portal. Used by the ACP portal module.
 */
class manager
{
	protected cache_service $cache;
	protected driver_interface $db;
	protected module_helper $module_helper;
	protected columns $portal_columns;
	protected module_registry $module_registry;
	protected constraints_handler $constraints_handler;
	protected database_handler $database_handler;
	protected portal_config $portal_config;
	protected request_interface $request;
	protected user $user;
	protected string $modules_table;
	protected string $tabs_table;

	protected ?module_interface $module = null;
	protected string $u_action = '';

	public function __construct(
		cache_service $cache,
		driver_interface $db,
		module_helper $module_helper,
		columns $portal_columns,
		module_registry $module_registry,
		constraints_handler $constraints_handler,
		database_handler $database_handler,
		portal_config $portal_config,
		request_interface $request,
		user $user,
		string $modules_table,
		string $tabs_table
	)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->module_helper = $module_helper;
		$this->portal_columns = $portal_columns;
		$this->module_registry = $module_registry;
		$this->constraints_handler = $constraints_handler;
		$this->database_handler = $database_handler;
		$this->portal_config = $portal_config;
		$this->request = $request;
		$this->user = $user;
		$this->modules_table = $modules_table;
		$this->tabs_table = $tabs_table;
	}

	/**
	 * Set ACP action URL.
	 */
	public function set_u_action(string $u_action): self
	{
		$this->u_action = $u_action;
		return $this;
	}

	/**
	 * Get module data for management operations.
	 *
	 * @return array|false
	 */
	public function get_move_module_data(int $module_id)
	{
		return $this->database_handler->get_module_data($module_id);
	}

	/**
	 * Move module vertically (up or down).
	 */
	public function move_module_vertical(int $module_id, int $direction): bool
	{
		$module_data = $this->get_move_module_data($module_id);
		if ($module_data === false)
		{
			return false;
		}

		$result = $this->database_handler->move_module_vertical($module_id, $module_data, $direction);
		if ($result)
		{
			$this->cache->destroy('sql', $this->modules_table);
		}

		return $result;
	}

	/**
	 * Move module horizontally (between columns).
	 */
	public function move_module_horizontal(int $module_id, int $direction): bool
	{
		$module_data = $this->get_move_module_data($module_id);
		if ($module_data === false)
		{
			return false;
		}

		$module = $this->module_registry->get_module($module_data['module_classname']);
		if (!$module instanceof module_interface)
		{
			return false;
		}

		// Find the next valid column in the given direction
		// Columns: top=1, center=2, right=3, bottom=4
		$min_column = $this->portal_columns->string_to_number('top');
		$max_column = $this->portal_columns->string_to_number('bottom');
		$current_column = (int) $module_data['module_column'];
		$target_column_num = $current_column + $direction;

		// Skip columns that aren't in the module's allowed bitmask
		while ($target_column_num >= $min_column && $target_column_num <= $max_column)
		{
			$target_name = $this->portal_columns->number_to_string($target_column_num);
			if ($target_name !== '' && ($module->get_allowed_columns() & $this->portal_columns->string_to_constant($target_name)))
			{
				break;
			}
			$target_column_num += $direction;
		}

		if ($target_column_num < $min_column || $target_column_num > $max_column)
		{
			return false;
		}

		// Calculate the step to jump (may skip columns)
		$step = $target_column_num - $current_column;
		$this->database_handler->move_module_horizontal($module_id, $module_data, $step);
		$this->cache->destroy('sql', $this->modules_table);

		return true;
	}

	/**
	 * Move module to a specific column.
	 */
	public function move_module_to_column(int $module_id, int $target_column): bool
	{
		$module_data = $this->get_move_module_data($module_id);
		if ($module_data === false)
		{
			return false;
		}

		if ((int) $module_data['module_column'] === $target_column)
		{
			return true;
		}

		$module = $this->module_registry->get_module($module_data['module_classname']);
		if (!$module instanceof module_interface)
		{
			return false;
		}

		// Verify target column is valid and allowed
		$target_name = $this->portal_columns->number_to_string($target_column);
		if ($target_name === '')
		{
			return false;
		}

		if (!($module->get_allowed_columns() & $this->portal_columns->string_to_constant($target_name)))
		{
			return false;
		}

		$step = $target_column - (int) $module_data['module_column'];
		$this->database_handler->move_module_horizontal($module_id, $module_data, $step);
		$this->cache->destroy('sql', $this->modules_table);

		return true;
	}

	/**
	 * Move a module to a different tab.
	 */
	public function move_module_to_tab(int $module_id, int $target_tab): bool
	{
		$module_data = $this->get_move_module_data($module_id);
		if ($module_data === false)
		{
			return false;
		}

		if ((int) $module_data['module_tab'] === $target_tab)
		{
			return true;
		}

		$this->database_handler->move_module_to_tab($module_id, $module_data, $target_tab);
		$this->cache->destroy('sql', $this->modules_table);

		return true;
	}

	/**
	 * Add a module to a guild's portal tab.
	 */
	public function add_module(string $classname, int $column, int $guild_id, int $tab_id): int
	{
		$module = $this->module_registry->get_module($classname);
		if (!$module instanceof module_interface)
		{
			return 0;
		}

		if (!$this->constraints_handler->can_add_module($module, $column))
		{
			return 0;
		}

		// Get last order in this column for this guild's tab
		$modules = $this->database_handler->get_modules($guild_id, $tab_id);
		$last_order = 0;
		foreach ($modules as $row)
		{
			if ((int) $row['module_column'] === $column)
			{
				$last_order = max($last_order, (int) $row['module_order']);
			}
		}

		$module_id = $this->database_handler->add_module(
			$classname,
			$column,
			$last_order + 1,
			$guild_id,
			$tab_id,
			$module->get_name(),
			$module->get_image()
		);

		if ($module_id)
		{
			$module->set_guild_context($guild_id);
			$module->install($module_id);
			$this->cache->destroy('sql', $this->modules_table);
		}

		return $module_id;
	}

	/**
	 * Delete a module from a guild's portal.
	 */
	public function delete_module(int $module_id): bool
	{
		$module_data = $this->get_move_module_data($module_id);
		if ($module_data === false)
		{
			return false;
		}

		$module = $this->module_registry->get_module($module_data['module_classname']);
		if ($module instanceof module_interface)
		{
			$module->set_guild_context((int) $module_data['guild_id']);
			$module->uninstall($module_id);
		}

		$this->database_handler->delete_module($module_id, $module_data);
		$this->cache->destroy('sql', $this->modules_table);

		return true;
	}

	/**
	 * Reset a module to its default settings.
	 */
	public function reset_module(int $module_id): bool
	{
		$module_data = $this->get_move_module_data($module_id);
		if ($module_data === false)
		{
			return false;
		}

		$module = $this->module_registry->get_module($module_data['module_classname']);
		if (!$module instanceof module_interface)
		{
			return false;
		}

		$this->database_handler->reset_module($module, $module_id);
		$module->set_guild_context((int) $module_data['guild_id']);
		$module->install($module_id);
		$this->cache->purge();

		return true;
	}

	/**
	 * Get all tabs for a guild.
	 */
	public function get_tabs(int $guild_id): array
	{
		return $this->database_handler->get_tabs($guild_id);
	}

	/**
	 * Add a new tab to a guild, appended after its existing tabs.
	 */
	public function add_tab(string $name, string $slug, int $guild_id): int
	{
		$tabs = $this->database_handler->get_tabs($guild_id);
		$order = count($tabs);

		$tab_id = $this->database_handler->add_tab($guild_id, $name, $slug, $order);
		if ($tab_id)
		{
			$this->cache->destroy('sql', $this->tabs_table);
		}

		return $tab_id;
	}

	/**
	 * Rename a tab / change its slug.
	 */
	public function edit_tab(int $tab_id, string $name, string $slug): bool
	{
		$result = $this->database_handler->edit_tab($tab_id, $name, $slug);
		if ($result)
		{
			$this->cache->destroy('sql', $this->tabs_table);
		}

		return $result;
	}

	/**
	 * Delete a tab. Refuses if it is the guild's only remaining tab.
	 */
	public function delete_tab(int $tab_id, int $guild_id): bool
	{
		$tabs = $this->database_handler->get_tabs($guild_id);
		if (count($tabs) <= 1)
		{
			return false;
		}

		$this->database_handler->delete_tab($tab_id, $guild_id);
		$this->cache->destroy('sql', $this->modules_table);
		$this->cache->destroy('sql', $this->tabs_table);

		return true;
	}

	/**
	 * Move a tab vertically (up or down) within a guild's tab list.
	 */
	public function move_tab_vertical(int $tab_id, int $direction): bool
	{
		$tab_data = $this->database_handler->get_tab_data($tab_id);
		if ($tab_data === false)
		{
			return false;
		}

		$result = $this->database_handler->move_tab_vertical($tab_id, $tab_data, $direction);
		if ($result)
		{
			$this->cache->destroy('sql', $this->tabs_table);
		}

		return $result;
	}
}
