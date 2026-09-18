# Issue #360 Implementation Plan: Portal page-level guild tabs

**Design doc:** `../specs/2026-09-12-issue-360-portal-tabs-design.md` (approved). This plan implements that design in 5 tasks.

**Branch:** `issue-360-portal-tabs` (worktree already created).

**Migration dependency chain tip used:** `\avathar\bbguild\migrations\v210b1\release_2_1_0_b1` — confirmed via grep across all `migrations/**/*.php` for `depends_on()` return values; nothing in the tree currently depends on it. The new migration in Task 1 depends on it.

## Judgment calls made while writing this plan (not fully spelled out in the design doc)

1. **`module_tab` scoping in the move/delete SQL.** The design doc says `get_modules()` needs `guild_id + module_tab + module_column` together (hence the folded index), but doesn't spell out that `move_module_vertical`, `move_module_horizontal`, `delete_module`, and `add_module`'s "last order in column" lookup in `manager::add_module()` must ALSO scope by `module_tab`, not just `module_column` + `guild_id`. Without this, two different tabs' column-1 modules would share one `module_order` sequence space and the swap-based reorder logic would silently touch the wrong tab's row. Fixed by threading `module_tab` through every one of these queries, sourced from the already-fetched `module_data` row (no signature changes needed there — `SELECT *` already returns it once the column exists).
2. **`seed_guild_layout()` must now also create the new guild's default tab**, not just copy modules — a new guild has zero tabs otherwise. It looks up guild 0's default tab, creates one tab of the same name/slug/order for the new guild, then copies guild 0's modules into it.
3. **A `move_to_tab` action was added** (mirroring the existing `move_to_column`), because the design doc explicitly calls for a "Tab `<select>` next to the existing Column `<select>`" on each module row (design §3) — that control needs a backing action. Unlike `move_to_column`, there is no allowed-tab bitmask to validate against (modules have no tab-class constraint), so it's a straight reassignment + reorder, closer to `delete_module`'s gap-closing than `move_module_horizontal`'s column-hopping.
4. **Tab CRUD lives on `manager.php`**, not as a new dependency injected directly into `admin_portal.php`. `manager` already wraps `database_handler` with cache invalidation (`cache->destroy('sql', ...)`) for every mutation, and `admin_portal.php` already holds a `manager` instance for exactly this purpose. Reusing that facade avoids adding a second `database_handler`-touching entry point with independent cache-invalidation logic to keep in sync.
5. **A guild's last remaining tab cannot be deleted** (`manager::delete_tab()` refuses if `count(get_tabs()) <= 1`) — the design doc's "always resolves to something" invariant would otherwise be violated the moment a guild's only tab is removed.
6. **`tab_slug` uniqueness is enforced at both layers**: a `UNIQUE(guild_id, tab_slug)` key at the schema level (backstop), and an explicit `get_tab_by_slug()` check in `admin_portal.php` before insert/update (so the ACP shows a proper error message instead of a raw SQL failure).

---

## Task 1: Migration — `bb_portal_tabs` table, `module_tab` column, data backfill

**Files:**
- Create: `migrations/v210b2/add_portal_tabs.php`

### Step 1.1 — Write the migration

```php
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
```

**Note for Task 5's live-board verification:** this migration's `update_data()` step is the one piece of this whole issue that mutates existing production rows rather than just adding schema. Task 5 must confirm on the live board that (a) every pre-existing guild still shows exactly the same modules/columns it did before, under one "Overview" tab, and (b) `bb_portal_modules` for guild_id=0 (template) also got a `module_tab` value, not `0`.

---

## Task 2: `database_handler` + `manager` — tab data layer and facade methods

**Files:**
- Modify: `portal/modules/database_handler.php`
- Modify: `portal/modules/manager.php`
- Modify: `config/tables.yml`
- Modify: `config/portal_services.yml`
- Create: `tests/portal/modules/database_handler_test.php`

### Step 2.1 — `config/tables.yml`: add the tabs table parameter

Add, next to the existing `bb_portal_modules` line:

```yaml
    avathar.bbguild.tables.bb_portal_tabs: '%core.table_prefix%bb_portal_tabs'
```

### Step 2.2 — `config/portal_services.yml`: wire the new table into `database_handler` and `manager`

`database_handler`'s arguments gain the tabs table:

```yaml
    avathar.bbguild.portal.modules.database_handler:
        class: avathar\bbguild\portal\modules\database_handler
        arguments:
            - '@dbal.conn'
            - '%avathar.bbguild.tables.bb_portal_modules%'
            - '%avathar.bbguild.tables.bb_portal_tabs%'
```

`manager`'s arguments gain the tabs table (appended, same position pattern as the existing trailing `modules_table` arg):

```yaml
    avathar.bbguild.portal.modules.manager:
        class: avathar\bbguild\portal\modules\manager
        arguments:
            - '@cache'
            - '@dbal.conn'
            - '@avathar.bbguild.portal.module_helper'
            - '@avathar.bbguild.portal.columns'
            - '@avathar.bbguild.portal.module_registry'
            - '@avathar.bbguild.portal.modules.constraints_handler'
            - '@avathar.bbguild.portal.modules.database_handler'
            - '@avathar.bbguild.portal.config'
            - '@request'
            - '@user'
            - '%avathar.bbguild.tables.bb_portal_modules%'
            - '%avathar.bbguild.tables.bb_portal_tabs%'
```

`portal_renderer`'s arguments are unchanged in this task (Task 3 adds a `helper` argument to it).

### Step 2.3 — `database_handler.php`: constructor + tab methods + `module_tab` scoping

Full replacement of the file:

```php
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
		if ($source_tab === null)
		{
			return;
		}

		$new_tab_id = $this->add_tab($guild_id, $source_tab['tab_name'], $source_tab['tab_slug'], (int) $source_tab['tab_order']);

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
			$sql = 'UPDATE ' . $this->modules_table . '
				SET module_tab = ' . $fallback_id . '
				WHERE module_tab = ' . (int) $tab_id . '
					AND guild_id = ' . (int) $guild_id;
			$this->db->sql_query($sql);
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
```

### Step 2.4 — `manager.php`: tab facade methods

