<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Races Class
 *
 */

namespace avathar\bbguild\model\games\rpg;

use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\user;

/**
 * Races
 *
 * Manages creation of Game races
 *
 *   @package bbguild
 */
class races
{

	public $bb_races_table;
	public $bb_language_table;
	public $bb_players_table;
	public $bb_games_table;
	public $bb_factions_table;

	/** @var driver_interface */
	protected $db;
	/** @var config */
	protected $config;
	/** @var cache_interface */
	protected $cache;
	/** @var user */
	protected $user;

	/**
	  * the game_id (unique key)
	  *
	  * @var string
	  */
	public $game_id;

	/**
	 * race id
	*
	 * @var int
	 */
	public $race_id;

	/**
	 * race faction id
	*
	 * @var int
	 */
	public $race_faction_id;

	/**
	 * true if race is visible
	*
	 * @var bool
	 */
	public $race_hide;

	/**
	 * image path female icon
	*
	 * @var string
	 */
	public $image_female;

	/**
	 * image path male icon
	*
	 * @var string
	 */
	public $image_male;

	/**
	 * race nam
	*
	 * @var String
	 */
	public $race_name;

	/**
	 * constructor
	 *
	 * @param driver_interface $db
	 * @param config           $config
	 * @param cache_interface  $cache
	 * @param user             $user
	 * @param string           $bb_language_table
	 * @param string           $bb_players_table
	 * @param string           $bb_games_table
	 * @param string           $bb_races_table
	 * @param string           $bb_factions_table
	 */
	public function __construct(driver_interface $db, config $config, cache_interface $cache, user $user, $bb_language_table, $bb_players_table, $bb_games_table, $bb_races_table, $bb_factions_table)
	{
		$this->db = $db;
		$this->config = $config;
		$this->cache = $cache;
		$this->user = $user;
		$this->bb_language_table = $bb_language_table;
		$this->bb_players_table = $bb_players_table;
		$this->bb_games_table = $bb_games_table;
		$this->bb_races_table = $bb_races_table;
		$this->bb_factions_table = $bb_factions_table;
	}

	/**
	 * gets race from database
	 */
	public function get_race()
	{
		$sql_array = array (
		'SELECT' => ' r.game_id, r.race_id, l.name AS race_name, r.race_faction_id,  r.image_female, r.image_male, r.race_hide ',
		'FROM' => array ($this->bb_races_table => 'r', $this->bb_language_table => 'l' ),
		'WHERE' => "   r.game_id = l.game_id
						AND r.race_id = l.attribute_id
						AND l.attribute='race'
						AND l.language= '" . $this->config['bbguild_lang'] . "'
						AND l.game_id = '" . $this->db->sql_escape($this->game_id) . "'
						AND r.race_id = " . ( int ) $this->race_id
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		while ( $row = $this->db->sql_fetchrow($result) )
		{
			$this->race_faction_id= $row['race_faction_id'];
			$this->race_name = $row['race_name'];
			$this->image_male =$row['image_male'];
			$this->image_female = $row['image_female'];
			$this->race_hide = $row['race_hide'];
		}
		$this->db->sql_freeresult($result);
		unset($result);
	}

	/**
	 * adds a race to database
	 */
	public function make_race()
	{
		$sql = 'SELECT COUNT(race_id) AS countrace
			FROM ' . $this->bb_races_table . '
			WHERE race_id  = ' . ( int ) $this->race_id . "
			AND game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$resultr = $this->db->sql_query($sql);
		$a = $this->db->sql_fetchfield('countrace', false, $resultr);
		if (( int ) $a > 0)
		{
			//uh oh that race exists
			trigger_error(sprintf($this->user->lang['ADMIN_ADD_RACE_FAILED'], $this->race_id), E_USER_WARNING);
		}
		$this->db->sql_freeresult($resultr);
		$data = array (
		'game_id' => ( string ) $this->game_id,
		'race_id' => ( int ) $this->race_id,
		'race_faction_id' => ( int ) $this->race_faction_id,
		'image_male' => ( string ) $this->image_male,
		'image_female' => ( string ) $this->image_female,
		'race_hide' => 0 );

		$this->db->sql_transaction('begin');
		$sql = 'INSERT INTO ' . $this->bb_races_table . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);

		$names = array (
		'attribute_id' => $this->race_id,
		'game_id' => $this->game_id,
		'language' => $this->config['bbguild_lang'],
		'attribute' => 'race',
		'name' => ( string ) $this->race_name,
		'name_short' => ( string ) $this->race_name);

		$sql = 'INSERT INTO ' . $this->bb_language_table . ' ' . $this->db->sql_build_array('INSERT', $names);
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');
		$this->cache->destroy('sql', $this->bb_language_table);
		$this->cache->destroy('sql', $this->bb_races_table);
	}


