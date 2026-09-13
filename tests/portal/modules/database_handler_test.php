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

		// queries[0] is the get_tabs() SELECT issued at the top of delete_tab().
		$this->assertStringContainsString('SET module_tab = 2', $this->queries[1]);
		$this->assertStringContainsString('DELETE FROM', $this->queries[2]);
		$this->assertStringContainsString('SET tab_order = tab_order - 1', $this->queries[3]);
	}

	public function test_seed_guild_layout_returns_early_when_source_has_no_tab()
	{
		$this->db->method('sql_fetchrow')->willReturn(false);

		$this->handler->seed_guild_layout(6);

		// get_tabs(0) is the only query issued before the early return.
		$this->assertCount(1, $this->queries);
	}
}