Full replacement of the file:

```php
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
```

### Step 2.5 — Test: `tests/portal/modules/database_handler_test.php`

Follows `statistics_test.php`'s style (mock `driver_interface`, script `sql_fetchrow`/`sql_affectedrows` via `willReturnOnConsecutiveCalls`, assert on captured SQL where the WHERE-clause shape itself is the thing under test).

```php
<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild\tests\portal\modules;

use PHPUnit\Framework\TestCase;
use avathar\bbguild\portal\modules\database_handler;

class database_handler_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $db;

	/** @var database_handler */
	protected $handler;

	/** @var array Captured sql_query() strings */
	protected $queries;

	protected function setUp(): void
	{
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$this->queries = [];

		$this->db->method('sql_query')
			->willReturnCallback(function ($sql) {
				$this->queries[] = $sql;
				return true;
			});
		$this->db->method('sql_query_limit')
			->willReturnCallback(function ($sql) {
				$this->queries[] = $sql;
				return true;
			});
		$this->db->method('sql_escape')->willReturnCallback(fn($s) => $s);
		$this->db->method('sql_freeresult')->willReturn(null);
		$this->db->method('sql_build_array')->willReturn('SET_CLAUSE');

		$this->handler = new database_handler($this->db, 'phpbb_bb_portal_modules', 'phpbb_bb_portal_tabs');
	}

	public function test_get_modules_filters_by_guild_and_tab()
	{
		$this->db->method('sql_fetchrow')->willReturn(false);

		$this->handler->get_modules(5, 2);

		$this->assertStringContainsString('WHERE guild_id = 5', $this->queries[0]);
		$this->assertStringContainsString('AND module_tab = 2', $this->queries[0]);
	}

	public function test_get_tabs_orders_by_tab_order()
	{
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['tab_id' => 1, 'guild_id' => 5, 'tab_order' => 0],
			false
		);

		$tabs = $this->handler->get_tabs(5);

		$this->assertCount(1, $tabs);
		$this->assertStringContainsString('ORDER BY tab_order ASC', $this->queries[0]);
	}

	public function test_get_default_tab_returns_lowest_order_tab()
	{
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['tab_id' => 3, 'guild_id' => 5, 'tab_order' => 0],
			['tab_id' => 4, 'guild_id' => 5, 'tab_order' => 1],
			false
		);

		$default_tab = $this->handler->get_default_tab(5);

		$this->assertSame(3, $default_tab['tab_id']);
	}

	public function test_get_default_tab_returns_null_when_no_tabs()
	{
		$this->db->method('sql_fetchrow')->willReturn(false);

		$this->assertNull($this->handler->get_default_tab(5));
	}

	public function test_get_tab_by_slug_returns_null_when_not_found()
	{
		$this->db->method('sql_fetchrow')->willReturn(false);

		$this->assertNull($this->handler->get_tab_by_slug(5, 'unknown'));
	}

	public function test_get_tab_by_slug_returns_matching_row()
	{
		$this->db->method('sql_fetchrow')->willReturn(['tab_id' => 7, 'tab_slug' => 'raids']);

		$tab = $this->handler->get_tab_by_slug(5, 'raids');

		$this->assertSame(7, $tab['tab_id']);
	}

	public function test_move_module_vertical_scopes_by_tab()
	{
		$this->db->method('sql_affectedrows')->willReturn(1);

		$module_data = ['module_order' => 2, 'module_column' => 1, 'module_tab' => 9, 'guild_id' => 5];
		$this->handler->move_module_vertical(42, $module_data, database_handler::MOVE_DIRECTION_UP);

		$this->assertStringContainsString('AND module_tab = 9', $this->queries[0]);
	}

	public function test_delete_module_closes_gap_within_tab()
	{
		$module_data = ['module_order' => 2, 'module_column' => 1, 'module_tab' => 9, 'guild_id' => 5];
		$this->handler->delete_module(42, $module_data);

		$this->assertStringContainsString('AND module_tab = 9', $this->queries[1]);
	}

	public function test_delete_tab_reassigns_to_fallback_and_closes_gap()
	{
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['tab_id' => 1, 'guild_id' => 5, 'tab_order' => 0],
			['tab_id' => 2, 'guild_id' => 5, 'tab_order' => 1],
			false
		);

		$this->handler->delete_tab(1, 5);

		$this->assertStringContainsString('SET module_tab = 2', $this->queries[2]);
		$this->assertStringContainsString('DELETE FROM', $this->queries[3]);
		$this->assertStringContainsString('SET tab_order = tab_order - 1', $this->queries[4]);
	}

	public function test_seed_guild_layout_returns_early_when_source_has_no_tab()
	{
		$this->db->method('sql_fetchrow')->willReturn(false);

		$this->handler->seed_guild_layout(6);

		// get_tabs(0) is the only query issued before the early return.
		$this->assertCount(1, $this->queries);
	}
}
```

---

## Task 3: `portal_renderer` tab resolution + routing + `view_controller` threading + template

**Files:**
- Modify: `portal/portal_renderer.php`
- Modify: `config/routing.yml`
- Modify: `controller/view_controller.php`
- Modify: `config/portal_services.yml` (add `helper` argument to `portal_renderer`)
- Modify: `styles/all/template/main.html`
- Create: `tests/portal/portal_renderer_test.php`

### Step 3.1 — `config/routing.yml`: widen the `page` slug requirement

Change `avathar_bbguild_00`'s requirement from the dead `welcome|roster` whitelist to a generic slug shape (tab slugs are user-authored in ACP, same charset as the existing hardcoded values):

```yaml
avathar_bbguild_00:
    path: /guild/{page}/{guild_id}
    defaults: { _controller: avathar.bbguild.controller:handleview , guild_id: 1, page: "welcome" }
    requirements:
        guild_id: \d*
        page: '[a-zA-Z0-9_\-]+'
```

(`avathar_bbguild_guild` and the rest of the file are unchanged.)

### Step 3.2 — `config/portal_services.yml`: add `helper` to `portal_renderer`

