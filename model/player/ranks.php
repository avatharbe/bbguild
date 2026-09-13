<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Ranks class file
 *
 */

namespace avathar\bbguild\model\player;

use avathar\bbguild\model\player\guilds;

/**
 * Ranks Class
 *
 * Manages Guildranks, extends the guild class
 *
 *   @package bbguild
 */
class ranks extends guilds
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;
	/** @var \phpbb\user */
	protected $user;
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;
	/** @var \avathar\bbguild\model\admin\log */
	protected $log;

	public $bb_ranks_table;
	public $bb_players_table;

	/**
	 * Name of rank
	*
	 * @var string
	 */
	public $RankName;
	/**
	 * id of rank. 0 is highest
	*
	 * @var int
	 */
	public $RankId;
	/**
	 * id of guild
	*
	 * @var int
	 */
	public $RankGuild;
	/**
	 * is rank shown ?
	*
	 * @var int (1 or 0)
	 */
	public $RankHide;
	/**
	 * prefix of rank
	*
	 * @var string
	 */
	public $RankPrefix;
	/**
	 * suffix of rank
	*
	 * @var string
	 */
	public $RankSuffix;

	/**
	 * ranks constructor.
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\user $user
	 * @param \phpbb\cache\driver\driver_interface $cache
	 * @param \avathar\bbguild\model\admin\log $log
	 * @param string $bb_players_table
	 * @param string $bb_ranks_table
	 * @param int $RankGuild
	 * @param int $RankId
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\cache\driver\driver_interface $cache,
		\avathar\bbguild\model\admin\log $log,
		$bb_players_table, $bb_ranks_table, $RankGuild, $RankId = 0)
	{
		$this->db = $db;
		$this->user = $user;
		$this->cache = $cache;
		$this->log = $log;
		$this->bb_players_table = $bb_players_table;
		$this->bb_ranks_table = $bb_ranks_table;

		if (($RankGuild >= 0 && $RankId == 0) or ($RankGuild == 0 && $RankId == 99))
		{
			$this->RankGuild=$RankGuild;
			$this->RankId=$RankId;
			$this->Getrank();
		}
		else
		{
			$this->RankGuild=$RankGuild;
			$this->RankId=0;
			$this->RankName='';
			$this->RankHide=0;
			$this->RankPrefix='';
			$this->RankSuffix='';
		}
	}

	/**
	 * gets all info on one rank
	 */
	public function Getrank()
	{
		$sql = 'SELECT rank_name, rank_hide, rank_prefix, rank_suffix
    			FROM ' . $this->bb_ranks_table . '
    			WHERE rank_id = ' . (int) $this->RankId . ' and guild_id = ' . (int) $this->RankGuild;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->RankName = $row['rank_name'];
			$this->RankHide    = $row['rank_hide'];
			$this->RankPrefix = $row['rank_prefix'];
			$this->RankSuffix = $row['rank_suffix'];
		}
		$this->db->sql_freeresult($result);

	}

	/**
	 * adds a rank
	 */
	public function Makerank()
	{
		$this->cache->destroy('sql', $this->bb_ranks_table);

		if ($this->RankName == '')
		{
			trigger_error($this->user->lang('ERROR_RANK_NAME_EMPTY'), E_USER_WARNING);
		}

		//check if guildid is valid
		if ($this->RankGuild == 0)
		{
			trigger_error($this->user->lang('ERROR_INVALID_GUILDID'), E_USER_WARNING);
		}

		$sql = 'DELETE FROM ' . $this->bb_ranks_table . '
                   	WHERE rank_id = ' . (int) $this->RankId . '
                   	AND guild_id = ' . (int) $this->RankGuild;
		$this->db->sql_query($sql);

		// build insert array
		$query = $this->db->sql_build_array(
			'INSERT', array(
			'rank_id' => (int) $this->RankId ,
			'rank_name' => $this->RankName ,
			'rank_hide' => $this->RankHide ,
			'rank_prefix' => $this->RankPrefix ,
			'rank_suffix' => $this->RankSuffix ,
			'guild_id' => (int) $this->RankGuild)
		);
		// insert new rank
		$this->db->sql_query('INSERT INTO ' . $this->bb_ranks_table . $query);

		// log the action
		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_RANK_ADDED',
				'log_action' => [$this->RankName],
			)
		);

		unset($bbguild);
	}

	/**
	 * deletes a rank
	 *
	 * @param  bool $override
	 * @return bool
	 */
	public function Rankdelete($override = false)
	{
		if ($this->countplayers() > 0 )
		{
			return false;
		}

		if (! $override)
		{
			// check if rank is used
			$sql = 'SELECT count(*) as rankcount FROM ' . $this->bb_players_table . ' WHERE
            		 player_rank_id   = ' . (int) $this->RankId . ' and
            		 player_guild_id =  ' . (int) $this->RankGuild;
			$result = $this->db->sql_query($sql);
			if ((int) $this->db->sql_fetchfield('rankcount') >= 1)
			{
				trigger_error('Cannot delete rank ' . $this->RankId . '. There are players with this rank in guild . ' . $this->RankGuild, E_USER_WARNING);
			}
		}

		// hardcoded exclusion of ranks 90/99
		$sql = 'DELETE FROM ' . $this->bb_ranks_table . ' WHERE rank_id != 90 and rank_id != 99 and rank_id= ' .
		(int) $this->RankId . ' and guild_id = ' . (int) $this->RankGuild;
		$this->db->sql_query($sql);

		// log the action

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_RANK_DELETED',
				'log_action' => [$this->RankName],
			)
		);

		unset($bbguild);

		return true;

	}

	/**
	 * updates a rank
	 *
	 * @param  ranks $old_rank
	 * @return boolean
	 */
	public function Rankupdate(ranks $old_rank)
	{
		$sql_ary = array(
		'rank_id' => $this->RankId ,
		'guild_id' => $this->RankGuild ,
		'rank_name' => $this->RankName ,
		'rank_hide' => $this->RankHide ,
		'rank_prefix' => $this->RankPrefix ,
		'rank_suffix' => $this->RankSuffix
		);

		$sql = 'UPDATE ' . $this->bb_ranks_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE rank_id=' . (int) $old_rank->RankId . '
			AND guild_id = ' . (int) $old_rank->RankGuild;
		$this->db->sql_query($sql);

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_RANK_UPDATED',
				'log_action' => [$old_rank->RankName, $this->RankName],
			)
		);

		unset($bbguild);
		return true;

	}

	/**
	 * returns rank array
	*
	 * @return array
	 */
	public function listranks()
	{
		// rank 99 is the out-rank
		$sql = 'SELECT rank_id, rank_name, rank_hide, rank_prefix, rank_suffix, guild_id FROM ' . $this->bb_ranks_table . '
	        		WHERE guild_id = ' . (int) $this->RankGuild . '
	        		ORDER BY rank_id, rank_hide  ASC ';

		return $this->db->sql_query($sql);

	}

	/**
	 * counts players in guild with a given rank
	 */
	public function countplayers()
	{
		$sql = 'SELECT count(*) as countm FROM ' . $this->bb_players_table . '
			WHERE player_rank_id = ' . (int) $this->RankId . ' and player_guild_id = ' . (int) $this->RankGuild;
		$result = $this->db->sql_query($sql);
		$countm = (int) $this->db->sql_fetchfield('countm');
		$this->db->sql_freeresult($result);

		return $countm;
	}


}
