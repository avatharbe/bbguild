<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Role class
 *
 */
namespace avathar\bbguild\model\games\rpg;

use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\user;

/**
 * Roles
 *
* @package avathar\bbguild\model\games\rpg
 */
class roles
{
	public $bb_gameroles_table;
	public $bb_language_table;
	public $bb_games_table;
	public $bb_classes_table;

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
	 * Primary key
	 *
	 * @var int
	 */
	public $role_pkid;

	/**
	 * Class id
	 *
	 * @var INT
	 */
	public $role_id;

	/**
	 * name of class
	 *
	 * @var String
	 */
	public $rolename;

	/**
	 * name of image file without extension
	 *
	 * @var String
	 */
	public $role_icon;

	/**
	 * name of role category icon without extension
	 *
	 * @var
	 */
	public $role_cat_icon;

	/**
	 * class color hex
	 * used in raid planner
	 *
	 * @var string
	 */
	public $role_color;

	/**
	 * Role constructor
	 *
	 * @param driver_interface $db
	 * @param config           $config
	 * @param cache_interface  $cache
	 * @param user             $user
	 * @param string           $bb_gameroles_table
	 * @param string           $bb_language_table
	 * @param string           $bb_games_table
	 * @param string           $bb_classes_table
	 */
	public function __construct(driver_interface $db, config $config, cache_interface $cache, user $user, $bb_gameroles_table, $bb_language_table, $bb_games_table, $bb_classes_table)
	{
		$this->db = $db;
		$this->config = $config;
		$this->cache = $cache;
		$this->user = $user;
		$this->bb_gameroles_table = $bb_gameroles_table;
		$this->bb_language_table = $bb_language_table;
		$this->bb_games_table = $bb_games_table;
		$this->bb_classes_table = $bb_classes_table;

		$this->role_pkid = 0;
		$this->role_id = 0;
		$this->rolename = '';
		$this->role_icon = '';
		$this->role_cat_icon = '';
		$this->role_color = '';
	}

	/**
	 * gets 1 class from database
	 */
	public function get()
	{
		$sql_array = array (
			'SELECT' => ' r.role_pkid, r.game_id, r.role_id, l.name AS rolename, r.role_icon, r.role_cat_icon, r.role_color ',
			'FROM' => array (
				$this->bb_gameroles_table => 'r', $this->bb_language_table => 'l' ),
			'WHERE' => " r.role_id = l.attribute_id
							AND l.attribute='role'
							AND l.game_id = '" . $this->db->sql_escape($this->game_id) . "'
							AND r.game_id = l.game_id
							AND l.language= '" . $this->config['bbguild_lang'] . "'
							AND r.role_id = " . ( int ) $this->role_id);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		while ( $row = $this->db->sql_fetchrow($result))
		{
			$this->role_pkid = $row['role_pkid'];
			$this->role_id = (int) $row['role_id'];
			$this->rolename = (string) $row['rolename'];
			$this->role_icon = (string) $row['role_icon'];
			$this->role_cat_icon = (string) $row['role_cat_icon'];
			$this->role_color = (string) $row['role_color'];
		}
		$this->db->sql_freeresult($result);

	}

	/**
	 * adds a role to database
	 */
	public function make_role()
	{
		$sql = 'SELECT max(role_id) + 1 AS new_role_id FROM ' . $this->bb_gameroles_table . ' WHERE ' .
			" game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$resultc = $this->db->sql_query($sql);

		$this->role_id = (int) $this->db->sql_fetchfield('new_role_id', false, $resultc);
		$this->db->sql_freeresult($resultc);

		$data = array (
			'game_id' => ( string ) $this->game_id,
			'role_id' => ( int ) $this->role_id,
			'role_icon' => $this->role_icon,
			'role_cat_icon' => $this->role_cat_icon,
			'role_color' => $this->role_color );

		$this->db->sql_transaction('begin');

		$sql = 'INSERT INTO ' . $this->bb_gameroles_table . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);

		$names = array (
			'game_id' => ( string ) $this->game_id,
			'attribute_id' => $this->role_id,
			'language' => $this->config['bbguild_lang'],
			'attribute' => 'role',
			'name' => ( string ) $this->rolename,
			'name_short' => ( string ) $this->rolename );

		$sql = 'INSERT INTO ' . $this->bb_language_table  . ' ' . $this->db->sql_build_array('INSERT', $names);
		$this->db->sql_query($sql);
		$this->db->sql_transaction('commit');
		$this->cache->destroy('sql', $this->bb_language_table );
		$this->cache->destroy('sql', $this->bb_gameroles_table);
	}