```yaml
    avathar.bbguild.portal.renderer:
        class: avathar\bbguild\portal\portal_renderer
        arguments:
            - '@avathar.bbguild.portal.columns'
            - '@avathar.bbguild.portal.module_helper'
            - '@avathar.bbguild.portal.modules.database_handler'
            - '@config'
            - '@template'
            - '@user'
            - '@controller.helper'
```

(`@controller.helper` is phpBB's standard core service id for `\phpbb\controller\helper`, the same object `roster.php` already receives as `$this->helper` for its own route-building — confirm the exact core service id used elsewhere in this file for `roster.php`'s own `helper` argument and match it verbatim rather than assuming `@controller.helper` if this codebase aliases it differently.)

### Step 3.3 — `portal_renderer.php`: resolve the tab, filter modules by it, render the tab bar

Full replacement of the file:

```php
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
```

Confirmed against `portal_services.yml`'s existing `avathar.bbguild.portal.module.roster` service definition: the DI service id is `@controller.helper`, matching the `\phpbb\controller\helper` type hint used above — both already used verbatim by this codebase, no further check needed.

### Step 3.4 — `controller/view_controller.php`: thread `$page` through

```php
	public function handleview($guild_id, $page = 'welcome')
	{
		if (!$this->auth->acl_get('u_bbguild'))
		{
			throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
		}

		$this->guild_context->init((int) $guild_id);
		$this->portal_renderer->render($this->guild_context->guild_id, (string) $page);
		$this->template->assign_vars(['S_DISPLAY_WELCOME' => true]);

		return $this->helper->render('main.html', $this->guild_context->guild->getName());
	}
```

(Only the `render()` call line changes; the rest of `view_controller.php` — including `playerdetail()` — is untouched.)

### Step 3.5 — `styles/all/template/main.html`: render the tab bar

Insert between the closing `.guild-header` div (existing line 28) and the portal include (existing line 35):

```html
        </div>

        {% if loops.tabs|length > 1 %}
        <div class="guild-portal-tabs">
            <ul>
                {% for tabs in loops.tabs %}
                <li class="{% if tabs.TAB_ACTIVE %}activetab{% endif %}">
                    <a href="{{ tabs.U_TAB }}">{{ tabs.TAB_NAME }}</a>
                </li>
                {% endfor %}
            </ul>
        </div>
        {% endif %}

        {% if S_ERROR %}
```

