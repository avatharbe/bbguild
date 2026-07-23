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
 * NOT covered (documented gap, not silently dropped):
 *   - The trigger_error(E_USER_WARNING) guard clauses in make_guild(),
 *     update_guild(), and delete_guild() (empty name/realm, duplicate
 *     guild, invalid guild id, guild still has players). These are
 *     phpBB's human-facing UI error path — msg_handler() catches
 *     E_USER_WARNING and renders a message to the browser user — not a
 *     programmatic control-flow signal, so they're not a unit-test target.
 *   - make_guild()'s and delete_guild()'s full success paths. make_guild()
 *     success instantiates a real `ranks` object via `new ranks(...)`,
 *     whose constructor requires real `\phpbb\user`/
 *     `\phpbb\cache\driver\driver_interface`/`\avathar\bbguild\model\admin\log`
 *     typed arguments — those can't be satisfied with the untyped-property
 *     injection trick this file otherwise uses. delete_guild()'s success
 *     path touches the real filesystem (emblem cleanup) and
 *     `log->log_insert()`. Both left for a follow-up once a fuller phpBB
 *     test double library exists.
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
}
