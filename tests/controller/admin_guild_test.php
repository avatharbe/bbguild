<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Characterization test for admin_guild::show_editguildrecruitment() —
 * CC=31 per contrib/architecture.md's cyclomatic-complexity pass,
 * previously at 0% coverage. Simpler than the ucp/bbguild_module and
 * acp/player_module methods covered previously: no internal construction
 * of other domain objects, and `guilds $updateguild` is a regular typed
 * parameter rather than something built deep inside the method.
 *
 * Scope: the base "view page, no action" happy path — GET request with
 * no recruit_action, no add/update POST — with all DB reads returning
 * no rows (zero existing recruitment postings, empty class/role
 * dropdowns). Not covered: the delete/add/update/edit action branches —
 * each touches phpBB's global check_form_key()/trigger_event()
 * machinery and would be a reasonable follow-up.
 */

namespace avathar\bbguild\tests\controller;

use avathar\bbguild\controller\admin_guild;
use avathar\bbguild\model\player\guilds;
use PHPUnit\Framework\TestCase;

// What: a minimal stand-in for phpBB's real template service.
// Why: admin_guild's $template property is untyped, so a plain object
// that just records what it's given (instead of a PHPUnit mock) lets the
// test assert on the exact vars/blocks the method assigned, in plain
// arrays, without having to configure return-value expectations per call.
class fake_admin_guild_template
{
	public $vars = [];
	public $blocks = [];
	public function assign_vars($a) { $this->vars = array_merge($this->vars, $a); }
	public function assign_block_vars($block, $a) { $this->blocks[$block][] = $a; }
}

// What: a no-listener stand-in for phpBB's event dispatcher.
// Why: show_editguildrecruitment() calls $this->dispatcher->trigger_event()
// inside the add/update/delete branches (skipped by this test, but the
// property still needs a real-enough object in case that ever changes)
// and phpBB's own trigger_event() just returns its input array back when
// nothing is subscribed — this mirrors that instead of pulling in the
// real event-dispatcher service and its own dependency chain.
class fake_admin_guild_dispatcher
{
	public function trigger_event($name, $vars) { return $vars; }
}

