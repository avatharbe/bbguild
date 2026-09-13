<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Recruitment Class
 *
 */

namespace avathar\bbguild\model\player;

use avathar\bbguild\model\games\rpg\roles;

/**
 * holds vacancies per guild, game, role and class
 *
 * class hierarchy Game --> Roles --> Recruitment
 *
 *   @package bbguild
 */
class recruitment extends roles
{

	public $bb_gameroles_table;
	public $bb_guild_table;
	public $bb_recruit_table;
	public $bb_language_table;
	public $bb_classes_table;
	public $bb_players_table;
	public $bb_ranks_table;

	/**
	 * primary key
	 */
	public $id;

	/**
	 * guild for which we want to recruit
	 *
	 * @var int
	 */
	protected $guild_id;

	/**
	 * @return int
	 */
	public function getGuildId()
	{
		return $this->guild_id;
	}

	/**
	 * @param int $guild_id
	 */
	public function setGuildId($guild_id)
	{
		$this->guild_id = $guild_id;
	}

	/**
	 * Class id needed
	 *
	 * @var int
	 */
	protected $class_id;

	/**
	 * @return int
	 */
	public function getClassId()
	{
		return $this->class_id;
	}

	/**
	 * @param int $class_id
	 */
	public function setClassId($class_id)
	{
		$this->class_id = $class_id;
	}

	/**
	 * how many are needed ?
	 *
	 * @var int
	 */
	protected $positions;

	/**
	 * @return int
	 */
	public function getPositions()
	{
		return $this->positions;
	}

	/**
	 * @param int $positions
	 */
	public function setPositions($positions)
	{
		$this->positions = $positions;
	}

	/**
	 * minimum level required ?
	 *
	 * @var int
	 */
	protected $level;

	/**
	 * @return int
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * @param int $level
	 */
	public function setLevel($level)
	{
		$this->level = $level;
	}


	/**
	 * how many did apply ?
	 *
	 * @var int
	 */
	protected $applicants;

	/**
	 * @return int
	 */
	public function getApplicants()
	{
		return $this->applicants;
	}

	/**
	 * @param int $applicants
	 */
	public function setApplicants($applicants)
	{
		$this->applicants = $applicants;
	}

	/**
	 * date last update -- epoch date
	 *
	 * @var int
	 */
	protected $last_update;

	/**
	 * @return int
	 */
	public function getLastUpdate()
	{
		return $this->last_update;
	}

	/**
	 * @param int $last_update
	 */
	public function setLastUpdate($last_update)
	{
		$this->last_update = $last_update;
	}

	/**
	 * a note on this recruitment
	 *
	 * @var int
	 */
	protected $note;

	/**
	 * @return int
	 */
	public function getNote()
	{
		return $this->note;
	}

	/**
	 * @param int $note
	 */
	public function setNote($note)
	{
		$this->note = $note;
	}

	/**
	 * status of recruitment
	 * 0 to 3
	 *
	 * @var int
	 */
	protected $status;

	/**
	 * @return int
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param int $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}


	/**
	 * possible recruitment statuses
	 *
	 * @var array
	 */
	private $recruitstatus = array();

	/**
	 * possible recruitment status colors
	 *
	 * @var array
	 */
	private $classreccolor = array();

	/**
	 * Apply template ID
	 *
	 * @var int
	 */
	protected $applytemplate_id;

	/**
	 * @return int
	 */
	public function getApplytemplateid()
	{
		return $this->applytemplate_id;
	}

	/**
	 * @param $applytemplate_id
	 */
	public function setApplytemplateid($applytemplate_id)
	{
		$this->applytemplate_id = $applytemplate_id;
	}

	/**
	 * recruitment constructor.
	 * @param string $bb_gameroles_table
	 * @param string $bb_guild_table
	 * @param string $bb_recruit_table
	 * @param string $bb_language_table
	 * @param string $bb_classes_table
	 * @param string $bb_players_table
	 * @param string $bb_ranks_table
	 */
	public function __construct($bb_gameroles_table, $bb_guild_table, $bb_recruit_table, $bb_language_table, $bb_classes_table,
								$bb_players_table, $bb_ranks_table )
	{

		$this->bb_ranks_table = $bb_ranks_table;
		$this->bb_guild_table = $bb_guild_table;
		$this->bb_players_table = $bb_players_table;
		$this->bb_classes_table = $bb_classes_table;
		$this->bb_gameroles_table = $bb_gameroles_table;
		$this->bb_language_table = $bb_language_table;
		$this->bb_recruit_table = $bb_recruit_table;

		global $user;
		$this->recruitstatus = array(
			0 => $user->lang['CLOSED'],
			1 => $user->lang['LOW'],
			2 => $user->lang['MEDIUM'],
			3 => $user->lang['HIGH']);

		$this->classreccolor = array(
			0 => 'bullet_white.png',
			1 => 'bullet_yellow.png',
			2 => 'bullet_red.png',
			3 => 'bullet_purple.png');
	}

