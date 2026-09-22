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

	/** @var array Captured sql_build_array() [op, data] calls */
	protected $built_arrays;

	protected function setUp(): void
	{
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$this->queries = [];
		$this->built_arrays = [];

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
		$this->db->method('sql_build_array')
			->willReturnCallback(function ($op, $data) {
				$this->built_arrays[] = [$op, $data];
				return 'SET_CLAUSE';
			});

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

	public function test_delete_tab_reassigns_modules_to_fallback_and_closes_gap()
	{
		// tabs: 1 (deleted), 2 (fallback) — get_tabs(5)
		// modules in deleted tab 1: two in column 1, one in column 2 — get_modules(5, 1)
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['tab_id' => 1, 'guild_id' => 5, 'tab_order' => 0],
			['tab_id' => 2, 'guild_id' => 5, 'tab_order' => 1],
			false,
			['module_id' => 10, 'module_column' => 1, 'module_order' => 3, 'module_tab' => 1, 'guild_id' => 5],
			['module_id' => 11, 'module_column' => 1, 'module_order' => 5, 'module_tab' => 1, 'guild_id' => 5],
			['module_id' => 12, 'module_column' => 2, 'module_order' => 1, 'module_tab' => 1, 'guild_id' => 5],
			false
		);
		// Fallback tab (tab 2) already has: column 1 max order 4, column 2 empty (NULL/0).
		$this->db->method('sql_fetchfield')->willReturnOnConsecutiveCalls(4, null);

		$this->handler->delete_tab(1, 5);

		// [0] get_tabs(5), [1] get_modules(5, 1)
		$this->assertStringContainsString('WHERE guild_id = 5', $this->queries[0]);
		$this->assertStringContainsString('module_tab = 1', $this->queries[1]);

		// [2] MAX(module_order) for column 1 in fallback tab — queried once.
		$this->assertStringContainsString('MAX(module_order)', $this->queries[2]);
		$this->assertStringContainsString('module_column = 1', $this->queries[2]);
		$this->assertStringContainsString('module_tab = 2', $this->queries[2]);

		// [3] module 10 appended after the existing max (4 -> 5).
		$this->assertStringContainsString('module_order = 5', $this->queries[3]);
		$this->assertStringContainsString('module_tab = 2', $this->queries[3]);
		$this->assertStringContainsString('WHERE module_id = 10', $this->queries[3]);

		// [4] module 11, same column: no repeat MAX query, order tracked
		// locally so it doesn't collide with module 10's new order (5 -> 6).
		$this->assertStringContainsString('module_order = 6', $this->queries[4]);
		$this->assertStringContainsString('WHERE module_id = 11', $this->queries[4]);

		// [5] MAX(module_order) for column 2 — a different column, so a
		// second (and only second) MAX query is issued.
		$this->assertStringContainsString('MAX(module_order)', $this->queries[5]);
		$this->assertStringContainsString('module_column = 2', $this->queries[5]);

		// [6] module 12 appended after column 2's (empty) max (0 -> 1).
		$this->assertStringContainsString('module_order = 1', $this->queries[6]);
		$this->assertStringContainsString('WHERE module_id = 12', $this->queries[6]);

		// [7] tab delete, [8] order-gap close — unchanged from before.
		$this->assertStringContainsString('DELETE FROM', $this->queries[7]);
		$this->assertStringContainsString('SET tab_order = tab_order - 1', $this->queries[8]);

		// Exactly 9 queries: no N+1 MAX query per module — only one per
		// distinct column touched (2 columns => 2 MAX queries, not 3).
		$this->assertCount(9, $this->queries);
	}

	public function test_seed_guild_layout_only_creates_tab_then_returns_when_source_has_no_tab()
	{
		$this->db->method('sql_fetchrow')->willReturn(false);
		$this->db->method('sql_last_inserted_id')->willReturn(0);

		$this->handler->seed_guild_layout(6);

		// [0] get_tabs(0) (source lookup), [1] add_tab() INSERT for the
		// guild's own new default tab — created even with no source tab.
		// No get_modules()/add_module() calls follow: nothing to copy.
		$this->assertCount(2, $this->queries);
		$this->assertStringContainsString('INSERT INTO', $this->queries[1]);
	}

	public function test_seed_guild_layout_creates_default_tab_when_source_has_no_tab()
	{
		$this->db->method('sql_fetchrow')->willReturn(false);
		$this->db->method('sql_last_inserted_id')->willReturn(42);

		$this->handler->seed_guild_layout(6);

		// Only one INSERT should have been built: the new guild's tab.
		// No modules are copied because there is no source tab to copy from.
		$this->assertCount(1, $this->built_arrays);
		[$op, $data] = $this->built_arrays[0];
		$this->assertSame('INSERT', $op);
		$this->assertSame(6, $data['guild_id']);
		$this->assertSame('Overview', $data['tab_name']);
		$this->assertSame('welcome', $data['tab_slug']);
		$this->assertSame(0, $data['tab_order']);
	}

	public function test_seed_guild_layout_copies_source_tab_and_modules_when_present()
	{
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			// get_tabs(0) inside get_default_tab(0)
			['tab_id' => 1, 'guild_id' => 0, 'tab_order' => 0, 'tab_name' => 'Welcome', 'tab_slug' => 'welcome'],
			false,
			// get_modules(0, 1)
			['module_classname' => 'motd', 'module_column' => 1, 'module_order' => 1, 'module_name' => 'MOTD', 'module_image_src' => ''],
			false
		);
		$this->db->method('sql_last_inserted_id')->willReturn(99);

		$this->handler->seed_guild_layout(6);

		// [0] get default tab select, [1] add_tab insert, [2] get_modules select, [3] add_module insert
		$this->assertCount(4, $this->queries);
		$this->assertCount(2, $this->built_arrays);

		[$tab_op, $tab_data] = $this->built_arrays[0];
		$this->assertSame('INSERT', $tab_op);
		$this->assertSame('Welcome', $tab_data['tab_name']);
		$this->assertSame('welcome', $tab_data['tab_slug']);

		[$module_op, $module_data] = $this->built_arrays[1];
		$this->assertSame('INSERT', $module_op);
		$this->assertSame(6, $module_data['guild_id']);
		$this->assertSame(99, $module_data['module_tab']);
	}
}
