<?php
/**
 * bbGuild Extension — migration idempotency smoke test
 *
 * @package   bbGuild Extension
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

/**
 * Disables bbguild core (data preserved) and re-enables it, then asserts
 * the sample data seeded by v200b3's insert_sample_data()/seed_portal_layout()
 * and the permission/ACP-module rows added by the same migration were not
 * duplicated. Disable does not revert schema/data, so re-enabling re-runs
 * every migration's effectively_installed() check against data that's
 * already there — this is the only way to exercise that path without a
 * second fresh install. Mirrors bbguildwow's
 * smoke_migration_idempotency_test.php, which already covers the same gap
 * for the plugin side; this is the core-side counterpart requested by #372.
 *
 * insert_sample_data() explicitly calls remove_sample_data() first ("Clean
 * up any partial previous run") specifically to guard against this — this
 * test is what actually exercises that guard, which until now had never
 * run under any automated check.
 *
 * @group smoke
 */
class avathar_bbguild_smoke_migration_idempotency_test extends phpbb_functional_test_case
{
	static protected function setup_extensions()
	{
		return array('avathar/bbguild');
	}

	private function count_rows(string $table, string $where): int
	{
		$db = $this->get_db();
		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $table . ' WHERE ' . $where;
		$result = $db->sql_query($sql);
		$count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		return $count;
	}

	public function test_reenable_does_not_duplicate_seeded_data()
	{
		$prefix = $this->get_table_prefix();

		$before_games = $this->count_rows($prefix . 'bb_games', "game_id = 'custom'");
		$before_factions = $this->count_rows($prefix . 'bb_factions', "game_id = 'custom'");
		$before_classes = $this->count_rows($prefix . 'bb_classes', "game_id = 'custom'");
		$before_races = $this->count_rows($prefix . 'bb_races', "game_id = 'custom'");
		$before_guild = $this->count_rows($prefix . 'bb_guild', 'id IN (0, 1)');
		$before_portal_modules = $this->count_rows($prefix . 'bb_portal_modules', 'guild_id = 1');
		$before_perm = $this->count_rows($prefix . 'acl_options', "auth_option = 'a_bbguild'");
		$before_acp_cat = $this->count_rows($prefix . 'modules', "module_langname = 'ACP_CAT_BBGUILD'");
		$before_migrations = $this->count_rows($prefix . 'migrations', "migration_name LIKE '%bbguild%'");

		$this->disable_ext('avathar/bbguild');
		$this->install_ext('avathar/bbguild');

		$after_games = $this->count_rows($prefix . 'bb_games', "game_id = 'custom'");
		$after_factions = $this->count_rows($prefix . 'bb_factions', "game_id = 'custom'");
		$after_classes = $this->count_rows($prefix . 'bb_classes', "game_id = 'custom'");
		$after_races = $this->count_rows($prefix . 'bb_races', "game_id = 'custom'");
		$after_guild = $this->count_rows($prefix . 'bb_guild', 'id IN (0, 1)');
		$after_portal_modules = $this->count_rows($prefix . 'bb_portal_modules', 'guild_id = 1');
		$after_perm = $this->count_rows($prefix . 'acl_options', "auth_option = 'a_bbguild'");
		$after_acp_cat = $this->count_rows($prefix . 'modules', "module_langname = 'ACP_CAT_BBGUILD'");
		$after_migrations = $this->count_rows($prefix . 'migrations', "migration_name LIKE '%bbguild%'");

		$this->assertSame(1, $before_games, 'expected exactly one custom-game row in bb_games before re-enable');
		$this->assertSame(2, $before_factions, 'expected exactly two seeded factions before re-enable');
		$this->assertSame(1, $before_perm, "expected exactly one 'a_bbguild' row in acl_options before re-enable");
		$this->assertSame(1, $before_acp_cat, 'expected exactly one ACP_CAT_BBGUILD module row before re-enable');

		$this->assertSame($before_games, $after_games, 'bb_games custom-game row was duplicated on re-enable');
		$this->assertSame($before_factions, $after_factions, 'bb_factions rows were duplicated on re-enable');
		$this->assertSame($before_classes, $after_classes, 'bb_classes rows were duplicated on re-enable');
		$this->assertSame($before_races, $after_races, 'bb_races rows were duplicated on re-enable');
		$this->assertSame($before_guild, $after_guild, 'sample bb_guild rows were duplicated on re-enable');
		$this->assertSame($before_portal_modules, $after_portal_modules, 'seeded portal modules were duplicated on re-enable');
		$this->assertSame($before_perm, $after_perm, "'a_bbguild' permission row was duplicated on re-enable");
		$this->assertSame($before_acp_cat, $after_acp_cat, 'ACP_CAT_BBGUILD module row was duplicated on re-enable');
		$this->assertSame($before_migrations, $after_migrations, 'bbguild migration rows changed on re-enable');
	}

	private function get_table_prefix(): string
	{
		return self::$config['table_prefix'];
	}
}
