<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace avathar\bbguild\controller;

use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\request\request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * AJAX controller — returns JSON for dependent dropdowns in ACP.
 */
class ajax_controller
{
	/** @var string */
	public $bb_games_table;
	/** @var string */
	public $bb_ranks_table;
	/** @var string */
	public $bb_guild_table;
	/** @var string */
	public $bb_players_table;
	/** @var string */
	public $bb_classes_table;
	/** @var string */
	public $bb_races_table;
	/** @var string */
	public $bb_factions_table;
	/** @var string */
	public $bb_language_table;

	/** @var config */
	protected $config;

	/** @var driver_interface */
	protected $db;

	/** @var request */
	protected $request;

	/**
	 * Constructor
	 *
	 * @param config           $config
	 * @param driver_interface $db
	 * @param request          $request
	 * @param string           $bb_ranks_table
	 * @param string           $bb_guild_table
	 * @param string           $bb_players_table
	 * @param string           $bb_classes_table
	 * @param string           $bb_races_table
	 * @param string           $bb_factions_table
	 * @param string           $bb_language_table
	 */
	public function __construct(
		config $config,
		driver_interface $db,
		request $request,
		$bb_ranks_table,
		$bb_guild_table,
		$bb_players_table,
		$bb_classes_table,
		$bb_races_table,
		$bb_factions_table,
		$bb_language_table
	)
	{
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->bb_ranks_table = $bb_ranks_table;
		$this->bb_guild_table = $bb_guild_table;
		$this->bb_players_table = $bb_players_table;
		$this->bb_classes_table = $bb_classes_table;
		$this->bb_races_table = $bb_races_table;
		$this->bb_factions_table = $bb_factions_table;
		$this->bb_language_table = $bb_language_table;
	}

	/**
	 * returns GameFaction json list based on ajax call given game_id
	 * used in guild acp & player acp
	 *
	 * @internal string $game_id must be string values from game table
	 * @return JsonResponse
	 */
	public function getfaction()
	{
		$game_id = $this->request->variable('game_id', '', true);
		$sql = 'SELECT faction_id, faction_name
			FROM ' . $this->bb_factions_table . "
			WHERE game_id = '" . $this->db->sql_escape($game_id) . "'
			ORDER BY faction_id";
		$result = $this->db->sql_query($sql);

		$data = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$data[] = [
				'faction_id'   => $row['faction_id'],
				'faction_name' => $row['faction_name'],
			];
		}
		$this->db->sql_freeresult($result);

		return new JsonResponse($data);
	}

	/**
	 * returns Guild rank json list based on ajax call given guild id
	 * used in acp
	 *
	 * @return JsonResponse
	 * @internal int     $guild_id
	 */
	public function getguildrank()
	{
		$guild_id = (int) $this->request->variable('guild_id', 0);

		$sql = 'SELECT a.rank_id, a.rank_name, b.game_id
			FROM ' . $this->bb_ranks_table . ' a, ' . $this->bb_guild_table . ' b
			WHERE a.rank_hide = 0
				AND a.guild_id = ' . (int) $guild_id . '
				AND a.guild_id = b.id
			ORDER BY rank_id DESC';

		$result = $this->db->sql_query($sql);
		$data = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$data[] = [
				'rank_game_id' => $row['game_id'],
				'rank_id'      => $row['rank_id'],
				'rank_name'    => $row['rank_name'],
			];
		}
		$this->db->sql_freeresult($result);

		return new JsonResponse($data);
	}


	/**
	 * returns playerlist json based on ajax call
	 * used by acp_addraid.html
	 *
	 * @internal string      $guild_id
	 * @return JsonResponse  $JsonResponse
	 */
	public function getplayerList()
	{
		$guild_id = (int) $this->request->variable('guild_id', 0);

		$sql = 'SELECT p.player_id, p.player_name, r.rank_name
			FROM ' . $this->bb_players_table . ' p
			LEFT JOIN ' . $this->bb_ranks_table . ' r
				ON p.player_rank_id = r.rank_id AND p.player_guild_id = r.guild_id
			WHERE p.player_guild_id = ' . (int) $guild_id . '
			ORDER BY p.player_name ASC';

		$result = $this->db->sql_query($sql);
		$data = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$data[] = [
				'player_id'   => $row['player_id'],
				'player_name' => $row['rank_name'] . ' ' . $row['player_name'],
			];
		}
		$this->db->sql_freeresult($result);

		return new JsonResponse($data);
	}


	/**
	* returns race & class json based on ajax call
	* @return JsonResponse  $JsonResponse
	* @internal string $game_id
	*/
	public function getclassrace()
	{
		$game_id = $this->request->variable('game_id', '', true);
		$lang = $this->config['bbguild_lang'];

		// Races
		$sql_array = [
			'SELECT'   => 'r.race_id, l.name as race_name',
			'FROM'     => [
				$this->bb_races_table    => 'r',
				$this->bb_language_table => 'l',
			],
			'WHERE'    => "r.race_id = l.attribute_id
				AND r.game_id = '" . $this->db->sql_escape($game_id) . "'
				AND l.attribute = 'race'
				AND l.game_id = r.game_id
				AND l.language = '" . $this->db->sql_escape($lang) . "'",
			'ORDER_BY' => 'l.name',
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);

		$races = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$races[] = [
				'race_id'   => $row['race_id'],
				'race_name' => $row['race_name'],
			];
		}
		$this->db->sql_freeresult($result);

		// Classes
		$sql_array = [
			'SELECT'   => 'c.class_id, l.name as class_name',
			'FROM'     => [
				$this->bb_classes_table   => 'c',
				$this->bb_language_table  => 'l',
			],
			'WHERE'    => "l.game_id = c.game_id
				AND c.game_id = '" . $this->db->sql_escape($game_id) . "'
				AND l.attribute_id = c.class_id
				AND l.language = '" . $this->db->sql_escape($lang) . "'
				AND l.attribute = 'class'",
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);

		$classes = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$classes[] = [
				'class_id'   => $row['class_id'],
				'class_name' => $row['class_name'],
			];
		}
		$this->db->sql_freeresult($result);

		return new JsonResponse([
			'races'   => $races,
			'classes' => $classes,
		]);
	}

}
