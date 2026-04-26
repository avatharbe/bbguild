<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player class file
 *
 */

namespace avathar\bbguild\model\player;

use avathar\bbguild\model\games\game;
use avathar\bbguild\model\games\game_provider_interface;

/**
 * manages player creation
 *
 * @package  avathar\bbguild\model\player
 * @property array $playerdata
 * @property int $game_id
 * @property int $player_id
 * @property string $player_name
 * @property bool $player_status
 * @property int $player_level
 * @property int $player_race_id
 * @property string $player_race
 * @property int $player_class_id
 * @property int $player_rank_id
 * @property string $player_comment
 * @property int $player_joindate
 * @property int $player_joindate_d
 * @property int $player_joindate_mo
 * @property int $player_joindate_y
 * @property int $player_outdate
 * @property int $player_outdate_d
 * @property int $player_outdate_mo
 * @property int $player_outdate_y
 * @property int $player_guild_id
 * @property string $player_guild_name
 * @property string $player_realm
 * @property string $player_region
 * @property array $regionlist
 * @property int $player_gender_id
 * @property int $player_achiev
 * @property string $player_armory_url
 * @property string $player_portrait_url
 * @property int $phpbb_user_id
 * @property string $colorcode
 * @property string $race_image
 * @property string $class_image
 * @property string $talents
 * @property string $player_title
 * @property string $deactivate_reason
 * @property int $last_update
 * @property string $player_role
 * @property array $guildplayerlist
 * @property array $guildlist
 */
class player
{

	public $bb_players_table;
	public $bb_ranks_table;
	public $bb_classes_table;
	public $bb_races_table;
	public $bb_language_table;
	public $bb_guild_table;
	public $bb_factions_table;
	public $bb_games_table;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;
	/** @var \phpbb\config\config */
	protected $config;
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;
	/** @var \phpbb\user */
	protected $user;
	/** @var \phpbb\extension\manager */
	protected $phpbb_extension_manager;
	/** @var \avathar\bbguild\model\admin\log */
	protected $log;
	/** @var \avathar\bbguild\model\admin\util */
	protected $util;
	/** @var \avathar\bbguild\model\games\game_registry|null */
	protected $game_registry;

	/**
	 * game id
	 *
	 * @var string
	 */
	public $game_id;

	/**
	 * primary key in the bbDKP player table
	 *
	 * @var integer
	 */
	public $player_id;

	/**
	 * utF-8 player name
	 *
	 * @var string
	 */
	protected $player_name;

	/**
	 * status (0 or 1)
	 *
	 * @var bool
	 */
	protected $player_status;

	/**
	 * level
	 *
	 * @var int
	 */
	protected $player_level;

	/**
	 * race id
	 *
	 * @var integer
	 */
	protected $player_race_id;
	/**
	 * race name
	 *
	 * @var string
	 */
	protected $player_race;
	/**
	 * Class id
	 *
	 * @var integer
	 */
	protected $player_class_id;

	/**
	 * Class name
	 *
	 * @var string
	 */
	protected $player_class;

	/**
	 * Specialization (subclass) id, FK to bb_specializations.
	 * 0 = unset.
	 *
	 * @var int
	 */
	protected $player_spec_id = 0;

	/**
	 * guild rankid
	 *
	 * @var int
	 */
	protected $player_rank_id;

	/**
	 * administrator comment
	 *
	 * @var string
	 */
	protected $player_comment;

	/**
	 * player guild join date
	 *
	 * @var integer
	 */
	protected $player_joindate;
	/**
	 * join day
	 *
	 * @var integer
	 */
	protected $player_joindate_d;
	/**
	 * join month
	 *
	 * @var integer
	 */
	protected $player_joindate_mo;
	/**
	 * join year
	 *
	 * @var integer
	 */
	protected $player_joindate_y;
	/**
	 * out date
	 *
	 * @var int
	 */
	protected $player_outdate;
	/**
	 * out day
	 *
	 * @var integer
	 */
	protected $player_outdate_d;
	/**
	 * out month
	 *
	 * @var integer
	 */
	protected $player_outdate_mo;
	/**
	 * out year
	 *
	 * @var integer
	 */
	protected $player_outdate_y;

	/**
	 * the id of my guild
	 *
	 * @var int
	 */
	protected $player_guild_id;

	/**
	 * my guildname
	 *
	 * @var string
	 */
	protected $player_guild_name;

	/**
	 * character realm
	 *
	 * @var string
	 */
	protected $player_realm;

	/**
	 * region to which the char is on
	 *
	 * @var string
	 */
	protected $player_region;


	/**
	 * Allowed regions
	 *
	 * @var array
	 */
	protected $regionlist;

	/**
	 *gender ID 0=male, 1=female
	 *
	 * @var int
	 */
	protected $player_gender_id;

	/**
	 * Achievement points
	 *
	 * @var int
	 */
	protected $player_achiev;

	/**
	 * url to armory
	 *
	 * @var string
	 */
	protected $player_armory_url;

	/**
	 * (wow) battle.net portrait url
	 *
	 * @var string
	 */
	protected $player_portrait_url;

	/**
	 * The phpBB player id linked to this player
	 *
	 * @var int
	 */
	protected $phpbb_user_id;

	/**
	 * Class color
	 *
	 * @var string
	 */
	protected $colorcode;

	/**
	 * Race icon
	 *
	 * @var string
	 */
	protected $race_image;

	/**
	 * Class icon
	 *
	 * @var string
	 */
	protected $class_image;

	/**
	 * current talent builds
	 *
	 * @var string
	 */
	protected $talents;

	/**
	 * current title
	 *
	 * @var string
	 */
	protected $player_title;


	/**
	 * reason for deactivating account
	 * choice between
	 * - "inactivity" (>90 days)
	 * - "other"
	 *
	 * @var string
	 */
	protected $deactivate_reason;

	/**
	 * datetime of last update of this player account
	 *
	 * @var int
	 */
	protected $last_update;

	/**
	 * the role (for possible roles see role class)
	 *
	 * @var string
	 */
	protected $player_role;

	/**
	 * contains list of players for guild x
	 *
	 * @var array
	 */
	public $guildplayerlist;

