<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild\tests\portal\modules;

use avathar\bbguild\portal\modules\roster;
use PHPUnit\Framework\TestCase;

class roster_test extends TestCase
{
	private function make_roster($dispatcher): roster
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$template = $this->createMock(\phpbb\template\template::class);
		$user = $this->createMock(\phpbb\user::class);
		$user->lang = ['MEMBERS' => 'Members'];
		$config = new \phpbb\config\config(['bbguild_user_llimit' => 0]);
		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);
		$ext_manager = $this->getMockBuilder(\phpbb\extension\manager::class)
			->disableOriginalConstructor()
			->getMock();
		$bbguild_log = $this->getMockBuilder(\avathar\bbguild\model\admin\log::class)
			->disableOriginalConstructor()
			->getMock();
		$bbguild_util = $this->getMockBuilder(\avathar\bbguild\model\admin\util::class)
			->disableOriginalConstructor()
			->getMock();
		$pagination = $this->getMockBuilder(\phpbb\pagination::class)
			->disableOriginalConstructor()
			->getMock();
		$request = $this->createMock(\phpbb\request\request::class);
		$path_helper = $this->getMockBuilder(\phpbb\path_helper::class)
			->disableOriginalConstructor()
			->getMock();
		$helper = $this->createMock(\phpbb\controller\helper::class);
		$helper->method('route')->willReturn('/guild/1/player/1');
		$game_registry = $this->getMockBuilder(\avathar\bbguild\model\games\game_registry::class)
			->disableOriginalConstructor()
			->getMock();
		$asset_resolver = $this->getMockBuilder(\avathar\bbguild\model\admin\asset_url_resolver::class)
			->disableOriginalConstructor()
			->getMock();

		return new roster(
			$db, $template, $user, $config, $cache, $ext_manager, $bbguild_log, $bbguild_util,
			$pagination, $request, $path_helper, $helper, $game_registry, $asset_resolver, $dispatcher,
			'bb_players', 'bb_ranks', 'bb_classes', 'bb_races', 'bb_language', 'bb_guild', 'bb_factions', 'bb_games'
		);
	}

	public function test_display_listing_fires_roster_display_per_character(): void
	{
		$dispatcher = $this->createMock(\phpbb\event\dispatcher_interface::class);
		$dispatcher->expects($this->exactly(2))
			->method('trigger_event')
			->with(
				'avathar.bbguild.roster_display',
				$this->callback(function ($vars) {
					return in_array($vars['player_id'], [10, 11], true)
						&& $vars['game_id'] === 'wow'
						&& $vars['guild_id'] === 42
						&& is_array($vars['tpl_ary'])
						&& $vars['tpl_ary']['PLAYER_ID'] === $vars['player_id'];
				})
			)
			->willReturnCallback(function ($event_name, $vars) {
				// Mutate tpl_ary to prove the write-back is actually consumed
				// by the subsequent assign_block_vars() call.
				$vars['tpl_ary']['LISTENER_INJECTED'] = 'yes-' . $vars['player_id'];
				return $vars;
			});

		$roster = $this->make_roster($dispatcher);
		$roster_reflection = new \ReflectionProperty($roster, 'guild_id');
		$roster_reflection->setAccessible(true);
		$roster_reflection->setValue($roster, 42);

		$template = $this->getprivateproperty($roster, 'template');
		$captured = [];
		$template->expects($this->exactly(2))
			->method('assign_block_vars')
			->with('portal_roster_row', $this->callback(function ($tpl_ary) use (&$captured) {
				$captured[] = $tpl_ary;
				return true;
			}));

		$characters = [
			0 => [
				['player_id' => 10, 'game_id' => 'wow', 'colorcode' => '#fff', 'class_name' => 'Warrior', 'player_name' => 'Alice', 'race_name' => 'Human', 'player_rank' => 'Member', 'player_level' => 60, 'player_armory_url' => '', 'username' => 'alice', 'player_achiev' => 0, 'class_image' => 'x.png', 'race_image' => 'y.png'],
				['player_id' => 11, 'game_id' => 'wow', 'colorcode' => '#fff', 'class_name' => 'Mage', 'player_name' => 'Bob', 'race_name' => 'Gnome', 'player_rank' => 'Member', 'player_level' => 60, 'player_armory_url' => '', 'username' => 'bob', 'player_achiev' => 0, 'class_image' => 'x.png', 'race_image' => 'y.png'],
			],
			2 => 2,
		];

		$reflection = new \ReflectionMethod($roster, 'display_listing');
		$reflection->setAccessible(true);
		$reflection->invoke($roster, $characters, 'images/', '/guild/1/roster', 0, [], false);

		// The listener's mutation to tpl_ary must have reached assign_block_vars().
		$this->assertSame('yes-10', $captured[0]['LISTENER_INJECTED']);
		$this->assertSame('yes-11', $captured[1]['LISTENER_INJECTED']);
	}

	public function test_display_grid_fires_roster_display_per_character(): void
	{
		$dispatcher = $this->createMock(\phpbb\event\dispatcher_interface::class);
		$dispatcher->expects($this->once())
			->method('trigger_event')
			->with(
				'avathar.bbguild.roster_display',
				$this->callback(function ($vars) {
					return $vars['player_id'] === 20
						&& $vars['game_id'] === 'wow'
						&& $vars['guild_id'] === 42
						&& is_array($vars['tpl_ary'])
						&& $vars['tpl_ary']['PLAYER_ID'] === 20;
				})
			)
			->willReturnCallback(function ($event_name, $vars) {
				$vars['tpl_ary']['LISTENER_INJECTED'] = 'grid-yes';
				return $vars;
			});

		$roster = $this->make_roster($dispatcher);
		$roster_reflection = new \ReflectionProperty($roster, 'guild_id');
		$roster_reflection->setAccessible(true);
		$roster_reflection->setValue($roster, 42);

		$template = $this->getprivateproperty($roster, 'template');
		$captured = [];
		$template->method('assign_block_vars')
			->willReturnCallback(function ($block, $vars) use (&$captured) {
				if ($block === 'class.players_row')
				{
					$captured[] = $vars;
				}
			});

		$players = $this->getMockBuilder(\avathar\bbguild\model\player\player::class)
			->disableOriginalConstructor()
			->onlyMethods(['get_classes'])
			->getMock();
		$players->game_id = 'wow';
		$players->method('get_classes')->willReturn([
			['class_id' => 1, 'class_name' => 'Warrior', 'imagename' => 'warrior', 'colorcode' => '#fff'],
		]);

		$characters = [
			0 => [
				['player_id' => 20, 'game_id' => 'wow', 'colorcode' => '#fff', 'class_name' => 'Warrior', 'player_name' => 'Carol', 'race_name' => 'Human', 'player_rank' => 'Member', 'player_level' => 60, 'player_armory_url' => '', 'username' => 'carol', 'player_achiev' => 0, 'class_image' => 'x.png', 'race_image' => 'y.png', 'player_class_id' => 1],
			],
			2 => 1,
		];

		$reflection = new \ReflectionMethod($roster, 'display_grid');
		$reflection->setAccessible(true);
		$reflection->invoke($roster, $players, $characters, 'images/', '/guild/1/roster', 0, '', false, 0, []);

		$this->assertCount(1, $captured);
		$this->assertSame('grid-yes', $captured[0]['LISTENER_INJECTED']);
	}

	/**
	 * Reflect a protected property off an object under test.
	 *
	 * @return mixed
	 */
	private function getprivateproperty(object $object, string $property)
	{
		$reflection = new \ReflectionProperty($object, $property);
		$reflection->setAccessible(true);
		return $reflection->getValue($object);
	}
}