	/**
	 * construct recruitment object
	 *
	 * @param  int $id
	 * @return int|void
	 */
	public function get($id = 0)
	{
		global $config, $db;
		$sql_array = array(
			'SELECT'   => ' u.id, g.game_id, u.guild_id, u.role_id, u.class_id, u.positions, u.applytemplate_id,
                u.applicants, u.status, u.last_update, u.note, u.level,
                r.role_color, r.role_icon, role_cat_icon, l.name as role_name ',
			'FROM'     => array(
				$this->bb_recruit_table   => 'u',
				$this->bb_guild_table       => 'g',
				$this->bb_gameroles_table => 'r',
				$this->bb_language_table       => 'l',
			),
			'WHERE'    => " 1=1
                AND u.guild_id = g.id
                AND u.role_id = r.role_id
                AND l.attribute='role'
                AND r.game_id=l.game_id AND l.attribute_id = r.role_id  AND l.language = '" . $db->sql_escape($config['bbguild_lang']) . "' and l.attribute='role'
                AND u.id = " . (int) $this->id,
			'ORDER_BY' => ' u.role_id '
		);
		$sql    = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		$row    = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			return 0;
		}
		else
		{
			$this->guild_id      = $row['guild_id'];
			$this->game_id       = $row['game_id'];
			$this->role_id       = $row['role_id'];
			$this->class_id      = $row['class_id'];
			$this->rolename      = $row['role_name'];
			$this->role_color    = $row['role_color'];
			$this->role_icon     = $row['role_icon'];
			$this->role_cat_icon = $row['role_cat_icon'];
			$this->positions     = $row['positions'];
			$this->applicants    = $row['applicants'];
			$this->level         = $row['level'];
			$this->note          = $row['note'];
			$this->last_update   = $row['last_update'];
			$this->status        = $row['status'];
			$this->applytemplate_id = $row['applytemplate_id'];
			return 1;
		}
	}

	/**
	 * inserts a new recruitment
	 */
	public function make_role()
	{
		global $db;

		$query = $db->sql_build_array(
			'INSERT', array(
			'guild_id'   => $this->guild_id,
			'role_id'    => $this->role_id,
			'class_id'   => $this->class_id,
			'positions'  => $this->positions,
			'applicants' => $this->applicants,
			'note'       => $this->note,
			'last_update' => $this->last_update,
			'level'      => $this->level,
			'status'     => $this->status,
			'applytemplate_id' => $this->applytemplate_id,
			)
		);
		$db->sql_query('INSERT INTO ' . $this->bb_recruit_table . $query);
		return 1;
	}

	/**
	 * update my roles
	 */
	public function update_role()
	{
		global $db;
		$query = $db->sql_build_array(
			'UPDATE', array(
			'guild_id'   => $this->guild_id,
			'role_id'    => $this->role_id,
			'class_id'   => $this->class_id,
			'positions'  => $this->positions,
			'applicants' => $this->applicants,
			'note'       => $this->note,
			'level'      => $this->level,
			'last_update' => $this->last_update,
			'status'      => $this->status,
			'applytemplate_id' => $this->applytemplate_id,
			)
		);
		$db->sql_query('UPDATE ' . $this->bb_recruit_table . ' SET ' . $query . ' WHERE id = ' . (int) $this->id);
	}

	/**
	 * deletes a role
	 */
	public function delete_role()
	{
		global $db;
		$sql = 'DELETE FROM ' . $this->bb_recruit_table . ' WHERE id = ' . (int) $this->id;
		$db->sql_query($sql);
	}

	/**
	 * get all current recruitments for a guild
	 *
	 * @param  int $mode
	 * @return mixed
	 */
	public function list_recruitments($mode = 0)
	{
		global $config, $db;

		$sql_array = array(

			'SELECT'   => ' u.id, u.guild_id, u.role_id,
                u.class_id, u.positions, u.applicants, u.status, u.last_update, u.note, u.level, u.applytemplate_id,
                r.role_color, r.role_icon, r.role_cat_icon, r1.name as role_name,
                c1.name as class_name, c.colorcode, c.imagename
                 ',

			'FROM'     => array(
				$this->bb_recruit_table   => 'u',
				$this->bb_guild_table       => 'g',
				$this->bb_gameroles_table => 'r',
				$this->bb_classes_table       => 'c',
				$this->bb_language_table       => 'r1',
			),

			'LEFT_JOIN' => array(
				array(
					'FROM'  => array($this->bb_language_table => 'c1'),
					'ON'    => "c.game_id=c1.game_id AND c1.attribute_id = c.class_id  AND c1.language = '" . $config['bbguild_lang'] . "' and c1.attribute='class'",
				)
			),

			'WHERE'    => " 1=1
                AND u.guild_id = g.id
                AND u.role_id = r.role_id
                AND r.game_id= g.game_id
                AND r1.attribute = 'role'
                AND r.game_id = r1.game_id AND r1.attribute_id = r.role_id  AND r1.language = '" . $db->sql_escape($config['bbguild_lang']) . "' and r1.attribute='role'
                AND c.class_id > 0 AND c.class_id = u.class_id AND c.game_id = g.game_id
                AND g.id =  " . (int) $this->guild_id,
			'ORDER_BY' => 'c.game_id, c.class_id '
		);

		if ($mode ==1)
		{
			$sql_array['WHERE'] .= ' AND u.status = 1 ';
		}

		$sql          = $db->sql_build_query('SELECT', $sql_array);
		return $db->sql_query($sql);
	}


	/**
	 * get the guilds that recruit
	 *
	 * @return array
	 */
	public function get_recruiting_guilds()
	{
		global $db;
		$sql_array = array(
		'SELECT'   => ' g.id, g.name, g.emblemurl, rec_status, recruitforum  ',
			'FROM'     => array(
				$this->bb_guild_table     => 'g',
				$this->bb_recruit_table => 'r'),
			'WHERE'    => 'r.guild_id = g.id ',
			'GROUP_BY' => ' g.name ',
			'ORDER_BY' => ' g.name ');
		$sql    = $db->sql_build_query('SELECT', $sql_array);
		return $db->sql_query($sql);

	}
}
