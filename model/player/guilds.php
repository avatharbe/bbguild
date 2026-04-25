<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Guilds class
 *
 */

namespace avathar\bbguild\model\player;

use avathar\bbguild\model\games\game;
use avathar\bbguild\model\games\game_api_interface;
use avathar\bbguild\model\games\game_provider_interface;

/**
 * Class guilds
 * @package avathar\bbguild\model\player
 */
class guilds
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;
	/** @var \phpbb\user */
	protected $user;
	/** @var \phpbb\config\config */
	protected $config;
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;
	/** @var \avathar\bbguild\model\admin\log */
	protected $log;

	public $bb_players_table;
	public $bb_ranks_table;
	public $bb_classes_table;
	public $bb_races_table;
	public $bb_language_table;
	public $bb_guild_table;
	public $bb_factions_table;

	/**
	 * guild game id
	 *
	 * @var string
	 */
	public $game_id = '';
	/**
	 * Guild pk
	 *
	 * @var int
	 */
	public $guildid = 0;
	/**
	 * guild name
	 *
	 * @var string
	 */
	protected $name = '';
	/**
	 * guiled realm
	 *
	 * @var string
	 */
	protected $realm = '';
	/**
	 * guild region
	 *
	 * @var string
	 */
	protected $region = '';
	/**
	 * guild achievements
	 *
	 * @var int
	 */
	protected $achievements = 0;
	/**
	 * guild player count
	 *
	 * @var int
	 */
	protected $playercount = 0;
	/**
	 * guild start date
	 *
	 * @var int
	 */
	protected $startdate = 0;
	/**
	 * guild on roster ?
	 *
	 * @var int 1 or 0
	 */
	protected $showroster = 0;
	/**
	 * min. level on roster
	 *
	 * @var int
	 */
	protected $min_armory = 0;
	/**
	 * does guild recruit ?
	 *
	 * @var int 1 or 0
	 */
	protected $recstatus = 1;
	/**
	 * is this the default guild ?
	 *
	 * @var int 1 or 0
	 */
	protected $guilddefault = 1;

	/**
	 * guild emblem image path
	 */
	protected $emblempath = '';
	/**
	 * guild players
	 *
	 * @var array
	 */
	protected $playerdata = array();
	/**
	 * guild faction
	 *
	 * @var int 0 or 1
	 */
	protected $faction = 0;

	/**
	 * holds recruitment statuses
	 *
	 * @var array
	 */
	protected $possible_recstatus = array();

	/**
	 * true if armory is on
	 *
	 * @var boolean
	 */
	protected $armory_enabled = false;

	/**
	 * rank to which raidtracker should add new attendees
	 *
	 * @var int
	 */
	protected $raidtrackerrank = 0;

	/**
	 * rank to which apply should add new recruits
	 *
	 * @var int
	 */
	protected $applyrank = 0;
	/**
	 * search result Battle.NET
	 *
	 * @var string
	 */
	protected $armoryresult = '';

	/**
	 * default recruitment forum. this is the forum linked to in the recruitment block
	 * you can install the Apply plugin to further customise the application process.
	 *
	 * @var int
	 */
	protected $recruitforum = 0;

	/**
	 * @type string
	 */
	protected $factionname = '';

	/**
	 * Game edition (e.g. 'retail', 'classic_era', 'classic_prog', 'classic_ann')
	 *
	 * @var string
	 */
	protected $game_edition = 'retail';

	/**
	 * @return int
	 */
	public function getStartdate()
	{
		return $this->startdate;
	}

	/**
	 * @param int $startdate
	 */
	public function setStartdate($startdate)
	{
		$this->startdate = (int) $startdate;
	}

	/**
	 * @return int
	 */
	public function getShowroster()
	{
		return $this->showroster;
	}

	/**
	 * @param int $showroster
	 */
	public function setShowroster($showroster)
	{
		$this->showroster = (int) $showroster;
	}

	/**
	 * @return int
	 */
	public function getRecstatus()
	{
		return  $this->recstatus;
	}

	/**
	 * @param int $recstatus
	 */
	public function setRecstatus($recstatus)
	{
		$this->recstatus = (int) $recstatus;
	}

	/**
	 * @return string
	 */
	public function getRegion()
	{
		return $this->region;
	}

	/**
	 * @param string $region
	 */
	public function setRegion($region)
	{
		$this->region = $region;
	}

	/**
	 * @return int
	 */
	public function getAchievements()
	{
		return $this->achievements;
	}

	/**
	 * @param int $achievements
	 */
	public function setAchievements($achievements)
	{
		$this->achievements = (int) $achievements;
	}

	/**
	 * @return int
	 */
	public function getApplyrank()
	{
		return $this->applyrank;
	}

	/**
	 * @param int $applyrank
	 */
	public function setApplyrank($applyrank)
	{
		$this->applyrank = (int) $applyrank;
	}

	/**
	 * @return boolean
	 */
	public function isArmoryEnabled()
	{
		return $this->armory_enabled;
	}

	/**
	 * @param boolean $armory_enabled
	 */
	public function setArmoryEnabled($armory_enabled)
	{
		$this->armory_enabled = (int) $armory_enabled;
	}

	/**
	 * @return string
	 */
	public function getArmoryresult()
	{
		return $this->armoryresult;
	}

	/**
	 * @param string $armoryresult
	 */
	public function setArmoryresult($armoryresult)
	{
		$this->armoryresult = (string) $armoryresult;
	}

	/**
	 * @return string
	 */
	public function getEmblempath()
	{
		return $this->emblempath;
	}

	/**
	 * @param string $emblempath
	 */
	public function setEmblempath($emblempath)
	{
		$this->emblempath = (string) $emblempath;
	}

	/**
	 * @return int
	 */
	public function getFaction()
	{
		return $this->faction;
	}

	/**
	 * @param int $faction
	 */
	public function setFaction($faction)
	{
		$this->faction = (int) $faction;
	}

	/**
	 * @return string
	 */
	public function getFactionname()
	{
		return $this->factionname;
	}

	/**
	 * @param string $factionname
	 */
	public function setFactionname($factionname)
	{
		$this->factionname = (string) $factionname;
	}

	/**
	 * @return string
	 */
	public function getGameEdition()
	{
		return $this->game_edition;
	}

	/**
	 * @param string $game_edition
	 */
	public function setGameEdition($game_edition)
	{
		$this->game_edition = (string) $game_edition;
	}

	/**
	 * @return int
	 */
	public function getGameId()
	{
		return $this->game_id;
	}

	/**
	 * @param int $game_id
	 */
	public function setGameId($game_id)
	{
		$this->game_id = (string) $game_id;
	}

	/**
	 * @return int
	 */
	public function getGuilddefault()
	{
		return $this->guilddefault;
	}

	/**
	 * @param int $guilddefault
	 */
	public function setGuilddefault($guilddefault)
	{
		$this->guilddefault = (int) $guilddefault;
	}

	/**
	 * @return int
	 */
	public function getGuildid()
	{
		return $this->guildid;
	}

	/**
	 * @param int $guildid
	 */
	public function setGuildid($guildid)
	{
		$this->guildid = (int) $guildid;
	}

	/**
	 * @return int
	 */
	public function getMinArmory()
	{
		return $this->min_armory;
	}

	/**
	 * @param int $min_armory
	 */
	public function setMinArmory($min_armory)
	{
		$this->min_armory = (int) $min_armory;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return int
	 */
	public function getPlayercount()
	{
		return $this->playercount;
	}

	/**
	 * @param int $playercount
	 */
	public function setPlayercount($playercount)
	{
		$this->playercount = (int) $playercount;
	}

	/**
	 * @return array
	 */
	public function getPlayerdata()
	{
		return $this->playerdata;
	}

	/**
	 * @param array $playerdata
	 */
	public function setPlayerdata($playerdata)
	{
		$this->playerdata = $playerdata;
	}

	/**
	 * @return array
	 */
	public function getPossibleRecstatus()
	{
		return $this->possible_recstatus;
	}

	/**
	 * @param array $possible_recstatus
	 */
	public function setPossibleRecstatus($possible_recstatus)
	{
		$this->possible_recstatus = $possible_recstatus;
	}

	/**
	 * @return int
	 */
	public function getRaidtrackerrank()
	{
		return $this->raidtrackerrank;
	}

	/**
	 * @param int $raidtrackerrank
	 */
	public function setRaidtrackerrank($raidtrackerrank)
	{
		$this->raidtrackerrank = (int) $raidtrackerrank;
	}

	/**
	 * @return string
	 */
	public function getRealm()
	{
		return $this->realm;
	}

	/**
	 * @param string $realm
	 */
	public function setRealm($realm)
	{
		$this->realm = (string) $realm;
	}

	/**
	 * @return int
	 */
	public function getRecruitforum()
	{
		return $this->recruitforum;
	}

	/**
	 * @param int $recruitforum
	 */
	public function setRecruitforum($recruitforum)
	{
		$this->recruitforum = $recruitforum;
	}

	// pulling guild achievements from api is met by the achievements class

	/**
	 * guilds constructor.
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\user $user
	 * @param \phpbb\config\config $config
	 * @param \phpbb\cache\driver\driver_interface $cache
	 * @param \avathar\bbguild\model\admin\log $log
	 * @param string $bb_players_table
	 * @param string $bb_ranks_table
	 * @param string $bb_classes_table
	 * @param string $bb_races_table
	 * @param string $bb_language_table
	 * @param string $bb_guild_table
	 * @param string $bb_factions_table
	 * @param int $guild_id
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\config\config $config,
		\phpbb\cache\driver\driver_interface $cache,
		\avathar\bbguild\model\admin\log $log,
		$bb_players_table, $bb_ranks_table, $bb_classes_table, $bb_races_table, $bb_language_table, $bb_guild_table, $bb_factions_table, $guild_id = 0)
	{
		$this->db = $db;
		$this->user = $user;
		$this->config = $config;
		$this->cache = $cache;
		$this->log = $log;
		$this->bb_players_table = $bb_players_table;
		$this->bb_ranks_table = $bb_ranks_table;
		$this->bb_classes_table = $bb_classes_table;
		$this->bb_races_table = $bb_races_table;
		$this->bb_language_table = $bb_language_table;
		$this->bb_guild_table = $bb_guild_table;
		$this->bb_factions_table = $bb_factions_table;
		$this->guildid = $guild_id;
		if ($guild_id > 0)
		{
			$this->get_guild();
		}

		$this->possible_recstatus = array(
			0 => $this->user->lang['CLOSED'] ,
			1 => $this->user->lang['OPEN']);

	}

	/**
	 * Call guild API endpoint.
	 *
	 * When a game_provider_interface with an API is given, delegates to
	 * game_api_interface::fetch_guild_data(). Otherwise falls back to
	 * the legacy Battle.net client.
	 *
	 * @param array $params
	 * @param \avathar\bbguild\model\games\game $game
	 * @param game_provider_interface|null $provider Optional game provider with API
	 * @return bool|array
	 */
	public function Call_Guild_API($params, game $game, ?game_provider_interface $provider = null)
	{
		$data= 0;

		if ( (!$game->getArmoryEnabled()) || trim((string) $game->getApikey()) == '' || trim((string) $game->get_apilocale()) == '')
		{
			$this->armoryresult = 'KO';
			return false;
		}

		//is this guild armory-enabled ?
		if ($this->armory_enabled == 0)
		{
			$this->armoryresult = 'KO';
			return false;
		}

		// Pass edition to the API so it can use the correct namespace
		$params['edition'] = $this->game_edition;

		// Delegate to game provider's API
		if ($provider !== null && $provider->has_api())
		{
			$api = $provider->get_api();
			$data = $api->fetch_guild_data($this->name, $this->realm, $this->region, $params);
		}
		else
		{
			$this->armoryresult = 'KO';
			return false;
		}

		if (!isset($data))
		{
			$this->armoryresult = 'KO';
			$this->log->log_insert(
				array(
					'log_type'   => 'L_ERROR_ARMORY_DOWN',
					'log_action' => [],
					'log_result' => 'L_ERROR',
				)
			);
			return false;
		}

		//if we get error code
		if (isset($data['code']))
		{
			if ($data['code'] == '403')
			{
				// even if we have active API account, it may be that Blizzard account is inactive
				$this->armoryresult = 'KO';
				$this->armory_enabled = false;
				$this->log->log_insert(
					array(
						'log_type'   => 'L_ERROR_BATTLENET_ACCOUNT_INACTIVE',
						'log_action' => [$this->name . '-' . $this->realm],
						'log_result' => 'L_ERROR',
					)
				);
				return false;
			}
		}

		if (!is_array($data))
		{
			$this->armoryresult = 'KO';
			return false;
		}

		if (isset($data['status']))
		{
			$this->armoryresult = 'KO';
			return false;
		}

		$this->armoryresult = 'OK';

		return $data;
	}

	/**
	 * Update the guild object from API data.
	 *
	 * Delegates processing to game_api_interface::process_guild_data() and
	 * saves generic fields to bb_guild. Game-specific fields are persisted
	 * by calling save_guild_extension() on the provider's API.
	 *
	 * @param array $data Raw API response
	 * @param array $params Request parameters (e.g. ['members'])
	 * @param game_provider_interface $provider Game provider with API
	 */
	public function update_guild_battleNet(array $data, $params, game_provider_interface $provider)
	{

		if ($this->armoryresult == 'KO')
		{
			return;
		}

		if (!$provider->has_api())
		{
			return;
		}

		$api = $provider->get_api();
		$processed = $api->process_guild_data($data, $params);

		$this->faction = isset($processed['faction']) ? $processed['faction'] : $this->faction;
		$this->emblempath = isset($processed['emblempath']) ? $processed['emblempath'] : '';
		if (isset($processed['playercount']))
		{
			$this->playercount = $processed['playercount'];
		}

		// Save generic guild fields to bb_guild
		$query = $this->db->sql_build_array(
			'UPDATE', array(
				'emblemurl'         => $this->emblempath,
				'armoryresult'      => $this->armoryresult,
				'players'           => $this->playercount,
				'faction'           => $this->faction,
			)
		);
		$this->db->sql_query('UPDATE ' . $this->bb_guild_table . ' SET ' . $query . ' WHERE id= ' . $this->guildid);

		// Let the provider's API persist game-specific guild fields
		$api->save_guild_extension($this->guildid, $processed);

		// Let the provider's API handle member sync if 'members' was requested
		if (in_array('members', $params, true) && isset($data['members']))
		{
			$api->sync_guild_members($data['members'], $this->guildid, $this->region, $this->min_armory);
		}
	}


	/**
	 * inserts a new guild to database
	 *
	 * we always add guilds with an id greater than zero. this way, the guild with id=zero is the "guildless" guild
	 * the zero guild is added by default in a new install.
	 * do not delete the zero record in the guild table or you will see that guildless players
	 * become invisible in the roster and in the playerlist or in any list player selection that makes
	 * an inner join with the guild table.
	 *
	 * @return integer
	 */
	public function make_guild()
	{
		if ($this->name == null || $this->realm == null)
		{
			trigger_error($this->user->lang['ERROR_GUILDEMPTY'], E_USER_WARNING);
		}
		$this->cache->destroy('sql', $this->bb_guild_table);

		// check existing guild-realmname
		$result = $this->db->sql_query(
			'SELECT count(*) as evcount from ' . $this->bb_guild_table . "
			WHERE id !=0 AND UPPER(name) = '" . strtoupper($this->db->sql_escape($this->name)) . "'
			AND UPPER(realm) = '" . strtoupper($this->db->sql_escape($this->realm)) . "'"
		);
		$grow = $this->db->sql_fetchrow($result);

		if ($grow['evcount'] != 0)
		{
			trigger_error($this->user->lang['ERROR_GUILDTAKEN'], E_USER_WARNING);
		}

		$result = $this->db->sql_query('SELECT MAX(id) as id FROM ' . $this->bb_guild_table);
		$row = $this->db->sql_fetchrow($result);
		$this->guildid = (int) $row['id'] + 1;
		$query = $this->db->sql_build_array(
			'INSERT', array(
				'id' => $this->guildid,
				'name' => $this->name ,
				'realm' => $this->realm,
				'region' => $this->region ,
				'roster' => $this->showroster,
				'players' => $this->playercount,
				'emblemurl' => $this->emblempath,
				'game_id' => $this->game_id,
				'game_edition' => $this->game_edition,
				'min_armory' => $this->min_armory,
				'rec_status' => $this->recstatus,
				'guilddefault' => $this->guilddefault,
				'armory_enabled' => $this->armory_enabled,
				'armoryresult' => $this->armoryresult,
				'recruitforum' => $this->recruitforum,
				'faction' => $this->faction
			)
		);

		$this->db->sql_query('INSERT INTO ' . $this->bb_guild_table . $query);

		$newrank = new ranks($this->db, $this->user, $this->cache, $this->log, $this->bb_players_table, $this->bb_ranks_table, $this->guildid);
		// add guildleader rank
		$newrank->RankName = $this->user->lang['GUILDLEADER'];
		$newrank->RankId = 0;
		$newrank->RankHide = 0;
		$newrank->RankPrefix = '';
		$newrank->RankSuffix = '';
		$newrank->Makerank();

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_GUILD_ADDED',
				'log_action' => [$this->realm . '-' . $this->name],
			)
		);

	}

	/**
	 * updates a guild to database
	 *
	 * @param  guilds $old_guild
	 * @return bool
	 */
	public function update_guild(guilds $old_guild)
	{
		$this->cache->destroy('sql', $this->bb_guild_table);

		// check if already exists
		if ($this->name != $old_guild->name || $this->realm != $old_guild->realm)
		{
			// check existing guild-realmname
			$result = $this->db->sql_query(
				'SELECT count(*) as evcount from ' . $this->bb_guild_table . "
				WHERE UPPER(name) = '" . strtoupper($this->db->sql_escape($this->name)) . "'
				AND UPPER(realm) = '" . strtoupper($this->db->sql_escape($this->realm)) . "'"
			);
			$grow = $this->db->sql_fetchrow($result);
			if ($grow['evcount'] != 0)
			{
				trigger_error($this->user->lang['ERROR_GUILDTAKEN'], E_USER_WARNING);
			}
		}
		$this->count_players();

		$query = $this->db->sql_build_array(
			'UPDATE', array(
				'id' => $this->guildid,
				'name' => $this->name ,
				'realm' => $this->realm,
				'region' => $this->region ,
				'roster' => $this->showroster,
				'players' => $this->playercount,
				'emblemurl' => $this->emblempath,
				'game_id' => $this->game_id,
				'game_edition' => $this->game_edition,
				'min_armory' => $this->min_armory,
				'rec_status' => $this->recstatus,
				'guilddefault' => $this->guilddefault,
				'armory_enabled' => $this->armory_enabled,
				'armoryresult' => $this->armoryresult,
				'recruitforum' => $this->recruitforum,
				'faction' => $this->faction
			)
		);

		$this->db->sql_query('UPDATE ' . $this->bb_guild_table . ' SET ' . $query . ' WHERE id= ' . $this->guildid);
		return true;
	}


	/**
	 * updates the default guild flag
	 *
	 * @param int $id
	 */
	public function update_guilddefault($id)
	{
		$this->cache->destroy('sql', $this->bb_guild_table);
		$sql = 'UPDATE ' . $this->bb_guild_table . ' SET guilddefault = 1 WHERE id = ' . (int) $id;
		$this->db->sql_query($sql);

		$sql = 'UPDATE ' . $this->bb_guild_table . ' SET guilddefault = 0 WHERE id != ' . (int) $id;
		$this->db->sql_query($sql);
	}

	/**
	 * deletes a guild from database
	 */
	public function delete_guild()
	{
		if ($this->guildid == 0)
		{
			trigger_error($this->user->lang['ERROR_INVALID_GUILD_PROVIDED'], E_USER_WARNING);
		}
		$this->cache->destroy('sql', $this->bb_guild_table);
		// check if guild has players
		$sql = 'SELECT COUNT(*) as mcount FROM ' . $this->bb_players_table . '
           WHERE player_guild_id = ' . $this->guildid;
		$result = $this->db->sql_query($sql);
		if ((int) $this->db->sql_fetchfield('mcount') >= 1)
		{
			trigger_error($this->user->lang['ERROR_GUILDHASPLAYERS'], E_USER_WARNING);
		}
		$this->db->sql_freeresult($result);

		$sql = 'DELETE FROM ' . $this->bb_ranks_table . ' WHERE guild_id = ' .  $this->guildid;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->bb_guild_table . ' WHERE id = ' .  $this->guildid;
		$this->db->sql_query($sql);

		// Clean up emblem file from stored path or legacy location
		global $phpbb_root_path;
		$emblem_deleted = false;
		if (!empty($this->emblempath) && strpos($this->emblempath, 'bbguild_wow/emblems/') !== false)
		{
			$imgfile = $phpbb_root_path . $this->emblempath;
			if (file_exists($imgfile))
			{
				@unlink($imgfile);
				$emblem_deleted = true;
			}
		}

		// Also try legacy location
		if (!$emblem_deleted)
		{
			$imgfile = $phpbb_root_path . 'ext/avathar/bbguild/images/guildemblem/' . $this->region . '_' . $this->realm . '_' . str_replace(' ', '_', $this->name) . '.png';
			if (file_exists($imgfile))
			{
				@unlink($imgfile);
			}
		}

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_GUILD_DELETED',
				'log_action' => [$this->name],
			)
		);
	}

	/**
	 * gets a guild from database
	 * used in sidebar
	 * cached for 7 days
	 */
	public function get_guild()
	{
		$sql = 'SELECT g.id, g.name, g.realm, g.region, g.roster, g.game_id, g.game_edition, g.players,
				g.emblemurl, g.min_armory, g.rec_status, g.guilddefault, g.armory_enabled, g.armoryresult, g.recruitforum,
				g.faction, f.faction_name
				FROM ' . $this->bb_guild_table . ' g
				LEFT JOIN '  . $this->bb_factions_table . ' f ON f.game_id=g.game_id and f.faction_id=g.faction
				WHERE id = ' . $this->guildid;
		$result = $this->db->sql_query($sql, 604800);

		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		if ($row)
		{
			// load guild object
			$this->game_id = $row['game_id'];
			$this->game_edition = isset($row['game_edition']) ? $row['game_edition'] : 'retail';
			$this->guildid = $row['id'];
			$this->name = $row['name'];
			$this->realm = $row['realm'];
			$this->region = $row['region'];
			$this->showroster = $row['roster'];
			$this->emblempath = $row['emblemurl'];
			$this->min_armory = $row['min_armory'];
			$this->recstatus = $row['rec_status'];
			$this->armory_enabled = $row['armory_enabled'];
			$this->armoryresult = $row['armoryresult'];
			$this->count_players();
			$this->guilddefault = $row['guilddefault'];
			$this->raidtrackerrank = $this->maxrank();
			$this->applyrank = $this->maxrank();
			$this->recruitforum = $row['recruitforum'];
			$this->faction = $row['faction'];
			$this->factionname = $row['faction_name'];
		}

	}

	/**
	 * returns a player listing for this guild
	 *
	 * @param  string $order
	 * @param  int    $start
	 * @param  int    $mode
	 * @param  int    $minlevel
	 * @param  int    $maxlevel
	 * @param  int    $selectactive
	 * @param  int    $selectnonactive
	 * @param  string $player_filter
	 * @param  bool   $last_update
	 * @return array
	 */
	public function list_players($order = 'm.player_name', $start = 0, $mode = 0, $minlevel = 1, $maxlevel = 200, $selectactive = 1, $selectnonactive = 1, $player_filter = '', $last_update = false)
	{
		$sql_array = array(
			'SELECT' => 'm.* , u.username, u.user_id, u.user_colour, g.name, l.name as player_class, r.rank_id,
			    				r.rank_name, r.rank_prefix, r.rank_suffix,
								 c.colorcode , c.imagename, a.image_female, a.image_male' ,
			'FROM' => array(
				$this->bb_players_table  => 'm' ,
				$this->bb_ranks_table  => 'r' ,
				$this->bb_classes_table  => 'c' ,
				$this->bb_races_table => 'a' ,
				$this->bb_language_table => 'l' ,
				$this->bb_guild_table => 'g') ,
			'LEFT_JOIN' => array(
				array(
					'FROM' => array(
						USERS_TABLE => 'u') ,
					'ON' => 'u.user_id = m.phpbb_user_id ')) ,
			'WHERE' => " (m.player_rank_id = r.rank_id)
			    				and m.game_id = l.game_id
			    				AND l.attribute_id = c.class_id and l.game_id = c.game_id AND l.language= '" . $this->config['bbguild_lang'] . "' AND l.attribute = 'class'
								AND (m.player_guild_id = g.id)
								AND (m.player_guild_id = r.guild_id)
								AND (m.player_guild_id = " . $this->guildid . ')
								AND m.game_id =  a.game_id
								AND m.game_id =  c.game_id
								AND m.player_race_id =  a.race_id
								AND (m.player_class_id = c.class_id)
								AND m.player_level >= ' . $minlevel . '
								AND m.player_level <= ' . $maxlevel,
			'ORDER_BY' => $order);

		if ($selectactive == 0 && $selectnonactive == 1)
		{
			$sql_array['WHERE'] .= ' AND m.player_status = 0 ';
		}
		else if ($selectactive == 1 && $selectnonactive == 0)
		{
			$sql_array['WHERE'] .= ' AND m.player_status = 1 ';
		}
		else if ($selectactive == 1 && $selectnonactive == 1)
		{
			$sql_array['WHERE'] .= ' AND 1=1 ';
		}
		else if ($selectactive == 0 && $selectnonactive == 0)
		{
			$sql_array['WHERE'] .= ' AND 1=0 ';
		}

		if ($last_update)
		{
			$sql_array['WHERE'] .= ' AND m.last_update >= 0 and m.last_update < ' . ($this->time - 900) ;
		}

		if ($player_filter != '')
		{
			$sql_array['WHERE'] .= ' AND lcase(m.player_name) ' . $this->db->sql_like_expression($this->db->get_any_char() . $this->db->sql_escape(mb_strtolower($player_filter)) . $this->db->get_any_char());
		}

		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		if ($mode == 1)
		{
			$players_result = $this->db->sql_query_limit($sql, $this->config['bbguild_user_llimit'], $start);
		}
		else
		{
			$players_result = $this->db->sql_query($sql);
		}

		return $players_result;

	}

	/**
	 * returns a class distribution array for this guild
	 *
	 * @return array
	 */
	public function class_distribution()
	{
		$sql = 'SELECT c.class_id, ';
		$sql .= ' l.name                   AS classname, ';
		$sql .= ' Count(m.player_class_id) AS classcount ';
		$sql .= ' FROM  ' . $this->bb_classes_table . ' c ';
		$sql .= ' INNER JOIN ' . $this->bb_guild_table . ' g ON c.game_id = g.game_id ';
		$sql .= ' LEFT OUTER JOIN (SELECT * FROM ' . $this->bb_players_table . ' WHERE player_level >= ' . $this->min_armory . ') m';
		$sql .= '   ON m.game_id = c.game_id  AND m.player_class_id = c.class_id  ';
		$sql .= ' INNER JOIN ' . $this->bb_language_table . ' l ON  l.attribute_id = c.class_id AND l.game_id = c.game_id ';
		$sql .= ' WHERE  1=1 ';
		$sql .= " AND l.language = '" . $this->config['bbguild_lang']."' AND l.attribute = 'class' ";
		$sql .= ' AND g.id =  ' . $this->guildid;
		$sql .= ' GROUP  BY c.class_id, l.name ';
		$sql .= ' ORDER  BY c.class_id ASC ';

		$result = $this->db->sql_query($sql);
		$classes = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$classes[$row['class_id']] = array(
				'classname' => $row['classname'],
				'classcount' => $row['classcount']
			);
		}
		$this->db->sql_freeresult($result);
		return $classes;
	}

	/**
	 * counts all guild players
	 */
	private function count_players()
	{
		//get total players
		$sql_array = array(
			'SELECT' => 'count(*) as playercount ' ,
			'FROM' => array(
				$this->bb_players_table => 'm' ,
				$this->bb_ranks_table => 'r' ,
				$this->bb_classes_table => 'c' ,
				$this->bb_races_table => 'a' ,
				$this->bb_language_table => 'l' ,
				$this->bb_guild_table => 'g') ,
			'LEFT_JOIN' => array(
				array(
					'FROM' => array(
						USERS_TABLE => 'u') ,
					'ON' => 'u.user_id = m.phpbb_user_id ')) ,
			'WHERE' => " (m.player_rank_id = r.rank_id)
				    				and m.game_id = l.game_id
				    				AND l.attribute_id = c.class_id and l.game_id = c.game_id AND l.language= '" . $this->config['bbguild_lang'] . "' AND l.attribute = 'class'
									AND (m.player_guild_id = g.id)
									AND (m.player_guild_id = r.guild_id)
									AND (m.player_guild_id = " . $this->guildid . ')
									AND m.game_id =  a.game_id
									AND m.game_id =  c.game_id
									AND m.player_race_id =  a.race_id
									AND (m.player_class_id = c.class_id)');
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$total_players = (int) $this->db->sql_fetchfield('playercount');
		$this->db->sql_freeresult($result);
		$this->playercount = $total_players;
		return $total_players;
	}

	/**
	 * get default rank to add new player to
	 *
	 * @return number
	 */
	private function maxrank()
	{
		$sql = 'SELECT MAX(player_rank_id) AS rank_id FROM ' . $this->bb_players_table . ' WHERE player_guild_id = ' . (int) $this->guildid . ' AND player_rank_id != 90';
		$result = $this->db->sql_query($sql);
		$defaultrank_id = (int) $this->db->sql_fetchfield('rank_id', false, $result);
		$this->db->sql_freeresult($result);
		return $defaultrank_id;
	}

	/**
	 * gets list of guilds, used in dropdowns
	 *
	 * @param  int $guild_id, defqults to zero, to include noguild
	 * @return array
	 */
	public function guildlist($guild_id = 0)
	{
		$sql_array = array(
			'SELECT' => 'a.game_id, a.guilddefault, a.id, a.name, a.realm, a.region, count(c.player_id) as playercount, max(b.rank_id) as joinrank ' ,
			'FROM' => array(
				$this->bb_guild_table => 'a' ,
				$this->bb_ranks_table => 'b' ,),
			'LEFT_JOIN' => array(
				array(
					'FROM'  => array($this->bb_players_table => 'c'),
					'ON'    => 'a.id = c.player_guild_id '
				)
			),
			'WHERE' => ' a.id = b.guild_id AND b.rank_id != 90 and b.guild_id >= ' . $guild_id,
			'GROUP_BY' => ' a.game_id, a.guilddefault, a.id, a.name, a.realm, a.region ',
			'ORDER_BY' => ' a.guilddefault desc,  count(c.player_id) desc, a.id asc'
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql, 604800);
		$guildlist = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$guildlist[] = array (
				'game_id' => $row['game_id'] ,
				'id' => $row['id'] ,
				'name' => $row['name'],
				'guilddefault' => $row['guilddefault'],
				'playercount' => $row['playercount'],
				'realm' => $row['realm'],
				'joinrank' => $row['joinrank'],
			);
		}
		$this->db->sql_freeresult($result);
		return $guildlist;
	}



}