class admin_guild_test extends TestCase
{
	// What: builds an admin_guild instance without running its real
	// constructor.
	// Why: the real constructor takes ~25 typed phpBB/bbguild service
	// arguments and does actual DI-driven setup (builds a `game` object,
	// queries the installed-games list, resolves a controller route) —
	// none of which show_editguildrecruitment() itself needs. Bypassing it
	// and injecting only the handful of properties this method actually
	// touches keeps the test focused on that method's own behavior.
	private function make_controller(): admin_guild
	{
		$reflection = new \ReflectionClass(admin_guild::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	// What: builds a `guilds` model instance without its real constructor.
	// Why: show_editguildrecruitment() takes `guilds $updateguild` as a
	// plain typed parameter — it doesn't need a DB-backed guild, just one
	// whose getters return known values, so reflection + setters (same
	// pattern as tests/player/guilds_crud_test.php) is simpler than
	// satisfying the constructor's own DB/cache/log dependencies.
	private function make_guild(): guilds
	{
		$reflection = new \ReflectionClass(guilds::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	// What: sets a declared (possibly protected) property via reflection.
	// Why: admin_guild's dependencies are properly declared class
	// properties (unlike ucp\bbguild_module's, which are only ever
	// dynamically created — see tests/ucp/bbguild_module_test.php's own
	// comment on that), so reflection-based injection works uniformly
	// here without needing a separate "dynamic property" code path.
	private function set_prop($obj, string $name, $value): void
	{
		$reflection = new \ReflectionObject($obj);
		$prop = $reflection->getProperty($name);
		$prop->setAccessible(true);
		$prop->setValue($obj, $value);
	}

	public function test_show_editguildrecruitment_view_only_assigns_template_vars(): void
	{
		// What: a DB double where every query "succeeds" but returns no
		// rows.
		// Why: this collapses the recruit-list loop and both the class and
		// role dropdown loops to zero iterations, so the test only has to
		// assert on the always-executed setup/assign_vars code — the loop
		// bodies themselves are a documented, separate follow-up (see the
		// file docblock).
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn('RESULT');
		$db->method('sql_fetchrow')->willReturn(false); // no recruits, empty class/role dropdowns
		$db->method('sql_escape')->willReturnArgument(0);
		$db->method('sql_build_array')->willReturnCallback(
			fn($op, $data) => '(' . implode(', ', array_keys($data)) . ')'
		);

		// What: only the language keys this specific code path actually
		// reads.
		// Why: RECRUIT_FOOTCOUNT is a sprintf format string ("%d ..."),
		// not a literal — exercising the real sprintf() call is how the
		// "0 recruitment postings" assertion below is meaningful, rather
		// than just echoing back a hardcoded string.
		$user = $this->createMock(\phpbb\user::class);
		$user->lang = [
			'RETURN_GUILDLIST' => 'Return to guild list',
			'ACP_EDITGUILD' => 'Edit Guild',
			'ACP_EDITGUILD_EXPLAIN' => 'Explain',
			'RECRUIT_FOOTCOUNT' => '%d recruitment postings',
		];

		// What: request stub that always returns whatever default the
		// production code asked for.
		// Why: that default is '' for recruit_action and 0 for the
		// id/role/class_id/etc. request vars the add/update/edit branches
		// would otherwise read — i.e. this is what drives the method down
		// the plain "view page" path this test targets. is_set_post()
		// returning false for both add_recruit/update_recruit is what
		// skips the add/update block (and, with it, the need to mock
		// phpBB's global check_form_key()).
		$request = $this->createMock(\phpbb\request\request::class);
		$request->method('variable')->willReturnCallback(fn($name, $default) => $default);
		$request->method('is_set_post')->willReturn(false); // no add_recruit / update_recruit POST

		$template = new fake_admin_guild_template();

		// What: phpBB's append_sid() (called internally by acp_url(), used
		// to build $this->link and every U_EDIT_*/U_DELETE template var)
		// reads `global $phpbb_dispatcher` directly, not through the
		// controller's own $this->dispatcher property.
		// Why: without this global set, append_sid() fatals on a null
		// method call — same root cause as the add_form_key()/
		// $phpbb_dispatcher dependency documented in
		// tests/ucp/bbguild_module_test.php and tests/acp/player_module_test.php.
		$GLOBALS['phpbb_dispatcher'] = new fake_admin_guild_dispatcher();

		$c = $this->make_controller();
		$this->set_prop($c, 'db', $db);
		$this->set_prop($c, 'config', ['bbguild_lang' => 'en']);
		$this->set_prop($c, 'user', $user);
		$this->set_prop($c, 'template', $template);
		$this->set_prop($c, 'request', $request);
		$this->set_prop($c, 'dispatcher', new fake_admin_guild_dispatcher());
		$this->set_prop($c, 'root_path', '');
		$this->set_prop($c, 'adm_relative_path', 'adm/');
		$this->set_prop($c, 'php_ext', 'php');
		$this->set_prop($c, 'ext_path', 'ext/avathar/bbguild/');
		$c->bb_recruit_table = 'bb_recruit';
		$c->bb_classes_table = 'bb_classes';
		$c->bb_language_table = 'bb_language';
		$c->bb_gameroles_table = 'bb_gameroles';

		// What: a guild with a known id/name/game/min-armory-level.
		// Why: these four values are what the method echoes back into
		// GUILDID/GUILD_NAME/RECRUIT_LEVEL (min armory is the fallback
		// recruit level when there's no in-progress edit) — asserting on
		// them below confirms the method actually reads from the guild
		// object passed in, not just from its own state.
		$guild = $this->make_guild();
		$guild->setGuildid(5);
		$guild->setName('Test Guild');
		$guild->setGameId('wow');
		$guild->setMinArmory(10);

		$c->show_editguildrecruitment($guild);

		$this->assertSame('acp_editguild_recruitment', $c->tpl_name);
		$this->assertSame('Edit Guild', $c->page_title);
		$this->assertSame('Test Guild', $template->vars['GUILD_NAME']);
		$this->assertSame(5, $template->vars['GUILDID']);
		$this->assertSame('0 recruitment postings', $template->vars['RECRUIT_FOOTCOUNT']);
		$this->assertFalse($template->vars['S_EDIT_RECRUIT']);
		$this->assertSame(10, $template->vars['RECRUIT_LEVEL']); // falls back to the guild's min armory level
		$this->assertSame('checked="checked"', $template->vars['RECRUIT_STATUS_CHECKED']); // defaults open
		$this->assertArrayNotHasKey('recruit_row', $template->blocks); // zero postings
	}
}