(The tab bar is hidden via `loops.tabs|length > 1` when a guild has only its one default tab — the common case today — so nothing visually changes for any existing guild until a second tab is added, matching the design doc's stated backward-compat goal.)

### Step 3.6 — Test: `tests/portal/portal_renderer_test.php`

```php
<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild\tests\portal;

use PHPUnit\Framework\TestCase;

class portal_renderer_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $database_handler;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $template;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $helper;

	/** @var array Captured assign_block_vars() calls: [blockname, vars][] */
	protected $block_calls;

	protected function get_renderer()
	{
		$portal_columns = $this->createMock(\avathar\bbguild\portal\columns::class);
		$module_helper = $this->createMock(\avathar\bbguild\portal\module_helper::class);
		$this->database_handler = $this->createMock(\avathar\bbguild\portal\modules\database_handler::class);
		$config = new \phpbb\config\config([]);
		$this->template = $this->createMock(\phpbb\template\template::class);
		$user = $this->createMock(\phpbb\user::class);
		$this->helper = $this->createMock(\phpbb\controller\helper::class);

		$this->block_calls = [];
		$this->template->method('assign_block_vars')
			->willReturnCallback(function ($blockname, $vars) {
				$this->block_calls[] = [$blockname, $vars];
			});
		$this->template->method('assign_vars')->willReturn(null);

		$this->helper->method('route')->willReturn('/guild/welcome/5');
		$this->database_handler->method('get_modules')->willReturn([]);

		return new \avathar\bbguild\portal\portal_renderer(
			$portal_columns, $module_helper, $this->database_handler, $config, $this->template, $user, $this->helper
		);
	}

	private function blocks(string $name): array
	{
		return array_values(array_map(
			fn($call) => $call[1],
			array_filter($this->block_calls, fn($call) => $call[0] === $name)
		));
	}

	public function test_empty_slug_resolves_to_default_tab()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([
			['tab_id' => 1, 'tab_name' => 'Overview', 'tab_slug' => 'welcome'],
			['tab_id' => 2, 'tab_name' => 'Raids', 'tab_slug' => 'raids'],
		]);

		$this->database_handler->expects($this->once())
			->method('get_modules')
			->with(5, 1);

		$renderer->render(5, '');

		$tabs = $this->blocks('tabs');
		$this->assertTrue($tabs[0]['TAB_ACTIVE']);
		$this->assertFalse($tabs[1]['TAB_ACTIVE']);
	}

	public function test_unknown_slug_falls_back_to_default_tab()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([
			['tab_id' => 1, 'tab_name' => 'Overview', 'tab_slug' => 'welcome'],
		]);

		$this->database_handler->expects($this->once())
			->method('get_modules')
			->with(5, 1);

		$renderer->render(5, 'nonexistent-slug');
	}

	public function test_known_slug_resolves_to_that_tab()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([
			['tab_id' => 1, 'tab_name' => 'Overview', 'tab_slug' => 'welcome'],
			['tab_id' => 2, 'tab_name' => 'Raids', 'tab_slug' => 'raids'],
		]);

		$this->database_handler->expects($this->once())
			->method('get_modules')
			->with(5, 2);

		$renderer->render(5, 'raids');

		$tabs = $this->blocks('tabs');
		$this->assertFalse($tabs[0]['TAB_ACTIVE']);
		$this->assertTrue($tabs[1]['TAB_ACTIVE']);
	}

	public function test_guild_with_no_tabs_does_not_query_modules()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([]);

		$this->database_handler->expects($this->never())->method('get_modules');

		$renderer->render(5, '');
	}
}
```

---

## Task 4: `admin_portal.php` tab CRUD + `acp_editguild_portal.html` + language keys

**Files:**
- Modify: `controller/admin_portal.php`
- Modify: `adm/style/acp_editguild_portal.html`
- Modify: `language/en/portal.php` (+ the other 6 locales, English copy — matches the convention already used for `ACP_PORTAL_*` keys, which are English-only across all 7 files today)

### Step 4.1 — `language/en/portal.php`: new keys

Add after the existing `ACP_PORTAL_COLUMN_BOTTOM` line:

```php
	'ACP_PORTAL_TABS'                => 'Tabs',
	'ACP_PORTAL_TAB'                 => 'Tab',
	'ACP_PORTAL_TAB_NAME'            => 'Tab name',
	'ACP_PORTAL_TAB_SLUG'            => 'Slug',
	'ACP_PORTAL_TAB_SLUG_EXPLAIN'    => 'Used in the page URL, e.g. <samp>raids</samp>. Letters, numbers, hyphens and underscores only.',
	'ACP_PORTAL_ADD_TAB'             => 'Add Tab',
	'ACP_PORTAL_TAB_ADDED'           => 'Tab has been added.',
	'ACP_PORTAL_TAB_ADD_FAILED'      => 'Could not add tab. A tab with that slug already exists for this guild.',
	'ACP_PORTAL_TAB_UPDATED'         => 'Tab has been updated.',
	'ACP_PORTAL_TAB_UPDATE_FAILED'   => 'Could not update tab. A tab with that slug already exists for this guild.',
	'ACP_PORTAL_TAB_DELETED'         => 'Tab has been removed.',
	'ACP_PORTAL_TAB_DELETE_FAILED'   => 'Could not remove tab. A guild must always have at least one tab.',
```

Repeat the identical English text (same convention already used for every existing `ACP_PORTAL_*` key across `fr`/`de`/`it`/`nl`/`es_x_tu`/`pl`) in each of the other 6 `language/<locale>/portal.php` files.

### Step 4.2 — `controller/admin_portal.php`: tab CRUD actions + tab-scoped module list

Full replacement of the file:

```php
<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * ACP controller for portal module management
 */

namespace avathar\bbguild\controller;

use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\language\language;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\path_helper;
use phpbb\user;
use phpbb\cache\service as cache_service;
use avathar\bbguild\portal\columns;
use avathar\bbguild\portal\module_registry;
use avathar\bbguild\portal\portal_config;
use avathar\bbguild\portal\modules\database_handler;
use avathar\bbguild\portal\modules\manager;

class admin_portal
{
	protected cache_service $cache;
	protected config $config;
	protected driver_interface $db;
	protected language $language;
	protected request $request;
	protected template $template;
	protected user $user;
	protected columns $portal_columns;
	protected module_registry $module_registry;
	protected database_handler $database_handler;
	protected manager $module_manager;
	protected portal_config $portal_config;
	protected path_helper $path_helper;
	protected string $guild_table;
	protected string $u_action = '';
	protected string $action_param = 'action';

	public function __construct(
		cache_service $cache,
		config $config,
		driver_interface $db,
		language $language,
		request $request,
		template $template,
		user $user,
		columns $portal_columns,
		module_registry $module_registry,
		database_handler $database_handler,
		manager $module_manager,
		portal_config $portal_config,
		path_helper $path_helper,
		string $guild_table
	)
	{
		$this->cache = $cache;
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->portal_columns = $portal_columns;
		$this->module_registry = $module_registry;
		$this->database_handler = $database_handler;
		$this->module_manager = $module_manager;
		$this->portal_config = $portal_config;
		$this->path_helper = $path_helper;
		$this->guild_table = $guild_table;
	}

	public function set_u_action(string $u_action): self
	{
		$this->u_action = $u_action;
		return $this;
	}

	/**
	 * Main display: handles actions + renders the module list.
	 */
	public function display(string $action_param = 'action'): string
	{
		$this->action_param = $action_param;
		add_form_key('acp_bbguild_portal');

		$guild_id = $this->request->variable('guild_id', 0);
		$action = $this->request->variable($action_param, '');
		$module_id = $this->request->variable('module_id', 0);
		$tab_id = $this->request->variable('tab_id', 0);

		// Handle actions
		$redirect_url = $this->u_action . '&guild_id=' . $guild_id . '&tab_id=' . $tab_id;
		switch ($action)
		{
			case 'move_up':
				$this->module_manager->move_module_vertical($module_id, database_handler::MOVE_DIRECTION_UP);
				redirect($redirect_url);
				break;

			case 'move_down':
				$this->module_manager->move_module_vertical($module_id, database_handler::MOVE_DIRECTION_DOWN);
				redirect($redirect_url);
				break;

			case 'move_to_column':
				$target_column = $this->request->variable('target_column', 0);
				$this->module_manager->move_module_to_column($module_id, $target_column);
				redirect($redirect_url);
				break;

			case 'move_to_tab':
				$target_tab = $this->request->variable('target_tab', 0);
				$this->module_manager->move_module_to_tab($module_id, $target_tab);
				redirect($redirect_url);
				break;

			case 'delete':
				if (confirm_box(true))
				{
					$this->module_manager->delete_module($module_id);
					trigger_error($this->language->lang('ACP_PORTAL_MODULE_DELETED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_NOTICE);
				}
				else
				{
					confirm_box(false, $this->language->lang('CONFIRM_OPERATION'), build_hidden_fields([
						$this->action_param => 'delete',
						'module_id' => $module_id,
						'guild_id'  => $guild_id,
					]));
				}
				break;

			case 'toggle':
				$this->toggle_module($module_id);
				redirect($redirect_url);
				break;

			case 'configure':
				$this->display_module_config($module_id, $guild_id);
				return 'acp_portal_config';

			case 'save_config':
				if (!check_form_key('acp_bbguild_portal'))
				{
					trigger_error('FORM_INVALID', E_USER_WARNING);
				}
				$this->save_module_config($module_id, $guild_id);
				trigger_error($this->language->lang('ACP_PORTAL_MODULE_UPDATED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_NOTICE);
				return 'acp_editguild_portal';

			case 'add':
				if (!check_form_key('acp_bbguild_portal'))
				{
					trigger_error('FORM_INVALID', E_USER_WARNING);
				}
				$classname = $this->request->variable('module_classname', '');
				$column = $this->request->variable('module_column', 2);
				$module_tab = $this->request->variable('module_tab', 0);
				if ($classname && $guild_id && $module_tab)
				{
					$new_id = $this->module_manager->add_module($classname, $column, $guild_id, $module_tab);
					if ($new_id)
					{
						trigger_error($this->language->lang('ACP_PORTAL_MODULE_ADDED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_NOTICE);
					}
					else
					{
						trigger_error($this->language->lang('ACP_PORTAL_MODULE_ADD_FAILED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_WARNING);
					}
				}
				break;

			case 'add_tab':
				if (!check_form_key('acp_bbguild_portal'))
				{
					trigger_error('FORM_INVALID', E_USER_WARNING);
				}
				$this->handle_add_tab($guild_id);
				break;

			case 'edit_tab':
				if (!check_form_key('acp_bbguild_portal'))
				{
					trigger_error('FORM_INVALID', E_USER_WARNING);
				}
				$this->handle_edit_tab($guild_id);
				break;

			case 'delete_tab':
				if (confirm_box(true))
				{
					$deleted_tab_id = $this->request->variable('delete_tab_id', 0);
					if ($this->module_manager->delete_tab($deleted_tab_id, $guild_id))
					{
						trigger_error($this->language->lang('ACP_PORTAL_TAB_DELETED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_NOTICE);
					}
					else
					{
						trigger_error($this->language->lang('ACP_PORTAL_TAB_DELETE_FAILED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_WARNING);
					}
				}
				else
				{
					confirm_box(false, $this->language->lang('CONFIRM_OPERATION'), build_hidden_fields([
						$this->action_param => 'delete_tab',
						'delete_tab_id' => $this->request->variable('delete_tab_id', 0),
						'guild_id'  => $guild_id,
					]));
				}
				break;

			case 'move_tab_up':
				$this->module_manager->move_tab_vertical($this->request->variable('move_tab_id', 0), database_handler::MOVE_DIRECTION_UP);
				redirect($this->u_action . '&guild_id=' . $guild_id);
				break;

			case 'move_tab_down':
				$this->module_manager->move_tab_vertical($this->request->variable('move_tab_id', 0), database_handler::MOVE_DIRECTION_DOWN);
				redirect($this->u_action . '&guild_id=' . $guild_id);
				break;
		}

		// Show modules for selected guild + tab
		if ($guild_id)
		{
			$tabs = $this->database_handler->get_tabs($guild_id);
			if (!$tab_id)
			{
				$default_tab = $tabs[0] ?? null;
				$tab_id = $default_tab ? (int) $default_tab['tab_id'] : 0;
			}

			$this->build_tab_list($tabs, $tab_id, $guild_id);

			if ($tab_id)
			{
				$this->build_module_list($guild_id, $tab_id);
				$this->build_add_module_form($guild_id, $tabs, $tab_id);
			}
		}

		$this->template->assign_vars([
			'U_ACTION'         => $this->u_action,
			'GUILD_ID'         => $guild_id,
			'TAB_ID'           => $tab_id,
			'S_BBGUILD'        => true,
			'ACTION_PARAM'     => $this->action_param,
		]);

		return 'acp_editguild_portal';
	}

	/**
	 * Build the tab switcher + tab management list for a guild.
	 */
	protected function build_tab_list(array $tabs, int $active_tab_id, int $guild_id): void
	{
		$ap = $this->action_param;

		foreach ($tabs as $row)
		{
			$this->template->assign_block_vars('tab_row', [
				'TAB_ID'       => $row['tab_id'],
				'TAB_NAME'     => $row['tab_name'],
				'TAB_SLUG'     => $row['tab_slug'],
				'S_ACTIVE'     => (int) $row['tab_id'] === $active_tab_id,
				'U_SELECT'     => $this->u_action . '&amp;guild_id=' . $guild_id . '&amp;tab_id=' . $row['tab_id'],
				'U_MOVE_UP'    => $this->u_action . '&amp;' . $ap . '=move_tab_up&amp;move_tab_id=' . $row['tab_id'] . '&amp;guild_id=' . $guild_id,
				'U_MOVE_DOWN'  => $this->u_action . '&amp;' . $ap . '=move_tab_down&amp;move_tab_id=' . $row['tab_id'] . '&amp;guild_id=' . $guild_id,
				'U_DELETE'     => $this->u_action . '&amp;' . $ap . '=delete_tab&amp;delete_tab_id=' . $row['tab_id'] . '&amp;guild_id=' . $guild_id,
			]);
		}
	}

	/**
	 * Build the list of modules for a guild's active tab.
	 */
	protected function build_module_list(int $guild_id, int $tab_id): void
	{
		$modules = $this->database_handler->get_modules($guild_id, $tab_id);
		$tabs = $this->database_handler->get_tabs($guild_id);

		foreach ($modules as $row)
		{
			$module_obj = $this->module_registry->get_module($row['module_classname']);
			$display_name = $module_obj
				? ($this->language->lang($module_obj->get_name()) ?: $row['module_name'])
				: $row['module_classname'];

			$ap = $this->action_param;
			$this->template->assign_block_vars('module_row', [
				'MODULE_ID'     => $row['module_id'],
				'MODULE_NAME'   => $display_name,
				'MODULE_COLUMN' => (int) $row['module_column'],
				'MODULE_ORDER'  => $row['module_order'],
				'MODULE_STATUS' => (int) $row['module_status'],
				'S_ENABLED'     => (int) $row['module_status'] === 1,
				'U_CONFIGURE'   => $this->u_action . '&amp;' . $ap . '=configure&amp;module_id=' . $row['module_id'] . '&amp;guild_id=' . $guild_id,
				'U_MOVE_UP'     => $this->u_action . '&amp;' . $ap . '=move_up&amp;module_id=' . $row['module_id'] . '&amp;guild_id=' . $guild_id . '&amp;tab_id=' . $tab_id,
				'U_MOVE_DOWN'   => $this->u_action . '&amp;' . $ap . '=move_down&amp;module_id=' . $row['module_id'] . '&amp;guild_id=' . $guild_id . '&amp;tab_id=' . $tab_id,
				'U_MOVE_COLUMN' => $this->u_action . '&amp;' . $ap . '=move_to_column&amp;module_id=' . $row['module_id'] . '&amp;guild_id=' . $guild_id . '&amp;tab_id=' . $tab_id,
				'U_MOVE_TAB'    => $this->u_action . '&amp;' . $ap . '=move_to_tab&amp;module_id=' . $row['module_id'] . '&amp;guild_id=' . $guild_id . '&amp;tab_id=' . $tab_id,
				'U_DELETE'      => $this->u_action . '&amp;' . $ap . '=delete&amp;module_id=' . $row['module_id'] . '&amp;guild_id=' . $guild_id,
				'U_TOGGLE'      => $this->u_action . '&amp;' . $ap . '=toggle&amp;module_id=' . $row['module_id'] . '&amp;guild_id=' . $guild_id . '&amp;tab_id=' . $tab_id,
			]);

			// Add allowed column options for this module
			$allowed = $module_obj ? $module_obj->get_allowed_columns() : 0;
			foreach ($this->portal_columns->get_column_names() as $col_name)
			{
				$col_num = $this->portal_columns->string_to_number($col_name);
				$col_const = $this->portal_columns->string_to_constant($col_name);
				if ($allowed & $col_const)
				{
					$this->template->assign_block_vars('module_row.column_option', [
						'VALUE'    => $col_num,
						'LABEL'    => $this->language->lang('ACP_PORTAL_COLUMN_' . strtoupper($col_name)),
						'SELECTED' => ((int) $row['module_column'] === $col_num),
					]);
				}
			}

			// Add tab options for this module (move-to-tab dropdown)
			foreach ($tabs as $tab_row)
			{
				$this->template->assign_block_vars('module_row.tab_option', [
					'VALUE'    => $tab_row['tab_id'],
					'LABEL'    => $tab_row['tab_name'],
					'SELECTED' => ((int) $tab_row['tab_id'] === $tab_id),
				]);
			}
		}
	}

	/**
	 * Build the add-module dropdown with available modules.
	 */
	protected function build_add_module_form(int $guild_id, array $tabs, int $active_tab_id): void
	{
		$available = $this->module_registry->get_all_modules();

		foreach ($available as $classname => $module)
		{
			$display_name = $this->language->lang($module->get_name()) ?: $module->get_name();
			$this->template->assign_block_vars('available_modules', [
				'CLASSNAME' => $classname,
				'NAME'      => $display_name,
			]);
		}

		// Column options
		foreach ($this->portal_columns->get_column_names() as $name)
		{
			$this->template->assign_block_vars('column_options', [
				'VALUE' => $this->portal_columns->string_to_number($name),
				'LABEL' => $this->language->lang('ACP_PORTAL_COLUMN_' . strtoupper($name)),
			]);
		}

		// Tab options
		foreach ($tabs as $tab_row)
		{
			$this->template->assign_block_vars('tab_options', [
				'VALUE'    => $tab_row['tab_id'],
				'LABEL'    => $tab_row['tab_name'],
				'SELECTED' => ((int) $tab_row['tab_id'] === $active_tab_id),
			]);
		}
	}

	/**
	 * Handle the add_tab action: validate + create.
	 */
	protected function handle_add_tab(int $guild_id): void
	{
		$name = $this->request->variable('tab_name', '', true);
		$slug = $this->sanitize_slug($this->request->variable('tab_slug', ''));

		if (!$guild_id || $name === '' || $slug === '' || $this->database_handler->get_tab_by_slug($guild_id, $slug) !== null)
		{
			trigger_error($this->language->lang('ACP_PORTAL_TAB_ADD_FAILED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_WARNING);
		}

		$this->module_manager->add_tab($name, $slug, $guild_id);
		trigger_error($this->language->lang('ACP_PORTAL_TAB_ADDED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_NOTICE);
	}

	/**
	 * Handle the edit_tab action: validate + update.
	 */
	protected function handle_edit_tab(int $guild_id): void
	{
		$tab_id = $this->request->variable('edit_tab_id', 0);
		$name = $this->request->variable('tab_name', '', true);
		$slug = $this->sanitize_slug($this->request->variable('tab_slug', ''));

		$existing = $this->database_handler->get_tab_by_slug($guild_id, $slug);
		$slug_taken_by_other = $existing !== null && (int) $existing['tab_id'] !== $tab_id;

		if (!$tab_id || $name === '' || $slug === '' || $slug_taken_by_other)
		{
			trigger_error($this->language->lang('ACP_PORTAL_TAB_UPDATE_FAILED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_WARNING);
		}

		$this->module_manager->edit_tab($tab_id, $name, $slug);
		trigger_error($this->language->lang('ACP_PORTAL_TAB_UPDATED') . adm_back_link($this->u_action . '&guild_id=' . $guild_id), E_USER_NOTICE);
	}

	/**
	 * Reduce a user-entered slug to the URL-safe charset the widened
	 * avathar_bbguild_00 route requirement accepts.
	 */
	protected function sanitize_slug(string $slug): string
	{
		return preg_replace('/[^a-zA-Z0-9_\-]/', '', $slug);
	}

	/**
	 * Toggle module enabled/disabled.
	 */
	protected function toggle_module(int $module_id): void
	{
		$module_data = $this->database_handler->get_module_data($module_id);
		if ($module_data === false)
		{
			return;
		}

		$new_status = (int) $module_data['module_status'] === 1 ? 0 : 1;

		$sql = 'UPDATE ' . $this->database_handler->get_table_name() . '
			SET module_status = ' . $new_status . '
			WHERE module_id = ' . (int) $module_id;
		$this->db->sql_query($sql);
		$this->cache->destroy('sql', $this->database_handler->get_table_name());
	}

	/**
	 * Display module configuration form.
	 */
	protected function display_module_config(int $module_id, int $guild_id): void
	{
		add_form_key('acp_bbguild_portal');

		$module_data = $this->database_handler->get_module_data($module_id);
		if ($module_data === false)
		{
			trigger_error('NO_MODULE', E_USER_WARNING);
		}

		$module_obj = $this->module_registry->get_module($module_data['module_classname']);

		// Build group options
		$selected_groups = !empty($module_data['module_group_ids'])
			? explode(',', $module_data['module_group_ids'])
			: [];

		$sql = 'SELECT group_id, group_name, group_type FROM ' . GROUPS_TABLE . ' ORDER BY group_name ASC';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_name = ($row['group_type'] == GROUP_SPECIAL)
				? $this->language->lang('G_' . $row['group_name'])
				: $row['group_name'];

			$this->template->assign_block_vars('group_options', [
				'VALUE'    => $row['group_id'],
				'LABEL'    => $group_name,
				'SELECTED' => in_array($row['group_id'], $selected_groups),
			]);
		}
		$this->db->sql_freeresult($result);

		// Module-specific settings from get_template_acp()
		$has_module_settings = false;
		if ($module_obj)
		{
			$module_obj->set_guild_context($guild_id);
			$acp_settings = $module_obj->get_template_acp($module_id);
			if (!empty($acp_settings))
			{
				$has_module_settings = true;
				foreach ($acp_settings as $setting)
				{
					$value = $this->portal_config->get(
						$setting['key'] ?? '',
						$guild_id,
						$setting['default'] ?? ''
					);

					$tpl_row = [
						'KEY'          => $setting['key'] ?? '',
						'LABEL'        => isset($setting['label']) ? $this->language->lang($setting['label']) : '',
						'EXPLAIN'      => isset($setting['explain']) ? $this->language->lang($setting['explain']) : '',
						'TYPE'         => $setting['type'] ?? 'text',
						'VALUE'        => $value,
						'LABEL_INLINE' => $setting['label_inline'] ?? '',
					];

					if (isset($setting['options']) && is_array($setting['options']))
					{
						foreach ($setting['options'] as $opt_value => $opt_label)
						{
							$tpl_row['options'][] = [
								'VALUE'    => $opt_value,
								'LABEL'    => $this->language->lang($opt_label),
								'SELECTED' => ($value == $opt_value),
							];
						}
					}

					$this->template->assign_block_vars('module_settings', $tpl_row);
				}
			}
		}

		$image_src = $module_data['module_image_src'] ?? '';
		$ext_path = $this->path_helper->get_web_root_path() . 'ext/avathar/bbguild/';

		$this->template->assign_vars([
			'U_ACTION'             => $this->u_action,
			'MODULE_ID'            => $module_id,
			'GUILD_ID'             => $guild_id,
			'MODULE_NAME'          => $module_data['module_name'],
			'MODULE_STATUS'        => (int) $module_data['module_status'],
			'MODULE_IMAGE_SRC'     => $image_src,
			'MODULE_IMAGE_PREVIEW' => !empty($image_src)
				? $ext_path . 'styles/all/theme/images/portal/' . $image_src
				: '',
			'MODULE_ICON'          => $module_data['module_icon'] ?? '',
			'MODULE_ICON_SIZE'     => (int) ($module_data['module_icon_size'] ?? 16),
			'S_HAS_MODULE_SETTINGS' => $has_module_settings,
			'S_BBGUILD'            => true,
			'ACTION_PARAM'         => $this->action_param,
		]);

	}

	/**
	 * Save module configuration.
	 */
	protected function save_module_config(int $module_id, int $guild_id): void
	{
		if ($this->request->is_set('cancel'))
		{
			return;
		}

		$module_data = $this->database_handler->get_module_data($module_id);
		if ($module_data === false)
		{
			return;
		}

		// Save base module settings
		$group_ids = $this->request->variable('module_group_ids', [0]);
		$group_ids_str = implode(',', array_filter($group_ids));

		$sql_ary = [
			'module_name'         => $this->request->variable('module_name', '', true),
			'module_status'       => $this->request->variable('module_status', 1),
			'module_image_src'    => $this->request->variable('module_image_src', ''),
			'module_icon'         => $this->request->variable('module_icon', ''),
			'module_icon_size'    => $this->request->variable('module_icon_size', 16),
			'module_group_ids'    => $group_ids_str,
		];

		$sql = 'UPDATE ' . $this->database_handler->get_table_name() . '
			SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE module_id = ' . (int) $module_id;
		$this->db->sql_query($sql);

		// Save module-specific config values
		$config_values = $this->request->variable('config', [''], true);
		foreach ($config_values as $key => $value)
		{
			$this->portal_config->set($key, $value, $guild_id);
		}

		$this->cache->destroy('sql', $this->database_handler->get_table_name());
	}
}
```

**Note on `edit_tab`'s form:** the ACP template (Step 4.3) needs a way to enter edit mode per tab (e.g. an inline "Edit" link that pre-fills `tab_name`/`tab_slug`/`edit_tab_id` into the same add/edit form, toggled by a small amount of vanilla JS — matching this codebase's existing precedent of plain `onchange="window.location=...` and no JS framework). The implementer should keep this to the same minimal-JS style already used for `U_MOVE_COLUMN`'s `<select onchange>` at `acp_editguild_portal.html:44`, not introduce a build step or framework.