	/**
	 * array of guilds
	 *
	 * @var array
	 */
	public $guildlist;


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
		$this->game_id = $game_id;
	}

	/**
	 * @return string
	 */
	public function getPlayerName()
	{
		return $this->player_name;
	}

	/**
	 * @param string $player_name
	 */
	public function setPlayerName($player_name)
	{
		$this->player_name = $player_name;
	}

	/**
	 * @return boolean
	 */
	public function isPlayerStatus()
	{
		return $this->player_status;
	}

	/**
	 * @param boolean $player_status
	 */
	public function setPlayerStatus($player_status)
	{
		$this->player_status = $player_status;
	}

	/**
	 * @return int
	 */
	public function getPlayerLevel()
	{
		return $this->player_level;
	}

	/**
	 * @param int $player_level
	 */
	public function setPlayerLevel($player_level)
	{
		$this->player_level = $player_level;
	}

	/**
	 * @return int
	 */
	public function getPlayerRaceId()
	{
		return $this->player_race_id;
	}

	/**
	 * @param int $player_race_id
	 */
	public function setPlayerRaceId($player_race_id)
	{
		$this->player_race_id = $player_race_id;
	}


	/**
	 * @return string
	 */
	public function getTalents()
	{
		return $this->talents;
	}

	/**
	 * @param string $talents
	 */
	public function setTalents($talents)
	{
		$this->talents = $talents;
	}

	/**
	 * @return string
	 */
	public function getClassImage()
	{
		return $this->class_image;
	}

	/**
	 * @param string $class_image
	 */
	public function setClassImage($class_image)
	{
		$this->class_image = $class_image;
	}

	/**
	 * @return string
	 */
	public function getColorcode()
	{
		return $this->colorcode;
	}

	/**
	 * @param string $colorcode
	 */
	public function setColorcode($colorcode)
	{
		$this->colorcode = $colorcode;
	}

	/**
	 * @return string
	 */
	public function getDeactivateReason()
	{
		return $this->deactivate_reason;
	}

	/**
	 * @param string $deactivate_reason
	 */
	public function setDeactivateReason($deactivate_reason)
	{
		$this->deactivate_reason = $deactivate_reason;
	}


	/**
	 * @return array
	 */
	public function getGuildlist()
	{
		return $this->guildlist;
	}

	/**
	 * @param array $guildlist
	 */
	public function setGuildlist($guildlist)
	{
		$this->guildlist = $guildlist;
	}

	/**
	 * @return array
	 */
	public function getGuildplayerlist()
	{
		return $this->guildplayerlist;
	}

	/**
	 * @param array $guildplayerlist
	 */
	public function setGuildplayerlist($guildplayerlist)
	{
		$this->guildplayerlist = $guildplayerlist;
	}

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
	 * @return int
	 */
	public function getPhpbbUserId()
	{
		return $this->phpbb_user_id;
	}

	/**
	 * @param int $phpbb_user_id
	 */
	public function setPhpbbUserId($phpbb_user_id)
	{
		$this->phpbb_user_id = $phpbb_user_id;
	}

	/**
	 * @return int
	 */
	public function getPlayerAchiev()
	{
		return $this->player_achiev;
	}

	/**
	 * @param int $player_achiev
	 */
	public function setPlayerAchiev($player_achiev)
	{
		$this->player_achiev = $player_achiev;
	}

	/**
	 * @return string
	 */
	public function getPlayerArmoryUrl()
	{
		return $this->player_armory_url;
	}

	/**
	 * @param string $player_armory_url
	 */
	public function setPlayerArmoryUrl($player_armory_url)
	{
		$this->player_armory_url = $player_armory_url;
	}

	/**
	 * @return string
	 */
	public function getPlayerClass()
	{
		return $this->player_class;
	}

	/**
	 * @param string $player_class
	 */
	public function setPlayerClass($player_class)
	{
		$this->player_class = $player_class;
	}

	/**
	 * @return int
	 */
	public function getPlayerClassId()
	{
		return $this->player_class_id;
	}

	/**
	 * @param int $player_class_id
	 */
	public function setPlayerClassId($player_class_id)
	{
		$this->player_class_id = $player_class_id;
	}

	/**
	 * @return int FK to bb_specializations, or 0 when no spec is set
	 */
	public function getPlayerSpecId(): int
	{
		return (int) $this->player_spec_id;
	}

	/**
	 * @param int $player_spec_id
	 */
	public function setPlayerSpecId($player_spec_id): void
	{
		$this->player_spec_id = (int) $player_spec_id;
	}

	/**
	 * @return string
	 */
	public function getPlayerComment()
	{
		return $this->player_comment;
	}

	/**
	 * @param string $player_comment
	 */
	public function setPlayerComment($player_comment)
	{
		$this->player_comment = $player_comment;
	}

	/**
	 * @return int
	 */
	public function getPlayerGenderId()
	{
		return $this->player_gender_id;
	}

	/**
	 * @param int $player_gender_id
	 */
	public function setPlayerGenderId($player_gender_id)
	{
		$this->player_gender_id = $player_gender_id;
	}

	/**
	 * @return int
	 */
	public function getPlayerGuildId()
	{
		return $this->player_guild_id;
	}

	/**
	 * @param int $player_guild_id
	 */
	public function setPlayerGuildId($player_guild_id)
	{
		$this->player_guild_id = $player_guild_id;
	}

	/**
	 * @return string
	 */
	public function getPlayerGuildName()
	{
		return $this->player_guild_name;
	}

	/**
	 * @param string $player_guild_name
	 */
	public function setPlayerGuildName($player_guild_name)
	{
		$this->player_guild_name = $player_guild_name;
	}

	/**
	 * @return int
	 */
	public function getPlayerJoindate()
	{
		return $this->player_joindate;
	}

	/**
	 * @param int $player_joindate
	 */
	public function setPlayerJoindate($player_joindate)
	{
		$this->player_joindate = $player_joindate;
	}

	/**
	 * @return int
	 */
	public function getPlayerJoindateD()
	{
		return $this->player_joindate_d;
	}

	/**
	 * @param int $player_joindate_d
	 */
	public function setPlayerJoindateD($player_joindate_d)
	{
		$this->player_joindate_d = $player_joindate_d;
	}

	/**
	 * @return int
	 */
	public function getPlayerJoindateMo()
	{
		return $this->player_joindate_mo;
	}

	/**
	 * @param int $player_joindate_mo
	 */
	public function setPlayerJoindateMo($player_joindate_mo)
	{
		$this->player_joindate_mo = $player_joindate_mo;
	}

	/**
	 * @return int
	 */
	public function getPlayerJoindateY()
	{
		return $this->player_joindate_y;
	}

	/**
	 * @param int $player_joindate_y
	 */
	public function setPlayerJoindateY($player_joindate_y)
	{
		$this->player_joindate_y = $player_joindate_y;
	}

	/**
	 * @return int
	 */
	public function getPlayerOutdate()
	{
		return $this->player_outdate;
	}

	/**
	 * @param int $player_outdate
	 */
	public function setPlayerOutdate($player_outdate)
	{
		$this->player_outdate = $player_outdate;
	}

	/**
	 * @return int
	 */
	public function getPlayerOutdateD()
	{
		return $this->player_outdate_d;
	}

	/**
	 * @param int $player_outdate_d
	 */
	public function setPlayerOutdateD($player_outdate_d)
	{
		$this->player_outdate_d = $player_outdate_d;
	}

	/**
	 * @return int
	 */
	public function getPlayerOutdateMo()
	{
		return $this->player_outdate_mo;
	}

	/**
	 * @param int $player_outdate_mo
	 */
	public function setPlayerOutdateMo($player_outdate_mo)
	{
		$this->player_outdate_mo = $player_outdate_mo;
	}

	/**
	 * @return int
	 */
	public function getPlayerOutdateY()
	{
		return $this->player_outdate_y;
	}

	/**
	 * @param int $player_outdate_y
	 */
	public function setPlayerOutdateY($player_outdate_y)
	{
		$this->player_outdate_y = $player_outdate_y;
	}

	/**
	 * @return string
	 */
	public function getPlayerPortraitUrl()
	{
		return $this->player_portrait_url;
	}

	/**
	 * @param string $player_portrait_url
	 */
	public function setPlayerPortraitUrl($player_portrait_url)
	{
		$this->player_portrait_url = $player_portrait_url;
	}

	/**
	 * @return string
	 */
	public function getPlayerRace()
	{
		return $this->player_race;
	}

	/**
	 * @param string $player_race
	 */
	public function setPlayerRace($player_race)
	{
		$this->player_race = $player_race;
	}


	/**
	 * @return int
	 */
	public function getPlayerRankId()
	{
		return $this->player_rank_id;
	}

	/**
	 * @param int $player_rank_id
	 */
	public function setPlayerRankId($player_rank_id)
	{
		$this->player_rank_id = $player_rank_id;
	}

	/**
	 * @return string
	 */
	public function getPlayerRealm()
	{
		return $this->player_realm;
	}

	/**
	 * @param string $player_realm
	 */
	public function setPlayerRealm($player_realm)
	{
		$this->player_realm = $player_realm;
	}

	/**
	 * @return string
	 */
	public function getPlayerRegion()
	{
		return $this->player_region;
	}

	/**
	 * @param string $player_region
	 */
	public function setPlayerRegion($player_region)
	{
		$this->player_region = $player_region;
	}

	/**
	 * @return string
	 */
	public function getPlayerRole()
	{
		return $this->player_role;
	}

	/**
	 * @param string $player_role
	 */
	public function setPlayerRole($player_role)
	{
		$this->player_role = $player_role;
	}


	/**
	 * @return string
	 */
	public function getPlayerTitle()
	{
		return $this->player_title;
	}

	/**
	 * @param string $player_title
	 */
	public function setPlayerTitle($player_title)
	{
		$this->player_title = $player_title;
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
	 * @return string
	 */
	public function getRaceImage()
	{
		return $this->race_image;
	}

	/**
	 * @param string $race_image
	 */
	public function setRaceImage($race_image)
	{
		$this->race_image = $race_image;
	}

	/**
	 * @param string $region
	 */
	public function getRegionlist()
	{
		return array(
			'eu' => $this->user->lang['REGIONEU'],
			'kr' => $this->user->lang['REGIONKR'],
			'sea' => $this->user->lang['REGIONSEA'],
			'tw' => $this->user->lang['REGIONTW'],
			'us' => $this->user->lang['REGIONUS'],
		);

	}

	/**
	 * Player class constructor
	 *
	 * @param int   $player_id
	 * @param array $guildlist
	 */

	/**
	 * player constructor.
	 *
	 * @param \phpbb\db\driver\driver_interface    $db
	 * @param \phpbb\config\config                 $config
	 * @param \phpbb\cache\driver\driver_interface $cache
	 * @param \phpbb\user                          $user
	 * @param \phpbb\extension\manager             $ext_manager
	 * @param \avathar\bbguild\model\admin\log     $log
	 * @param string $bb_players_table
	 * @param string $bb_ranks_table
	 * @param string $bb_classes_table
	 * @param string $bb_races_table
	 * @param string $bb_language_table
	 * @param string $bb_guild_table
	 * @param string $bb_factions_table
	 * @param string $bb_games_table
	 * @param int $player_id
	 * @param array $guildlist
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\user $user,
		\phpbb\extension\manager $ext_manager,
		\avathar\bbguild\model\admin\log $log,
		\avathar\bbguild\model\admin\util $util,
		$bb_players_table, $bb_ranks_table, $bb_classes_table, $bb_races_table, $bb_language_table, $bb_guild_table, $bb_factions_table, $bb_games_table,
		$game_registry = null,
		$player_id = 0, $guildlist = null)
	{
		$this->db = $db;
		$this->config = $config;
		$this->cache = $cache;
		$this->user = $user;
		$this->phpbb_extension_manager = $ext_manager;
		$this->log = $log;
		$this->util = $util;
		$this->ext_path = $ext_manager->get_extension_path('avathar/bbguild', true);

		$this->bb_players_table = $bb_players_table;
		$this->bb_races_table = $bb_races_table;
		$this->bb_ranks_table = $bb_ranks_table;
		$this->bb_classes_table = $bb_classes_table;
		$this->bb_language_table = $bb_language_table;
		$this->bb_guild_table = $bb_guild_table;
		$this->bb_factions_table = $bb_factions_table;
		$this->bb_games_table = $bb_games_table;
		$this->game_registry = $game_registry;
		$this->time = time();

		$games_obj = new \avathar\bbguild\model\games\game(
			$db, $cache, $config, $user, $ext_manager,
			$bb_classes_table, $bb_races_table, $bb_language_table, $bb_factions_table,
			$bb_games_table
		);
		$this->games = $games_obj->games ?? [];
		unset($games_obj);

		if (isset($player_id))
		{
			if ($player_id > 0)
			{
				$this->player_id = $player_id;
				$this->Getplayer();
			}
		}
		else
		{
			$this->player_id = 0;
		}

		$this->guildplayerlist = array();
		if ($guildlist == null)
		{
			$guild = new guilds(
				$this->db, $this->user, $this->config, $this->cache, $this->log,
				$this->bb_players_table,
				$this->bb_ranks_table,
				$this->bb_classes_table,
				$this->bb_races_table,
				$this->bb_language_table,
				$this->bb_guild_table,
				$this->bb_factions_table);

			$this->guildlist = $guild->guildlist(1);
		}
		else
		{
			$this->guildlist = $guildlist;
		}

	}

	/**
	 * gets 1 player from database
	 */
	public function Getplayer()
	{

		$sql_array = array(
			'SELECT' => 'm.*, c.colorcode , c.imagename,  c1.name AS player_class, l1.name AS player_race,
						r.image_female, r.image_male,
						g.id as guild_id, g.name as guild_name, m.player_realm , g.region' ,
			'FROM' => array(
				$this->bb_players_table => 'm' ,
				$this->bb_classes_table => 'c' ,
				$this->bb_language_table => 'l1' ,
				$this->bb_races_table => 'r' ,
				$this->bb_guild_table => 'g') ,
			'LEFT_JOIN' => array(
				array(
					'FROM' => array(
						$this->bb_language_table => 'c1') ,
					'ON' => "c1.attribute_id = c.class_id
						AND c1.game_id = c.game_id
						AND c1.language= '" . $this->config['bbguild_lang'] . "'
						AND c1.attribute = 'class'")) ,
			'WHERE' => "
					l1.attribute_id = r.race_id AND l1.game_id = r.game_id AND l1.language= '" . $this->config['bbguild_lang'] . "' AND l1.attribute = 'race'
					AND m.game_id = c.game_id
					AND m.player_class_id = c.class_id
					AND m.game_id = r.game_id
					AND m.player_race_id = r.race_id
					AND m.player_guild_id = g.id
					AND player_id = " . (int) $this->player_id);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$this->player_id = $row['player_id'] ;
			$this->player_name = $row['player_name'] ;
			$this->player_race_id = $row['player_race_id'] ;
			$this->player_race = $row['player_race'] ;
			$this->player_class_id = $row['player_class_id'] ;
			$this->player_class = $row['player_class'] ;
			$this->player_spec_id = isset($row['player_spec_id']) ? (int) $row['player_spec_id'] : 0;
			$this->player_level = $row['player_level'] ;
			$this->player_rank_id = $row['player_rank_id'] ;
			$this->player_comment = $row['player_comment'] ;
			$this->player_gender_id = $row['player_gender_id'] ;
			$this->player_joindate = $row['player_joindate'] ;
			$this->player_role = $row['player_role'] ;
			$this->player_joindate_d = date('j', $row['player_joindate']);
			$this->player_joindate_mo = date('n', $row['player_joindate']);
			$this->player_joindate_y = date('Y', $row['player_joindate']);
			$this->player_outdate = $row['player_outdate'];
			$this->player_outdate_d = date('j', $row['player_outdate']);
			$this->player_outdate_mo = date('n', $row['player_outdate']);
			$this->player_outdate_y = date('Y', $row['player_outdate']);
			$this->player_guild_name = $row['guild_name'];
			$this->player_guild_id = $row['guild_id'];
			$this->player_realm = $row['player_realm'];
			$this->player_region = $row['region'];
			$this->player_armory_url = $row['player_armory_url'];
			$this->player_portrait_url = $row['player_portrait_url'];
			$this->phpbb_user_id = $row['phpbb_user_id'];
			$this->player_status = $row['player_status'];
			$this->player_achiev = $row['player_achiev'];
			$this->game_id = $row['game_id'];
			$this->last_update = $row['last_update'];
			$this->deactivate_reason = $row['deactivate_reason'];
			$this->colorcode = $row['colorcode'];
			$race_image = (string) (($row['player_gender_id'] == 0) ? $row['image_male'] : $row['image_female']);

			// Resolve image path via game registry (game-specific extension) or fall back to core
			$img_base = $this->ext_path . 'images/';
			if ($this->game_registry !== null)
			{
				$provider = $this->game_registry->get($this->game_id);
				if ($provider)
				{
					$img_base = $provider->get_images_path();
				}
			}
			$this->race_image = (strlen($race_image) > 1) ? $img_base . 'race_images/' . $race_image . '.png' : '';
			$this->class_image = (strlen($row['imagename']) > 1) ? $img_base . 'class_images/' . $row['imagename'] . '.png' : '';
			$this->player_title  = $row['player_title'];

			return $this->player_id;
		}
		else
		{
			// load games class
			$games = new game(
				$this->db, $this->cache, $this->config, $this->user, $this->phpbb_extension_manager,
				$this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_factions_table,
				$this->bb_games_table
			);
			if (isset($games->games))
			{
				$this->games = $games->games;
				foreach ($this->games as $key => $value)
				{
					$this->game_id = $key;
					break;
				}
			}
			unset($games);
			$this->player_id = 0 ;
			$this->player_name = '';
			$this->player_race_id = 0 ;
			$this->player_race = '' ;
			$this->player_class_id = 0;
			$this->player_class = '' ;
			$this->player_level = 1;
			$this->player_rank_id = 0;
			$this->player_comment = '' ;
			$this->player_gender_id = 0;
			$this->player_role = 'NA';
			$this->player_joindate = $this->time;
			$this->player_joindate_d = date('j', $this->time);
			$this->player_joindate_mo = date('n', $this->time);
			$this->player_joindate_y = date('Y', $this->time);
			$this->player_outdate = 0;
			$this->player_outdate_d = 1;
			$this->player_outdate_mo = 1;
			$this->player_outdate_y = 1970;
			$this->player_guild_name = '';
			$this->player_guild_id = 0;
			$this->player_realm = '';
			$this->player_region = '';
			$this->player_armory_url = '';
			$this->player_portrait_url = '';
			$this->phpbb_user_id = '';
			$this->player_status = 1;
			$this->player_achiev = 0;
			$this->colorcode = '#8899aa';
			$this->race_image = '';
			$this->class_image = '';
			$this->player_title = '';
			$this->last_update =  $this->time;
			$this->deactivate_reason = '';
			return 0;
		}
	}

	/**
	 * get player id given a player name and guild
	 *
	 * @param  string $playername
	 * @param  string $playerrealm
	 * @param  int    $guild_id    optional
	 * @return int
	 */
	public function get_player_id($playername, $playerrealm, $guild_id = 0)
	{
		if ($guild_id !=0)
		{
			$sql = 'SELECT player_id
                FROM ' . $this->bb_players_table . '
                WHERE player_name ' . $this->db->sql_like_expression($this->db->get_any_char() . $this->db->sql_escape($playername) . $this->db->get_any_char()) . '
                AND player_realm ' . $this->db->sql_like_expression($this->db->get_any_char() . $this->db->sql_escape($playerrealm) . $this->db->get_any_char()) . '
                AND player_guild_id = ' . (int) $this->db->sql_escape($guild_id);
		}
		else
		{
			$sql = 'SELECT player_id
                FROM ' . $this->bb_players_table . '
                WHERE player_name ' . $this->db->sql_like_expression($this->db->get_any_char() . $this->db->sql_escape($playername) . $this->db->get_any_char()) . '
                AND player_realm ' . $this->db->sql_like_expression($this->db->get_any_char() . $this->db->sql_escape($playerrealm) . $this->db->get_any_char());
		}

		$result = $this->db->sql_query($sql);
		//take first one
		while ($row = $this->db->sql_fetchrow($result))
		{
			$membid = $row['player_id'];
			break;
		}

		$this->db->sql_freeresult($result);
		if (isset($membid))
		{
			return $membid;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * update player
	 *
	 * @param  player $old_player
	 * @return bool
	 */
	public function Updateplayer(player $old_player)
	{

		if ($this->player_id == 0)
		{
			return false;
		}

		// if user chooses other name then check if the new name already exists. if so refuse update
		// namechange to existing playername is not allowed
		if ($this->player_name != $old_player->player_name)
		{
			$sql = 'SELECT count(*) as playerexists
				FROM ' . $this->bb_players_table . '
				WHERE player_id <> ' . $this->player_id . "
				AND UPPER(player_name) = UPPER('" . $this->db->sql_escape($this->player_name) . "')";
			$result = $this->db->sql_query($sql);
			$countm = $this->db->sql_fetchfield('playerexists');
			$this->db->sql_freeresult($result);
			if ($countm != 0)
			{
				trigger_error(sprintf($this->user->lang['ADMIN_UPDATE_PLAYER_FAIL'], ucwords($this->player_name)), E_USER_WARNING);
			}
		}

		// check if rank exists
		$sql = 'SELECT count(*) as rankccount
				FROM ' . $this->bb_ranks_table . '
				WHERE rank_id=' . (int) $this->player_rank_id . ' and guild_id = ' . $this->player_guild_id;
		$result = $this->db->sql_query($sql);
		$countm = $this->db->sql_fetchfield('rankccount');
		$this->db->sql_freeresult($result);
		if ($countm == 0)
		{
			trigger_error($this->user->lang['ERROR_INCORRECTRANK'], E_USER_WARNING);
		}

		// check level
		$sql = 'SELECT max(class_max_level) as maxlevel FROM ' . $this->bb_classes_table;
		$result = $this->db->sql_query($sql);
		$maxlevel = $this->db->sql_fetchfield('maxlevel');
		$this->db->sql_freeresult($result);
		if ($this->player_level > $maxlevel)
		{
			$this->player_level = $maxlevel;
		}

		// Generate portrait from game provider if available, otherwise use legacy method
		if (trim($this->player_portrait_url) == '')
		{
			$this->generate_portrait_from_provider();
		}

		// update the data including the phpbb userid
		$query = $this->db->sql_build_array(
			'UPDATE', array(
				'player_name' => $this->player_name ,
				'player_realm' => $this->player_realm,
				'player_region' => $this->player_region,
				'player_status' => $this->player_status ,
				'player_level' => $this->player_level ,
				'player_race_id' => $this->player_race_id ,
				'player_class_id' => $this->player_class_id,
				'player_spec_id' => (int) $this->player_spec_id,
				'player_role' => $this->player_role,
				'player_rank_id' => $this->player_rank_id ,
				'player_gender_id' => $this->player_gender_id ,
				'player_comment' => $this->player_comment ,
				'player_guild_id' => $this->player_guild_id,
				'player_outdate' => $this->player_outdate,
				'player_joindate' => $this->player_joindate,
				'phpbb_user_id' => $this->phpbb_user_id,
				'player_armory_url' => $this->player_armory_url,
				'player_portrait_url' => $this->player_portrait_url,
				'player_achiev' => $this->player_achiev,
				'game_id' => $this->game_id,
				'player_title' => $this->player_title,
				'deactivate_reason' => $this->deactivate_reason,
				'last_update'   => $this->time
			)
		);

		$sql = 'UPDATE ' . $this->bb_players_table . ' SET ' . $query . '
			WHERE player_id= ' . $this->player_id;

		$this->db->sql_query($sql);

		// if status was 1 before then add a line in user comments and set an adjustment
		if ($this->player_status == 0 && $old_player->player_status == 1)
		{
			// update the comment including the phpbb userid
			$query = $this->db->sql_build_array(
				'UPDATE', array(
					'player_comment' => $this->player_comment . '
' . sprintf($this->user->lang['BBGUILD_PLAYERDEACTIVATED'], $this->user->data['username'], date('d.m.y G:i:s', $this->time))  ,
				)
			);

			$this->db->sql_query(
				'UPDATE ' . $this->bb_players_table . ' SET ' . $query . '
				WHERE player_id= ' . $this->player_id
			);

		}

		// if status was 0 before then add a line in user comments and set an adjustment
		if ($this->player_status == 1 && $old_player->player_status == 0)
		{
			// update the comment including the phpbb userid
			$query = $this->db->sql_build_array(
				'UPDATE', array(
					'player_comment' => $this->player_comment . '
' . sprintf($this->user->lang['BBGUILD_PLAYERACTIVATED'], $this->user->data['username'], date('d.m.y G:i:s', $this->time))  ,
				)
			);
			$this->db->sql_query(
				'UPDATE ' . $this->bb_players_table . ' SET ' . $query . '
				WHERE player_id= ' . $this->player_id
			);
		}

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_PLAYER_UPDATED',
				'log_action' => [$this->player_name],
			)
		);

		return true;

	}

	/**
	 * delete player from all tables
	 */
	public function Deleteplayer()
	{

		$sql = 'DELETE FROM ' . $this->bb_players_table . ' where player_id = ' . (int) $this->player_id;
		$this->db->sql_query($sql);

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_PLAYER_DELETED',
				'log_action' => [$this->player_name],
			)
		);

	}

	/**
	 * Insert new player
	 *
	 * @return number
	 */
	public function Makeplayer()
	{

		$error = array ();

		//perform checks

		// check if playername exists
		$sql = 'SELECT count(*) as playerexists
				FROM ' . $this->bb_players_table . "
				WHERE player_name= '" . $this->db->sql_escape(ucwords($this->player_name)) . "'
				AND player_realm= '" . $this->db->sql_escape(ucwords($this->player_realm)) . "'
				AND player_guild_id = " . $this->player_guild_id;
		$result = $this->db->sql_query($sql);
		$countm = $this->db->sql_fetchfield('playerexists');
		$this->db->sql_freeresult($result);
		if ($countm != 0)
		{
			$error[]= $this->user->lang['ERROR_PLAYEREXIST'];
		}

		if ($this->player_rank_id === null)
		{
			$error[]= $this->user->lang['ERROR_INCORRECTRANK'];
		}

		if ($this->player_status === null )
		{
			$this->player_status = 1;
		}

		if ($this->player_title === null )
		{
			$this->player_title = ' ';
		}

		// check if rank exists
		$sql = 'SELECT count(*) as rankccount
			FROM ' . $this->bb_ranks_table . '
			WHERE rank_id=' . (int) $this->player_rank_id . '
			AND guild_id = ' . (int) $this->player_guild_id;
		$result = $this->db->sql_query($sql);
		$countm = $this->db->sql_fetchfield('rankccount');
		$this->db->sql_freeresult($result);
		if ($countm == 0)
		{
			$error[]= $this->user->lang['ERROR_INCORRECTRANK'];
		}

		if (count($error) > 0)
		{
			$this->log->log_insert(
				array(
					'log_type'   => 'L_ACTION_PLAYER_ADDED',
					'log_result' => 'L_FAILED',
					'log_action' => [ucwords($this->player_name) . ' ' . implode(',', $error)],
				)
			);

			return 0;
		}

		// check level
		$sql = 'SELECT max(class_max_level) as maxlevel FROM ' . $this->bb_classes_table;
		$result = $this->db->sql_query($sql);
		$maxlevel = $this->db->sql_fetchfield('maxlevel');
		$this->db->sql_freeresult($result);
		if ($this->player_level > $maxlevel)
		{
			$this->player_level = $maxlevel;
		}

		// if region/realm is nil then default it from guild
		if ($this->player_realm =='' or  $this->player_region =='')
		{
			$sql = 'SELECT realm, region FROM ' . $this->bb_guild_table . ' WHERE id = ' . (int) $this->player_guild_id;
			$result = $this->db->sql_query($sql);
			$this->player_realm  = $this->config['bbguild_default_realm'];
			$this->player_region = '';
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->player_realm = $row['realm'];
				$this->player_region = $row['region'];
			}
		}

		// Generate portrait from game provider if available, otherwise use legacy method
		$this->generate_portrait_from_provider();

		$this->last_update = $this->time;
		$query = $this->db->sql_build_array(
			'INSERT', array(
				'player_name' => ucwords($this->player_name) ,
				'player_status' => $this->player_status ,
				'player_level' => $this->player_level,
				'player_race_id' => $this->player_race_id ,
				'player_class_id' => $this->player_class_id ,
				'player_spec_id' => (int) $this->player_spec_id ,
				'player_rank_id' => $this->player_rank_id ,
				'player_role' => $this->player_role,
				'player_realm' => $this->player_realm,
				'player_region' => $this->player_region,
				'player_comment' => (string) $this->player_comment ,
				'player_joindate' => (int) $this->player_joindate ,
				'player_outdate' => (int) $this->player_outdate ,
				'player_guild_id' => $this->player_guild_id ,
				'player_gender_id' => $this->player_gender_id ,
				'player_achiev' => $this->player_achiev ,
				'player_armory_url' => (string) $this->player_armory_url ,
				'phpbb_user_id' => (int) $this->phpbb_user_id ,
				'game_id' => (string) $this->game_id ,
				'player_portrait_url' => (string) $this->player_portrait_url,
				'player_title' => $this->player_title,
				'last_update' => $this->last_update,
			)
		);

		$this->db->sql_query('INSERT INTO ' . $this->bb_players_table . $query);

		$this->player_id = $this->db->sql_nextid();

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_PLAYER_ADDED',
				'log_action' => [ucwords($this->player_name)],
			)
		);

		return $this->player_id;

	}


	/**
	 * Fetch info from game API
	 *
	 * Delegates to game_api_interface::fetch_character_data() when a
	 * game provider with API support is available.
	 *
	 * @param game_provider_interface|null $provider Optional game provider with API
	 * @return integer 1 on success, -1 on failure
	 */
	public function Armory_getplayer(?game_provider_interface $provider = null)
	{
		// Delegate to game provider's API
		if ($provider !== null && $provider->has_api())
		{
			$api = $provider->get_api();
			$data = $api->fetch_character_data($this->player_name, $this->player_realm, $this->player_region);
			if (!$data || !is_array($data))
			{
				$this->armoryresult = 'KO';
				return -1;
			}
			$this->player_level = isset($data['level']) ? (int) $data['level'] : $this->player_level;
			$this->player_race_id = isset($data['playable_race']['id']) ? (int) $data['playable_race']['id'] : $this->player_race_id;
			$this->player_class_id = isset($data['playable_class']['id']) ? (int) $data['playable_class']['id'] : $this->player_class_id;
			$this->player_gender_id = isset($data['gender']['type']) ? ($data['gender']['type'] === 'FEMALE' ? 1 : 0) : $this->player_gender_id;
			$this->player_achiev = isset($data['achievement_points']) ? (int) $data['achievement_points'] : $this->player_achiev;
			$this->player_armory_url = $api->get_player_armory_url($this->player_name, $this->player_realm, $this->player_region);
			$this->player_portrait_url = $api->get_player_portrait_url($data);
			if (isset($data['realm']['slug']))
			{
				$this->player_realm = $data['realm']['slug'];
			}
			$this->armoryresult = 'OK';
			return 1;
		}

		// No provider available — cannot fetch character data
		return -1;
	}

	/**
	 * activates all checked players
	*  for each player in $mwindow
	*   get player
	*   if player is in $mlist
	*       add activation dkp
	*   else
	*      if player status was active then deactivate and add deactivation dkp
	 * @param array $mlist
	 * @param array $mwindow
	 */
	public function Activateplayers(array $mlist, array $mwindow)
	{
		foreach ($mwindow as $player_id)
		{
			$this->player_id = $player_id;
			$this->Getplayer();
			if (in_array($player_id, $mlist))
			{
				$this->Activate_player();
			}
			else
			{
				$this->Deactivate_player();
			}
		}
	}

	/**
	 * @internal param $config
	 * @internal param $user
	 * @return bool
	 */
	private function Activate_player()
	{
		$changed = false;
		if ($this->player_status == '0')
		{
			$changed = true;
			$this->player_status = '1';
		}

		if ($changed)
		{
			$query = $this->db->sql_build_array(
				'UPDATE', array(
					'player_status' => $this->player_status ,
				)
			);

			$this->db->sql_query(
				'UPDATE ' . $this->bb_players_table . ' SET ' . $query . '
                WHERE player_id= ' . $this->player_id
			);
		}

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_PLAYER_UPDATED',
				'log_action' => [ucwords($this->player_name)],
			)
		);

		return $changed;
	}

	/**
	 * @internal param $config
	 * @internal param $user
	 * @return bool
	 */
	private function Deactivate_player()
	{
		$changed = false;
		if ($this->player_status == '1')
		{
			$changed = true;
			$this->player_status = '0';
		}

		if ($changed)
		{
			$query = $this->db->sql_build_array(
				'UPDATE', array(
					'player_status' => $this->player_status ,
				)
			);

			$this->db->sql_query(
				'UPDATE ' . $this->bb_players_table . ' SET ' . $query . '
                WHERE player_id= ' . $this->player_id
			);
		}

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_PLAYER_DEACTIVATED',
				'log_action' => [ucwords($this->player_name)],
			)
		);

		return $changed;
	}


	/**
	 * Generate portrait URL from game provider if available.
	 *
	 * Checks the game registry for a provider that can generate
	 * portrait URLs. Falls back to the legacy aion-specific method.
	 */
	private function generate_portrait_from_provider()
	{

		// Try game registry for a provider with an API
		if ($this->game_registry !== null)
		{
			try
			{
				$provider = $this->game_registry->get($this->game_id);
				if ($provider !== null && $provider->has_api())
				{
					$api = $provider->get_api();
					$player_data = array(
						'race'   => $this->player_race_id,
						'gender' => $this->player_gender_id,
						'game_id' => $this->game_id,
					);
					$this->player_portrait_url = $api->get_player_portrait_url($player_data);
					return;
				}
			}
			catch (\Exception $e)
			{
				// Registry not available, fall through to legacy
			}
		}

		// Legacy fallback: aion-specific portrait generation
		if ($this->game_id == 'aion')
		{
			$this->generate_portraitlink();
		}
	}

	/**
	 * generates a standard portrait image url for aion based on characterdata
	 *
	 * @deprecated Use generate_portrait_from_provider() instead. Will be removed when aion is extracted.
	 */
	private function generate_portraitlink()
	{
		$this->player_portrait_url = $this->ext_path . 'images/roster_portraits/aion/' . $this->player_race_id . '_' . $this->player_gender_id . '.jpg';
		return $this->player_portrait_url;
	}

	/**
	 * function for removing player from guild but leave him in the player table.;
	 *
	 * @param  string $player_name
	 * @param  int    $guild_id
	 * @return boolean
	 * @todo   fix this
	 */
	public function GuildKick($player_name, $guild_id)
	{
		// find id for existing player name
		$sql = 'SELECT *
				FROM ' . $this->bb_players_table . "
				WHERE player_name = '" . $this->db->sql_escape($player_name) . "' and player_guild_id = " . (int) $guild_id;
		$result = $this->db->sql_query($sql);
		// get old data
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->old_player = array(
				'player_id' => $row['player_id'] ,
				'player_rank_id' => $row['player_rank_id'] ,
				'player_guild_id' => $row['player_guild_id'] ,
				'player_comment' => $row['player_comment']);
		}
		$this->db->sql_freeresult($result);

		$sql_arr = array(
			'player_rank_id' => 99 ,
			'player_comment' => 'Player left ' . date('F j, Y, g:i a') . ' by Armory plugin' ,
			'player_outdate' => $this->time ,
			'player_guild_id' => 0);

		$sql = 'UPDATE ' . $this->bb_players_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $sql_arr) . '
			WHERE player_id = ' . (int) $this->old_player['player_id'] . ' and player_guild_id = ' . (int) $this->old_player['player_guild_id'];

		$this->db->sql_query($sql);

		$this->log->log_insert(
			array(
				'log_type'   => 'L_ACTION_PLAYER_UPDATED',
				'log_action' => [$player_name],
			)
		);

		return true;
	}

	/**
	 * Enter joindate for guildplayer (query is cached for 1 week !)
	 *
	 * @param  $player_id
	 * @return bool|mixed
	 */
	public function get_joindate($player_id)
	{
		// get player joindate
		$sql = 'SELECT player_joindate  FROM ' . $this->bb_players_table . ' WHERE player_id = ' . $player_id;
		$result = $this->db->sql_query($sql, 3600);
		$joindate = $this->db->sql_fetchfield('player_joindate');

		$this->db->sql_freeresult($result);
		return $joindate;

	}


	/**
	 * ACP list_players grid
	 * get a player list for given guild
	 *
	 * @param int  $guild_id
	 * @param bool $assignedonly
	 */
	public function listallplayers($guild_id = 0, $assignedonly = false)
	{

		$sql_array = array(
			'SELECT'    => 'r.rank_name, m.player_id ,m.player_name, m.player_realm ',
			'FROM'      => array(
				$this->bb_players_table       => 'm',
				$this->bb_ranks_table    => 'r',
			),

			'WHERE'     =>  ' m.player_guild_id = r.guild_id
								AND m.player_rank_id = r.rank_id
								AND r.rank_hide != 1 ',
			'ORDER_BY' => 'm.player_rank_id asc, m.player_level desc, m.player_name asc'
		);

		if ($assignedonly == true)
		{
			$sql_array['WHERE'] .= ' AND m.phpbb_user_id = 0 ';
		}

		if ($guild_id != 0)
		{
			$sql_array['WHERE'] .= ' AND m.player_guild_id = ' . $guild_id;
		}

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);

		while ( $row = $this->db->sql_fetchrow($result) )
		{
			$this->guildplayerlist[] = array(
				'player_id'     => $row['player_id'],
				'player_name'     => $row['player_name'],
				'rank_name'      => $row['rank_name'],
				'player_realm'    => $row['player_realm'],
			);
		}

		$this->db->sql_freeresult($result);
	}

	/**
	 * Claim a character to your phpbb User
	 */
	public function Claim_Player()
	{

		$sql_ary = array(
			'phpbb_user_id'    => $this->user->data['user_id'],
		);

		$sql = 'UPDATE ' . $this->bb_players_table . '
						SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
						WHERE player_id = ' . $this->player_id;
		$this->db->sql_query($sql);
	}

	/**
	 * Unclaim a character from your phpBB user.
	 * Only succeeds if the character is currently claimed by the calling user.
	 *
	 * @return bool True if unclaimed, false if not owned by this user
	 */
	public function Unclaim_Player()
	{
		$sql = 'UPDATE ' . $this->bb_players_table . '
			SET phpbb_user_id = 0
			WHERE player_id = ' . (int) $this->player_id . '
				AND phpbb_user_id = ' . (int) $this->user->data['user_id'];
		$this->db->sql_query($sql);

		return ($this->db->sql_affectedrows() > 0);
	}

	/**
	 * check if user exceeded allowed character count
	 *
	 * @return boolean
	 */
	public function has_reached_maxbbguildaccounts()
	{
		$sql = 'SELECT count(*) as charcount
				FROM ' . $this->bb_players_table . '
				WHERE phpbb_user_id = ' . (int) $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);
		$countc = $this->db->sql_fetchfield('charcount');
		$this->db->sql_freeresult($result);
		return ($countc >= (int) $this->config['bbguild_maxchars']);
	}

	/**
	 * Frontview : player Roster listing
	 *
	 * @param  $start
	 * @param  $mode ('listing' or 'class')
	 * @param  $query_by_armor
	 * @param  $query_by_class
	 * @param  $filter
	 * @param  $game_id
	 * @param  int                         $guild_id      optional=0
	 * @param  int                         $class_id      optional=0
	 * @param  int                         $race_id       optional=0
	 * @param  int                         $level1        optional=1
	 * @param  int                         $level2        optional=200
	 * @param  bool                        $mycharsonly   optional=false
	 * @param  string                      $player_filter optional = ''
	 * @param  int                         $all
	 * @return array
	 */
	public function getplayerlist($start, $mode, $query_by_armor, $query_by_class, $filter,
	   $game_id, $guild_id = 0, $class_id = 0, $race_id = 0, $level1 = 0, $level2 = 200, $mycharsonly = false, $player_filter = '', $all = 1
	)
	{

		$sql_array = array();

		$sql_array['SELECT'] =  'm.player_id, m.game_id, m.player_guild_id,  m.player_name, m.player_level, m.player_race_id, e1.name as race_name,
    		m.player_class_id, m.player_gender_id, m.player_rank_id, m.player_achiev, m.player_armory_url, m.player_portrait_url, m.player_spec, m.player_status,
    		r.rank_prefix , r.rank_name, r.rank_suffix, e.image_female, e.image_male,
    		g.name as guildname, m.player_realm, g.region, c1.name as class_name, c.colorcode, c.imagename, m.phpbb_user_id, u.username, u.user_colour  ';

		$sql_array['FROM'] = array(
			$this->bb_players_table =>  'm',
			$this->bb_classes_table =>  'c',
			$this->bb_guild_table =>  'g',
			$this->bb_ranks_table =>  'r',
			$this->bb_races_table =>  'e',
			$this->bb_language_table  =>  'e1');

		$sql_array['LEFT_JOIN'] = array(
			array(
				'FROM'  => array(USERS_TABLE => 'u'),
				'ON'    => 'u.user_id = m.phpbb_user_id '),
			array(
				'FROM'  => array($this->bb_language_table => 'c1'),
				'ON'    => "c1.attribute_id = c.class_id AND c1.language= '" . $this->config['bbguild_lang'] . "' AND c1.attribute = 'class'  and c1.game_id = c.game_id "
			));

		$sql_array['WHERE'] = ' c.class_id = m.player_class_id
			AND c.game_id = m.game_id
			AND e.race_id = m.player_race_id
			AND e.game_id = m.game_id
			AND g.id = m.player_guild_id

			AND r.guild_id = m.player_guild_id
			AND r.rank_id = m.player_rank_id AND r.rank_hide = 0
			 ';

		if ($all != 1)
		{
			$sql_array['WHERE'] .= " AND m.player_status = '1' ";
		}

		if ($mycharsonly ==false)
		{
			$sql_array['WHERE'] .= ' AND m.player_level >= ' . (int) $this->config['bbguild_minrosterlvl'];
		}

		if ($player_filter != '')
		{
			$sql_array['WHERE'] .= ' AND lcase(m.player_name) ' . $this->db->sql_like_expression($this->db->get_any_char() . $this->db->sql_escape(mb_strtolower($player_filter)) . $this->db->get_any_char());
		}

		$sql_array['WHERE'] .= " AND m.player_rank_id != 99
			AND e1.attribute_id = e.race_id AND e1.language= '" . $this->config['bbguild_lang'] . "'
			AND e1.attribute = 'race' and e1.game_id = e.game_id";

		if ($game_id != '' )
		{
			$sql_array['WHERE'] .= " AND m.game_id =  '" .  $this->db->sql_escape($game_id) . "'";
		}

		if ($mycharsonly == true)
		{
			$sql_array['WHERE'] .= ' AND m.phpbb_user_id =  ' . $this->user->data['user_id'];
		}

		if ($guild_id > 0)
		{
			$sql_array['WHERE'] .= ' AND m.player_guild_id =  ' . $guild_id;
		}

		if ($class_id > 0 && $query_by_class == true)
		{
			$sql_array['WHERE'] .= ' AND m.player_class_id =  ' . $class_id;
		}

		if ($race_id > 0)
		{
			$sql_array['WHERE'] .= ' AND m.player_race_id =  ' . $race_id;
		}

		if ($level1 > 0)
		{
			$sql_array['WHERE'] .= ' AND m.player_level >=  ' . $level1;
		}

		if ($level2 != 200)
		{
			$sql_array['WHERE'] .= ' AND m.player_level <=  ' . $level2;
		}

		if ($filter != '' && $query_by_armor == true)
		{
			$sql_array['WHERE'] .= " AND c.class_armor_type =  '" . $this->db->sql_escape($filter) . "'";
		}

		// order
		$sort_order = array(
			0 => array('m.player_name', 'm.player_name desc'),
			1 => array('m.game_id', 'm.player_name desc'),
			2 => array('m.player_class_id', 'm.player_class_id desc'),
			3 => array('m.player_rank_id', 'm.player_rank_id desc'),
			4 => array('m.player_level', 'm.player_level  desc'),
			5 => array('u.username', 'u.username desc'),
			6 => array('m.player_achiev', 'm.player_achiev  desc')
		);

		$current_order = $this->util->switch_order($sort_order);

		if ($mode == 1)
		{
			$sql_array['ORDER_BY']  = ' m.player_class_id, ' . $current_order['sql'];
		}
		else if ($mode == 0)
		{
			$sql_array['ORDER_BY']  = $current_order['sql'];
		}

		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		if ($mode == 0)
		{
			// LISTING MODE
			$player_count=0;

			// get playercount in selection
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$player_count++;
			}

			//now get wanted window
			$result = $this->db->sql_query_limit($sql, $this->config['bbguild_user_llimit'], $start);
			$dataset = $this->db->sql_fetchrowset($result);
		}
		else if ($mode == 1)
		{
			// CLASS mode
			$result = $this->db->sql_query($sql);
			$dataset = $this->db->sql_fetchrowset($result);
			$player_count = count($dataset);
		}

		$this->db->sql_freeresult($result);

		$characters = array();

		foreach ($dataset as $row)
		{
			$characters[]= array(
				'game_id' => $this->games[$row['game_id']],
				'player_id' => $row['player_id'],
				'player_name' => $row['player_name'],
				'guildname' => $row['guildname'],
				'realm'    => $row['player_realm'],
				'region'    => $row['region'],
				'colorcode' => $row['colorcode'],
				'player_class_id' => $row['player_class_id'],
				'class_name' => $row['class_name'],
				'class_image' => $this->resolve_game_image_path($row['game_id'], 'class_images/' . $row['imagename'] . '.png'),
				'race_name' => $row['race_name'],
				'player_gender_id' => $row['player_gender_id'],
				'race_image' => $this->resolve_game_image_path($row['game_id'], 'race_images/' . (($row['player_gender_id'] == 0) ? $row['image_male'] : $row['image_female']) . '.png'),
				'image_female' => $row['image_female'],
				'image_male' => $row['image_male'],
				'player_rank'    => $row['rank_prefix'] . ' ' . $row['rank_name'] . ' ' . $row['rank_suffix'],
				'player_level' => $row['player_level'],
				'username' => get_username_string('full', $row['phpbb_user_id'], $row['username'], $row['user_colour']),
				'player_portrait_url' => $row['player_portrait_url'],
				'player_spec' => isset($row['player_spec']) ? $row['player_spec'] : '',
				'player_armory_url' => $row['player_armory_url'],
				'player_achiev' => $row['player_achiev'],
				'player_status' => $row['player_status'],
			);
		}

		return array($characters, $current_order, $player_count);
	}

	/**
	 * Frontview : gets all classes in roster selection
	 *
	 * @param  string $filter
	 * @param  bool   $query_by_armor
	 * @param  int    $classid
	 * @param  string $game_id
	 * @param  int    $guild_id
	 * @param  int    $race_id
	 * @param  int    $level1
	 * @param  int    $level2
	 * @return array
	 */
	public function get_classes($filter, $query_by_armor, $classid, $game_id, $guild_id = 0, $race_id = 0, $level1 = 0, $level2 = 200)
	{
		$sql_array = array(
			'SELECT'    => 'c.class_id, c1.name as class_name, c.imagename, c.colorcode' ,
			'FROM'      => array(
				$this->bb_players_table    =>  'm',
				$this->bb_classes_table          =>  'c',
				$this->bb_language_table            =>  'c1',
				$this->bb_ranks_table   =>  'r',
			),
			'WHERE'     => " c.class_id = m.player_class_id
    							AND c.game_id = m.game_id
    							AND r.guild_id = m.player_guild_id
    							AND r.rank_id = m.player_rank_id AND r.rank_hide = 0
    							AND c1.attribute_id =  c.class_id AND c1.language= '" . $this->config['bbguild_lang'] . "' AND c1.attribute = 'class'
    							AND (c.game_id = '" . $this->db->sql_escape($game_id) . "')
    							AND c1.game_id=c.game_id

    							",
			'ORDER_BY'  =>  'c1.name asc',
			'GROUP_BY'  =>  'c.class_id, c1.name, c.imagename, c.colorcode'
		);

		// filters
		if ($filter != $this->user->lang['ALL'] && $query_by_armor == true)
		{
			$sql_array['WHERE'] .= " AND c.class_armor_type =  '" . $this->db->sql_escape($filter) . "'";
		}

		if ($guild_id > 0)
		{
			$sql_array['WHERE'] .= ' AND m.player_guild_id =  ' . $guild_id;
		}

		if ($filter != $this->user->lang['ALL']  && $classid > 0)
		{
			$sql_array['WHERE'] .= ' AND m.player_class_id =  ' . $classid;
		}

		if ($race_id > 0)
		{
			$sql_array['WHERE'] .= ' AND m.player_race_id =  ' . $race_id;
		}

		if ($level1 > 0)
		{
			$sql_array['WHERE'] .= ' AND m.player_level >=  ' . $level1;
		}

		if ($level2 != 200)
		{
			$sql_array['WHERE'] .= ' AND m.player_level <=  ' . $level2;
		}

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$dataset = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		unset($result);
		return $dataset;
	}

	/**
	 * Resolve a game-specific image path via game_registry.
	 *
	 * @param string $game_id Game identifier
	 * @param string $relative_path Relative path within the images directory (e.g. 'class_images/wow_hunter.png')
	 * @return string Full path to image
	 */
	private function resolve_game_image_path(string $game_id, string $relative_path): string
	{
		if ($this->game_registry !== null)
		{
			$provider = $this->game_registry->get($game_id);
			if ($provider)
			{
				return $provider->get_images_path() . $relative_path;
			}
		}
		return $this->ext_path . 'images/' . $relative_path;
	}
}
