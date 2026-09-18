<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Characterization test for ucp\bbguild_module::fill_addplayer() — the
 * single highest-complexity method in the codebase (CC=73 per the
 * cyclomatic-complexity pass in contrib/architecture.md), previously at
 * 0% coverage. fill_addplayer() is private and produces its output
 * entirely as template-assignment side effects (no return value), so
 * this exercises it via reflection + a template double that records
 * every assign_vars()/assign_block_vars() call, rather than asserting
 * on a return value.
 *
 * Scope: the "add new character" happy path (player_id == 0) with one
 * real guild in the guild list (to exercise the default-guild-selection
 * and guild-popup branches) and one guildless placeholder (id 0, to
 * exercise the "exclude guildless" skip). All DB reads return no rows,
 * which collapses every dropdown-population loop (rank/race/class/role/
 * spec) to zero iterations — those loop bodies are a real, separate gap,
 * not claimed as covered here. Not covered: the player_id > 0 (edit)
 * branch, the max-chars-exceeded branch, and every dropdown loop body —
 * good candidates for a follow-up pass.
 */

namespace avathar\bbguild\tests\ucp;

use avathar\bbguild\ucp\bbguild_module;
use PHPUnit\Framework\TestCase;

// What: a minimal stand-in for phpBB's real template service.
// Why: $this->template is untyped, so a plain recording object lets the
// test assert on exactly what fill_addplayer() assigned, in plain PHP
// arrays, instead of configuring per-call expectations on a mock.
class fake_module_template
{
	public $vars = [];
	public $blocks = [];
	public function assign_vars($a) { $this->vars = array_merge($this->vars, $a); }
	public function assign_var($name, $value) { $this->vars[$name] = $value; }
	public function assign_block_vars($block, $a) { $this->blocks[$block][] = $a; }
}

// What: an auth stand-in that grants every permission by default.
// Why: fill_addplayer()'s add-mode guard checks u_charadd/u_charclaim via
// $this->auth->acl_get() and trigger_error()s (E_USER_WARNING) if either
// is denied — granting everything keeps this test on the happy path;
// $granted lets a future test override specific permissions to false.
class fake_module_auth
{
	public $granted = [];
	public function acl_get($opt) { return $this->granted[$opt] ?? true; }
}

// What: a stand-in for phpBB's real service container.
// Why: fill_addplayer() reads `global $phpbb_container` directly (not
// through DI) for one table-name parameter, and game_has_api() (called
// twice from the assign_vars block) tries the container's game_registry
// service first. Making get() always throw forces game_has_api() down
// its DB-fallback branch instead, which is simpler to stub than a real
// registry.
class fake_module_container
{
	private $params;
	public function __construct(array $params) { $this->params = $params; }
	public function get($id) { throw new \Exception('not stubbed: ' . $id); } // forces registry-lookup fallback paths
	public function getParameter($id) { return $this->params[$id] ?? ('bb_' . substr($id, strrpos($id, '.') + 1)); }
}

// What: a no-listener stand-in for phpBB's event dispatcher.
// Why: phpBB's global add_form_key() (called near the end of
// fill_addplayer()) unconditionally calls
// $phpbb_dispatcher->trigger_event() — without this global set, that
// call fatals on a null method call. Real trigger_event() just returns
// its input when nothing is subscribed, which is what this mirrors.
class fake_module_dispatcher
{
	public function trigger_event($name, $vars) { return $vars; } // no listeners registered
}

