<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Character Sync Cron Task
 * Picks a small batch of the stalest characters across all games with a
 * registered character_sync_interface handler, and delegates each to its
 * owning game plugin. Runs under phpBB's default web-embedded cron or
 * system cron with no special-casing (both converge on cron.manager).
 *
 */

namespace avathar\bbguild\cron\task;

use avathar\bbguild\model\admin\log;
use avathar\bbguild\model\admin\util;
use avathar\bbguild\model\games\character_sync_registry;
use avathar\bbguild\model\player\player;

class character_sync extends \phpbb\cron\task\base
{
	/** Characters synced per cron run */
	const BATCH_SIZE = 10;

	/** Minimum seconds between runs */
	const MIN_INTERVAL = 300;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var character_sync_registry */
	protected $registry;

	/** @var log */
	protected $bbguild_log;

	/**
	 * @var player|null Lazily built by get_player() — see its docblock.
	 */
	protected $player;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\extension\manager */
	protected $ext_manager;

	/** @var util */
	protected $util;

	/** @var string */
	protected $bb_players_table;

	/** @var string */
	protected $bb_ranks_table;

	/** @var string */
	protected $bb_classes_table;

	/** @var string */
	protected $bb_races_table;

	/** @var string */
	protected $bb_language_table;

	/** @var string */
	protected $bb_guild_table;

	/** @var string */
	protected $bb_factions_table;

	/** @var string */
	protected $bb_games_table;

	/**
	 * Constructor. The trailing block of raw dependencies + table names
	 * mirrors every other manual `new player(...)` call site in this
	 * codebase (e.g. acp/player_module.php) — bbGuild has no DI service
	 * for `player` itself, so each consumer threads its own construction.
	 *
	 * `player` itself is NOT built here — only stored as raw arguments.
	 * phpBB's cron dispatch eagerly instantiates every registered
	 * `cron.task` service roughly once a minute (via service_collection)
	 * just to check is_runnable()/should_run(), regardless of whether the
	 * task is ready to run. player's constructor unconditionally runs DB
	 * queries and reads $user->lang[...], so building it here would pay
	 * that cost on every board, every minute, indefinitely — even before
	 * #362 ships a game plugin that makes this task runnable at all. See
	 * get_player() for the lazy singleton.
	 */
	public function __construct(
		\phpbb\config\config $config,
		character_sync_registry $registry,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\user $user,
		\phpbb\extension\manager $ext_manager,
		log $bbguild_log,
		util $util,
		string $bb_players_table,
		string $bb_ranks_table,
		string $bb_classes_table,
		string $bb_races_table,
		string $bb_language_table,
		string $bb_guild_table,
		string $bb_factions_table,
		string $bb_games_table
	)
	{
		$this->config = $config;
		$this->registry = $registry;
		$this->bbguild_log = $bbguild_log;
		$this->db = $db;
		$this->cache = $cache;
		$this->user = $user;
		$this->ext_manager = $ext_manager;
		$this->util = $util;
		$this->bb_players_table = $bb_players_table;
		$this->bb_ranks_table = $bb_ranks_table;
		$this->bb_classes_table = $bb_classes_table;
		$this->bb_races_table = $bb_races_table;
		$this->bb_language_table = $bb_language_table;
		$this->bb_guild_table = $bb_guild_table;
		$this->bb_factions_table = $bb_factions_table;
		$this->bb_games_table = $bb_games_table;
	}

	/**
	 * Lazily builds (and caches) the `player` instance. Deliberately not
	 * called from is_runnable() or should_run() — only run() needs it,
	 * and run() only ever executes once should_run()/is_runnable() have
	 * already confirmed the task is actually ready to do work.
	 */
	private function get_player(): player
	{
		if ($this->player === null)
		{
			$this->player = new player(
				$this->db, $this->config, $this->cache, $this->user, $this->ext_manager,
				$this->bbguild_log, $this->util,
				$this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table,
				$this->bb_races_table, $this->bb_language_table, $this->bb_guild_table,
				$this->bb_factions_table, $this->bb_games_table
			);
		}

		return $this->player;
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_runnable()
	{
		return !empty($this->registry->get_supported_game_ids());
	}

	/**
	 * {@inheritdoc}
	 */
	public function should_run()
	{
		return (time() - (int) $this->config['bbguild_sync_last_run']) > self::MIN_INTERVAL;
	}

	/**
	 * {@inheritdoc}
	 */
	public function run()
	{
		$game_ids = $this->registry->get_supported_game_ids();

		if (empty($game_ids))
		{
			return;
		}

		$player = $this->get_player();

		$stale_players = $player->get_stalest_players($game_ids, self::BATCH_SIZE);

		foreach ($stale_players as $player_row)
		{
			$handler = $this->registry->get($player_row['game_id']);

			$success = false;
			if ($handler !== null)
			{
				try
				{
					$success = $handler->sync_character($player_row);
				}
				catch (\Throwable $e)
				{
					$success = false;
				}
			}

			if (!$success)
			{
				$this->bbguild_log->log_insert([
					'log_type'   => 'L_ERROR_CHARACTER_SYNC_FAILED',
					'log_result' => 'L_ERROR',
					'log_action' => [$player_row['player_name'], $player_row['game_id']],
				]);
			}

			$player->update_last_synced((int) $player_row['player_id']);
		}

		$this->config->set('bbguild_sync_last_run', time());
	}
}
