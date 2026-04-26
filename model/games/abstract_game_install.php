<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Abstract Game Installer
 * Base class for game installers in the plugin architecture.
 * Implements game_install_interface with the template method pattern.
 * Subclasses must implement install_factions(), install_classes(), install_races().
 *
 */

namespace avathar\bbguild\model\games;

use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\user;

use avathar\bbguild\model\games\rpg\classes;
use avathar\bbguild\model\games\rpg\faction;
use avathar\bbguild\model\games\rpg\races;
use avathar\bbguild\model\games\rpg\roles;

/**
 * Abstract base class for game installers.
 *
 * Uses the template method pattern: install() orchestrates the process,
 * subclasses provide the game-specific data via abstract methods.
 *
 * @package avathar\bbguild\model\games
 */
abstract class abstract_game_install implements game_install_interface
{
	/** @var string */
	protected $game_id;

	/** @var array Table name map, set during install/uninstall */
	protected $table_names = [];

	/** @var driver_interface */
	protected $db;
	/** @var cache_interface */
	protected $cache;
	/** @var config */
	protected $config;
	/** @var user */
	protected $user;

	/**
	 * Constructor
	 *
	 * @param driver_interface $db
	 * @param cache_interface  $cache
	 * @param config           $config
	 * @param user             $user
	 */
	public function __construct(driver_interface $db, cache_interface $cache, config $config, user $user)
	{
		$this->db = $db;
		$this->cache = $cache;
		$this->config = $config;
		$this->user = $user;
	}

	/**
	 * Install a game.
	 *
	 * Populates factions, classes, races, roles, and inserts the game record.
	 *
	 * @param array  $table_names    Associative array of table names
	 * @param string $game_id        The unique game identifier
	 * @param string $game_name      The display name of the game
	 * @param string $boss_base_url  The base URL for boss database links
	 * @param string $zone_base_url  The base URL for zone database links
	 * @param string $region         The region code
	 * @return void
	 */
	public final function install(array $table_names, string $game_id, string $game_name, string $boss_base_url, string $zone_base_url, string $region): void
	{
		$this->table_names = $table_names;
		$this->game_id = $game_id;

		$this->db->sql_transaction('begin');

		$this->install_factions();
		$this->install_classes();
		$this->install_races();
		$this->install_roles();
		$this->install_specs();

		// insert a new entry in the game table
		$data = array(
			'game_id'        => $game_id,
			'game_name'      => $game_name,
			'imagename'      => $game_id,
			'armory_enabled' => $this->has_api_support() ? 1 : 0,
			'bossbaseurl'    => $boss_base_url,
			'zonebaseurl'    => $zone_base_url,
			'status'         => 1,
			'region'         => $region,
		);

		$sql = 'INSERT INTO ' . $this->table('bb_games_table') . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');

		$this->cache->destroy('sql', $this->table('bb_games_table'));
		$this->cache->destroy('sql', $this->table('bb_classes_table'));
		$this->cache->destroy('sql', $this->table('bb_language_table'));
		$this->cache->destroy('sql', $this->table('bb_races_table'));
		$this->cache->destroy('sql', $this->table('bb_players_table'));
		$this->cache->destroy('sql', $this->table('bb_gameroles_table'));
		if (isset($this->table_names['bb_specializations_table']))
		{
			$this->cache->destroy('sql', $this->table('bb_specializations_table'));
		}
	}

	/**
	 * Uninstall a game.
	 *
	 * Removes all factions, classes, races, roles, and the game record.
	 *
	 * @param array  $table_names Associative array of table names
	 * @param string $game_id     The unique game identifier
	 * @param string $game_name   The display name of the game
	 * @return void
	 */
	public final function uninstall(array $table_names, string $game_id, string $game_name): void
	{
		$this->table_names = $table_names;
		$this->game_id = $game_id;

		$this->db->sql_transaction('begin');

		$factions = new faction($this->db, $this->cache, $this->user, $game_id, $this->table('bb_factions_table'));
		$factions->delete_all_factions();

		$races = new races(
			$this->db, $this->config, $this->cache, $this->user,
			$this->table('bb_language_table'),
			$this->table('bb_players_table'),
			$this->table('bb_games_table'),
			$this->table('bb_races_table'),
			$this->table('bb_factions_table')
		);
		$races->game_id = $game_id;
		$races->delete_all_races();

		$classes = new classes(
			$this->db, $this->config, $this->cache, $this->user,
			$this->table('bb_language_table'),
			$this->table('bb_players_table'),
			$this->table('bb_games_table'),
			$this->table('bb_classes_table')
		);
		$classes->game_id = $game_id;
		$classes->delete_all_classes();

		$roles = new roles(
			$this->db, $this->config, $this->cache, $this->user,
			$this->table('bb_gameroles_table'),
			$this->table('bb_language_table'),
			$this->table('bb_games_table'),
			$this->table('bb_classes_table')
		);
		$roles->game_id = $game_id;
		$roles->delete_all_roles();

		$sql = 'DELETE FROM ' . $this->table('bb_games_table') . " WHERE game_id = '" . $this->db->sql_escape($game_id) . "'";
		$this->db->sql_query($sql);

		// Drop specializations rows for this game (if the table is wired in).
		// Older installs without the v200b4 schema simply skip this step.
		if (isset($this->table_names['bb_specializations_table']))
		{
			$this->db->sql_query('DELETE FROM ' . $this->table('bb_specializations_table') . " WHERE game_id = '" . $this->db->sql_escape($game_id) . "'");
		}

		$this->db->sql_transaction('commit');

		$this->cache->destroy('sql', $this->table('bb_games_table'));
		$this->cache->destroy('sql', $this->table('bb_classes_table'));
		$this->cache->destroy('sql', $this->table('bb_language_table'));
		$this->cache->destroy('sql', $this->table('bb_races_table'));
		$this->cache->destroy('sql', $this->table('bb_players_table'));
		if (isset($this->table_names['bb_specializations_table']))
		{
			$this->cache->destroy('sql', $this->table('bb_specializations_table'));
		}
	}

