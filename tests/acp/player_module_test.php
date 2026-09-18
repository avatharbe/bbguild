<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Characterization test for acp\player_module::BuildTemplateAddEditplayers()
 * — CC=60, the second-highest-complexity method in the codebase per
 * contrib/architecture.md's cyclomatic-complexity pass, previously at 0%
 * coverage. Same shape of problem as ucp\bbguild_module::fill_addplayer()
 * (tests/ucp/bbguild_module_test.php): private, template-side-effect-only,
 * constructs several real domain objects (player/guilds/ranks/roles/
 * specialization/game) with typed phpBB dependencies internally, and
 * touches phpBB's global append_sid()/its event dispatcher.
 *
 * Scope: the "add new character" happy path (player_id == 0, from
 * $request->variable() returning 0) with all DB reads returning no rows,
 * collapsing every dropdown-population loop to zero iterations. Not
 * covered: the edit-mode (player_id > 0) branch and every dropdown loop
 * body — same documented follow-up scope as the UCP module test.
 */

namespace avathar\bbguild\tests\acp;

use avathar\bbguild\acp\player_module;
use PHPUnit\Framework\TestCase;

class fake_player_module_template
{
	public $vars = [];
	public $blocks = [];
	public function assign_vars($a) { $this->vars = array_merge($this->vars, $a); }
	public function assign_var($name, $value) { $this->vars[$name] = $value; }
	public function assign_block_vars($block, $a) { $this->blocks[$block][] = $a; }
}

class fake_player_module_container
{
	private $params;
	public function __construct(array $params) { $this->params = $params; }
	public function get($id) { throw new \Exception('not stubbed: ' . $id); } // forces registry-lookup fallback paths
	public function getParameter($id) { return $this->params[$id] ?? ('bb_' . substr($id, strrpos($id, '.') + 1)); }
}

class fake_player_module_dispatcher
{
	public function trigger_event($name, $vars) { return $vars; } // no listeners registered
}

