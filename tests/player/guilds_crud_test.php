<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Guild CRUD characterization tests — issue #244
 *
 * Covers: update_guilddefault(), get_guild() (both branches), and
 * update_guild()'s success path — both the unchanged-name/realm branch and
 * the renamed-but-not-a-duplicate branch (including the recompute-
 * playercount side effect via count_players() in both cases).
 *
 * make_guild()/delete_guild() success paths (#372): make_guild() internally
 * constructs a real `ranks` object via `new ranks(...)`, whose constructor
 * type-hints `\phpbb\db\driver\driver_interface`/
 * `\phpbb\cache\driver\driver_interface`/`\phpbb\user`/
 * `\avathar\bbguild\model\admin\log` — the plain duck-typed
 * fake_guilds_db_driver/fake_guilds_cache_driver classes below satisfy
 * guilds' own untyped properties fine, but fail that type check. Their
 * test uses PHPUnit's createMock()/getMockBuilder() against the real
 * interfaces/classes instead (same pattern already proven in
 * roster_test.php), which satisfies both the type hints and the untyped
 * property-injection this file otherwise uses. delete_guild() doesn't
 * construct anything with typed constructor args, so it keeps using the
 * plain fakes; its filesystem emblem-cleanup touches a real path that
 * doesn't exist for the test fixture, so no filesystem double is needed.
 *
 * NOT covered (documented gap, not silently dropped):
 *   - The trigger_error(E_USER_WARNING) guard clauses in make_guild(),
 *     update_guild(), and delete_guild() (empty name/realm, duplicate
 *     guild, invalid guild id, guild still has players). These are
 *     phpBB's human-facing UI error path — msg_handler() catches
 *     E_USER_WARNING and renders a message to the browser user — not a
 *     programmatic control-flow signal, so they're not a unit-test target.
 */

namespace avathar\bbguild\tests\player;

use avathar\bbguild\model\player\guilds;
use PHPUnit\Framework\TestCase;

class fake_guilds_db_driver
{
	public $queries = [];
	private $fetchrow_queue = [];
	private $fetchfield_queue = [];

	public function queue_fetchrow($row): void { $this->fetchrow_queue[] = $row; }
	public function queue_fetchfield($val): void { $this->fetchfield_queue[] = $val; }

	public function sql_build_array($op, $data) { return '(' . implode(', ', array_map(fn($k, $v) => "$k = '$v'", array_keys($data), $data)) . ')'; }
	public function sql_build_query($type, $array) { return 'BUILT_QUERY'; }
	public function sql_escape($s) { return $s; }
	public function sql_query($sql, $ttl = 0) { $this->queries[] = $sql; return true; }
	public function sql_fetchrow($result = false) { return array_shift($this->fetchrow_queue); }
	public function sql_fetchfield($field, $rownum = false, $result = false) { return array_shift($this->fetchfield_queue); }
	public function sql_freeresult($result = false) {}
	public function sql_affectedrows() { return 1; }
}

class fake_guilds_cache_driver
{
	public $destroyed = [];
	public function destroy($var_name, $table = '') { $this->destroyed[] = [$var_name, $table]; }
}

class guilds_crud_test extends TestCase
{
	protected function setUp(): void
	{
		if (!defined('USERS_TABLE'))
		{
			define('USERS_TABLE', 'phpbb_users');
		}
	}

	private function make_guild(): guilds
	{
		$reflection = new \ReflectionClass(guilds::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	private function set_prop(guilds $g, string $name, $value): void
	{
		$reflection = new \ReflectionObject($g);
		$prop = $reflection->getProperty($name);
		$prop->setAccessible(true);
		$prop->setValue($g, $value);
	}

	public function test_update_guilddefault_sets_target_true_and_others_false(): void
	{
		$g = $this->make_guild();
		$db = new fake_guilds_db_driver();
		$cache = new fake_guilds_cache_driver();
		$this->set_prop($g, 'db', $db);
		$this->set_prop($g, 'cache', $cache);
		$g->bb_guild_table = 'bb_guild';

		$g->update_guilddefault(5);

		$this->assertCount(2, $db->queries);
		$this->assertStringContainsString('SET guilddefault = 1 WHERE id = 5', $db->queries[0]);
		$this->assertStringContainsString('SET guilddefault = 0 WHERE id != 5', $db->queries[1]);
		$this->assertSame([['sql', 'bb_guild']], $cache->destroyed);
	}

	public function test_get_guild_no_matching_row_leaves_fields_untouched(): void
	{
		$g = $this->make_guild();
		$db = new fake_guilds_db_driver();
		$this->set_prop($g, 'db', $db);
		$g->bb_guild_table = 'bb_guild';
		$g->bb_factions_table = 'bb_factions';
		$g->guildid = 5;

		$g->get_guild();

		$this->assertSame(5, $g->getGuildid());
		$this->assertSame('', $g->getName());
	}

	public function test_get_guild_populates_fields_from_row(): void
	{
		$g = $this->make_guild();
		$db = new fake_guilds_db_driver();
		$this->set_prop($g, 'db', $db);
		$this->set_prop($g, 'config', ['bbguild_lang' => 'en']);
		$g->bb_guild_table = 'bb_guild';
		$g->bb_factions_table = 'bb_factions';
		$g->bb_players_table = 'bb_players';
		$g->bb_ranks_table = 'bb_ranks';
		$g->bb_classes_table = 'bb_classes';
		$g->bb_races_table = 'bb_races';
		$g->bb_language_table = 'bb_language';
		$g->guildid = 5;

		$db->queue_fetchrow([
			'game_id' => 'wow', 'game_edition' => 'retail', 'id' => 5, 'name' => 'Test Guild',
			'realm' => 'Realm', 'region' => 'EU', 'roster' => 1, 'emblemurl' => 'x.png',
			'min_armory' => 0, 'rec_status' => 1, 'armory_enabled' => 0, 'armoryresult' => '',
			'guilddefault' => 0, 'recruitforum' => 0, 'faction' => 0, 'faction_name' => 'Alliance',
		]);
		$db->queue_fetchfield(7); // count_players()
		$db->queue_fetchfield(3); // maxrank() call #1 -> raidtrackerrank
		$db->queue_fetchfield(3); // maxrank() call #2 -> applyrank (called twice in source)

		$g->get_guild();

		$this->assertSame('Test Guild', $g->getName());
		$this->assertSame(7, $g->getPlayercount());
		$this->assertSame(3, $g->getRaidtrackerrank());
		$this->assertSame(3, $g->getApplyrank());
		$this->assertSame('Alliance', $g->getFactionname());
	}

	public function test_update_guild_success_updates_and_recomputes_playercount(): void
	{
		$g = $this->make_guild();
		$old = $this->make_guild();
		$db = new fake_guilds_db_driver();
		$cache = new fake_guilds_cache_driver();
		$this->set_prop($g, 'db', $db);
		$this->set_prop($g, 'cache', $cache);
		$this->set_prop($g, 'config', ['bbguild_lang' => 'en']);
		$g->setName('Same Name');
		$g->setRealm('SameRealm');
		$old->setName('Same Name');
		$old->setRealm('SameRealm');
		$g->guildid = 5;
		$g->bb_guild_table = 'bb_guild';
		$g->bb_players_table = 'bb_players';
		$g->bb_ranks_table = 'bb_ranks';
		$g->bb_classes_table = 'bb_classes';
		$g->bb_races_table = 'bb_races';
		$g->bb_language_table = 'bb_language';
		$db->queue_fetchfield(9); // count_players()

		$result = $g->update_guild($old);

		$this->assertTrue($result);
		$this->assertSame(9, $g->getPlayercount());
		$this->assertStringContainsString('UPDATE bb_guild SET', $db->queries[1]);
		$this->assertStringContainsString('WHERE id= 5', $db->queries[1]);
	}

	public function test_update_guild_success_when_renamed_and_not_a_duplicate(): void
	{
		$g = $this->make_guild();
		$old = $this->make_guild();
		$db = new fake_guilds_db_driver();
		$cache = new fake_guilds_cache_driver();
		$this->set_prop($g, 'db', $db);
		$this->set_prop($g, 'cache', $cache);
		$this->set_prop($g, 'config', ['bbguild_lang' => 'en']);
		$g->setName('New Name');
		$g->setRealm('NewRealm');
		$old->setName('Old Name');
		$old->setRealm('OldRealm');
		$g->guildid = 5;
		$g->bb_guild_table = 'bb_guild';
		$g->bb_players_table = 'bb_players';
		$g->bb_ranks_table = 'bb_ranks';
		$g->bb_classes_table = 'bb_classes';
		$g->bb_races_table = 'bb_races';
		$g->bb_language_table = 'bb_language';
		$db->queue_fetchrow(['evcount' => 0]); // duplicate-check query: no clash
		$db->queue_fetchfield(4); // count_players()

		$result = $g->update_guild($old);

		$this->assertTrue($result);
		$this->assertSame(4, $g->getPlayercount());
		$this->assertCount(3, $db->queries);
		$this->assertStringContainsString('UPDATE bb_guild SET', $db->queries[2]);
		$this->assertStringContainsString('WHERE id= 5', $db->queries[2]);
	}

	// What: the guild-creation success path, including the implicit
	// "Guild Leader" rank it creates via a real `ranks` object.
	// Why: this is the case the file's own docblock used to document as
	// unsatisfiable with the plain fakes below — db/cache/user/log all
	// need to be real interface/class mocks here (not the duck-typed
	// fake_guilds_db_driver/fake_guilds_cache_driver used elsewhere in
	// this file) because make_guild() passes them straight into
	// `new ranks(...)`, whose constructor type-hints the real phpBB
	// types. createMock()/getMockBuilder() against those real types is
	// what makes this solvable (same trick as roster_test.php).
	public function test_make_guild_success_inserts_guild_and_guildleader_rank(): void
	{
		$g = $this->make_guild();

		// Captures every sql_query() call's SQL text in order, so the
		// assertions below can check the exact sequence of queries this
		// one make_guild() call produces (across both `guilds` and the
		// `ranks` object it constructs internally).
		$captured = [];
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturnCallback(function ($sql) use (&$captured) {
			$captured[] = $sql;
			return true;
		});
		// Exactly two sql_fetchrow() calls happen on this path: the
		// duplicate-guild-name check (0 = no clash) and the MAX(id)
		// lookup used to compute the new guild's id (4 -> new id 5).
		$db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(['evcount' => 0], ['id' => 4]);
		$db->method('sql_build_array')->willReturnCallback(
			fn($op, $data) => ' (' . implode(', ', array_keys($data)) . ')'
		);
		$db->method('sql_escape')->willReturnArgument(0);

		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);

		// GUILDLEADER is read when the internally-constructed `ranks`
		// object's RankName gets set to it, just before Makerank() runs.
		$user = $this->createMock(\phpbb\user::class);
		$user->lang = ['GUILDLEADER' => 'Guild Leader'];

		// Two log_insert() calls: make_guild()'s own L_ACTION_GUILD_ADDED,
		// plus Makerank()'s L_ACTION_RANK_ADDED for the guild-leader rank
		// it creates as a side effect.
		$log = $this->getMockBuilder(\avathar\bbguild\model\admin\log::class)
			->disableOriginalConstructor()
			->getMock();
		$log->expects($this->exactly(2))->method('log_insert');

		$this->set_prop($g, 'db', $db);
		$this->set_prop($g, 'cache', $cache);
		$this->set_prop($g, 'user', $user);
		$this->set_prop($g, 'log', $log);
		$g->setName('Test Guild');
		$g->setRealm('TestRealm');
		$g->bb_guild_table = 'bb_guild';
		$g->bb_players_table = 'bb_players';
		$g->bb_ranks_table = 'bb_ranks';

		$g->make_guild();

		// 6 queries: evcount check, MAX(id) check, INSERT guild, then the
		// new `ranks` object's constructor runs Getrank() (RankGuild>=0 &&
		// RankId==0 triggers it before Makerank() overwrites RankName etc.),
		// then Makerank()'s own DELETE + INSERT.
		$this->assertSame(5, $g->getGuildid());
		$this->assertCount(6, $captured);
		$this->assertStringContainsString('INSERT INTO bb_guild', $captured[2]);
		$this->assertStringContainsString('SELECT rank_name', $captured[3]);
		$this->assertStringContainsString('DELETE FROM bb_ranks', $captured[4]);
		$this->assertStringContainsString('rank_id = 0', $captured[4]);
		$this->assertStringContainsString('guild_id = 5', $captured[4]);
		$this->assertStringContainsString('INSERT INTO bb_ranks', $captured[5]);
	}

	public function test_delete_guild_success_removes_ranks_and_guild_row(): void
	{
		$GLOBALS['phpbb_root_path'] = '/tmp/bbguild-test-nonexistent-root/';

		$g = $this->make_guild();
		$db = new fake_guilds_db_driver();
		$cache = new fake_guilds_cache_driver();
		$log = $this->getMockBuilder(\avathar\bbguild\model\admin\log::class)
			->disableOriginalConstructor()
			->getMock();
		$log->expects($this->once())->method('log_insert');

		$this->set_prop($g, 'db', $db);
		$this->set_prop($g, 'cache', $cache);
		$this->set_prop($g, 'log', $log);
		$g->guildid = 5;
		$g->bb_guild_table = 'bb_guild';
		$g->bb_players_table = 'bb_players';
		$g->bb_ranks_table = 'bb_ranks';
		$db->queue_fetchfield(0); // no players in guild

		$g->delete_guild();

		$this->assertCount(3, $db->queries);
		$this->assertStringContainsString('SELECT COUNT(*) as mcount', $db->queries[0]);
		$this->assertStringContainsString('DELETE FROM bb_ranks', $db->queries[1]);
		$this->assertStringContainsString('guild_id = 5', $db->queries[1]);
		$this->assertStringContainsString('DELETE FROM bb_guild', $db->queries[2]);
		$this->assertStringContainsString('WHERE id = 5', $db->queries[2]);
		$this->assertSame([['sql', 'bb_guild']], $cache->destroyed);
	}
}
