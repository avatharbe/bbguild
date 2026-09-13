<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild\tests\portal;

use PHPUnit\Framework\TestCase;

class portal_renderer_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $database_handler;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $template;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $helper;

	/** @var array Captured assign_block_vars() calls: [blockname, vars][] */
	protected $block_calls;

	protected function get_renderer()
	{
		$portal_columns = $this->createMock(\avathar\bbguild\portal\columns::class);
		$module_helper = $this->createMock(\avathar\bbguild\portal\module_helper::class);
		$this->database_handler = $this->createMock(\avathar\bbguild\portal\modules\database_handler::class);
		$config = new \phpbb\config\config([]);
		$this->template = $this->createMock(\phpbb\template\template::class);
		$user = $this->createMock(\phpbb\user::class);
		$this->helper = $this->createMock(\phpbb\controller\helper::class);

		$this->block_calls = [];
		$this->template->method('assign_block_vars')
			->willReturnCallback(function ($blockname, $vars) {
				$this->block_calls[] = [$blockname, $vars];
			});
		$this->template->method('assign_vars')->willReturn(null);

		$this->helper->method('route')->willReturn('/guild/welcome/5');
		$this->database_handler->method('get_modules')->willReturn([]);

		return new \avathar\bbguild\portal\portal_renderer(
			$portal_columns, $module_helper, $this->database_handler, $config, $this->template, $user, $this->helper
		);
	}

	private function blocks(string $name): array
	{
		return array_values(array_map(
			fn($call) => $call[1],
			array_filter($this->block_calls, fn($call) => $call[0] === $name)
		));
	}

	public function test_empty_slug_resolves_to_default_tab()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([
			['tab_id' => 1, 'tab_name' => 'Overview', 'tab_slug' => 'welcome'],
			['tab_id' => 2, 'tab_name' => 'Raids', 'tab_slug' => 'raids'],
		]);

		$this->database_handler->expects($this->once())
			->method('get_modules')
			->with(5, 1);

		$renderer->render(5, '');

		$tabs = $this->blocks('tabs');
		$this->assertTrue($tabs[0]['TAB_ACTIVE']);
		$this->assertFalse($tabs[1]['TAB_ACTIVE']);
	}

	public function test_unknown_slug_falls_back_to_default_tab()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([
			['tab_id' => 1, 'tab_name' => 'Overview', 'tab_slug' => 'welcome'],
		]);

		$this->database_handler->expects($this->once())
			->method('get_modules')
			->with(5, 1);

		$renderer->render(5, 'nonexistent-slug');
	}

	public function test_known_slug_resolves_to_that_tab()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([
			['tab_id' => 1, 'tab_name' => 'Overview', 'tab_slug' => 'welcome'],
			['tab_id' => 2, 'tab_name' => 'Raids', 'tab_slug' => 'raids'],
		]);

		$this->database_handler->expects($this->once())
			->method('get_modules')
			->with(5, 2);

		$renderer->render(5, 'raids');

		$tabs = $this->blocks('tabs');
		$this->assertFalse($tabs[0]['TAB_ACTIVE']);
		$this->assertTrue($tabs[1]['TAB_ACTIVE']);
	}

	public function test_guild_with_no_tabs_does_not_query_modules()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([]);

		$this->database_handler->expects($this->never())->method('get_modules');

		$renderer->render(5, '');
	}
}
