<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Game Classes
 *
 */

namespace avathar\bbguild\model\games\rpg;

use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\user;

/**
 * Classes
 *
 * Manages all Game Classes
 *
 *   @package bbguild
 */
class classes
{
	public $bb_classes_table;
	public $bb_language_table;
	public $bb_players_table;
	public $bb_games_table;

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
	  * Class id
	  *
	  * @var INT
	  */
	public $class_id;

	/**
	  * Primary key
	  *
	  * @var int
	  */
	public $c_index;
	/**
	  * game faction ID
	  *
	  * @var int
	  */
	public $faction_id;
	/**
	  * minimum level
	  *
	  * @var number
	  */
	public $min_level;
	/**
	  * maximum level
	  *
	  * @var int
	  */
	public $max_level;
	/**
	  * name of class
	  *
	  * @var String
	  */
	public $classname;
	/**
	  * class is hidden or not?
	  *
	  * @var bool
	  */
	public $hide;
	/**
	  * how many needed of this class in the Dps role ?
	  *
	  * @var number
	  */
	public $dps;
	/**
	  * how many needed of this class in the tank role ?
	  *
	  * @var int
	  */
	public $tank;
	/**
	  * how many needed of this class in the heal role ?
	  *
	  * @var int
	  */
	public $heal;
	/**
	  * name of image file
	  *
	  * @var String
	  */
	public $imagename;
	/**
	  * class color hex
	  *
	  * @var string
	  */
	public $colorcode;
	/**
	  * armor type
	  *
	  * @var String
	  */
	public $armor_type;

	/**
	  * array of all armor types
	  *
	  * @var array
	  */
	public $armortypes;


	/**
	 * classes constructor.
	 *
	 * @param driver_interface $db
	 * @param config           $config
	 * @param cache_interface  $cache
	 * @param user             $user
	 * @param string           $bb_language_table
	 * @param string           $bb_players_table
	 * @param string           $bb_games_table
	 * @param string           $bb_classes_table
	 */
	public function __construct(driver_interface $db, config $config, cache_interface $cache, user $user, $bb_language_table, $bb_players_table, $bb_games_table, $bb_classes_table)
	{
		$this->db = $db;
		$this->config = $config;
		$this->cache = $cache;
		$this->user = $user;

		$this->bb_language_table = $bb_language_table;
		$this->bb_players_table = $bb_players_table;
		$this->bb_games_table = $bb_games_table;
		$this->bb_classes_table = $bb_classes_table;

		$this->armortypes = array (
		'CLOTH' => $user->lang['CLOTH'] ?? 'Cloth',
		'ROBE' => $user->lang['ROBE'] ?? 'Robes',
		'LEATHER' => $user->lang['LEATHER'] ?? 'Leather',
		'AUGMENTED' => $user->lang['AUGMENTED'] ?? 'Augmented',
		'MAIL' => $user->lang['MAIL'] ?? 'Mail',
		'HEAVY' => $user->lang['HEAVY'] ?? 'Heavy',
		'PLATE' => $user->lang['PLATE'] ?? 'Plate' );

		$this->game_id = '';
		$this->c_index = 0;
		$this->class_id = 0;
		$this->classname = '';
		$this->min_level = 0;
		$this->max_level = 0;
		$this->armor_type = '';
		$this->imagename = '';
		$this->colorcode = '';
		$this->faction_id = 0;
	}