class player_module_test extends TestCase
{
	private function make_module(): player_module
	{
		$reflection = new \ReflectionClass(player_module::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	private function set_prop($obj, string $name, $value): void
	{
		$reflection = new \ReflectionObject($obj);
		$prop = $reflection->getProperty($name);
		$prop->setAccessible(true);
		$prop->setValue($obj, $value);
	}

	public function test_build_template_addeditplayers_add_mode_assigns_template_vars(): void
	{
		if (!defined('USERS_TABLE')) { define('USERS_TABLE', 'phpbb_users'); }

		// $editplayer's guild (id 5) needs a resolvable region — otherwise
		// getRegionlist()['' /* unresolved region */] is a genuine undefined-
		// array-key hit in production code (getRegionlist() only has
		// eu/kr/sea/tw/us keys). Track the last query's SQL text so
		// sql_fetchrow can return a real row only for get_guild()'s specific
		// "WHERE id = 5" lookup and zero rows for every other query (every
		// dropdown-population loop).
		$last_sql = '';
		$guild_row_returned = false;
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturnCallback(function ($sql) use (&$last_sql) {
			$last_sql = $sql;
			return 'RESULT';
		});
		$db->method('sql_fetchrow')->willReturnCallback(function ($result) use (&$last_sql, &$guild_row_returned) {
			if (!$guild_row_returned && str_contains($last_sql, 'WHERE id = 5'))
			{
				$guild_row_returned = true;
				return [
					'game_id' => 'wow', 'game_edition' => 'retail', 'id' => 5, 'name' => 'Test Guild',
					'realm' => 'Realm', 'region' => 'eu', 'roster' => 1, 'emblemurl' => 'x.png',
					'min_armory' => 0, 'rec_status' => 1, 'armory_enabled' => 0, 'armoryresult' => '',
					'guilddefault' => 0, 'recruitforum' => 0, 'faction' => 0, 'faction_name' => 'Alliance',
				];
			}
			return false; // every dropdown loop sees zero rows
		});
		$db->method('sql_fetchfield')->willReturn(0);
		$db->method('sql_escape')->willReturnArgument(0);
		$db->method('sql_build_query')->willReturn('BUILT_QUERY');
		$db->method('sql_build_array')->willReturnCallback(
			fn($op, $data) => '(' . implode(', ', array_keys($data)) . ')'
		);

		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);
		$config = new \phpbb\config\config(['bbguild_lang' => 'en']);

		$user = $this->createMock(\phpbb\user::class);
		$user->lang = [
			'CLOSED' => 'Closed', 'OPEN' => 'Open',
			'REGIONEU' => 'Europe', 'REGIONKR' => 'Korea', 'REGIONSEA' => 'SEA',
			'REGIONTW' => 'Taiwan', 'REGIONUS' => 'US',
			'ACP_MM_ADDPLAYER' => 'Add player', 'ACP_MM_ADDPLAYER_EXPLAIN' => 'Explain',
			'ALERT_AJAX' => 'Ajax alert', 'ALERT_OLDBROWSER' => 'Old browser',
			'FV_REQUIRED_NAME' => 'Name required',
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

		$request = $this->createMock(\phpbb\request\request::class);
		$request->method('variable')->willReturnCallback(
			fn($name, $default) => $name === \avathar\bbguild\model\admin\constants::URI_GUILD ? 5 : 0
		); // player_id == 0 (add mode) with a resolvable target guild (id 5)

		$template = new fake_player_module_template();

		$container = new fake_player_module_container([
			'avathar.bbguild.tables.bb_specializations' => 'bb_specializations',
		]);
		$GLOBALS['phpbb_container'] = $container; // game_has_api() reads this via `global`
		$GLOBALS['config'] = $config;             // BuildTemplateAddEditplayers reads this via `global`
		$GLOBALS['phpbb_admin_path'] = '/adm/';
		$GLOBALS['phpEx'] = 'php';
		$GLOBALS['phpbb_dispatcher'] = new fake_player_module_dispatcher(); // append_sid()

		$m = $this->make_module();
		$this->set_prop($m, 'db', $db);
		$this->set_prop($m, 'config', $config);
		$this->set_prop($m, 'user', $user);
		$this->set_prop($m, 'template', $template);
		$this->set_prop($m, 'request', $request);
		$this->set_prop($m, 'phpbb_container', $container);
		$this->set_prop($m, 'games', ['' => 'Custom Game', 'wow' => 'World of Warcraft']);

		$this->set_prop($m, 'bbguild_cache', $cache);
		$this->set_prop($m, 'bbguild_log', $log);
		$this->set_prop($m, 'bbguild_util', $util);
		$this->set_prop($m, 'bbguild_ext_manager', $ext_manager);
		$this->set_prop($m, 'bbguild_game_registry', null);
		$this->set_prop($m, 'asset_resolver', $asset_resolver);
		$this->set_prop($m, 'bb_players_table', 'bb_players');
		$this->set_prop($m, 'bb_ranks_table', 'bb_ranks');
		$this->set_prop($m, 'bb_classes_table', 'bb_classes');
		$this->set_prop($m, 'bb_races_table', 'bb_races');
		$this->set_prop($m, 'bb_language_table', 'bb_language');
		$this->set_prop($m, 'bb_guild_table', 'bb_guild');
		$this->set_prop($m, 'bb_factions_table', 'bb_factions');
		$this->set_prop($m, 'bb_games_table', 'bb_games');
		$this->set_prop($m, 'bb_gameroles_table', 'bb_gameroles');

		$reflection = new \ReflectionObject($m);
		$method = $reflection->getMethod('BuildTemplateAddEditplayers');
		$method->setAccessible(true);
		$method->invoke($m, 'addplayer');

		$this->assertTrue($template->vars['S_ADD']);
		$this->assertNull($template->vars['PLAYER_ID']); // fresh player object, never assigned in add mode
		$this->assertSame('ACP_MM_ADDPLAYER', $m->page_title);
		$this->assertArrayHasKey('S_JOINDATE_DAY_OPTIONS', $template->vars);
		$this->assertStringContainsString('<option value="1"', $template->vars['S_JOINDATE_DAY_OPTIONS']);
		$this->assertArrayHasKey('UA_FINDRANK', $template->vars);
	}
}
