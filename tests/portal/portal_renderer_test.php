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

	/**
	 * Final whole-branch review fix #1(c): a tab whose slug can't satisfy
	 * the avathar_bbguild_00 route's `page` requirement (e.g. a
	 * digit-leading slug left in the DB from before sanitize_slug()'s
	 * fix #1(a)) makes helper->route() throw under Symfony's default
	 * strictRequirements. That one tab must be skipped, not take down the
	 * whole tab bar.
	 */
	public function test_tab_whose_route_generation_throws_is_skipped_not_fatal()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([
			['tab_id' => 1, 'tab_name' => 'Overview', 'tab_slug' => 'welcome'],
			['tab_id' => 2, 'tab_name' => '2026', 'tab_slug' => '2026'],
			['tab_id' => 3, 'tab_name' => 'Raids', 'tab_slug' => 'raids'],
		]);

		$this->helper->method('route')
			->willReturnCallback(function ($route, $params) {
				if ($params['page'] === '2026')
				{
					throw new \Symfony\Component\Routing\Exception\InvalidParameterException('page must not start with a digit');
				}
				return '/guild/' . $params['page'] . '/' . $params['guild_id'];
			});

		$renderer->render(5, '');

		$tabs = $this->blocks('tabs');

		// Only the two valid tabs were assigned to the template; the
		// digit-leading one was skipped, and rendering did not throw.
		$this->assertCount(2, $tabs);
		$this->assertSame('welcome', $tabs[0]['TAB_SLUG']);
		$this->assertSame('raids', $tabs[1]['TAB_SLUG']);
	}
}
