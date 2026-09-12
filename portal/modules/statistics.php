<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Guild statistics portal module. Game-agnostic roster analytics: class
 * distribution, race spread, level distribution, rank/member counts, and
 * recent joins/departures. DKP/raid stats and item level are out of scope
 * here (bbDKP v2 and per-game plugin territory respectively).
 */

namespace avathar\bbguild\portal\modules;

use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\template\template;

class statistics extends module_base
{
	protected int $columns = 4; // center only
	protected string $name = 'BBGUILD_PORTAL_STATISTICS';
	protected string $image_src = '';

	/** Recent joins/departures shown per list */
	const RECENT_LIMIT = 5;

	protected driver_interface $db;
	protected template $template;
	protected config $config;
	protected string $guild_table;
	protected string $players_table;
	protected string $races_table;
	protected string $ranks_table;
	protected string $language_table;

	public function __construct(
		driver_interface $db,
		template $template,
		config $config,
		string $guild_table,
		string $players_table,
		string $races_table,
		string $ranks_table,
		string $language_table
	)
	{
		$this->db = $db;
		$this->template = $template;
		$this->config = $config;
		$this->guild_table = $guild_table;
		$this->players_table = $players_table;
		$this->races_table = $races_table;
		$this->ranks_table = $ranks_table;
		$this->language_table = $language_table;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_template_center(int $module_id)
	{
		$game_id = $this->get_guild_game_id();
		if ($game_id === null)
		{
			return null;
		}

		$this->assign_class_distribution($game_id);
		$this->assign_race_distribution($game_id);
		$this->assign_level_distribution();
		$this->assign_rank_distribution();
		$this->assign_recent_joins();
		$this->assign_recent_departures();

		return 'statistics_center.html';
	}

	/**
	 * @return string|null The guild's game_id, or null if the guild doesn't exist.
	 */
	private function get_guild_game_id(): ?string
	{
		$sql = 'SELECT game_id FROM ' . $this->guild_table .
			' WHERE id = ' . (int) $this->guild_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ? (string) $row['game_id'] : null;
	}

	private function assign_class_distribution(string $game_id): void
	{
		$sql = 'SELECT p.player_class_id AS class_id, COALESCE(l.name, \'\') AS class_name, COUNT(*) AS player_count
			FROM ' . $this->players_table . ' p
			LEFT JOIN ' . $this->language_table . " l
				ON l.attribute_id = p.player_class_id
				AND l.attribute = 'class'
				AND l.game_id = '" . $this->db->sql_escape($game_id) . "'
				AND l.language = '" . $this->db->sql_escape((string) $this->config['bbguild_lang']) . "'
			WHERE p.player_guild_id = " . (int) $this->guild_id . '
				AND p.player_status = 1
			GROUP BY p.player_class_id, l.name
			ORDER BY player_count DESC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('stat_class', array(
				'CLASS_ID'   => (int) $row['class_id'],
				'CLASS_NAME' => (string) $row['class_name'],
				'COUNT'      => (int) $row['player_count'],
			));
		}
		$this->db->sql_freeresult($result);
	}

	private function assign_race_distribution(string $game_id): void
	{
		$sql = 'SELECT p.player_race_id AS race_id, COALESCE(l.name, \'\') AS race_name, COUNT(*) AS player_count
			FROM ' . $this->players_table . ' p
			LEFT JOIN ' . $this->language_table . " l
				ON l.attribute_id = p.player_race_id
				AND l.attribute = 'race'
				AND l.game_id = '" . $this->db->sql_escape($game_id) . "'
				AND l.language = '" . $this->db->sql_escape((string) $this->config['bbguild_lang']) . "'
			WHERE p.player_guild_id = " . (int) $this->guild_id . '
				AND p.player_status = 1
			GROUP BY p.player_race_id, l.name
			ORDER BY player_count DESC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('stat_race', array(
				'RACE_ID'   => (int) $row['race_id'],
				'RACE_NAME' => (string) $row['race_name'],
				'COUNT'     => (int) $row['player_count'],
			));
		}
		$this->db->sql_freeresult($result);
	}

	private function assign_level_distribution(): void
	{
		$sql = 'SELECT player_level AS level, COUNT(*) AS player_count
			FROM ' . $this->players_table . '
			WHERE player_guild_id = ' . (int) $this->guild_id . '
				AND player_status = 1
			GROUP BY player_level
			ORDER BY player_level DESC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('stat_level', array(
				'LEVEL' => (int) $row['level'],
				'COUNT' => (int) $row['player_count'],
			));
		}
		$this->db->sql_freeresult($result);
	}

	private function assign_rank_distribution(): void
	{
		$sql = 'SELECT p.player_rank_id AS rank_id, r.rank_name AS rank_name, COUNT(*) AS player_count
			FROM ' . $this->players_table . ' p
			LEFT JOIN ' . $this->ranks_table . ' r
				ON r.rank_id = p.player_rank_id
				AND r.guild_id = ' . (int) $this->guild_id . '
			WHERE p.player_guild_id = ' . (int) $this->guild_id . '
				AND p.player_status = 1
			GROUP BY p.player_rank_id, r.rank_name
			ORDER BY player_count DESC';
		$result = $this->db->sql_query($sql);

		$total_members = 0;
		while ($row = $this->db->sql_fetchrow($result))
		{
			$count = (int) $row['player_count'];
			$total_members += $count;

			$this->template->assign_block_vars('stat_rank', array(
				'RANK_ID'   => (int) $row['rank_id'],
				'RANK_NAME' => (string) $row['rank_name'],
				'COUNT'     => $count,
			));
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_var('TOTAL_MEMBERS', $total_members);
	}

	private function assign_recent_joins(): void
	{
		$sql = 'SELECT player_name, player_joindate
			FROM ' . $this->players_table . '
			WHERE player_guild_id = ' . (int) $this->guild_id . '
				AND player_status = 1
			ORDER BY player_joindate DESC';
		$result = $this->db->sql_query_limit($sql, self::RECENT_LIMIT);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('stat_recent_join', array(
				'NAME' => (string) $row['player_name'],
				'DATE' => date('d/m/Y', (int) $row['player_joindate']),
			));
		}
		$this->db->sql_freeresult($result);
	}

	private function assign_recent_departures(): void
	{
		$sql = 'SELECT player_name, last_update
			FROM ' . $this->players_table . '
			WHERE player_guild_id = ' . (int) $this->guild_id . '
				AND player_status = 0
			ORDER BY last_update DESC';
		$result = $this->db->sql_query_limit($sql, self::RECENT_LIMIT);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('stat_recent_leave', array(
				'NAME' => (string) $row['player_name'],
				'DATE' => date('d/m/Y', (int) $row['last_update']),
			));
		}
		$this->db->sql_freeresult($result);
	}
}