### Step 4.3 — `adm/style/acp_editguild_portal.html`: tab switcher, tab management fieldset, tab selects

Insert a tab switcher + tabs-management fieldset before the existing "Portal Modules" fieldset, and add the Tab `<select>` to the module row and add-module form:

```html
        <fieldset>
            <legend>{{ lang('ACP_PORTAL_TABS') }}</legend>

            {% if loops.tab_row|length %}
            <div class="tablewrap">
                <table class="table1">
                    <thead>
                    <tr>
                        <th style="width:10%">{{ lang('ID') }}</th>
                        <th style="width:30%">{{ lang('ACP_PORTAL_TAB_NAME') }}</th>
                        <th style="width:20%">{{ lang('ACP_PORTAL_TAB_SLUG') }}</th>
                        <th style="width:40%">{{ lang('ACTION') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {% for tab_row in loops.tab_row %}
                    <tr class="{% if tab_row.S_ROW_COUNT is even %}row1{% else %}row2{% endif %}{% if tab_row.S_ACTIVE %} activetab{% endif %}">
                        <td>{{ tab_row.TAB_ID }}</td>
                        <td><a href="{{ tab_row.U_SELECT }}">{{ tab_row.TAB_NAME }}</a></td>
                        <td>{{ tab_row.TAB_SLUG }}</td>
                        <td style="text-align:center; white-space:nowrap">
                            <a href="{{ tab_row.U_MOVE_UP }}" title="{{ lang('MOVE_UP') }}">{{ ICON_MOVE_UP }}</a>
                            <a href="{{ tab_row.U_MOVE_DOWN }}" title="{{ lang('MOVE_DOWN') }}">{{ ICON_MOVE_DOWN }}</a>
                            &nbsp;
                            <a href="#" onclick="document.getElementById('edit_tab_id').value='{{ tab_row.TAB_ID }}';document.getElementById('tab_name_input').value='{{ tab_row.TAB_NAME }}';document.getElementById('tab_slug_input').value='{{ tab_row.TAB_SLUG }}';return false;">{{ lang('EDIT') }}</a>
                            &nbsp;
                            <a href="{{ tab_row.U_DELETE }}" title="{{ lang('DELETE') }}">{{ ICON_DELETE }}</a>
                        </td>
                    </tr>
                    {% endfor %}
                    </tbody>
                </table>
            </div>
            {% endif %}

            <form method="post" action="{{ U_EDIT_GUILDPORTAL }}" id="tab_form">
                <input type="hidden" name="guild_id" value="{{ GUILD_ID }}" />
                <input type="hidden" name="edit_tab_id" id="edit_tab_id" value="0" />
                <dl>
                    <dt>{{ lang('ACP_PORTAL_TAB_NAME') }}:</dt>
                    <dd><input type="text" id="tab_name_input" name="tab_name" value="" maxlength="100" /></dd>
                </dl>
                <dl>
                    <dt>{{ lang('ACP_PORTAL_TAB_SLUG') }}:</dt>
                    <dd>
                        <input type="text" id="tab_slug_input" name="tab_slug" value="" maxlength="100" />
                        <p class="explain">{{ lang('ACP_PORTAL_TAB_SLUG_EXPLAIN') }}</p>
                    </dd>
                </dl>
                <fieldset class="quick" style="float: {{ S_CONTENT_FLOW_END }};">
                    {{ S_FORM_TOKEN }}
                    <input type="submit" name="{{ ACTION_PARAM }}" value="{{ lang('ACP_PORTAL_ADD_TAB') }}" onclick="this.form.elements['{{ ACTION_PARAM }}'].value = (document.getElementById('edit_tab_id').value == '0') ? 'add_tab' : 'edit_tab';" class="button1" />
                </fieldset>
            </form>
        </fieldset>

        <fieldset>
            <legend>{{ lang('ACP_PORTAL_MODULES') }}</legend>
```