	/**
	 * gets 1 class from database
	 */
	public function get_class()
	{
		$sql_array = array (
		'SELECT' => ' c.c_index, c.class_id, l.name AS class_name, c.class_min_level, c.class_max_level, c.class_faction_id,
							c.class_armor_type, c.imagename, c.colorcode ',
		'FROM' => array (
			$this->bb_classes_table => 'c',
			$this->bb_language_table => 'l' ),
		'WHERE' => " c.class_id = l.attribute_id
							AND l.attribute='class'
							AND l.game_id = '" . $this->game_id . "'
							AND c.game_id = l.game_id
							AND l.language= '" . $this->config['bbguild_lang'] . "'
							AND c.class_id = " . $this->class_id);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		while ( $row = $this->db->sql_fetchrow($result))
		{
			$this->c_index = $row['c_index'];
			$this->class_id = (int) $row['class_id'];
			$this->classname = (string) $row['class_name'];
			$this->min_level = (int) $row['class_min_level'];
			$this->max_level = (int) $row['class_max_level'];
			$this->armor_type = (string) $row['class_armor_type'];
			$this->imagename = (string) $row['imagename'];
			$this->colorcode = (string) $row['colorcode'];
			$this->faction_id = (int) $row['class_faction_id'];
		}
		$this->db->sql_freeresult($result);

	}

	/**
	 * adds a class to database
	 */
	public function make_class()
	{
		$sql = 'SELECT count(*) AS countclass FROM ' . $this->bb_classes_table . ' WHERE class_id  = ' .
		$this->class_id . " AND game_id = '" . $this->game_id . "'";
		$resultc = $this->db->sql_query($sql);

		if (( int ) $this->db->sql_fetchfield('countclass', false, $resultc) > 0)
		{
			trigger_error(sprintf($this->user->lang['ADMIN_ADD_CLASS_FAILED'], $this->classname), E_USER_WARNING);
		}
		$this->db->sql_freeresult($resultc);
		unset($resultc);

		$data = array (
		'class_id' => ( int ) $this->class_id,
		'game_id' => ( string ) $this->game_id,
		'class_min_level' => ( int ) $this->min_level,
		'class_max_level' => ( int ) $this->max_level,
		'class_armor_type' => ( string ) $this->armor_type,
		'imagename' => $this->imagename,
		'class_hide' => 0,
		'colorcode' => $this->colorcode );

		$this->db->sql_transaction('begin');

		$sql = 'INSERT INTO ' . $this->bb_classes_table . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);

		$names = array (
		'game_id' => ( string ) $this->game_id,
		'attribute_id' => $this->class_id,
		'language' => $this->config['bbguild_lang'],
		'attribute' => 'class',
		'name' => ( string ) $this->classname,
		'name_short' => ( string ) $this->classname );

		$sql = 'INSERT INTO ' . $this->bb_language_table . ' ' . $this->db->sql_build_array('INSERT', $names);
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');
		$this->cache->destroy('sql', $this->bb_language_table);
		$this->cache->destroy('sql', $this->bb_classes_table);
	}