	/**
	 * Get a table name from the table_names map.
	 *
	 * @param string $key The logical table name key
	 * @return string The actual table name
	 */
	protected function table(string $key): string
	{
		return $this->table_names[$key];
	}

	/**
	 * Whether this game supports an external API (armory).
	 *
	 * Override in subclass to return true if the game has API support.
	 *
	 * @return bool
	 */
	protected function has_api_support(): bool
	{
		return false;
	}

	/**
	 * Installs factions for this game.
	 * Must be implemented by each game installer.
	 */
	abstract protected function install_factions();

	/**
	 * Installs game classes for this game.
	 * Must be implemented by each game installer.
	 */
	abstract protected function install_classes();

	/**
	 * Installs races for this game.
	 * Must be implemented by each game installer.
	 */
	abstract protected function install_races();

	/**
	 * Install default roles (DPS, Healer, Tank).
	 *
	 * Games can override this for custom roles (e.g. GW2).
	 */
	protected function install_roles()
	{
		$this->db->sql_query('DELETE FROM ' . $this->table('bb_gameroles_table') . " WHERE role_id < 3 and game_id = '" . $this->db->sql_escape($this->game_id) . "'");
		$this->db->sql_query('DELETE FROM ' . $this->table('bb_language_table') . " WHERE attribute_id < 3 and attribute = 'role' and game_id = '" . $this->db->sql_escape($this->game_id) . "'");

		$sql_ary = array(
			array(
				'game_id'    => $this->game_id,
				'role_id'    => 0,
				'role_color' => '#FF4455',
				'role_icon'  => 'dps_icon',
			),
			array(
				'game_id'    => $this->game_id,
				'role_id'    => 1,
				'role_color' => '#11FF77',
				'role_icon'  => 'healer_icon',
			),
			array(
				'game_id'    => $this->game_id,
				'role_id'    => 2,
				'role_color' => '#c3834c',
				'role_icon'  => 'tank_icon',
			),
		);
		$this->db->sql_multi_insert($this->table('bb_gameroles_table'), $sql_ary);

		$sql_ary = array(
			array('game_id' => $this->game_id, 'attribute_id' => 0, 'language' => 'en', 'attribute' => 'role', 'name' => 'Damage',  'name_short' => 'DPS'),
			array('game_id' => $this->game_id, 'attribute_id' => 1, 'language' => 'en', 'attribute' => 'role', 'name' => 'Healer',  'name_short' => 'HPS'),
			array('game_id' => $this->game_id, 'attribute_id' => 2, 'language' => 'en', 'attribute' => 'role', 'name' => 'Defense', 'name_short' => 'DEF'),
			array('game_id' => $this->game_id, 'attribute_id' => 0, 'language' => 'fr', 'attribute' => 'role', 'name' => 'Dégats',  'name_short' => 'DPS'),
			array('game_id' => $this->game_id, 'attribute_id' => 1, 'language' => 'fr', 'attribute' => 'role', 'name' => 'Soigneur','name_short' => 'HPS'),
			array('game_id' => $this->game_id, 'attribute_id' => 2, 'language' => 'fr', 'attribute' => 'role', 'name' => 'Défense', 'name_short' => 'DEF'),
			array('game_id' => $this->game_id, 'attribute_id' => 0, 'language' => 'de', 'attribute' => 'role', 'name' => 'Kämpfer', 'name_short' => 'Schaden'),
			array('game_id' => $this->game_id, 'attribute_id' => 1, 'language' => 'de', 'attribute' => 'role', 'name' => 'Heiler',  'name_short' => 'Heil'),
			array('game_id' => $this->game_id, 'attribute_id' => 2, 'language' => 'de', 'attribute' => 'role', 'name' => 'Verteidigung', 'name_short' => 'Schutz'),
			array('game_id' => $this->game_id, 'attribute_id' => 0, 'language' => 'it', 'attribute' => 'role', 'name' => 'Danni',   'name_short' => 'Danni'),
			array('game_id' => $this->game_id, 'attribute_id' => 1, 'language' => 'it', 'attribute' => 'role', 'name' => 'Cura',    'name_short' => 'Cura'),
			array('game_id' => $this->game_id, 'attribute_id' => 2, 'language' => 'it', 'attribute' => 'role', 'name' => 'Difeza',  'name_short' => 'Tank'),
		);
		$this->db->sql_multi_insert($this->table('bb_language_table'), $sql_ary);
	}

	/**
	 * Install specializations (subclasses). Default no-op — opt in by
	 * overriding in a plugin's installer when the game has specs (issue #331).
	 *
	 * Skipped automatically when the bb_specializations table isn't wired
	 * in (older installs that haven't run core migration v200b4 yet).
	 */
	protected function install_specs(): void
	{
	}
}
