<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player CRUD characterization tests — issue #372 ("Player CRUD beyond
 * claim/unclaim" — add/edit/delete character). Claim/unclaim were already
 * covered by player_claim_test.php (#244); this file covers
 * Makeplayer()/Updateplayer()/Deleteplayer()'s success paths.
 *
 * Like player_claim_test.php, `$user`/`$log` on `player` are untyped
 * properties (docblocked only), so a plain stdClass/fake satisfies them
 * without needing phpBB's real \phpbb\user or bbguild's `log` class loadable.
 *
 * NOT covered (documented gap, not silently dropped):
 *   - The trigger_error(E_USER_WARNING) validation-failure branches in
 *     Makeplayer()/Updateplayer() (duplicate name, invalid rank) — same
 *     human-facing-only rationale as guilds_crud_test.php's equivalent
 *     gap note.
 *   - Updateplayer()'s two player_status-transition branches (the exact
 *     code #377 touched) — worth a dedicated follow-up given that history,
 *     but out of scope for this "success path" pass.
 *   - Game-provider-backed portrait generation (generate_portrait_from_provider()
 *     with a real provider) — game_registry is left null here, which
 *     exercises only the no-op fallback branch.
 */

namespace avathar\bbguild\tests\player;

use avathar\bbguild\model\player\player;
use PHPUnit\Framework\TestCase;

class fake_player_db_driver
{
	public $queries = [];
	private $fetchfield_queue = [];

	public function queue_fetchfield($val): void { $this->fetchfield_queue[] = $val; }

	public function sql_build_array($op, $data) { return '(' . implode(', ', array_keys($data)) . ')'; }
	public function sql_escape($s) { return $s; }
	public function sql_query($sql, $ttl = 0) { $this->queries[] = $sql; return true; }
	public function sql_fetchfield($field, $rownum = false, $result = false) { return array_shift($this->fetchfield_queue); }
	public function sql_fetchrow($result = false) { return false; }
	public function sql_freeresult($result = false) {}
	public function sql_nextid() { return 501; }
}

class fake_player_log
{
	public $inserted = [];
	public function log_insert($values) { $this->inserted[] = $values; }
}

class player_crud_test extends TestCase
{
	private function make_player(): player
	{
		$reflection = new \ReflectionClass(player::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	private function set_prop(player $p, string $name, $value): void
	{
		$reflection = new \ReflectionObject($p);
		$prop = $reflection->getProperty($name);
		$prop->setAccessible(true);
		$prop->setValue($p, $value);
	}

	private function make_user(array $lang = []): object
	{
		return (object) ['data' => ['user_id' => 7, 'username' => 'tester'], 'lang' => $lang];
	}

	public function test_makeplayer_success_inserts_and_returns_new_player_id(): void
	{
		$p = $this->make_player();
		$db = new fake_player_db_driver();
		$log = new fake_player_log();

		$this->set_prop($p, 'db', $db);
		$this->set_prop($p, 'user', $this->make_user());
		$this->set_prop($p, 'log', $log);
		$p->bb_players_table = 'bb_players';
		$p->bb_ranks_table = 'bb_ranks';
		$p->bb_classes_table = 'bb_classes';
		$p->setPlayerName('Newchar');
		$p->setPlayerRealm('TestRealm');
		$p->setPlayerRegion('EU');
		$p->setPlayerGuildId(5);
		$p->setPlayerRankId(2);
		$p->setPlayerLevel(80);
		$p->setPlayerStatus(1);
		$p->setPlayerRaceId(1);
		$p->setPlayerClassId(1);
		$p->setPlayerGenderId(0);

		$db->queue_fetchfield(0);   // playerexists: none
		$db->queue_fetchfield(1);   // rankccount: rank exists
		$db->queue_fetchfield(100); // maxlevel: no clamp needed at level 80

		$result = $p->Makeplayer();

		$this->assertSame(501, $result);
		$this->assertSame(501, $p->player_id);
		$this->assertCount(4, $db->queries);
		$this->assertStringContainsString('SELECT count(*) as playerexists', $db->queries[0]);
		$this->assertStringContainsString('rankccount', $db->queries[1]);
		$this->assertStringContainsString('maxlevel', $db->queries[2]);
		$this->assertStringContainsString('INSERT INTO bb_players', $db->queries[3]);
		$this->assertCount(1, $log->inserted);
		$this->assertSame('L_ACTION_PLAYER_ADDED', $log->inserted[0]['log_type']);
	}

	public function test_makeplayer_clamps_level_to_class_max_level(): void
	{
		$p = $this->make_player();
		$db = new fake_player_db_driver();
		$log = new fake_player_log();

		$this->set_prop($p, 'db', $db);
		$this->set_prop($p, 'user', $this->make_user());
		$this->set_prop($p, 'log', $log);
		$p->bb_players_table = 'bb_players';
		$p->bb_ranks_table = 'bb_ranks';
		$p->bb_classes_table = 'bb_classes';
		$p->setPlayerName('Overlevel');
		$p->setPlayerRealm('TestRealm');
		$p->setPlayerRegion('EU');
		$p->setPlayerGuildId(5);
		$p->setPlayerRankId(2);
		$p->setPlayerLevel(999);
		$p->setPlayerStatus(1);

		$db->queue_fetchfield(0);  // playerexists: none
		$db->queue_fetchfield(1);  // rankccount: rank exists
		$db->queue_fetchfield(60); // maxlevel: below requested 999

		$p->Makeplayer();

		$this->assertSame(60, $p->getPlayerLevel());
	}

	public function test_updateplayer_success_updates_row_and_logs(): void
	{
		$p = $this->make_player();
		$old = $this->make_player();
		$db = new fake_player_db_driver();
		$log = new fake_player_log();

		$this->set_prop($p, 'db', $db);
		$this->set_prop($p, 'user', $this->make_user());
		$this->set_prop($p, 'log', $log);
		$p->bb_players_table = 'bb_players';
		$p->bb_ranks_table = 'bb_ranks';
		$p->bb_classes_table = 'bb_classes';
		$p->player_id = 42;
		$p->setPlayerName('Samechar');
		$p->setPlayerStatus(1);
		$p->setPlayerLevel(50);
		$p->setPlayerRankId(2);
		$p->setPlayerGuildId(5);
		$p->setPlayerPortraitUrl('https://example.test/portrait.png');
		$old->setPlayerName('Samechar');
		$old->setPlayerStatus(1);

		$db->queue_fetchfield(1);   // rankccount: rank exists
		$db->queue_fetchfield(100); // maxlevel: no clamp

		$result = $p->Updateplayer($old);

		$this->assertTrue($result);
		$this->assertCount(3, $db->queries);
		$this->assertStringContainsString('rankccount', $db->queries[0]);
		$this->assertStringContainsString('maxlevel', $db->queries[1]);
		$this->assertStringContainsString('UPDATE bb_players', $db->queries[2]);
		$this->assertStringContainsString('WHERE player_id= 42', $db->queries[2]);
		$this->assertCount(1, $log->inserted);
		$this->assertSame('L_ACTION_PLAYER_UPDATED', $log->inserted[0]['log_type']);
	}

	public function test_updateplayer_returns_false_when_player_id_zero(): void
	{
		$p = $this->make_player();
		$old = $this->make_player();
		$p->player_id = 0;

		$this->assertFalse($p->Updateplayer($old));
	}

	public function test_deleteplayer_success_removes_row_and_logs(): void
	{
		$p = $this->make_player();
		$db = new fake_player_db_driver();
		$log = new fake_player_log();

		$this->set_prop($p, 'db', $db);
		$this->set_prop($p, 'log', $log);
		$p->bb_players_table = 'bb_players';
		$p->player_id = 42;
		$p->setPlayerName('Deleteme');

		$p->Deleteplayer();

		$this->assertCount(1, $db->queries);
		$this->assertStringContainsString('DELETE FROM bb_players', $db->queries[0]);
		$this->assertStringContainsString('player_id = 42', $db->queries[0]);
		$this->assertCount(1, $log->inserted);
		$this->assertSame('L_ACTION_PLAYER_DELETED', $log->inserted[0]['log_type']);
		$this->assertSame(['Deleteme'], $log->inserted[0]['log_action']);
	}
}
