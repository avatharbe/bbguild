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

	private function make_task(character_sync_registry $registry, $dispatcher = null): character_sync
	{
		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);
		$user = $this->createMock(\phpbb\user::class);
		// character_sync's constructor no longer builds `player` eagerly
		// (it's lazy — see get_player()), but run() still builds a real
		// player on first use, which builds a real game() internally
		// (established codebase convention — see task brief). game()'s
		// constructor unconditionally reads $user->lang['REGION*'] to seed
		// its regions list, so the mocked user needs that dynamic property
		// populated or PHP's "access array offset on null" warning aborts
		// construction under PHPUnit's error-to-exception conversion. Kept
		// here (harmless for tests that never reach run()) so every test
		// that DOES call run() stays covered without a second fixture.
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
		$dispatcher = $dispatcher ?? $this->createMock(\phpbb\event\dispatcher_interface::class);
		$dispatcher->method('trigger_event')->willReturnArgument(1);

		$task = new character_sync(
			$this->config,
			$registry,
			$this->db,
			$cache,
			$user,
			$ext_manager,
			$this->bbguild_log,
			$util,
			$dispatcher,
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

	public function test_is_runnable_with_empty_registry_never_touches_the_database(): void
	{
		// Locks in the lazy-construction fix: `player` (and the uncached DB
		// query its constructor runs) must not be built just to answer
		// is_runnable()/should_run() — phpBB's cron dispatch calls these on
		// every registered cron.task roughly once a minute, regardless of
		// whether the task is actually ready to run.
		$this->db->expects($this->never())->method('sql_query');
		$this->db->expects($this->never())->method('sql_query_limit');

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

	/**
	 * Stubs $this->db for a run() that lazily builds `player` on first use.
	 * That build eagerly queries the games list inside game()'s
	 * constructor (an existing, unrelated codebase convention — not part
	 * of the behavior under test), so sql_query()/sql_fetchrow() now see
	 * more than just the get_stalest_players()/update_last_synced() calls
	 * this test cares about. Rather than pin an exact call count/order
	 * (brittle, and not the thing under test), this dispatches on
	 * $result/$sql content and returns a closure the caller uses after
	 * run() to inspect the UPDATE call count and its SQL.
	 *
	 * @return \Closure(): array{0: int, 1: string} [$update_call_count, $last_update_sql]
	 */
	private function stub_db_for_run(array $stale_player_row): \Closure
	{
		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow')");
		$this->db->method('sql_query_limit')->willReturn('fake_result');

		$queue = [$stale_player_row];
		$this->db->method('sql_fetchrow')->willReturnCallback(function ($result) use (&$queue) {
			// Only the get_stalest_players() result set (sql_query_limit's
			// stubbed return value) should yield rows; any other result
			// (e.g. game()'s games-list SELECT) has none.
			return $result === 'fake_result' ? (array_shift($queue) ?: false) : false;
		});

		$update_sql = '';
		$update_calls = 0;
		$this->db->method('sql_query')->willReturnCallback(function ($sql = null) use (&$update_sql, &$update_calls) {
			// player's constructor also builds a `guilds` helper (existing
			// codebase convention, unrelated to the sync logic under test)
			// whose guildlist() call runs through sql_build_query() first;
			// that's unstubbed here and returns null, so $sql can be null.
			if (is_string($sql) && stripos($sql, 'UPDATE') === 0)
			{
				$update_calls++;
				$update_sql = $sql;
			}
			return false;
		});

		return function () use (&$update_calls, &$update_sql): array {
			return [$update_calls, $update_sql];
		};
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

		$get_update = $this->stub_db_for_run(['player_id' => 1, 'game_id' => 'wow', 'player_name' => 'Alice']);
		$this->bbguild_log->expects($this->never())->method('log_insert');
		$this->config->expects($this->once())->method('set')->with('bbguild_sync_last_run', $this->isType('int'));

		$task->run();

		$this->assertSame([1], $seen);
		[$update_calls, $update_sql] = $get_update();
		$this->assertSame(1, $update_calls);
		$this->assertStringContainsString('WHERE player_id = 1', $update_sql);
	}

	public function test_run_logs_failure_and_still_bumps_last_synced_when_handler_returns_false(): void
	{
		$registry = new character_sync_registry([
			$this->make_handler('wow', fn ($row) => false),
		]);
		$task = $this->make_task($registry);

		$get_update = $this->stub_db_for_run(['player_id' => 2, 'game_id' => 'wow', 'player_name' => 'Bob']);
		$this->bbguild_log->expects($this->once())
			->method('log_insert')
			->with($this->callback(function ($values) {
				return $values['log_type'] === 'L_ERROR_CHARACTER_SYNC_FAILED'
					&& $values['log_action'] === ['Bob', 'wow'];
			}));

		$task->run();

		[$update_calls, $update_sql] = $get_update();
		$this->assertSame(1, $update_calls);
		$this->assertStringContainsString('WHERE player_id = 2', $update_sql);
	}

	public function test_run_logs_failure_when_handler_throws(): void
	{
		$registry = new character_sync_registry([
			$this->make_handler('wow', function ($row) {
				throw new \RuntimeException('API down');
			}),
		]);
		$task = $this->make_task($registry);

		$get_update = $this->stub_db_for_run(['player_id' => 3, 'game_id' => 'wow', 'player_name' => 'Carol']);
		$this->bbguild_log->expects($this->once())->method('log_insert');

		$task->run();

		[$update_calls, $update_sql] = $get_update();
		$this->assertSame(1, $update_calls);
		$this->assertStringContainsString('WHERE player_id = 3', $update_sql);
	}

	public function test_run_is_noop_when_no_game_ids_supported(): void
	{
		$task = $this->make_task(new character_sync_registry([]));

		$this->db->expects($this->never())->method('sql_query_limit');
		$this->config->expects($this->never())->method('set');

		$task->run();
	}

	public function test_run_dispatches_character_sync_completed_event_on_success(): void
	{
		$registry = new character_sync_registry([
			$this->make_handler('wow', fn ($row) => true),
		]);

		$dispatcher = $this->createMock(\phpbb\event\dispatcher_interface::class);
		$dispatcher->expects($this->once())
			->method('trigger_event')
			->with(
				'avathar.bbguild.character_sync_completed',
				$this->callback(function ($vars) {
					return $vars['player_id'] === 1 && $vars['game_id'] === 'wow' && $vars['success'] === true;
				})
			)
			->willReturnArgument(1);

		$task = $this->make_task($registry, $dispatcher);

		$get_update = $this->stub_db_for_run(['player_id' => 1, 'game_id' => 'wow', 'player_name' => 'Alice']);
		$task->run();

		[$update_calls] = $get_update();
		$this->assertSame(1, $update_calls);
	}
}