	/**
	 * deletes a class from database
	 */
	public function delete_class()
	{
		// see if there are players in this class
		$sql_array = array (
		'SELECT' => ' c.class_id, COUNT(*) AS classcount  ',
		'FROM' => array (
			$this->bb_players_table => 'm',
			$this->bb_classes_table => 'c' ),
		'WHERE' =>     "m.game_id = c.game_id AND m.game_id = '" . $this->game_id . "'
    					and m.player_class_id = c.class_id AND c.class_id =  " . $this->class_id ,
		'GROUP_BY' => 'c.class_id'
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$classcount = ( int ) $this->db->sql_fetchfield('classcount', false, $result);
		$this->db->sql_freeresult($result);

		if ($classcount == 0)
		{
			$this->db->sql_transaction('begin');

			$sql = 'DELETE FROM ' . $this->bb_classes_table . ' WHERE class_id  = ' . $this->class_id . " and game_id = '" . $this->game_id . "'";
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->bb_language_table . " WHERE language= '" . $this->config['bbguild_lang'] . "' AND attribute = 'class'
					and attribute_id= " . $this->class_id . " and game_id = '" . $this->game_id . "'";
			$this->db->sql_query($sql);

			$this->db->sql_transaction('commit');
			$this->cache->destroy('sql', $this->bb_classes_table);
			$this->cache->destroy('sql', $this->bb_language_table);
		}
		else
		{
			trigger_error(sprintf($this->user->lang['ADMIN_DELETE_CLASS_FAILED'], $this->class_id), E_USER_WARNING);
		}

	}

	/**
	 * deletes all classes from a game
	 */
	public function delete_all_classes()
	{
		$sql = 'DELETE FROM ' . $this->bb_classes_table . " WHERE game_id = '" .   $this->game_id . "'"  ;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->bb_language_table . " WHERE attribute = 'class'
							AND game_id = '" . $this->game_id . "'";
		$this->db->sql_query($sql);

		$this->cache->destroy('sql', $this->bb_classes_table);
		$this->cache->destroy('sql', $this->bb_language_table);
	}

	/**
	 * updates a class to database
	  *
	 * @param classes $oldclass
	 */
	public function update_class(classes $oldclass)
	{
		// check for unique classid exception : if the new class id exists already
		$sql = 'SELECT count(*) AS countclass FROM ' . $this->bb_classes_table . '
				WHERE c_index != ' . $this->c_index . "
				AND class_id = '" . $this->db->sql_escape($oldclass->class_id) . "'
				AND game_id = '" . $this->game_id. "'";

		$result = $this->db->sql_query($sql);
		if (( int ) $this->db->sql_fetchfield('countclass', false, $result) > 0)
		{
			trigger_error(sprintf($this->user->lang['ADMIN_ADD_CLASS_FAILED'], $this->classname), E_USER_WARNING);
		}
		$this->db->sql_freeresult($result);

		$data = array (
		'class_id' => ( int ) $this->class_id,
		'class_min_level' => ( int ) $this->min_level,
		'class_max_level' => ( int ) $this->max_level,
		'class_armor_type' => ( string ) $this->armor_type,
		'imagename' => $this->imagename,
		'class_hide' => 0,
		'colorcode' => $this->colorcode );
		$this->db->sql_transaction('begin');

		$sql = 'UPDATE ' . $this->bb_classes_table . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . '
			    WHERE c_index = ' . $this->c_index;

		$this->db->sql_query($sql);

		// now update the language table!
		$names = array (
		'attribute_id' => ( string ) $this->class_id, //new classid
		'name' => ( string ) $this->classname,
		'name_short' => ( string ) $this->classname);

		$sql = 'UPDATE ' . $this->bb_language_table . ' SET ' . $this->db->sql_build_array('UPDATE', $names) . '
		 WHERE attribute_id = ' . $oldclass->class_id . " AND attribute='class'
		 AND language= '" . $this->config['bbguild_lang'] . "' AND game_id = '" . $this->game_id . "'";
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');
		$this->cache->destroy('sql', $this->bb_language_table);
		$this->cache->destroy('sql', $this->bb_classes_table);

	}


	/**
	  * lists all classes
	  *
	  * @param  string     $order
	  * @param  int|number $mode
	  * @return array
	  */
	public function list_classes($order = 'class_id', $mode = 0)
	{
		$sql_array = array (
		'SELECT' => ' c.game_id, c.c_index, c.class_id, l.name AS class_name, c.class_min_level, c.class_hide,
					c.class_max_level, c.class_armor_type, c.imagename, c.colorcode,  g.game_name ',
		'FROM' => array (
			$this->bb_classes_table => 'c',
			$this->bb_language_table => 'l',
			$this->bb_games_table => 'g'  ),
		'WHERE' => " c.class_id = l.attribute_id
							AND c.game_id = g.game_id AND c.game_id = l.game_id AND l.game_id = '" . $this->db->sql_escape($this->game_id) . "'
							AND l.attribute='class' AND l.language= '" . $this->config['bbguild_lang'] . "'",
		'ORDER_BY' => $order );

		if ($mode == 0)
		{
			$sql_array['WHERE'] .=    'AND c.class_id = ' . $this->class_id;
		}

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$cl=array();
		while ( $row = $this->db->sql_fetchrow($result))
		{
			$cl[$row['class_id']]  = array(
			'c_index' => (int) $row['c_index'],
			'game_name' => $row['game_name'],
			'class_id' => (int) $row['class_id'],
			'class_name' => (string) $row['class_name'],
			'class_min_level' => (int) $row['class_min_level'],
			'class_max_level' => (int) $row['class_max_level'],
			'class_armor_type' => (string) $row['class_armor_type'],
			'class_hide' => (string) $row['class_hide'],
			'imagename' => (string) $row['imagename'],
			'colorcode' => (string) $row['colorcode'],
			);
		}
		$this->db->sql_freeresult($result);
		return $cl;
	}

}