	/**
	 * deletes a race from database
	*/
	public function delete_race()
	{
		/* check if there are players with this raceid */
		$sql_array = array (
		'SELECT' => ' count(*) as racecount  ',
		'FROM' => array (
			$this->bb_players_table => 'm',
			$this->bb_races_table => 'r' ),
		'WHERE' => 'm.player_race_id = r.race_id
		    			and r.race_id =  ' .  ( int ) $this->race_id . "
		    			and r.game_id = m.game_id
		    			and r.game_id = '" . $this->db->sql_escape($this->game_id) . "'" );

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$racecount = ( int ) $this->db->sql_fetchfield('racecount', false, $result);

		if ($racecount != 0)
		{
			trigger_error(sprintf($this->user->lang['ADMIN_DELETE_RACE_FAILED'], $this->race_name), E_USER_WARNING);
		}
		else
		{
			$this->db->sql_transaction('begin');

			$sql = 'DELETE FROM ' . $this->bb_races_table . ' WHERE race_id =' . ( int ) $this->race_id . " AND game_id = '" . $this->db->sql_escape($this->game_id) . "'";
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->bb_language_table  . " WHERE language= '" . $this->config['bbguild_lang'] . "'
							AND attribute = 'race'
							AND attribute_id= " . ( int ) $this->race_id . "
							AND game_id = '" . $this->db->sql_escape($this->game_id) . "'";

			$this->db->sql_query($sql);

			$this->cache->destroy('sql', $this->bb_races_table);
			$this->cache->destroy('sql', $this->bb_language_table );

			$this->db->sql_transaction('commit');
		}

	}


	/**
	 * deletes all races from a game
	 */
	public function delete_all_races()
	{
		$sql = 'DELETE FROM ' . $this->bb_races_table  . " WHERE game_id = '" .   $this->db->sql_escape($this->game_id) . "'"  ;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->bb_language_table  . " WHERE attribute = 'race'
							AND game_id = '" . $this->db->sql_escape($this->game_id) . "'";

		$this->db->sql_query($sql);

		$this->cache->destroy('sql', $this->bb_races_table );
		$this->cache->destroy('sql', $this->bb_language_table );
	}

	/**
	 * updates a race to database
	*
	 * @param races $old_race
	 */
	public function update_race(races $old_race)
	{
		// note you cannot change the game to which a race belongs
		$data = array (
		'race_faction_id' => ( int ) $this->race_faction_id,
		'image_male' => ( string ) $this->image_male,
		'image_female' => ( string ) $this->image_female );

		$this->db->sql_transaction('begin');
		$sql = 'UPDATE ' . $this->bb_races_table  . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . '
			    WHERE race_id = ' . ( int ) $this->race_id . " AND game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$this->db->sql_query($sql);

		$names = array (
		'name' => ( string ) $this->race_name, 'name_short' => ( string ) $this->race_name );

		$sql = 'UPDATE ' . $this->bb_language_table  . ' SET ' . $this->db->sql_build_array('UPDATE', $names) . '
			WHERE attribute_id = ' . ( int ) $this->race_id . " AND attribute='race'  AND language= '" . $this->config['bbguild_lang'] . "' AND game_id =   '" . $this->db->sql_escape($this->game_id) . "'";
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');
		$this->cache->destroy('sql', $this->bb_language_table );
		$this->cache->destroy('sql', $this->bb_races_table );
	}

	/**
	 * get array with races
	  *
	  * @param  string $order
	  * @return array
	  */
	public function list_races($order = 'r.race_id')
	{
		$sql_array = array (
		'SELECT' => ' r.game_id, r.race_id, r.race_faction_id, r.race_hide, r.image_female, r.image_male,
							  l.name as race_name, f.faction_name, g.game_name ',
		'FROM' => array (
			$this->bb_races_table  => 'r',
			$this->bb_factions_table => 'f',
			$this->bb_language_table  => 'l',
			$this->bb_games_table => 'g'
		),
		'WHERE' => " r.race_faction_id = f.faction_id
					AND f.game_id = r.game_id AND r.game_id = g.game_id AND r.game_id = '" . $this->db->sql_escape($this->game_id) . "'
		    		AND l.attribute_id = r.race_id AND l.game_id = r.game_id and l.language= '" . $this->config['bbguild_lang'] . "'
		    		AND l.attribute = 'race' ", 'ORDER_BY' => $order );

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$ra = array();
		while ( $row = $this->db->sql_fetchrow($result) )
		{
			$ra[$row['race_id']] = array(
			'game_name' => $row['game_name'],
			'race_id' => $row['race_id'],
			'race_name' => $row['race_name'],
			'faction_name' => $row['faction_name'],
			'image_male' => $row['image_male'],
			'image_female' => $row['image_female']);
		}
		$this->db->sql_freeresult($result);
		return $ra;
	}
}