	/**
	 * updates a class to database
	 *
	 * @param roles $oldrole
	 */
	public function update_role(roles $oldrole)
	{
		$data = array (
			'game_id' => ( string ) $this->game_id,
			'role_id' => ( int ) $this->role_id,
			'role_icon' => $this->role_icon,
			'role_cat_icon' => $this->role_cat_icon,
			'role_color' => $this->role_color );

		$this->db->sql_transaction('begin');

		$sql = 'UPDATE ' . $this->bb_gameroles_table . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . '
			    WHERE role_pkid = ' . ( int ) $this->role_pkid;

		$this->db->sql_query($sql);

		// now update the language table!
		$names = array (
			'attribute_id' => ( string ) $this->role_id,
			'name' => ( string ) $this->rolename,
			'name_short' => ( string ) $this->rolename);

		$sql = 'UPDATE ' . $this->bb_language_table  . ' SET ' . $this->db->sql_build_array('UPDATE', $names) . '
             WHERE attribute_id = ' . ( int ) $oldrole->role_id . " AND attribute='role'
             AND language= '" . $this->config['bbguild_lang'] . "' AND game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');

		$this->cache->destroy('sql', $this->bb_language_table );
		$this->cache->destroy('sql', $this->bb_gameroles_table);

	}


	/**
	 * deletes a role from database
	 */
	public function delete_role()
	{
		$this->db->sql_transaction('begin');

		$sql = 'DELETE FROM ' . $this->bb_gameroles_table . ' WHERE role_id  = ' . ( int ) $this->role_id . " and game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->bb_language_table  . " WHERE language= '" . $this->config['bbguild_lang'] . "' AND attribute = 'role'
                and attribute_id= " . ( int ) $this->role_id . " and game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');
		$this->cache->destroy('sql', $this->bb_gameroles_table);
		$this->cache->destroy('sql', $this->bb_language_table);
	}

	/**
	 * deletes all roles from a game
	 */
	public function delete_all_roles()
	{
		$sql = 'DELETE FROM ' . $this->bb_gameroles_table . " WHERE game_id = '" .   $this->db->sql_escape($this->game_id) . "'"  ;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->bb_language_table  . " WHERE attribute = 'role' AND game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$this->db->sql_query($sql);

		$this->cache->destroy('sql', $this->bb_gameroles_table);
		$this->cache->destroy('sql', $this->bb_language_table );
	}

	/**
	 * lists all roles
	 *
	 * @param  string $order
	 * @return array
	 */
	public function list_roles($order = 'role_id')
	{
		$sql_array = array (
			'SELECT' => ' r.game_id, r.role_pkid, r.role_id, l.name AS rolename, r.role_icon, r.role_cat_icon, r.role_color, g.game_name ',
			'FROM' => array (
				$this->bb_gameroles_table => 'r',
				$this->bb_language_table  => 'l',
				$this->bb_games_table => 'g' ),
			'WHERE' => " r.role_id = l.attribute_id AND r.game_id = g.game_id
                            AND r.game_id = l.game_id AND l.game_id = '" . $this->db->sql_escape($this->game_id) . "'
							AND l.attribute='role' AND l.language= '" . $this->config['bbguild_lang'] . "'",
			'ORDER_BY' => $order);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$roles=array();
		while ( $row = $this->db->sql_fetchrow($result))
		{
			$roles[$row['role_id']]  = array(
				'role_pkid'     => (int) $row['role_pkid'],
				'game_name'     => $row['game_name'],
				'role_id'       => (int) $row['role_id'],
				'rolename'      => (string) $row['rolename'],
				'role_icon'     => (string) $row['role_icon'],
				'role_cat_icon'     => (string) $row['role_cat_icon'],
				'role_color'    => (string) $row['role_color'],
			);
		}
		$this->db->sql_freeresult($result);
		return $roles;
	}
}
