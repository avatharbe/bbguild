<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Character sync cron task tests — issue #361
 */

namespace avathar\bbguild\tests\cron;

use avathar\bbguild\cron\task\character_sync;
use avathar\bbguild\model\games\character_sync_interface;
use avathar\bbguild\model\games\character_sync_registry;
use PHPUnit\Framework\TestCase;

class character_sync_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\config\config */
	private $config;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\driver\driver_interface */
	private $db;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\avathar\bbguild\model\admin\log */
	private $bbguild_log;

	protected function setUp(): void
	{
		$this->config = $this->getMockBuilder(\phpbb\config\config::class)
			->disableOriginalConstructor()
			->getMock();
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$this->bbguild_log = $this->getMockBuilder(\avathar\bbguild\model\admin\log::class)
			->disableOriginalConstructor()
			->getMock();
	}

	private function make_handler(string $game_id, \Closure $sync): character_sync_interface
	{
		return new class($game_id, $sync) implements character_sync_interface {
			private $game_id;
			private $sync;

			public function __construct(string $game_id, \Closure $sync)
			{
				$this->game_id = $game_id;
				$this->sync = $sync;
			}

			public function get_game_id(): string
			{
				return $this->game_id;
			}

			public function sync_character(array $player_row): bool
			{
				return ($this->sync)($player_row);
			}
		};
	}

	private function make_task(character_sync_registry $registry): character_sync
	{
		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);
		$user = $this->createMock(\phpbb\user::class);
		// character_sync's constructor builds a real player, which builds a
		// real game() internally (established codebase convention — see
		// task brief). game()'s constructor unconditionally reads
		// $user->lang['REGION*'] to seed its regions list, so the mocked
		// user needs that dynamic property populated or PHP's "access
		// array offset on null" warning aborts construction under PHPUnit's
		// error-to-exception conversion. Not part of the behavior under
		// test — just fixture completeness for a dependency this task's
		// constructor is required to construct faithfully.
		$user->lang = [
			'REGIONEU'  => 'Europe',
			'REGIONKR'  => 'Korea',
			'REGIONSEA' => 'South-East Asia',
			'REGIONTW'  => 'Taiwan',
			'REGIONUS'  => 'United States',
			'CLOSED'    => 'Closed',
			'OPEN'      => 'Open',
		];
		$ext_manager = $this->getMockBuilder(\phpbb\extension\manager::class)
			->disableOriginalConstructor()
			->getMock();
		$util = $this->getMockBuilder(\avathar\bbguild\model\admin\util::class)
			->disableOriginalConstructor()
			->getMock();

		$task = new character_sync(
			$this->config,
			$registry,
			$this->db,
			$cache,
			$user,
			$ext_manager,
			$this->bbguild_log,
			$util,
			'bb_players', 'bb_ranks', 'bb_classes', 'bb_races', 'bb_language', 'bb_guild', 'bb_factions', 'bb_games'
		);
		$task->set_name('avathar.bbguild.cron.task.character_sync');

		return $task;
	}

	public function test_is_runnable_false_when_no_game_ids_supported(): void
	{
		$task = $this->make_task(new character_sync_registry([]));

		$this->assertFalse($task->is_runnable());
	}

	public function test_is_runnable_true_when_a_game_id_is_supported(): void
	{
		$task = $this->make_task(new character_sync_registry([
			$this->make_handler('wow', fn ($row) => true),
		]));

		$this->assertTrue($task->is_runnable());
	}

	public function test_should_run_false_within_min_interval(): void
	{
		$this->config->expects($this->once())
			->method('offsetGet')
			->with('bbguild_sync_last_run')
			->willReturn((string) (time() - 60));

		$task = $this->make_task(new character_sync_registry([]));

		$this->assertFalse($task->should_run());
	}

	public function test_should_run_true_after_min_interval(): void
	{
		$this->config->expects($this->once())
			->method('offsetGet')
			->with('bbguild_sync_last_run')
			->willReturn((string) (time() - 3600));

		$task = $this->make_task(new character_sync_registry([]));

		$this->assertTrue($task->should_run());
	}

	public function test_run_delegates_to_handler_and_always_bumps_last_synced_on_success(): void
	{
		$seen = [];
		$registry = new character_sync_registry([
			$this->make_handler('wow', function ($row) use (&$seen) {
				$seen[] = $row['player_id'];
				return true;
			}),
		]);
		$task = $this->make_task($registry);

		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow')");
		$this->db->method('sql_query_limit')->willReturn('fake_result');
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['player_id' => 1, 'game_id' => 'wow', 'player_name' => 'Alice'],
			false
		);
		$this->bbguild_log->expects($this->never())->method('log_insert');
		$this->config->expects($this->once())->method('set')->with('bbguild_sync_last_run', $this->isType('int'));
		$update_sql = '';
		$this->db->expects($this->once())
			->method('sql_query')
			->with($this->callback(function ($sql) use (&$update_sql) {
				$update_sql = $sql;
				return true;
			}));

		$task->run();

		$this->assertSame([1], $seen);
		$this->assertStringContainsString('WHERE player_id = 1', $update_sql);
	}

	public function test_run_logs_failure_and_still_bumps_last_synced_when_handler_returns_false(): void
	{
		$registry = new character_sync_registry([
			$this->make_handler('wow', fn ($row) => false),
		]);
		$task = $this->make_task($registry);

		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow')");
		$this->db->method('sql_query_limit')->willReturn('fake_result');
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['player_id' => 2, 'game_id' => 'wow', 'player_name' => 'Bob'],
			false
		);
		$this->bbguild_log->expects($this->once())
			->method('log_insert')
			->with($this->callback(function ($values) {
				return $values['log_type'] === 'L_ERROR_CHARACTER_SYNC_FAILED'
					&& $values['log_action'] === ['Bob', 'wow'];
			}));
		$this->db->expects($this->once())->method('sql_query');

		$task->run();
	}

	public function test_run_logs_failure_when_handler_throws(): void
	{
		$registry = new character_sync_registry([
			$this->make_handler('wow', function ($row) {
				throw new \RuntimeException('API down');
			}),
		]);
		$task = $this->make_task($registry);

		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow')");
		$this->db->method('sql_query_limit')->willReturn('fake_result');
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['player_id' => 3, 'game_id' => 'wow', 'player_name' => 'Carol'],
			false
		);
		$this->bbguild_log->expects($this->once())->method('log_insert');
		$this->db->expects($this->once())->method('sql_query');

		$task->run();
	}

	public function test_run_is_noop_when_no_game_ids_supported(): void
	{
		$task = $this->make_task(new character_sync_registry([]));

		$this->db->expects($this->never())->method('sql_query_limit');
		$this->config->expects($this->never())->method('set');

		$task->run();
	}
}