(The `submit` button's `onclick` swaps its own `name` value between `add_tab`/`edit_tab` right before submit — the same "no JS framework, one inline handler" style as the existing `<select onchange="window.location=...">` at line 44, just applied to a button instead of a select.)

Then in the existing module-row `<td>` that holds the Column `<select>`, add a sibling Tab `<select>` (new `<th>`/`<td>` pair):

```html
                        <th style="width:15%">{{ lang('ACP_PORTAL_COLUMN') }}</th>
                        <th style="width:15%">{{ lang('ACP_PORTAL_TAB') }}</th>
```

```html
                        <td>
                            <select onchange="if(this.value != {{ module_row.MODULE_COLUMN }}) window.location='{{ module_row.U_MOVE_COLUMN }}&amp;target_column='+this.value;">
                                {% for column_option in module_row.column_option %}
                                <option value="{{ column_option.VALUE }}"{% if column_option.SELECTED %} selected="selected"{% endif %}>{{ column_option.LABEL }}</option>
                                {% endfor %}
                            </select>
                        </td>
                        <td>
                            <select onchange="window.location='{{ module_row.U_MOVE_TAB }}&amp;target_tab='+this.value;">
                                {% for tab_option in module_row.tab_option %}
                                <option value="{{ tab_option.VALUE }}"{% if tab_option.SELECTED %} selected="selected"{% endif %}>{{ tab_option.LABEL }}</option>
                                {% endfor %}
                            </select>
                        </td>
```

And in the add-module form, after the existing Column `<dl>`:

```html
                <dl>
                    <dt>{{ lang('ACP_PORTAL_TAB') }}:</dt>
                    <dd>
                        <select name="module_tab">
                            {% for tab_options in loops.tab_options %}
                            <option value="{{ tab_options.VALUE }}"{% if tab_options.SELECTED %} selected="selected"{% endif %}>{{ tab_options.LABEL }}</option>
                            {% endfor %}
                        </select>
                    </dd>
                </dl>
```

The rest of the file (header, guild-select tabs, corners, footer) is unchanged.

---

## Task 5: Final live-board verification

No new files. On the live install (`/Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild`, after the migration has actually run):

1. Load an existing guild's portal page with no `?page=` / route segment — confirm it renders exactly as before (same modules, same columns), and that no tab bar appears (single-tab guild).
2. Confirm in the DB that the guild's `bb_portal_modules` rows now carry a non-zero `module_tab` matching a real row in `bb_portal_tabs`, and that guild_id=0's template row got the same treatment.
3. In ACP → Portal for that guild: add a second tab, add a module to it, confirm it does NOT appear on the first tab and the first tab's modules are unaffected.
4. Visit the guild page with `?page=<second-tab-slug>` (or via the new front-end tab bar, which should now be visible since there are 2 tabs) — confirm the second tab's module renders and the tab bar highlights the active tab correctly.
5. Move a module between tabs via the new per-row Tab `<select>` — confirm it disappears from the source tab and appears (at the end) of the target tab's same column.
6. Try deleting a tab that still has a guild's only tab left — confirm it's refused with `ACP_PORTAL_TAB_DELETE_FAILED`. Delete a non-last tab that has modules — confirm those modules reappear on the guild's remaining/default tab.
7. Create a brand-new guild (via ACP "Create custom game" → add guild path, or whatever the live board's normal new-guild flow is) — confirm `seed_guild_layout()` gives it one default tab with the same modules as guild_id=0's template, not zero tabs.
8. Reorder tabs via move up/down — confirm the front-end tab bar's order follows.

Report any discrepancy back before closing out the issue; this task's checklist directly maps to the design doc's explicitly deferred/kept-in-scope boundaries (§"Out of scope") and Task 1's data-migration risk note.
