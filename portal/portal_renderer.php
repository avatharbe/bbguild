<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @copyright (c) 2023 Board3 Group (www.board3.de) — original design
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

namespace avathar\bbguild\portal;

use avathar\bbguild\portal\modules\database_handler;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\template\template;
use phpbb\user;

/**
 * Renders the portal page by loading and assembling all enabled modules
 * for a given guild's active tab. Called from view_controller::handleview().
 */
class portal_renderer
{
	protected columns $portal_columns;
	protected module_helper $module_helper;
	protected database_handler $database_handler;
	protected config $config;
	protected template $template;
	protected user $user;
	protected helper $helper;

	/** @var array Module count per column */
	protected array $module_count = [];

	public function __construct(
		columns $portal_columns,
		module_helper $module_helper,
		database_handler $database_handler,
		config $config,
		template $template,
		user $user,
		helper $helper
	)
	{
		$this->portal_columns = $portal_columns;
		$this->module_helper = $module_helper;
		$this->database_handler = $database_handler;
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
	}

	/**
	 * Render all portal modules for a guild's tab (resolved from $tab_slug,
	 * falling back to the guild's default tab when empty or unknown).
	 */
	public function render(int $guild_id, string $tab_slug = ''): void
	{
		$this->module_count = [
			'top'    => 0,
			'center' => 0,
			'right'  => 0,
			'bottom' => 0,
		];

		$tabs = $this->database_handler->get_tabs($guild_id);
		$active_tab = $this->resolve_tab($tabs, $tab_slug);

		$this->assign_tab_bar($tabs, $active_tab, $guild_id);

		if ($active_tab === null)
		{
			$this->assign_column_vars();
			return;
		}

		$portal_modules = $this->database_handler->get_modules($guild_id, (int) $active_tab['tab_id']);

		foreach ($portal_modules as $row)
		{
			$module = $this->module_helper->get_portal_module($row);
			if (!$module)
			{
				continue;
			}

			// Set guild context so module can query guild-specific data
			$module->set_guild_context($guild_id);

			// Load language
			$this->module_helper->load_module_language($module);

			// Get template based on column type
			$template_module = $this->get_module_template($row, $module);
			if (empty($template_module))
			{
				continue;
			}

			// Assign to template block
			$this->module_helper->assign_module_vars($row, $template_module);
		}

		// Assign column visibility vars
		$this->assign_column_vars();
	}

	/**
	 * Resolve which tab is active: an unknown/empty slug falls back to the
	 * guild's default tab (lowest tab_order).
	 *
	 * @return array|null
	 */
	protected function resolve_tab(array $tabs, string $tab_slug)
	{
		if ($tab_slug !== '')
		{
			foreach ($tabs as $tab)
			{
				if ($tab['tab_slug'] === $tab_slug)
				{
					return $tab;
				}
			}
		}

		return $tabs[0] ?? null;
	}

	/**
	 * Assign the tab bar template loop.
	 */
	protected function assign_tab_bar(array $tabs, $active_tab, int $guild_id): void
	{
		$active_tab_id = $active_tab['tab_id'] ?? null;

		foreach ($tabs as $tab)
		{
			$this->template->assign_block_vars('tabs', [
				'TAB_NAME'   => $tab['tab_name'],
				'TAB_SLUG'   => $tab['tab_slug'],
				'TAB_ACTIVE' => $active_tab_id !== null && (int) $tab['tab_id'] === (int) $active_tab_id,
				'U_TAB'      => $this->helper->route('avathar_bbguild_00', [
					'guild_id' => $guild_id,
					'page'     => $tab['tab_slug'],
				]),
			]);
		}
	}

	/**
	 * Get the appropriate template for a module based on its column.
	 *
	 * @return string|array|null
	 */
	protected function get_module_template(array $row, modules\module_interface $module)
	{
		$column = $this->portal_columns->number_to_string((int) $row['module_column']);
		if ($column === '')
		{
			return null;
		}

		if ($column === 'right')
		{
			$this->module_count['right']++;
			return $module->get_template_side((int) $row['module_id']);
		}

		// top, center, and bottom use get_template_center
		$this->module_count[$column]++;
		return $module->get_template_center((int) $row['module_id']);
	}

	/**
	 * Assign template variables for column visibility.
	 */
	protected function assign_column_vars(): void
	{
		$right_column_width = isset($this->config['bbguild_portal_right_width'])
			? (int) $this->config['bbguild_portal_right_width']
			: 200;

		$this->template->assign_vars([
			'S_TOP_COLUMN'           => $this->module_count['top'] > 0,
			'S_CENTER_COLUMN'        => $this->module_count['center'] > 0,
			'S_RIGHT_COLUMN'         => $this->module_count['right'] > 0,
			'S_BOTTOM_COLUMN'        => $this->module_count['bottom'] > 0,
			'S_PORTAL_RIGHT_COLUMN'  => $right_column_width,
			'S_PORTAL_ACTIVE'        => true,
		]);
	}
}
