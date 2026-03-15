<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player detail service — loads a player and assigns template variables
 * for the individual player page.
 */

namespace avathar\bbguild\views;

use avathar\bbguild\model\admin\asset_url_resolver;
use avathar\bbguild\model\admin\log;
use avathar\bbguild\model\admin\util;
use avathar\bbguild\model\games\game_registry;
use avathar\bbguild\model\player\player;
use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\extension\manager;
use phpbb\template\template;
use phpbb\user;

class player_detail
{
	/** @var driver_interface */
	protected $db;

	/** @var user */
	protected $user;

	/** @var config */
	protected $config;

	/** @var cache_interface */
	protected $cache;

	/** @var manager */
	protected $ext_manager;

	/** @var log */
	protected $log;

	/** @var util */
	protected $util;

	/** @var game_registry */
	protected $game_registry;

	/** @var asset_url_resolver */
	protected $asset_resolver;

	/** @var string */
	protected $bb_players_table;
	/** @var string */
	protected $bb_ranks_table;
	/** @var string */
	protected $bb_classes_table;
	/** @var string */
	protected $bb_races_table;
	/** @var string */
	protected $bb_language_table;
	/** @var string */
	protected $bb_guild_table;
	/** @var string */
	protected $bb_factions_table;
	/** @var string */
	protected $bb_games_table;

	/** @var string Loaded player name */
	protected $player_name = '';

	public function __construct(
		driver_interface $db,
		user $user,
		config $config,
		cache_interface $cache,
		manager $ext_manager,
		log $log,
		util $util,
		game_registry $game_registry,
		asset_url_resolver $asset_resolver,
		string $bb_players_table,
		string $bb_ranks_table,
		string $bb_classes_table,
		string $bb_races_table,
		string $bb_language_table,
		string $bb_guild_table,
		string $bb_factions_table,
		string $bb_games_table
	)
	{
		$this->db = $db;
		$this->user = $user;
		$this->config = $config;
		$this->cache = $cache;
		$this->ext_manager = $ext_manager;
		$this->log = $log;
		$this->util = $util;
		$this->game_registry = $game_registry;
		$this->asset_resolver = $asset_resolver;
		$this->bb_players_table = $bb_players_table;
		$this->bb_ranks_table = $bb_ranks_table;
		$this->bb_classes_table = $bb_classes_table;
		$this->bb_races_table = $bb_races_table;
		$this->bb_language_table = $bb_language_table;
		$this->bb_guild_table = $bb_guild_table;
		$this->bb_factions_table = $bb_factions_table;
		$this->bb_games_table = $bb_games_table;
	}

