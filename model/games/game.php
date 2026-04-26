<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Game class file
 *
 */

namespace avathar\bbguild\model\games;

use avathar\bbguild\model\games\library\install_custom;

/**
 * Games
 *
 * Manages creation of Game
 *
 * @package bbguild
 */
class game
{
	public $bb_classes_table;
	public $bb_races_table;
	public $bb_language_table;
	public $bb_factions_table;
	public $bb_game_table;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;
	/** @var \phpbb\config\config */
	protected $config;
	/** @var \phpbb\user */
	protected $user;

	/**
	 * primary key in games table
	 *
	 * @var int
	 */
	private $id;

	/**
	 * the game_id (unique key)
	 *
	 * @var string
	 */
	public $game_id;

	/**
	 * name of game
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * game status (not used atm)
	 *
	 * @var boolean
	 */
	protected $status;

	/**
	 * game region
	 *
	 * @var string
	 */
	protected $region;

	/**
	 * date at which this game was installed
	 *
	 * @var int
	 */
	protected $install_date;

	/**
	 * name of game logo png
	 * in /images/bbguild/games/<game_id>
	 *
	 * @var string
	 */
	protected $imagename;

	/**
	 * true if armory is on
	 *
	 * @var boolean
	 */
	protected $armory_enabled;

	/**
	 * base boss database url
	 *
	 * @var boolean
	 */
	protected $bossbaseurl;

	/**
	 * base zone database url
	 *
	 * @var string
	 */
	protected $zonebaseurl;

	/**
	 * api key for game armory
	 *
	 * @var string
	 */
	protected $apikey;

	/**
	 * private api key for game armory
	 *
	 * @var string
	 */
	protected $privkey;

	/**
	 * locale string for the language in which api data are returned. en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR, or ru_RU
	 *
	 * @var string
	 */
	protected $apilocale;

	/**
	 * installed games
	 *
	 * @var array
	 */
	public $games;

	/**
	 * extension path
	 *
	 * @var
	 */
	private $ext_path;

	/**
	 * Game plugin registry (null if not injected)
	 *
	 * @var game_registry|null
	 */
	private $game_registry;

	/**
	 * possible regions
	 *
	 * @var array
	 */
	protected $regions;

	/**
	 * list of possible locale strings
	 *
	 * @var array
	 */
	protected $apilocales;

	/**
	 * list of possible realms
	 *
	 * @var string
	 */
	protected $realmlist;

	/**
	 * @param boolean $basebossurl
	 */
	public  function setBossbaseurl($basebossurl)
	{
		$this->bossbaseurl = $basebossurl;
	}

	/**
	 * @return boolean
	 */
	public  function getBossbaseurl()
	{
		return $this->bossbaseurl;
	}

	/**
	 * @param string $zonebaseurl
	 */
	public function setZonebaseurl($zonebaseurl)
	{
		$this->zonebaseurl = $zonebaseurl;
	}

	/**
	 * @return string
	 */
	public function getZonebaseurl()
	{
		return $this->zonebaseurl;
	}


	/**
	 * @param string $imagename
	 */
	public function setImagename($imagename)
	{
		$this->imagename = $imagename;
	}

