<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild\tests\portal\modules;

use PHPUnit\Framework\TestCase;

class statistics_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $db;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $template;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var array Captured assign_block_vars() calls: [blockname, vars][] */
	protected $block_calls;

	/** @var array Captured assign_var() calls: name => value */
	protected $var_calls;

	protected function get_module()
	{
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$this->template = $this->createMock(\phpbb\template\template::class);
		$this->config = new \phpbb\config\config(array('bbguild_lang' => 'en'));

		$this->block_calls = array();
		$this->var_calls = array();

		$this->db->method('sql_query')->willReturn(true);
		$this->db->method('sql_query_limit')->willReturn(true);
		$this->db->method('sql_freeresult')->willReturn(null);

		$this->template->method('assign_block_vars')
			->willReturnCallback(function ($blockname, $vars) {
				$this->block_calls[] = array($blockname, $vars);
			});
		$this->template->method('assign_var')
			->willReturnCallback(function ($name, $value) {
				$this->var_calls[$name] = $value;
			});

		$module = new \avathar\bbguild\portal\modules\statistics(
			$this->db,
			$this->template,
			$this->config,
			'phpbb_bb_guild',
			'phpbb_bb_players',
			'phpbb_bb_races',
			'phpbb_bb_ranks',
			'phpbb_bb_language'
		);
		$module->set_guild_context(5);

		return $module;
	}

	private function blocks(string $name): array
	{
		return array_values(array_map(
			fn($call) => $call[1],
			array_filter($this->block_calls, fn($call) => $call[0] === $name)
		));
	}

	public function test_returns_null_when_guild_not_found()
	{
		$module = $this->get_module();

		$this->db->method('sql_fetchrow')->willReturn(false);

		$this->db->expects($this->once())->method('sql_query');

		$result = $module->get_template_center(1);

		$this->assertNull($result);
	}

	public function test_returns_template_filename_when_guild_found()
	{
		$module = $this->get_module();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('game_id' => 'wow'),
			false, // class rows
			false, // race rows
			false, // level rows
			false, // rank rows
			false, // recent join rows
			false  // recent leave rows
		);

		$result = $module->get_template_center(1);

		$this->assertSame('statistics_center.html', $result);
	}

	public function test_class_distribution_assigned_to_template()
	{
		$module = $this->get_module();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('game_id' => 'wow'),
			array('class_id' => 1, 'class_name' => 'Warrior', 'player_count' => 3),
			array('class_id' => 2, 'class_name' => 'Mage', 'player_count' => 1),
			false, // end class rows
			false, // race rows
			false, // level rows
			false, // rank rows
			false, // recent join rows
			false  // recent leave rows
		);

		$module->get_template_center(1);

		$this->assertSame(
			array(
				array('CLASS_ID' => 1, 'CLASS_NAME' => 'Warrior', 'COUNT' => 3),
				array('CLASS_ID' => 2, 'CLASS_NAME' => 'Mage', 'COUNT' => 1),
			),
			$this->blocks('stat_class')
		);
	}

	public function test_race_distribution_assigned_to_template()
	{
		$module = $this->get_module();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('game_id' => 'wow'),
			false, // class rows
			array('race_id' => 4, 'race_name' => 'Orc', 'player_count' => 2),
			false, // end race rows
			false, // level rows
			false, // rank rows
			false, // recent join rows
			false  // recent leave rows
		);

		$module->get_template_center(1);

		$this->assertSame(
			array(
				array('RACE_ID' => 4, 'RACE_NAME' => 'Orc', 'COUNT' => 2),
			),
			$this->blocks('stat_race')
		);
	}

	public function test_level_distribution_assigned_to_template()
	{
		$module = $this->get_module();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('game_id' => 'wow'),
			false, // class rows
			false, // race rows
			array('level' => 80, 'player_count' => 5),
			array('level' => 79, 'player_count' => 2),
			false, // end level rows
			false, // rank rows
			false, // recent join rows
			false  // recent leave rows
		);

		$module->get_template_center(1);

		$this->assertSame(
			array(
				array('LEVEL' => 80, 'COUNT' => 5),
				array('LEVEL' => 79, 'COUNT' => 2),
			),
			$this->blocks('stat_level')
		);
	}

	public function test_rank_distribution_and_total_members_assigned()
	{
		$module = $this->get_module();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('game_id' => 'wow'),
			false, // class rows
			false, // race rows
			false, // level rows
			array('rank_id' => 0, 'rank_name' => 'Guild Master', 'player_count' => 1),
			array('rank_id' => 1, 'rank_name' => 'Officer', 'player_count' => 2),
			false, // end rank rows
			false, // recent join rows
			false  // recent leave rows
		);

		$module->get_template_center(1);

		$this->assertSame(
			array(
				array('RANK_ID' => 0, 'RANK_NAME' => 'Guild Master', 'COUNT' => 1),
				array('RANK_ID' => 1, 'RANK_NAME' => 'Officer', 'COUNT' => 2),
			),
			$this->blocks('stat_rank')
		);
		$this->assertSame(3, $this->var_calls['TOTAL_MEMBERS']);
	}

	public function test_recent_joins_assigned_to_template()
	{
		$module = $this->get_module();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('game_id' => 'wow'),
			false, // class rows
			false, // race rows
			false, // level rows
			false, // rank rows
			array('player_name' => 'Sajaki', 'player_joindate' => 1700000000),
			false, // end recent join rows
			false  // recent leave rows
		);

		$module->get_template_center(1);

		$joins = $this->blocks('stat_recent_join');
		$this->assertCount(1, $joins);
		$this->assertSame('Sajaki', $joins[0]['NAME']);
	}

	public function test_recent_departures_assigned_to_template()
	{
		$module = $this->get_module();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('game_id' => 'wow'),
			false, // class rows
			false, // race rows
			false, // level rows
			false, // rank rows
			false, // recent join rows
			array('player_name' => 'Exguildy', 'last_update' => 1700000000)
		);

		$module->get_template_center(1);

		$leaves = $this->blocks('stat_recent_leave');
		$this->assertCount(1, $leaves);
		$this->assertSame('Exguildy', $leaves[0]['NAME']);
	}
}
