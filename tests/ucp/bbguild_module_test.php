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

class fake_module_template
{
	public $vars = [];
	public $blocks = [];
	public function assign_vars($a) { $this->vars = array_merge($this->vars, $a); }
	public function assign_var($name, $value) { $this->vars[$name] = $value; }
	public function assign_block_vars($block, $a) { $this->blocks[$block][] = $a; }
}

class fake_module_auth
{
	public $granted = [];
	public function acl_get($opt) { return $this->granted[$opt] ?? true; }
}

class fake_module_container
{
	private $params;
	public function __construct(array $params) { $this->params = $params; }
	public function get($id) { throw new \Exception('not stubbed: ' . $id); } // forces registry-lookup fallback paths
	public function getParameter($id) { return $this->params[$id] ?? ('bb_' . substr($id, strrpos($id, '.') + 1)); }
}

class fake_module_dispatcher
{
	public function trigger_event($name, $vars) { return $vars; } // no listeners registered
}

class bbguild_module_test extends TestCase
{
	private function make_module(): bbguild_module
	{
		$reflection = new \ReflectionClass(bbguild_module::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	private function set_prop($obj, string $name, $value): void
	{
		$reflection = new \ReflectionObject($obj);
		$prop = $reflection->getProperty($name);
		$prop->setAccessible(true);
		$prop->setValue($obj, $value);
	}

	public function test_fill_addplayer_add_mode_assigns_template_vars_for_new_character(): void
	{
		if (!defined('ANONYMOUS')) { define('ANONYMOUS', 1); }

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

		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);

		$config = new \phpbb\config\config(['bbguild_lang' => 'en', 'bbguild_maxchars' => 5]);

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

		$ext_manager = $this->getMockBuilder(\phpbb\extension\manager::class)
			->disableOriginalConstructor()->getMock();
		$ext_manager->method('get_extension_path')->willReturn('ext/avathar/bbguild/');

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

		// Not declared class properties — only ever dynamically created
		// inside main(), so plain property assignment (not reflection)
		// is the correct way to inject them here.
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

		$guildlist = [
			['id' => 0, 'name' => 'Guildless', 'guilddefault' => 0, 'playercount' => 0],
			['id' => 7, 'name' => 'Test Guild', 'guilddefault' => 1, 'playercount' => 3],
		];

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