	/**
	 * @return string
	 */
	public function getImagename()
	{
		return $this->imagename;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param boolean $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * @return boolean
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param boolean $armory_enabled
	 */
	public function setArmoryEnabled($armory_enabled)
	{
		$this->armory_enabled = $armory_enabled;
	}

	/**
	 * @return boolean
	 */
	public function getArmoryEnabled()
	{
		return $this->armory_enabled;
	}


	/**
	 * @param string $apikey
	 */
	public function setApikey($apikey)
	{
		$this->apikey = $apikey;
	}

	/**
	 * @return string
	 */
	public function getApikey()
	{
		return $this->apikey;
	}

	/**
	 * @return string
	 */
	public function get_apilocale()
	{
		return $this->apilocale;
	}

	/**
	 * @param string $apilocale
	 */
	public function set_apilocale($apilocale)
	{
		$this->apilocale = $apilocale;
	}

	/**
	 * @return string
	 */
	public function get_privkey()
	{
		return $this->privkey;
	}

	/**
	 * @param string $privkey
	 */
	public function set_privkey($privkey)
	{
		$this->privkey = $privkey;
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
	 * @return array
	 */
	public function getRegions()
	{
		return $this->regions;
	}

	/**
	 * @return int
	 */
	public function getInstallDate()
	{
		return $this->install_date;
	}

	/**
	 * @param int $install_date
	 */
	public function setInstallDate($install_date)
	{
		$this->install_date = $install_date;
	}

	/**
	 * @return array with locales for this region
	 */
	public function getApilocales($region)
	{
		return $this->apilocales[$region];
	}


	/**
	 * Get all installable games from the plugin registry.
	 *
	 * @return array
	 */
	public function getInstallableGames()
	{
		if ($this->game_registry !== null)
		{
			return $this->game_registry->get_installable_games();
		}

		return array();
	}

	/**
	 * @return array
	 */
	public function getRealmlist()
	{
		return $this->realmlist;
	}


	/**
	 * Game class constructor
	 *
	 * @param \phpbb\db\driver\driver_interface    $db
	 * @param \phpbb\cache\driver\driver_interface $cache
	 * @param \phpbb\config\config                 $config
	 * @param \phpbb\user                          $user
	 * @param \phpbb\extension\manager             $ext_manager
	 * @param string                               $bb_classes_table
	 * @param string                               $bb_races_table
	 * @param string                               $bb_language_table
	 * @param string                               $bb_factions_table
	 * @param string                               $bb_game_table
	 * @param game_registry|null                   $game_registry     Optional plugin registry
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\cache\driver\driver_interface $cache, \phpbb\config\config $config, \phpbb\user $user, \phpbb\extension\manager $ext_manager, $bb_classes_table, $bb_races_table, $bb_language_table, $bb_factions_table, $bb_game_table, ?game_registry $game_registry = null)
	{
		$this->db = $db;
		$this->cache = $cache;
		$this->config = $config;
		$this->user = $user;
		$this->ext_path = $ext_manager->get_extension_path('avathar/bbguild', true);

		$this->bb_classes_table = $bb_classes_table;
		$this->bb_races_table = $bb_races_table;
		$this->bb_language_table = $bb_language_table;
		$this->bb_factions_table = $bb_factions_table;
		$this->bb_game_table = $bb_game_table;
		$this->game_registry = $game_registry;

		$this->regions = array(
			'eu' => $user->lang['REGIONEU'],
			'kr' => $user->lang['REGIONKR'],
			'sea' => $user->lang['REGIONSEA'],
			'tw' => $user->lang['REGIONTW'],
			'us' => $user->lang['REGIONUS'],
		);

		//available locale strings - used for wow only
		$this->apilocales = array(
			'eu' => array('en_GB', 'de_DE', 'es_ES', 'fr_FR', 'it_IT', 'pl_PL', 'pt_PT', 'ru_RU'),
			'kr' => array('ko_KR') ,
			'sea' => array('en_US'),
			'tw' => array('zh_TW'),
			'us' => array('en_US', 'pt_BR', 'es_MX')
		);

		//fill the installed games array
		$this->games = $this->gamesarray();
	}

	/**
	 * Get the game registry (may be null if not injected)
	 *
	 * @return game_registry|null
	 */
	public function getGameRegistry()
	{
		return $this->game_registry;
	}

	/**
	 * Build an associative array of table names for the new game_install_interface.
	 *
	 * @return array
	 */
	private function get_table_names()
	{
		global $phpbb_container;

		$tables = array(
			'bb_games_table'     => $phpbb_container->getParameter('avathar.bbguild.tables.bb_games'),
			'bb_logs_table'      => $phpbb_container->getParameter('avathar.bbguild.tables.bb_logs'),
			'bb_ranks_table'     => $phpbb_container->getParameter('avathar.bbguild.tables.bb_ranks'),
			'bb_guild_table'     => $phpbb_container->getParameter('avathar.bbguild.tables.bb_guild'),
			'bb_players_table'   => $phpbb_container->getParameter('avathar.bbguild.tables.bb_players'),
			'bb_classes_table'   => $this->bb_classes_table,
			'bb_races_table'     => $this->bb_races_table,
			'bb_gameroles_table' => $phpbb_container->getParameter('avathar.bbguild.tables.bb_gameroles'),
			'bb_factions_table'  => $this->bb_factions_table,
			'bb_language_table'  => $this->bb_language_table,
			'bb_motd_table'      => $phpbb_container->getParameter('avathar.bbguild.tables.bb_motd'),
			'bb_recruit_table'   => $phpbb_container->getParameter('avathar.bbguild.tables.bb_recruit'),
			'bb_bosstable'       => $phpbb_container->getParameter('avathar.bbguild.tables.bb_bosstable'),
			'bb_zonetable'       => $phpbb_container->getParameter('avathar.bbguild.tables.bb_zonetable'),
			'bb_news'            => $phpbb_container->getParameter('avathar.bbguild.tables.bb_news'),
		);
		// Only set when the spec migration (v200b4) has run; older installs
		// without bb_specializations skip the spec install/uninstall path.
		if ($phpbb_container->hasParameter('avathar.bbguild.tables.bb_specializations'))
		{
			$tables['bb_specializations_table'] = $phpbb_container->getParameter('avathar.bbguild.tables.bb_specializations');
		}
		return $tables;
	}

	/**
	 * adds a Game to database
	 */
	public function install_game()
	{
		if ($this->game_id == '')
		{
			trigger_error(sprintf($this->user->lang['ADMIN_INSTALL_GAME_FAILURE'], $this->name) . E_USER_WARNING);
		}

		// Plugin-provided games (via game_registry)
		if ($this->game_registry !== null && $this->game_registry->has($this->game_id))
		{
			$provider = $this->game_registry->get($this->game_id);
			$this->name = $provider->get_game_name();
			$installer = $provider->get_installer();

			$installer->install(
				$this->get_table_names(),
				$this->game_id,
				$this->name,
				$provider->get_boss_base_url(),
				$provider->get_zone_base_url(),
				$this->getRegion()
			);
		}
		else
		{
			// Custom game
			if ($this->name == '')
			{
				$this->name = 'Custom';
			}
			$installgame = new install_custom($this->db, $this->cache, $this->config, $this->user);
			$installgame->install(
				$this->get_table_names(),
				$this->game_id,
				$this->name,
				'',
				'',
				$this->getRegion()
			);
		}
	}

	/**
	 * deletes a Game from database, including all factions, classes and races.
	 */
	public function delete_game()
	{
		if ($this->game_id == '')
		{
			trigger_error(sprintf($this->user->lang['ADMIN_INSTALL_GAME_FAILURE'], $this->name) . E_USER_WARNING);
		}

		// Block deletion if guilds or players still reference this game
		$tables = $this->get_table_names();

		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $tables['bb_guild_table'] .
			" WHERE game_id = '" . $this->db->sql_escape($this->game_id) . "' AND id != 0";
		$result = $this->db->sql_query($sql);
		$guild_count = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($result);

		if ($guild_count > 0)
		{
			trigger_error(sprintf($this->user->lang['ERROR_GAME_HAS_GUILDS'], $this->name, $guild_count), E_USER_WARNING);
		}

		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $tables['bb_players_table'] .
			" WHERE game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$result = $this->db->sql_query($sql);
		$player_count = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($result);

		if ($player_count > 0)
		{
			trigger_error(sprintf($this->user->lang['ERROR_GAME_HAS_PLAYERS'], $this->name, $player_count), E_USER_WARNING);
		}

		// Plugin-provided games (via game_registry)
		if ($this->game_registry !== null && $this->game_registry->has($this->game_id))
		{
			$provider = $this->game_registry->get($this->game_id);
			$installer = $provider->get_installer();

			$installer->uninstall(
				$this->get_table_names(),
				$this->game_id,
				$this->name
			);
			return;
		}

		// Custom game
		$installgame = new install_custom($this->db, $this->cache, $this->config, $this->user);
		$installgame->uninstall(
			$this->get_table_names(),
			$this->game_id,
			$this->name
		);
	}

	/**
	 * gets Game info from database
	 * read phpbb_bbguild_games table
	 */
	public function get_game()
	{
		$sql = 'SELECT id, game_id, game_name, status, imagename, armory_enabled,
 				bossbaseurl, zonebaseurl, apikey, apilocale, privkey, region
    			FROM ' . $this->bb_game_table . "
    			WHERE game_id = '" . $this->game_id . "'";

		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->id = $row['id'];
			$this->name = $row['game_name'];
			$this->status= ($row['status'] == 1) ? true : false;
			$this->imagename = $row['imagename'];
			$this->armory_enabled = $row['armory_enabled'];
			$this->bossbaseurl = $row['bossbaseurl'];
			$this->zonebaseurl = $row['zonebaseurl'];
			$this->apikey = $row['apikey'];
			$this->apilocale = $row['apilocale'];
			$this->privkey = $row['privkey'];
			$this->region = $row['region'];
		}
		$this->db->sql_freeresult($result);
	}

	/**
	 * update this game
	 */
	public function update_game()
	{
		$this->db->sql_transaction('begin');

		$query = $this->db->sql_build_array(
			'UPDATE', array(
			'imagename'      => substr($this->imagename, 0, 20) ,
			'armory_enabled' => $this->armory_enabled,
			'game_name'      => $this->name,
			'bossbaseurl'    => $this->bossbaseurl,
			'zonebaseurl'    => $this->zonebaseurl,
			'apikey'         => $this->apikey,
			'apilocale'      => $this->apilocale,
			'privkey'        => $this->privkey,
			'region'         => $this->region,
			)
		);

		$sql = 'UPDATE ' . $this->bb_game_table . ' SET ' . $query . " WHERE game_id = '" . $this->game_id . "'";
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');
		$this->cache->destroy('sql', $this->bb_game_table);
	}

	/**
	 * exposed games array
	 *
	 * @return array|void
	 */
	private function gamesarray()
	{
		$this->games = array();
		$sql = ' SELECT g.id, g.game_id, g.game_name, g.status, g.imagename, g.bossbaseurl, g.zonebaseurl, g.region ';
		$sql .= ' FROM ' . $this->bb_game_table . '  g';
		$sql .= ' INNER JOIN '. $this->bb_races_table . ' r ON r.game_id = g.game_id';
		$sql .= ' INNER JOIN  ' . $this->bb_classes_table . ' c ON c.game_id= g.game_id';
		$sql .= ' GROUP BY g.id, g.game_id, g.game_name, g.status, g.imagename, g.bossbaseurl, g.zonebaseurl, g.region ';
		$sql .= ' ORDER BY g.game_id';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->games[$row['game_id']] = $row['game_name'];
		}
		$this->db->sql_freeresult($result);
		return $this->games;
	}

	/**
	 * lists all games (used in game acp)
	 *
	 * @param  string $order
	 * @return array
	 */
	public function list_games($order = 'game_id ASC')
	{
		$gamelist = array();
		$sql = 'SELECT id, game_id, game_name, status, imagename, bossbaseurl, zonebaseurl, region FROM ' . $this->bb_game_table . ' ORDER BY ' . $order;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$gamelist[$row['game_id']] = array(
				'id' => $row['id'] ,
				'name' => $row['game_name'] ,
				'game_id' => $row['game_id'] ,
				'status' => $row['status'],
				'imagename' => $row['imagename'],
				'bossbaseurl'   => $row['bossbaseurl'],
				'zonebaseurl'   => $row['zonebaseurl'],
				'region'        => $row['region'],
			);
		}
		$this->db->sql_freeresult($result);

		return $gamelist;
	}

	/**
	 * updates the default game flag
	 *
	 * @param int $id
	 */
	public function update_gamedefault($id)
	{
		$this->config->set('bbguild_default_game', $id, true);
	}


}
