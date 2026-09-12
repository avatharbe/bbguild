<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player character-sync query tests — issue #361
 */

namespace avathar\bbguild\tests\player;

use avathar\bbguild\model\player\player;
use PHPUnit\Framework\TestCase;

class character_sync_query_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\driver\driver_interface */
	private $db;

	protected function setUp(): void
	{
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
	}

	private function make_player(): player
	{
		$reflection = new \ReflectionClass(player::class);
		/** @var player $p */
		$p = $reflection->newInstanceWithoutConstructor();
		$p->bb_players_table = 'bb_players';

		$db_prop = $reflection->getProperty('db');
		$db_prop->setAccessible(true);
		$db_prop->setValue($p, $this->db);

		return $p;
	}

	public function test_get_stalest_players_returns_empty_array_for_no_game_ids(): void
	{
		$p = $this->make_player();

		$this->db->expects($this->never())->method('sql_query_limit');

		$this->assertSame([], $p->get_stalest_players([], 10));
	}

	public function test_get_stalest_players_filters_by_game_ids_and_active_status(): void
	{
		$p = $this->make_player();

		$captured_sql = '';
		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow', 'gw2')");
		$this->db->expects($this->once())
			->method('sql_query_limit')
			->with($this->callback(function ($sql) use (&$captured_sql) {
				$captured_sql = $sql;
				return true;
			}), 10)
			->willReturn('fake_result');
		$this->db->method('sql_fetchrow')->willReturn(false);

		$p->get_stalest_players(['wow', 'gw2'], 10);

		$this->assertStringContainsString('player_status = 1', $captured_sql);
		$this->assertStringContainsString("game_id IN ('wow', 'gw2')", $captured_sql);
		$this->assertStringContainsString('ORDER BY player_last_synced ASC', $captured_sql);
	}

	public function test_get_stalest_players_returns_all_fetched_rows(): void
	{
		$p = $this->make_player();

		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow')");
		$this->db->method('sql_query_limit')->willReturn('fake_result');
		$rows = [
			['player_id' => 1, 'game_id' => 'wow'],
			['player_id' => 2, 'game_id' => 'wow'],
		];
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls($rows[0], $rows[1], false);

		$result = $p->get_stalest_players(['wow'], 10);

		$this->assertSame($rows, $result);
	}

	public function test_update_last_synced_updates_correct_player(): void
	{
		$p = $this->make_player();

		$captured_sql = '';
		$this->db->expects($this->once())
			->method('sql_query')
			->with($this->callback(function ($sql) use (&$captured_sql) {
				$captured_sql = $sql;
				return true;
			}));

		$p->update_last_synced(42);

		$this->assertStringContainsString('UPDATE bb_players', $captured_sql);
		$this->assertStringContainsString('player_last_synced', $captured_sql);
		$this->assertStringContainsString('WHERE player_id = 42', $captured_sql);
	}
}