class bbguild_module_test extends TestCase
{
	// What: builds a bbguild_module instance without running its real
	// constructor.
	// Why: the real constructor pulls ~15 dependencies straight out of
	// PHP globals (`global $db, $user, $auth, ...`) rather than typed DI
	// args, none of which fill_addplayer() itself needs beyond what's
	// injected below — bypassing it keeps the test from having to fake an
	// entire legacy phpBB request environment just to construct the object.
	private function make_module(): bbguild_module
	{
		$reflection = new \ReflectionClass(bbguild_module::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	// What: sets a declared property via reflection, bypassing
	// visibility.
	// Why: only for db/config/user/template/games/regions below — the
	// bbguild_cache/bbguild_log/etc. properties further down are NOT
	// declared on the class (only ever dynamically created inside
	// main()), so those are injected with plain `$m->prop = ...`
	// assignment instead; reflection's getProperty() would throw for them.
	private function set_prop($obj, string $name, $value): void
	{
		$reflection = new \ReflectionObject($obj);
		$prop = $reflection->getProperty($name);
		$prop->setAccessible(true);
		$prop->setValue($obj, $value);
	}

	public function test_fill_addplayer_add_mode_assigns_template_vars_for_new_character(): void
	{
		// What: the phpBB "guest" user-id constant.
		// Why: phpBB's global add_form_key() compares
		// $user->data['user_id'] against ANONYMOUS — normally defined by
		// phpBB's own bootstrap, but not guaranteed loaded in this
		// unit-test harness, so it's defined defensively here.
		if (!defined('ANONYMOUS')) { define('ANONYMOUS', 1); }

		// What: a DB double where every query "succeeds" but returns no
		// rows/fields.
		// Why: fill_addplayer() constructs several real domain objects
		// internally (player/guilds/ranks/roles/specialization), each of
		// which runs its own queries — always-empty results collapse
		// every one of those dropdown-population loops to zero
		// iterations, so the test only has to reason about the
		// always-executed setup/assign_vars code (see the file docblock
		// for what that leaves uncovered).
		$db_queries = [];
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturnCallback(function ($sql) use (&$db_queries) {
			$db_queries[] = $sql;
			return 'RESULT';
		});
		$db->method('sql_fetchrow')->willReturn(false); // every dropdown loop sees zero rows
		$db->method('sql_fetchfield')->willReturn(0);
		$db->method('sql_escape')->willReturnArgument(0);
		$db->method('sql_build_query')->willReturn('BUILT_QUERY');
		$db->method('sql_build_array')->willReturnCallback(
			fn($op, $data) => '(' . implode(', ', array_keys($data)) . ')'
		);

		// What: a real (interface) cache mock, not a plain duck-typed fake.
		// Why: fill_addplayer() passes $this->cache straight into
		// `new guilds(...)`/`new ranks(...)`/etc., whose constructors
		// type-hint \phpbb\cache\driver\driver_interface — a plain class
		// wouldn't satisfy that type check (see guilds_crud_test.php's
		// file docblock for the same issue on make_guild()).
		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);

		// What: a real (not mocked) phpBB config object.
		// Why: \phpbb\config\config is cheap to construct directly and
		// several constructed objects below type-hint the concrete class,
		// not an interface, so a real instance is simpler than mocking it.
		$config = new \phpbb\config\config(['bbguild_lang' => 'en', 'bbguild_maxchars' => 5]);

		// What: only the language keys this code path actually reads.
		// Why: REGIONEU..US back player::getRegionlist() (constructed
		// inside player's own constructor via a nested `game` object) —
		// without them the region-list lookup would hit an
		// undefined-array-key warning unrelated to what this test targets.
		$user = $this->createMock(\phpbb\user::class);
		$user->lang = [
			'NOUCPADDCHARS' => 'Cannot add characters',
			'MAX_CHARS_EXCEEDED' => 'Max characters exceeded: %d',
			'CLOSED' => 'Closed', 'OPEN' => 'Open',
			'REGIONEU' => 'Europe', 'REGIONKR' => 'Korea', 'REGIONSEA' => 'SEA',
			'REGIONTW' => 'Taiwan', 'REGIONUS' => 'US',
		];
		$user->data = ['user_id' => 2, 'username' => 'tester', 'user_form_salt' => 'salt'];
		$user->session_id = 'sess123';

		// What: an extension-manager mock stubbed just enough to not
		// fatal.
		// Why: every domain object fill_addplayer() constructs
		// (player/game/etc.) calls get_extension_path('avathar/bbguild')
		// unconditionally in its own constructor — the real value doesn't
		// matter here, only that it returns a string.
		$ext_manager = $this->getMockBuilder(\phpbb\extension\manager::class)
			->disableOriginalConstructor()->getMock();
		$ext_manager->method('get_extension_path')->willReturn('ext/avathar/bbguild/');

		// What: no-op mocks for bbguild's own log/util/asset-resolver
		// services.
		// Why: constructor-required by player/etc. but their actual
		// behavior (writing log rows, resolving a portrait URL) isn't
		// under test here — disableOriginalConstructor() avoids needing
		// to satisfy their own real constructor dependencies.
		$log = $this->getMockBuilder(\avathar\bbguild\model\admin\log::class)
			->disableOriginalConstructor()->getMock();
		$util = $this->getMockBuilder(\avathar\bbguild\model\admin\util::class)
			->disableOriginalConstructor()->getMock();
		$asset_resolver = $this->getMockBuilder(\avathar\bbguild\model\admin\asset_url_resolver::class)
			->disableOriginalConstructor()->getMock();
		$asset_resolver->method('resolve_portrait_url')->willReturn('');

		$template = new fake_module_template();
		$auth = new fake_module_auth();

		$container = new fake_module_container([
			'avathar.bbguild.tables.bb_games' => 'bb_games',
			'avathar.bbguild.tables.bb_specializations' => 'bb_specializations',
		]);
		// What: the global state phpBB's own helper functions read from
		// directly, independent of the module's own injected properties.
		// Why: `global $phpbb_container` (game_has_api(), the
		// specializations-table lookup) and `global $config, $template,
		// $user, $phpbb_dispatcher` (phpBB's add_form_key()) — these
		// functions don't go through $this, so the module's own property
		// injection below doesn't reach them.
		$GLOBALS['phpbb_container'] = $container;
		$GLOBALS['phpbb_root_path'] = '';
		$GLOBALS['config'] = $config;
		$GLOBALS['template'] = $template;
		$GLOBALS['user'] = $user;
		$GLOBALS['phpbb_dispatcher'] = new fake_module_dispatcher();

		$m = $this->make_module();
		$this->set_prop($m, 'db', $db);
		$this->set_prop($m, 'config', $config);
		$this->set_prop($m, 'user', $user);
		$this->set_prop($m, 'template', $template);
		$m->auth = $auth;
		$this->set_prop($m, 'games', ['' => 'Custom Game', 'wow' => 'World of Warcraft']);
		$this->set_prop($m, 'regions', ['' => 'Any', 'EU' => 'Europe']);

		// What: not declared class properties — only ever dynamically
		// created inside main().
		// Why: reflection's getProperty() would throw "Property ... does
		// not exist" for these (verified while writing this test), so
		// plain property assignment is the correct injection route here,
		// not a workaround.
		$m->bbguild_cache = $cache;
		$m->bbguild_log = $log;
		$m->bbguild_util = $util;
		$m->bbguild_ext_manager = $ext_manager;
		$m->bbguild_game_registry = null;
		$m->asset_resolver = $asset_resolver;
		$m->bb_players_table = 'bb_players';
		$m->bb_ranks_table = 'bb_ranks';
		$m->bb_classes_table = 'bb_classes';
		$m->bb_races_table = 'bb_races';
		$m->bb_language_table = 'bb_language';
		$m->bb_guild_table = 'bb_guild';
		$m->bb_factions_table = 'bb_factions';
		$m->bb_games_table = 'bb_games';
		$m->bb_gameroles_table = 'bb_gameroles';

		// What: two guilds — the guildless placeholder (id 0) plus one
		// real, default guild.
		// Why: this is what exercises fill_addplayer()'s "auto-select the
		// default guild" branch and the "exclude guildless from the
		// popup" branch in the same pass, both asserted on below.
		$guildlist = [
			['id' => 0, 'name' => 'Guildless', 'guilddefault' => 0, 'playercount' => 0],
			['id' => 7, 'name' => 'Test Guild', 'guilddefault' => 1, 'playercount' => 3],
		];

		// fill_addplayer() is private, and produces its result only as
		// side effects on $template — invoking it via reflection is the
		// only way to exercise it directly.
		$reflection = new \ReflectionObject($m);
		$method = $reflection->getMethod('fill_addplayer');
		$method->setAccessible(true);
		$method->invoke($m, 0, $guildlist);

		$this->assertTrue($template->vars['S_ADD']);
		$this->assertTrue($template->vars['S_SHOW']);
		$this->assertNull($template->vars['PLAYER_ID']); // fresh player object, never assigned in add mode
		$this->assertArrayHasKey('S_JOINDATE_DAY_OPTIONS', $template->vars);
		$this->assertStringContainsString('<option value="1"', $template->vars['S_JOINDATE_DAY_OPTIONS']);
		$this->assertCount(1, $template->blocks['guild_row']); // guildless (id=0) excluded from the popup
		$this->assertSame(7, $template->blocks['guild_row'][0]['VALUE']);
		$this->assertSame(' selected="selected"', $template->blocks['guild_row'][0]['SELECTED']);
	}
}
