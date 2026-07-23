<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player claim/unclaim tests — issue #244
 */

namespace avathar\bbguild\tests\player;

use avathar\bbguild\model\player\player;
use PHPUnit\Framework\TestCase;

class player_claim_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\driver\driver_interface */
	private $db;

	protected function setUp(): void
	{
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
	}

	private function make_player(int $player_id, int $viewing_user_id): player
	{
		$reflection = new \ReflectionClass(player::class);
		/** @var player $p */
		$p = $reflection->newInstanceWithoutConstructor();
		$p->player_id = $player_id;
		$p->bb_players_table = 'bb_players';

		$db_prop = $reflection->getProperty('db');
		$db_prop->setAccessible(true);
		$db_prop->setValue($p, $this->db);

		// $user is a plain, untyped property on player (no `\phpbb\user` type
		// hint enforced at assignment) — a stdClass with a public `data` array
		// satisfies `$this->user->data['user_id']` without needing phpBB's real
		// user class (and its \phpbb\session dependency chain) loadable.
		$user = (object) ['data' => ['user_id' => $viewing_user_id]];
		$user_prop = $reflection->getProperty('user');
		$user_prop->setAccessible(true);
		$user_prop->setValue($p, $user);

		return $p;
	}

	public function test_claim_player_updates_phpbb_user_id_to_viewing_user(): void
	{
		$p = $this->make_player(42, 7);

		$captured_sql = '';
		$this->db->method('sql_build_array')->willReturn('phpbb_user_id = 7');
		$this->db->expects($this->once())
			->method('sql_query')
			->with($this->callback(function ($sql) use (&$captured_sql) {
				$captured_sql = $sql;
				return true;
			}));

		$p->Claim_Player();

		$this->assertStringContainsString('UPDATE bb_players', $captured_sql);
		$this->assertStringContainsString('WHERE player_id = 42', $captured_sql);
	}

	public function test_unclaim_player_returns_true_when_row_affected(): void
	{
		$p = $this->make_player(42, 7);

		$this->db->expects($this->once())->method('sql_query');
		$this->db->method('sql_affectedrows')->willReturn(1);

		$this->assertTrue($p->Unclaim_Player());
	}

	public function test_unclaim_player_returns_false_when_not_owned_by_viewing_user(): void
	{
		$p = $this->make_player(42, 7);

		$this->db->expects($this->once())->method('sql_query');
		$this->db->method('sql_affectedrows')->willReturn(0);

		$this->assertFalse($p->Unclaim_Player());
	}

	public function test_unclaim_player_scopes_query_to_both_player_id_and_viewing_user(): void
	{
		$p = $this->make_player(42, 7);

		$captured_sql = '';
		$this->db->expects($this->once())
			->method('sql_query')
			->with($this->callback(function ($sql) use (&$captured_sql) {
				$captured_sql = $sql;
				return true;
			}));
		$this->db->method('sql_affectedrows')->willReturn(1);

		$p->Unclaim_Player();

		$this->assertStringContainsString('player_id = 42', $captured_sql);
		$this->assertStringContainsString('phpbb_user_id = 7', $captured_sql);
	}
}
