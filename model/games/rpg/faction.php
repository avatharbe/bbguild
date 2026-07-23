<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Faction Class
 *
 */

namespace avathar\bbguild\model\games\rpg;

use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\db\driver\driver_interface;
use phpbb\user;

/**
 * Faction Class
 *
 * Manages all Game Factions
 *
 *   @package bbguild
 */
class faction
{
	public $bb_factions_table;
	public $bb_races_table;

	/** @var driver_interface */
	protected $db;
	/** @var cache_interface */
	protected $cache;
	/** @var user */
	protected $user;

	/**
	 * game id
	  *
	 * @var string
	 */
	public $game_id;

	/**
	 * pk
	*
	 * @var int
	 */
	protected $f_index; //readonly

	/**
	 * faction id
	*
	 * @var int
	 */
	protected $faction_id;
	/**
	 * name of faction
	*
	 * @var string
	 */
	protected $faction_name;
	/**
	 * flag to show or hide (0 or 1)
	*
	 * @var int
	 */
	protected $faction_hide;


	/**
	  * faction property getter
	  *
	  * @param  $fieldName
	  * @return null
	  */
	public function __get($fieldName)
	{
		if (property_exists($this, $fieldName))
		{
			return $this->$fieldName;
		}
		else
		{
			trigger_error($this->user->lang['ERROR'] . '  '. $fieldName, E_USER_WARNING);
		}
		return null;
	}

	/**
	  * faction property setter
	  *
	  * @param string $property
	  * @param string $value
	  */
	public function __set($property, $value)
	{
		switch ($property)
		{
		case 'f_index':
			break;
		default:
			if (property_exists($this, $property))
			{
				$this->$property = $value;
			}
			else
			{
				trigger_error($this->user->lang['ERROR'] . '  '. $property, E_USER_WARNING);
			}
		}
	}

	/**
	 * Faction class constructor
	 *
	 * @param driver_interface $db
	 * @param cache_interface  $cache
	 * @param user             $user
	 * @param string           $game_id
	 * @param string           $bb_factions_table
	 * @param string           $bb_races_table
	 */
	public function __construct(driver_interface $db, cache_interface $cache, user $user, $game_id, $bb_factions_table, $bb_races_table = '')
	{
		$this->db = $db;
		$this->cache = $cache;
		$this->user = $user;
		$this->bb_factions_table = $bb_factions_table;
		$this->bb_races_table = $bb_races_table;

		$this->game_id=$game_id;
		$this->get();
	}

	/**
	 * build a full object
	 */
	public function get()
	{
		$sql = 'SELECT game_id, f_index, faction_id, faction_name, faction_hide
    			FROM ' . $this->bb_factions_table . '
    			WHERE f_index = ' . (int) $this->faction_id . " and game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->faction_id = $row['faction_id'];
			$this->faction_name = $row['faction_name'];
			$this->faction_hide    = $row['faction_hide'];
		}
		$this->db->sql_freeresult($result);
	}


	/**
	 * adds a faction
	 */
	public function make_faction()
	{
		$sql = 'SELECT max(faction_id) as faction_id FROM ' . $this->bb_factions_table . "
				WHERE game_id = '" . $this->db->sql_escape($this->game_id) . "' ";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->faction_id = (int) $row['faction_id'] + 1;
		$this->db->sql_freeresult($result);

		$data = array (
		'game_id' => $this->game_id,
		'faction_name' => ( string ) $this->faction_name,
		'faction_id' => (int ) $this->faction_id,
		'faction_hide' => 0 );

		$this->db->sql_transaction('begin');

		$sql = 'INSERT INTO ' . $this->bb_factions_table . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');
		$this->cache->destroy('sql', $this->bb_factions_table);
	}


	/**
	  * Update a faction
	  */
	public function update_faction()
	{
		$data = array (
			'game_id' => $this->game_id,
			'faction_name' => ( string ) $this->faction_name,
			'faction_id' => (int ) $this->faction_id,
			'faction_hide' => 0 );

		$this->db->sql_transaction('begin');

		$sql = 'UPDATE ' . $this->bb_factions_table . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . ' WHERE faction_id = ' . $this->faction_id . " AND game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');
		$this->cache->destroy('sql', $this->bb_factions_table);
	}


	/**
	 * delete a faction
	 */
	public function delete_faction()
	{
		/* check if there are races tied to this faction */
		$sql_array = array (
		'SELECT' => ' count(*) AS factioncount  ',
		'FROM' => array (
		 $this->bb_races_table => 'r', $this->bb_factions_table => 'f' ),
		'WHERE' => "r.race_faction_id = f.faction_id
				AND f.game_id = '" . $this->db->sql_escape($this->game_id) . "'
				AND f.f_index =  " . $this->faction_id );

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$factioncount = (int) $this->db->sql_fetchfield('factioncount');

		if ($factioncount == 0)
		{
			$sql = 'DELETE FROM ' . $this->bb_factions_table . ' WHERE f_index =' . $this->faction_id . " AND game_id = '" .   $this->db->sql_escape($this->game_id) . "'"  ;
			$this->db->sql_query($sql);
			$this->cache->destroy('sql', $this->bb_factions_table);
		}
		else
		{
			trigger_error(sprintf($this->user->lang['ADMIN_DELETE_FACTION_FAILED'], $this->game_id, $this->faction_name), E_USER_WARNING);
		}
	}

	/**
	 * deletes all factions from a game
	 */
	public function delete_all_factions()
	{
		$sql = 'DELETE FROM ' . $this->bb_factions_table . " WHERE game_id = '" .   $this->db->sql_escape($this->game_id) . "'"  ;
		$this->db->sql_query($sql);
		$this->cache->destroy('sql', $this->bb_factions_table);
	}

	/**
	 * get factions for this game
	*
	 * @return array
	 */
	public function get_factions()
	{
		$sql_array = array (
		'SELECT' => ' f.game_id, f.f_index, f.faction_id, f.faction_name, f.faction_hide ',
		'FROM' => array ($this->bb_factions_table => 'f' ),
		'WHERE' => " f.game_id = '" . $this->db->sql_escape($this->game_id) . "'",
		'ORDER_BY' => 'faction_id ASC ' );
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$fa = array();
		while ( $row = $this->db->sql_fetchrow($result) )
		{
			$fa[$row['faction_id']] = array(
			'f_index' => $row['f_index'],
			'faction_name' => $row['faction_name'],
			'faction_id' => $row['faction_id']);
		}
		$this->db->sql_freeresult($result);
		return $fa;

	}
}
