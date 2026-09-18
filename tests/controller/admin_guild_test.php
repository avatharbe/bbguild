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

class fake_admin_guild_template
{
	public $vars = [];
	public $blocks = [];
	public function assign_vars($a) { $this->vars = array_merge($this->vars, $a); }
	public function assign_block_vars($block, $a) { $this->blocks[$block][] = $a; }
}

class fake_admin_guild_dispatcher
{
	public function trigger_event($name, $vars) { return $vars; }
}

class admin_guild_test extends TestCase
{
	private function make_controller(): admin_guild
	{
		$reflection = new \ReflectionClass(admin_guild::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	private function make_guild(): guilds
	{
		$reflection = new \ReflectionClass(guilds::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	private function set_prop($obj, string $name, $value): void
	{
		$reflection = new \ReflectionObject($obj);
		$prop = $reflection->getProperty($name);
		$prop->setAccessible(true);
		$prop->setValue($obj, $value);
	}

	public function test_show_editguildrecruitment_view_only_assigns_template_vars(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn('RESULT');
		$db->method('sql_fetchrow')->willReturn(false); // no recruits, empty class/role dropdowns
		$db->method('sql_escape')->willReturnArgument(0);
		$db->method('sql_build_array')->willReturnCallback(
			fn($op, $data) => '(' . implode(', ', array_keys($data)) . ')'
		);

		$user = $this->createMock(\phpbb\user::class);
		$user->lang = [
			'RETURN_GUILDLIST' => 'Return to guild list',
			'ACP_EDITGUILD' => 'Edit Guild',
			'ACP_EDITGUILD_EXPLAIN' => 'Explain',
			'RECRUIT_FOOTCOUNT' => '%d recruitment postings',
		];

		$request = $this->createMock(\phpbb\request\request::class);
		$request->method('variable')->willReturnCallback(fn($name, $default) => $default);
		$request->method('is_set_post')->willReturn(false); // no add_recruit / update_recruit POST

		$template = new fake_admin_guild_template();

		$GLOBALS['phpbb_dispatcher'] = new fake_admin_guild_dispatcher(); // append_sid() via acp_url()

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