	/**
	 * Load a player and assign all template variables.
	 *
	 * @param int      $player_id
	 * @param template $template
	 * @return bool True if found, false if not
	 */
	public function load(int $player_id, template $template): bool
	{
		if ($player_id <= 0)
		{
			return false;
		}

		$p = new player(
			$this->db, $this->config, $this->cache, $this->user,
			$this->ext_manager, $this->log, $this->util,
			$this->bb_players_table, $this->bb_ranks_table,
			$this->bb_classes_table, $this->bb_races_table,
			$this->bb_language_table, $this->bb_guild_table,
			$this->bb_factions_table, $this->bb_games_table,
			$this->game_registry, $player_id
		);

		if (!$p->player_id || $p->player_id <= 0)
		{
			return false;
		}

		$this->player_name = $p->getPlayerName();

		// Resolve rank name
		$rank_display = $this->get_rank_display($p->getPlayerRankId(), $p->getPlayerGuildId());

		// Resolve phpBB username
		$phpbb_username = '';
		if ($p->getPhpbbUserId() > 0)
		{
			$phpbb_username = $this->get_phpbb_username($p->getPhpbbUserId());
		}

		// Resolve game-specific image paths
		$ext_path_images = $this->get_game_images_path($p->getGameId());

		// Build class/race image URLs
		$class_image = '';
		if (!empty($p->getClassImage()))
		{
			$class_image = $ext_path_images . 'class_images/' . basename($p->getClassImage());
		}
		$race_image = '';
		if (!empty($p->getRaceImage()))
		{
			$race_image = $ext_path_images . 'race_images/' . basename($p->getRaceImage());
		}

		// Game name
		$game_name = $p->getGameId();
		try
		{
			$provider = $this->game_registry->get($p->getGameId());
			if ($provider !== null)
			{
				$game_name = $provider->get_game_name();
			}
		}
		catch (\Exception $e) {}

		// Gender
		$gender = ($p->getPlayerGenderId() == 0)
			? $this->user->lang('MALE')
			: $this->user->lang('FEMALE');

		// Date formatting
		$date_format = (string) $this->config['bbguild_date_format'];
		if (empty($date_format))
		{
			$date_format = 'd/m/Y';
		}

		$joindate = $p->getPlayerJoindate();
		$outdate = $p->getPlayerOutdate();
		$last_update = $p->getLastUpdate();

		$template->assign_vars([
			'PLAYER_ID'          => $p->player_id,
			'PLAYER_NAME'        => $p->getPlayerName(),
			'PLAYER_TITLE'       => $p->getPlayerTitle(),
			'PLAYER_LEVEL'       => $p->getPlayerLevel(),
			'PLAYER_CLASS'       => $p->getPlayerClass(),
			'PLAYER_CLASS_ID'    => $p->getPlayerClassId(),
			'PLAYER_RACE'        => $p->getPlayerRace(),
			'PLAYER_RACE_ID'     => $p->getPlayerRaceId(),
			'PLAYER_RANK'        => $rank_display,
			'PLAYER_RANK_ID'     => $p->getPlayerRankId(),
			'PLAYER_GENDER'      => $gender,
			'PLAYER_COLORCODE'   => $p->getColorcode(),
			'PLAYER_GUILD_NAME'  => $p->getPlayerGuildName(),
			'PLAYER_GUILD_ID'    => $p->getPlayerGuildId(),
			'PLAYER_REALM'       => $p->getPlayerRealm(),
			'PLAYER_REGION'      => $p->getPlayerRegion(),
			'PLAYER_GAME_ID'     => $p->getGameId(),
			'PLAYER_GAME_NAME'   => $game_name,
			'PLAYER_JOINDATE'    => ($joindate > 0) ? date($date_format, $joindate) : '',
			'PLAYER_OUTDATE'     => ($outdate > 0) ? date($date_format, $outdate) : '',
			'PLAYER_LAST_UPDATE' => ($last_update > 0) ? date($date_format . ' H:i', $last_update) : '',
			'PLAYER_STATUS'      => (int) $p->isPlayerStatus(),
			'PLAYER_DEACTIVATE_REASON' => $p->getDeactivateReason(),
			'PLAYER_ACHIEV'      => $p->getPlayerAchiev(),
			'PLAYER_COMMENT'     => $p->getPlayerComment(),
			'PLAYER_ARMORY_URL'  => $p->getPlayerArmoryUrl(),
			'PLAYER_PORTRAIT'    => $this->asset_resolver->resolve_portrait_url((string) $p->getPlayerPortraitUrl(), $player_id),
			'PLAYER_CLASS_IMAGE' => $class_image,
			'PLAYER_RACE_IMAGE'  => $race_image,
			'PLAYER_PHPBB_USER'  => $phpbb_username,
			'PLAYER_ROLE'        => $p->getPlayerRole(),
			'S_SHOWACH'          => (bool) $this->config['bbguild_show_achiev'],
			'S_PLAYER_ACTIVE'    => (bool) $p->isPlayerStatus(),
			'S_HAS_PORTRAIT'     => !empty($p->getPlayerPortraitUrl()),
			'S_HAS_ARMORY'       => !empty($p->getPlayerArmoryUrl()),
			'S_HAS_PHPBB_USER'   => ($p->getPhpbbUserId() > 0),
		]);

		return true;
	}

	/**
	 * @return string The loaded player's name
	 */
	public function get_player_name(): string
	{
		return $this->player_name;
	}

	/**
	 * Get the display string for a rank.
	 */
	protected function get_rank_display(int $rank_id, int $guild_id): string
	{
		$sql = 'SELECT rank_name, rank_prefix, rank_suffix
			FROM ' . $this->bb_ranks_table . '
			WHERE rank_id = ' . $rank_id . '
				AND guild_id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			return trim($row['rank_prefix'] . ' ' . $row['rank_name'] . ' ' . $row['rank_suffix']);
		}

		return '';
	}

	/**
	 * Get phpBB username string with profile link.
	 */
	protected function get_phpbb_username(int $user_id): string
	{
		$sql = 'SELECT user_id, username, user_colour
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			return get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
		}

		return '';
	}

	/**
	 * Get the web path for game-specific images.
	 */
	protected function get_game_images_path(string $game_id): string
	{
		$web_root = $this->path_helper->get_web_root_path();
		try
		{
			$provider = $this->game_registry->get($game_id);
			if ($provider !== null)
			{
				$path = $provider->get_images_path();
				$pos = strpos($path, 'ext/');
				if ($pos !== false)
				{
					return $web_root . substr($path, $pos);
				}
			}
		}
		catch (\Exception $e) {}

		// Fallback to core images
		return $web_root . 'ext/avathar/bbguild/images/';
	}
}
