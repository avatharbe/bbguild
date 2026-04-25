<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player ACP module
 *
 */

namespace avathar\bbguild\acp;

use avathar\bbguild\model\admin\admin;
use avathar\bbguild\model\admin\constants;
use avathar\bbguild\model\player\player;
use avathar\bbguild\model\player\guilds;
use avathar\bbguild\model\games\game;
use avathar\bbguild\model\games\rpg\roles;
use avathar\bbguild\model\player\ranks;

/**
 * This class manages player general info
 *
* @package avathar\bbguild\acp
 */
class player_module
{
	/**
	 * trigger link
	 *
	 * @var string
	 */
	public $link = '';

	protected $phpbb_container;
	/**
	 * @var \phpbb\request\request
	 **/
	protected $request;
	/**
	 * @var \phpbb\template\template
	 **/
	protected $template;
	/**
	 * @var \phpbb\user
	 **/
	protected $user;
	/**
	 * @var \phpbb\db\driver\driver_interface
	 */
	protected $db;

	public $id;
	public $mode;
	public $auth;
	public $tpl_name;
	public $page_title;
	protected $ext_path;
	protected $games;

	protected $factionroute;

	/** @var \phpbb\config\config */
	protected $config;
	/** @var \phpbb\cache\driver\driver_interface */
	protected $bbguild_cache;
	/** @var \avathar\bbguild\model\admin\log */
	protected $bbguild_log;
	/** @var \avathar\bbguild\model\admin\util */
	protected $bbguild_util;
	/** @var \phpbb\extension\manager */
	protected $bbguild_ext_manager;
	/** @var \avathar\bbguild\model\games\game_registry */
	protected $bbguild_game_registry;
	/** @var \phpbb\path_helper */
	protected $path_helper;
	/** @var \avathar\bbguild\model\admin\asset_url_resolver */
	protected $asset_resolver;

	protected $bb_players_table;
	protected $bb_ranks_table;
	protected $bb_classes_table;
	protected $bb_races_table;
	protected $bb_language_table;
	protected $bb_guild_table;
	protected $bb_factions_table;
	protected $bb_games_table;
	protected $bb_gameroles_table;

	/**
	* @var \phpbb\controller\helper
	*/
	protected  $helper;

	/**
	 * @type guilds
	 */
	protected $guild;

	/**
	 * Check if a game has API capability.
	 *
	 * Checks the game_registry first, falls back to the game's armory_enabled DB flag.
	 *
	 * @param string $game_id
	 * @return bool
	 */
	private function game_has_api($game_id)
	{
		global $phpbb_container;

		// Try game registry first
		if (isset($phpbb_container))
		{
			try
			{
				$registry = $phpbb_container->get('avathar.bbguild.game_registry');
				$provider = $registry->get($game_id);
				if ($provider !== null)
				{
					return $provider->has_api();
				}
			}
			catch (\Exception $e)
			{
				// Registry not available, fall through
			}
		}

		// Fall back to checking the game's armory_enabled flag in DB
		$game_check = new game(
			$this->db, $this->bbguild_cache, $this->config, $this->user, $this->bbguild_ext_manager,
			$this->bb_classes_table, $this->bb_races_table,
			$this->bb_language_table, $this->bb_factions_table,
			$this->bb_games_table
		);
		$game_check->game_id = $game_id;
		$game_check->get_game();
		return (bool) $game_check->getArmoryEnabled();
	}

	/**
	 * Look up the game provider for a given game_id.
	 *
	 * @param string $game_id
	 * @return \avathar\bbguild\model\games\game_provider_interface|null
	 */
	private function get_game_provider($game_id)
	{
		global $phpbb_container;
		if (isset($phpbb_container))
		{
			try
			{
				$registry = $phpbb_container->get('avathar.bbguild.game_registry');
				return $registry->get($game_id);
			}
			catch (\Exception $e) {}
		}
		return null;
	}

	/**
	 * @param $id
	 * @param $mode
	 */
	public function main($id, $mode)
	{
		global $user, $db, $template, $phpbb_admin_path, $phpEx;
		global $request, $phpbb_container, $auth;

		$this->helper = $phpbb_container->get('controller.helper');
		$this->factionroute =  $this->helper->route('avathar_bbguild_01', array());

		$this->id = $id;
		$this->mode = $mode;
		$this->request=$request;
		$this->template=$template;
		$this->user=$user;
		$this->db=$db;
		$this->phpbb_container = $phpbb_container;
		$this->auth=$auth;
		$this->config = $phpbb_container->get('config');
		$this->bbguild_cache = $phpbb_container->get('cache.driver');
		$this->bbguild_log = $phpbb_container->get('avathar.bbguild.log');
		$this->bbguild_util = $phpbb_container->get('avathar.bbguild.util');
		$this->bbguild_ext_manager = $phpbb_container->get('ext.manager');
		$this->bbguild_game_registry = $phpbb_container->get('avathar.bbguild.game_registry');
		$this->path_helper = $phpbb_container->get('path_helper');
		$this->asset_resolver = $phpbb_container->get('avathar.bbguild.asset_url_resolver');

		// Resolve table names from container parameters
		$this->bb_players_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_players');
		$this->bb_ranks_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_ranks');
		$this->bb_classes_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_classes');
		$this->bb_races_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_races');
		$this->bb_language_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_language');
		$this->bb_guild_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_guild');
		$this->bb_factions_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_factions');
		$this->bb_games_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_games');
		$this->bb_gameroles_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_gameroles');



		$form_key = 'avathar/bbguild';
		add_form_key($form_key);
		$this->tpl_name   = 'acp_' . $mode;

		if (! $this->auth->acl_get('a_bbguild'))
		{
			trigger_error($this->user->lang['NOAUTH_A_PLAYERS_MAN']);
		}

		//css trigger
		$this->template->assign_vars(
			array (
				'S_BBGUILD' => true,
			)
		);

		// Build installed games list
		$gameObj = new game(
			$this->db, $this->bbguild_cache, $this->config, $this->user, $this->bbguild_ext_manager,
			$this->bb_classes_table, $this->bb_races_table,
			$this->bb_language_table, $this->bb_factions_table,
			$this->bb_games_table
		);
		$gamelist = $gameObj->list_games();
		$this->games = array();
		foreach ($gamelist as $game_id => $data)
		{
			$this->games[$game_id] = $data['name'];
		}

		switch ($mode)
		{
			/**
			 * List players
			 */
			case 'listplayers':
				$this->link = '<br /><a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers') . '"><h3>Return to Index</h3></a>';
				$this->guild = new guilds($this->db, $this->user, $this->config, $this->bbguild_cache, $this->bbguild_log, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table);

				$guildlist = $this->guild->guildlist(1);
				if (count((array) $guildlist) == 0  )
				{
					trigger_error('ERROR_NOGUILD', E_USER_WARNING);
				}

				if (count((array) $guildlist) == 1 )
				{
					$this->guild->setGuildid($guildlist[0]['id']);
					$this->guild->setName($guildlist[0]['name']);
					if ($this->guild->getGuildid() == 0 && $this->guild->getName() == 'Guildless' )
					{
						trigger_error('ERROR_NOGUILD', E_USER_WARNING);
					}
				}

				foreach ($guildlist as $g)
				{
					$this->guild->setGuildid($g['id']);
					break;
				}

				$activate = $this->request->is_set_post('deactivate');
				if ($activate)
				{
					$this->ActivateList();
				}

				// batch delete
				$del_batch = $this->request->is_set_post('delete');
				if ($del_batch)
				{
					$this->player_batch_delete();
				}

				// guild dropdown query
				$getguild_dropdown = $this->request->is_set_post('player_guild_id');
				if ($getguild_dropdown)
				{
					// user selected dropdown - get guildid
					$this->guild->setGuildid($this->request->variable('player_guild_id', 0));
				}

				$sortlink = isset($_GET[constants::URI_GUILD])  ? true : false;
				if ($sortlink)
				{
					// user selected dropdown - get guildid
					$this->guild->setGuildid($this->request->variable(constants::URI_GUILD, 0));
				}

				$charapicall = $this->request->is_set_post('charapicall');
				if ($charapicall)
				{
					if (confirm_box(true))
					{
						list($i, $log) = $this->CallCharacterAPI();
						trigger_error(sprintf($this->user->lang['CHARAPIDONE'], $i, $log), E_USER_NOTICE);
					}
					else
					{
						$s_hidden_fields = build_hidden_fields(
							array(
								'charapicall' => true ,
								'hidden_guildid' => $this->request->variable('player_guild_id', 0),
								'hidden_minlevel' => $this->request->variable('hidden_minlevel', $this->request->variable('minlevel', 0)),
								'hidden_maxlevel' => $this->request->variable('maxlevel', $this->request->variable('hidden_maxlevel', 200)),
								'hidden_active' => $this->request->variable('active', $this->request->variable('hidden_active', 0)),
								'hidden_nonactive' => $this->request->variable('nonactive', $this->request->variable('hidden_nonactive', 0)),
								'hidden_player_name' => $this->request->variable('player_name', $this->request->variable('hidden_player_name', '', true), true)
							)
						);
						confirm_box(false, $this->user->lang['WARNING_BATTLENET'], $s_hidden_fields);

					}
				}

				// add player button redirect
				$showadd = $this->request->is_set_post('playeradd');
				if ($showadd)
				{
					$a = $this->request->variable('player_guild_id', $this->request->variable('hidden_guildid', 0));
					redirect(append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=addplayer&amp;guild_id=' . $a));
					break;
				}

				// pageloading
				$this->BuildTemplateListPlayers($mode);
				break;

			/***************************************/
			// add player
			/***************************************/
			case 'addplayer' :
				$this->link = '<br /><a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers') . '"><h3>' . $this->user->lang['RETURN_PLAYERLIST'] . '</h3></a>';

				$add = $this->request->is_set_post('add');
				$update = $this->request->is_set_post('update');
				$delete = $this->request->variable('delete', '')  != '' ? true : false;

				if ($add || $update)
				{
					if (! check_form_key('avathar/bbguild'))
					{
						trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action));
					}
				}

				if ($add)
				{
					$this->Addplayer();

				}

				if ($update)
				{
					$this->UpdatePlayer();
				}

				if ($delete)
				{
					if (confirm_box(true))
					{
						$deleteplayer = $this->DeletePlayer();
					}
					else
					{
						$deleteplayer = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry);
						$deleteplayer->player_id = $this->request->variable('player_id', 0);
						$deleteplayer->Getplayer();
						$s_hidden_fields = build_hidden_fields(
							array(
								'delete' => true ,
								'del_player_id' => $deleteplayer->player_id)
						);

						confirm_box(false, sprintf($this->user->lang['CONFIRM_DELETE_PLAYER'], $deleteplayer->getPlayerName()), $s_hidden_fields);
					}
					unset($deleteplayer);
				}

				$this->BuildTemplateAddEditplayers($mode);
				break;

			default:
				$this->page_title = 'ACP_BBGUILD_PLAYER_ADD';
				$success_message = $this->user->lang['L_ERROR'];
				trigger_error($success_message . $this->link, E_USER_WARNING);
		}
	}

	/**
	 * function to batch delete players, called from listing
	 */
	private function player_batch_delete()
	{

		$players_to_delete = $this->request->variable('delete_id', array(0));

		if (! is_array($players_to_delete))
		{
			return;
		}

		if (count($players_to_delete) == 0)
		{
			return;
		}

		if (confirm_box(true))
		{
			// recall hidden vars
			$players_to_delete = $this->request->variable('delete_id', array(0 => 0));
			$player_names = $this->request->variable('players', array(0 => ''), true);
			foreach ($players_to_delete as $playerid => $value)
			{
				$delplayer = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry);
				$delplayer->player_id = $playerid;
				$delplayer->Getplayer();
				$delplayer->Deleteplayer();
				unset($delplayer);
			}
			$str_players = implode(',', $player_names);
			$success_message = sprintf($this->user->lang['ADMIN_DELETE_PLAYERS_SUCCESS'], $str_players);
			trigger_error($success_message . $this->link, E_USER_NOTICE);
		}
		else
		{
			$sql = 'SELECT player_name, player_id FROM ' . $this->bb_players_table . ' WHERE ' . $this->db->sql_in_set('player_id', array_keys($players_to_delete));
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$player_names[] = $row['player_name'];
			}
			$this->db->sql_freeresult($result);
			$s_hidden_fields = build_hidden_fields(
				array(
					'delete' => true ,
					'delete_id' => $players_to_delete ,
					'players' => $player_names)
			);
			$str_players = implode(', ', $player_names);

			confirm_box(false, sprintf($this->user->lang['CONFIRM_DELETE_PLAYER'], $str_players), $s_hidden_fields);
		}
	}

	/**
	 * Add a new player
	 */
	private function Addplayer()
	{
		global $phpbb_admin_path, $phpEx;


		$newplayer = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry);
		$newplayer->game_id = $this->request->variable('game_id', '');
		$newplayer->setPlayerName($this->request->variable('player_name', '', true));
		$newplayer->setPlayerTitle($this->request->variable('player_title', '', true));
		$newplayer->setPlayerGuildId($this->request->variable('player_guild_id', 0));
		$newplayer->setPlayerRankId($this->request->variable('player_rank_id', 99));
		$newplayer->setPlayerLevel($this->request->variable('player_level', 1));
		$newplayer->setPlayerRealm($this->request->variable('realm', ''));

		/* @todo */
		$newplayer->setPlayerRegion($this->request->variable('hidden_player_region', ''));

		if (!in_array($newplayer->getPlayerRegion(), $newplayer->getRegionlist()))
		{
			$newplayer->setPlayerRegion('');
		}
		$newplayer->setPlayerRaceId($this->request->variable('player_race_id', 1));
		$newplayer->setPlayerClassId($this->request->variable('player_class_id', 1));
		$newplayer->setPlayerRole($this->request->variable('player_role', 0));
		$newplayer->setPlayerGenderId($this->request->variable('gender', 0));
		$newplayer->setPlayerComment($this->request->variable('player_comment', '', true));
		$newplayer->setPlayerJoindate(mktime(0, 0, 0, $this->request->variable('player_joindate_mo', 0), $this->request->variable('player_joindate_d', 0), $this->request->variable('player_joindate_y', 0)));
		$newplayer->setPlayerOutdate(0);
		if ($this->request->variable('player_outdate_mo', 0) + $this->request->variable('player_outdate_d', 0) != 0)
		{
			$newplayer->setPlayerOutdate(mktime(0, 0, 0, $this->request->variable('player_outdate_mo', 0), $this->request->variable('player_outdate_d', 0), $this->request->variable('player_outdate_y', 0)));
		}
		$newplayer->setPlayerAchiev(0);
		$newplayer->setPlayerArmoryUrl($this->request->variable('player_armorylink', '', true));
		$newplayer->setPhpbbUserId($this->request->variable('phpbb_user_id', 0));
		$newplayer->setPlayerStatus($this->request->variable('activated', '') == 'on' ? 1 : 0);

		$this->guild = new guilds($this->db, $this->user, $this->config, $this->bbguild_cache, $this->bbguild_log, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $newplayer->getPlayerGuildId());
		$this->guild->get_guild();

		//only call armory if it is enabled.
		if ($newplayer->getPlayerRankId() < 90 && $this->guild->isArmoryEnabled() == 1 )
		{
			$newplayer->Armory_getplayer($this->get_game_provider($newplayer->game_id));
		}

		$newplayer->Makeplayer();

		if ($newplayer->player_id > 0)
		{
			//record added. now update some stats
			meta_refresh(2, append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;' . constants::URI_GUILD . '=' . $newplayer->getPlayerGuildId()));
			$success_message = sprintf($this->user->lang['ADMIN_ADD_PLAYER_SUCCESS'], ucwords($newplayer->getPlayerName()), date('F j, Y, g:i a'));

			$this->link = '<br /><a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;' . constants::URI_GUILD . '=' . $newplayer->getPlayerGuildId()) . '"><h3>' . $this->user->lang['RETURN_PLAYERLIST'] . '</h3></a>';
			trigger_error($success_message . $this->link, E_USER_NOTICE);

		}
		else
		{
			meta_refresh(2, append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;' . constants::URI_GUILD . '=' . $newplayer->getPlayerGuildId()));

			$failure_message = sprintf($this->user->lang['ADMIN_ADD_PLAYER_FAIL'], ucwords($newplayer->getPlayerName()));
			trigger_error($failure_message . $this->link, E_USER_WARNING);
		}
	}

	/**
	 * Update bbguild player
	 */
	private function UpdatePlayer()
	{
		global $phpbb_admin_path, $phpEx;


		$updateplayer = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry);
		$updateplayer->player_id = $this->request->variable('hidden_player_id', 0);

		if ($updateplayer->player_id == 0)
		{
			$updateplayer->player_id = $this->request->variable(constants::URI_NAMEID, 0);
		}
		$updateplayer->Getplayer();

		$updateplayer->game_id = $this->request->variable('game_id', '');
		$updateplayer->setPlayerClassId($this->request->variable('player_class_id', 0));
		$updateplayer->setPlayerRaceId($this->request->variable('player_race_id', 0));
		$updateplayer->setPlayerRole($this->request->variable('player_role', 0));
		$updateplayer->setPlayerRealm($this->request->variable('realm', ''));

		/* @todo */

		$updateplayer->setPlayerRegion($this->request->variable('hidden_player_region', ''));
		if (!in_array($updateplayer->getPlayerRegion(), $updateplayer->getRegionlist()))
		{
			$updateplayer->setPlayerRegion('');
		}

		$updateplayer->setPlayerName($this->request->variable('player_name', '', true));
		$updateplayer->setPlayerGenderId($this->request->variable('gender', 0));
		$updateplayer->setPlayerTitle($this->request->variable('player_title', '', true));
		$updateplayer->setPlayerGuildId($this->request->variable('player_guild_id', 0));
		$updateplayer->setPlayerRankId($this->request->variable('player_rank_id', 99));
		$updateplayer->setPlayerLevel($this->request->variable('player_level', 0));
		$updateplayer->setPlayerJoindate(mktime(0, 0, 0, $this->request->variable('player_joindate_mo', 0), $this->request->variable('player_joindate_d', 0), $this->request->variable('player_joindate_y', 0)));
		$updateplayer->setPlayerOutdate(mktime(0, 0, 0, 12, 31, 2030));

		if ($this->request->variable('player_outdate_mo', 0) + $this->request->variable('player_outdate_d', 0) != 0)
		{
			$updateplayer->setPlayerOutdate(mktime(0, 0, 0, $this->request->variable('player_outdate_mo', 0), $this->request->variable('player_outdate_d', 0), $this->request->variable('player_outdate_y', 0)));
		}

		$updateplayer->setPlayerAchiev($this->request->variable('player_achiev', 0));
		$updateplayer->setPlayerComment($this->request->variable('player_comment', '', true));
		$updateplayer->setPhpbbUserId($this->request->variable('phpbb_user_id', 0));

		$this->guild = new guilds($this->db, $this->user, $this->config, $this->bbguild_cache, $this->bbguild_log, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $updateplayer->getPlayerGuildId());
		$this->guild->get_guild();

		// Fall back to guild region if player region is empty
		if (empty($updateplayer->getPlayerRegion()))
		{
			$updateplayer->setPlayerRegion($this->guild->getRegion());
		}

		if ($updateplayer->getPlayerRankId() < 90 && $this->guild->isArmoryEnabled() == 1 )
		{
			$updateplayer->Armory_getplayer($this->get_game_provider($updateplayer->game_id));
		}

		$updateplayer->setPlayerStatus($this->request->variable('activated', '') == 'on' ? 1 : 0);

		$old_player = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry);
		$old_player->player_id = $updateplayer->player_id;
		$old_player->Getplayer();
		$updateplayer->Updateplayer($old_player);

		meta_refresh(1, append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;' . constants::URI_GUILD . '=' . $updateplayer->getPlayerGuildId()));
		$this->link = '<br /><a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;' . constants::URI_GUILD . '=' . $updateplayer->getPlayerGuildId()) . '"><h3>' . $this->user->lang['RETURN_PLAYERLIST'] . '</h3></a>';
		$success_message = sprintf($this->user->lang['ADMIN_UPDATE_PLAYER_SUCCESS'], $updateplayer->getPlayerName());
		trigger_error($success_message . $this->link);

	}

	/**
	 * Delete bbguild player
	 */
	private function DeletePlayer()
	{
		global $phpbb_admin_path, $phpEx;

		$deleteplayer = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry);
		$deleteplayer->player_id = $this->request->variable('del_player_id', 0);
		$deleteplayer->Getplayer();
		$deleteplayer->Deleteplayer();
		$success_message = sprintf($this->user->lang['ADMIN_DELETE_PLAYERS_SUCCESS'], $deleteplayer->getPlayerName());

		meta_refresh(1, append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;' . constants::URI_GUILD . '=' . $deleteplayer->getPlayerGuildId()));
		$this->link = '<br /><a href="' . append_sid(
				"{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;' .
				constants::URI_GUILD . '=' . $deleteplayer->getPlayerGuildId()
			) . '"><h3>' . $this->user->lang['RETURN_PLAYERLIST'] . '</h3></a>';

		trigger_error($success_message . $this->link, E_USER_WARNING);

	}

	/**
	 * Activates/deactivates the selected players
	 */
	private function ActivateList()
	{

		if (!check_form_key('avathar/bbguild'))
		{
			trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action));
		}
		$activateplayer = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry);
		$activate_players = $this->request->variable('activate_id', array(0));
		$player_window = $this->request->variable('hidden_player', array(0));
		$activateplayer->Activateplayers($activate_players, $player_window);
		unset($activateplayer);
	}

	/**
	 * Call the Character API
	 */
	private function CallCharacterAPI()
	{

		$this->guild = new guilds($this->db, $this->user, $this->config, $this->bbguild_cache, $this->bbguild_log, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table);
		$this->guild->setGuildid($this->request->variable('hidden_guildid', 0));
		$this->guild->get_guild();

		$minlevel = $this->request->variable('hidden_minlevel', 0);
		$maxlevel = $this->request->variable('hidden_maxlevel', 200);
		$selectactive = $this->request->variable('hidden_active', 0);
		$selectnonactive = $this->request->variable('hidden_nonactive', 0);
		$player_filter = $this->request->variable('hidden_player_name', '', true);

		$players_result = $this->guild->list_players('player_id', 0, 0, $minlevel, $maxlevel, $selectactive, $selectnonactive, $player_filter, true);

		$log = '';
		$i = 0;
		$j=0;
		while ($row = $this->db->sql_fetchrow($players_result))
		{
			if ($j > 100)
			{
				break;
			}
			$player = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry, $row['player_id']);

			$last_update = $player->getLastUpdate();

			$diff = \round(\abs((\time() - $last_update)) / 86400, 2);

			// 1 days ago ? call armory
			if ($diff > 1)
			{
				$i += 1;
				if ($log != '')
				{
					$log .= ', ';
				}
				$old_player = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry, $row['player_id']);

				if (isset($player))
				{
					if ($player->getPlayerRankId() < 90)
					{
						$player->Armory_getplayer($this->get_game_provider($player->game_id));
					}
					$player->Updateplayer($old_player);
				}

				unset($old_player);
				$log .= $row['player_name'];
			}

			unset($player);
			$j++;

		}
		$this->db->sql_freeresult($players_result);
		unset($players_result);
		return array($i, $log);

	}

	/**
	 * List Players
	 *
	 * @param $mode
	 * @param guilds $Guild
	 */
	private function BuildTemplateListPlayers($mode)
	{
		global  $config, $phpbb_admin_path, $phpEx, $phpbb_container;

		// fill popup and set selected to default selection
		$this->guild->get_guild();
		$guildlist = $this->guild->guildlist(1);
		foreach ($guildlist as $g)
		{
			$this->template->assign_block_vars(
				'guild_row', array(
					'VALUE'    => $g['id'],
					'SELECTED' => ($g['id'] == $this->guild->getGuildid()) ? ' selected="selected"' : '',
					'OPTION'   => (!empty($g['name'])) ? $g['name'] : '(None)')
			);
		}
		$previous_data = '';
		//get window
		$start    = $this->request->variable('start', 0, false);
		$minlevel = $this->request->variable('minlevel', 0);
		$maxlevel = $this->request->variable('maxlevel', 200);

		if (isset($_GET['active']) || isset($_GET['nonactive']) || $this->request->is_set_post('search') )
		{
			$selectactive    = $this->request->variable('active', 0);
			$selectnonactive = $this->request->variable('nonactive', 0);
		}
		else
		{
			// set standard
			$selectactive    = 1;
			$selectnonactive = 1;
		}
		$player_filter = $this->request->variable('player_name', '', true);
		$sort_order = array(
			0 => array('player_name', 'player_name desc'),
			1 => array('username', 'username desc'),
			2 => array('player_level', 'player_level desc'),
			3 => array('player_class', 'player_class desc'),
			4 => array('rank_name', 'rank_name desc'),
			5 => array('last_update', 'last_update desc'),
			7 => array('player_id', 'player_id desc')
		);
		$util = $this->phpbb_container->get('avathar.bbguild.util');
		$current_order   = $util->switch_order($sort_order);
		$sort_index      = explode('.', $current_order['uri']['current']);
		$previous_source = preg_replace('/( (asc|desc))?/i', '', $sort_order[$sort_index[0]][$sort_index[1]]);
		$show_all        = ((isset($_GET['show'])) && $this->request->variable('show', '') == 'all') ? true : false;

		$result       = $this->guild->list_players($current_order['sql'], 0, 0, $minlevel, $maxlevel, $selectactive, $selectnonactive, $player_filter);
		$player_count = 0;

		while ($row = $this->db->sql_fetchrow($result))
		{
			$player_count += 1;
		}
		if (!($result))
		{
			trigger_error($this->user->lang['ERROR_PLAYERNOTFOUND'], E_USER_WARNING);
		}
		$this->db->sql_freeresult($result);
		$players_result = $this->guild->list_players($current_order['sql'], $start, 1, $minlevel, $maxlevel, $selectactive, $selectnonactive, $player_filter);
		$lines          = 0;
		$image_cache = [];
		while ($row = $this->db->sql_fetchrow($players_result))
		{
			$phpbb_user_id = (int) $row['phpbb_user_id'];
			$race_image    = (string) (($row['player_gender_id'] == 0) ? $row['image_male'] : $row['image_female']);
			$lines += 1;

			// Resolve game-specific image paths via game_registry
			$game_id = $row['game_id'] ?? '';
			if (!isset($image_cache[$game_id]))
			{
				$provider = $this->bbguild_game_registry->get($game_id);
				if ($provider)
				{
					$image_cache[$game_id] = [
						'path' => $provider->get_images_path(),
						'web'  => $this->path_helper->update_web_root_path($provider->get_images_path()),
					];
				}
				else
				{
					$image_cache[$game_id] = ['path' => '', 'web' => ''];
				}
			}
			$img_path = $image_cache[$game_id]['path'];
			$img_web  = $image_cache[$game_id]['web'];

			if ($img_path && file_exists($img_path . 'class_images/' . $row['imagename'] . '.png'))
			{
				$class_img = $img_web . 'class_images/' . $row['imagename'] . '.png';
			}
			else
			{
				$class_img = '';
			}

			if ($img_path && file_exists($img_path . 'race_images/' . $race_image . '.png'))
			{
				$race_img = $img_web . 'race_images/' . $race_image . '.png';
			}
			else
			{
				$race_img = '';
			}

			$this->template->assign_block_vars(
				'players_row', array(
					'S_READONLY'           => ($row['rank_id'] == 90 || $row['rank_id'] == 99) ? true : false,
					'STATUS'               => ($row['player_status'] == 1) ? 'checked="checked" ' : '',
					'ID'                   => $row['player_id'],
					'COUNT'                => $player_count,
					'NAME'                 => $row['rank_prefix'] . $row['player_name'] . $row['rank_suffix'],
					'USERNAME'             => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
					'RANK'                 => $row['rank_name'],
					'LEVEL'                => ($row['player_level'] > 0) ? $row['player_level'] : '&nbsp;',
					'ARMOR'                => (!empty($row['armor_type'])) ? $row['armor_type'] : '&nbsp;',
					'COLORCODE'            => ($row['colorcode'] == '') ? '#254689' : $row['colorcode'],
					'CLASS_IMAGE'          => $class_img,
					'S_CLASS_IMAGE_EXISTS' => !empty($class_img),
					'RACE_IMAGE'           => $race_img,
					'S_RACE_IMAGE_EXISTS'  => !empty($race_img),
					'CLASS'                => ($row['player_class'] != 'NULL') ? $row['player_class'] : '&nbsp;',
					'LAST_UPDATE'          => ($row['last_update'] == 0) ? '' : $this->user->format_date($row['last_update']),
					'U_VIEW_USER'          => append_sid("{$phpbb_admin_path}index.$phpEx", "i=users&amp;icat=13&amp;mode=overview&amp;u=$phpbb_user_id"),
					'U_VIEW_PLAYER'        => append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=addplayer&amp;' . constants::URI_NAMEID . '=' . $row['player_id']),
					'U_DELETE_PLAYER'      => append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=addplayer&amp;delete=1&amp;' . constants::URI_NAMEID . '=' . $row['player_id']))
			);
			$previous_data = $row[$previous_source];
		}
		$this->db->sql_freeresult($players_result);

		$footcount_text   = sprintf($this->user->lang['LISTPLAYERS_FOOTCOUNT'], $player_count);

		$playerpagination = $this->phpbb_container->get('pagination');

		$pagination_url = append_sid(
			"{$phpbb_admin_path}index.$phpEx",
			'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;o=' . $current_order['uri']['current'] .
			'&amp;' . constants::URI_GUILD . '=' . $this->guild->getGuildid() .
			'&amp;minlevel=' . $minlevel .
			'&amp;maxlevel=' . $maxlevel .
			'&amp;active=' . $selectactive .
			'&amp;nonactive=' . $selectnonactive
		);

		$playerpagination->generate_template_pagination($pagination_url, 'pagination', 'start', $player_count, $config['bbguild_user_llimit'], $start, true);

		$this->template->assign_vars(
			array(
				'F_SELECTACTIVE'        => $selectactive,
				'F_SELECTNONACTIVE'     => $selectnonactive,
				'GUILDID'               => $this->guild->getGuildid(),
				'GUILDNAME'             => $this->guild->getName(),
				'MINLEVEL'              => $minlevel,
				'MAXLEVEL'              => $maxlevel,
				'START'                 => $start,
				'PLAYER_NAME'           => $player_filter,
				'F_PLAYERS'             => append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module') . '&amp;mode=addplayer',
				'F_PLAYERS_LIST'        => append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module') . '&amp;mode=listplayers',
				'L_TITLE'               => $this->user->lang['ACP_MM_LISTPLAYERS'],
				'L_EXPLAIN'             => $this->user->lang['ACP_MM_LISTPLAYERS_EXPLAIN'],
				'O_NAME'                => append_sid(
					"{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;o=' . $current_order['uri'][0] . '&amp;' . constants::URI_GUILD . '=' . $this->guild->getGuildid() . '&amp;minlevel=' . $minlevel .
					'&amp;maxlevel=' . $maxlevel .
					'&amp;active=' . $selectactive .
					'&amp;nonactive=' . $selectnonactive
				),
				'O_USERNAME'            => append_sid(
					"{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;o=' . $current_order['uri'][1] . '&amp;' . constants::URI_GUILD . '=' . $this->guild->getGuildid() . '&amp;minlevel=' . $minlevel .
					'&amp;maxlevel=' . $maxlevel .
					'&amp;active=' . $selectactive .
					'&amp;nonactive=' . $selectnonactive
				),
				'O_LEVEL'               => append_sid(
					"{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;o=' . $current_order['uri'][2] . '&amp;' . constants::URI_GUILD . '=' . $this->guild->getGuildid() . '&amp;minlevel=' . $minlevel .
					'&amp;maxlevel=' . $maxlevel .
					'&amp;active=' . $selectactive .
					'&amp;nonactive=' . $selectnonactive
				),
				'O_CLASS'               => append_sid(
					"{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;o=' . $current_order['uri'][3] . '&amp;' . constants::URI_GUILD . '=' . $this->guild->getGuildid() . '&amp;minlevel=' . $minlevel .
					'&amp;maxlevel=' . $maxlevel .
					'&amp;active=' . $selectactive .
					'&amp;nonactive=' . $selectnonactive
				),
				'O_RANK'                => append_sid(
					"{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;o=' . $current_order['uri'][4] . '&amp;' . constants::URI_GUILD . '=' . $this->guild->getGuildid() . '&amp;minlevel=' . $minlevel .
					'&amp;maxlevel=' . $maxlevel .
					'&amp;active=' . $selectactive .
					'&amp;nonactive=' . $selectnonactive
				),
				'O_LAST_UPDATE'         => append_sid(
					"{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;o=' . $current_order['uri'][5] . '&amp;' . constants::URI_GUILD . '=' . $this->guild->getGuildid() . '&amp;minlevel=' . $minlevel .
					'&amp;maxlevel=' . $maxlevel .
					'&amp;active=' . $selectactive .
					'&amp;nonactive=' . $selectnonactive
				),
				'O_ID'                  => append_sid(
					"{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;o=' . $current_order['uri'][7] . '&amp;' . constants::URI_GUILD . '=' . $this->guild->getGuildid() . '&amp;minlevel=' . $minlevel .
					'&amp;maxlevel=' . $maxlevel .
					'&amp;active=' . $selectactive .
					'&amp;nonactive=' . $selectnonactive
				),
				'U_LIST_PLAYERS'        => append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=listplayers&amp;'),
				'LISTPLAYERS_FOOTCOUNT' => $footcount_text,
				'U_VIEW_GUILD'          => append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=editguild&amp;' . constants::URI_GUILD . '=' . $this->guild->getGuildid()),
				'PAGE_NUMBER'           => $playerpagination->on_page($player_count, $config['bbguild_user_llimit'], $start),
				'GUILD_EMBLEM'          => $this->asset_resolver->resolve_emblem_url((string) $this->guild->getEmblempath(), (int) $this->guild->getGuildid()),
				'GUILD_NAME'            => $this->guild->getName(),
			)
		);
		// Dispatch event so game plugins can add API-specific template vars
		$dispatcher = $phpbb_container->get('dispatcher');
		$game_id = $this->guild->getGameId();
		$has_api = $this->game_has_api($game_id);

		/**
		 * Event dispatched when the list players template is being built.
		 * Allows game plugins to inject API-specific template variables.
		 *
		 * @event avathar.bbguild.acp_listplayers_display
		 * @var string game_id  The game identifier for the current guild
		 * @var bool   has_api  Whether this game has API support
		 */
		$vars = array('game_id', 'has_api');
		extract($dispatcher->trigger_event('avathar.bbguild.acp_listplayers_display', compact($vars)));

		$this->template->assign_vars(array(
			'HAS_API' => $has_api,
			'GAME_ID' => $game_id,
		));

		$this->page_title = 'ACP_BBGUILD_PLAYER_LIST';

	}

	/**
	 * Build addplayer template
	 *
	 * @param $mode
	 */
	private function BuildTemplateAddEditplayers($mode)
	{
		global $config, $phpbb_admin_path, $phpEx;


		$player_id  = $this->request->variable('hidden_player_id', $this->request->variable(constants::URI_NAMEID, 0));
		$editplayer = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry, $player_id);
		$S_ADD = ($player_id > 0) ? false : true;
		if ($S_ADD)
		{
			// set defaults
			$editplayer->setPlayerGuildId($this->request->variable(constants::URI_GUILD, 0));
		}
		$this->guild     = new guilds($this->db, $this->user, $this->config, $this->bbguild_cache, $this->bbguild_log, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $editplayer->getPlayerGuildId());

		if ($S_ADD)
		{
			$editplayer->game_id          = $this->guild->getGameId();
			$editplayer->setPlayerRegion($this->guild->getRegion());
			$editplayer->setPlayerRealm($this->guild->getRealm());
			$editplayer->setPlayerRankId($this->guild->getRaidtrackerrank());
			$editplayer->setPlayerStatus(1);
			$editplayer->setPlayerGenderId(0);
		}

		// Game dropdown
		if (isset($this->games))
		{
			foreach ($this->games as $gameid => $gamename)
			{
				$this->template->assign_block_vars(
					'game_row', array(
						'VALUE'    => $gameid,
						'SELECTED' => ($editplayer->game_id == $gameid) ? ' selected="selected"' : '',
						'OPTION'   => $gamename)
				);
			}
		}
		else
		{
			trigger_error('ERROR_NOGAMES', E_USER_WARNING);
		}

		$guildlist = $this->guild->guildlist();
		foreach ($guildlist as $g)
		{
			//populate guild popup
			$this->template->assign_block_vars(
				'guild_row', array(
					'VALUE'    => $g['id'],
					'SELECTED' => ($g['id'] == $editplayer->getPlayerGuildId()) ? ' selected="selected"' : '',
					'OPTION'   => (!empty($g['name'])) ? $g['name'] : '(None)')
			);
		}

		// Rank drop-down -> for initial load
		// reloading is done from ajax to prevent redraw
		$Ranks  = new ranks($this->db, $this->user, $this->bbguild_cache, $this->bbguild_log, $this->bb_players_table, $this->bb_ranks_table, $editplayer->getPlayerGuildId());
		$result = $Ranks->listranks();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars(
				'rank_row', array(
					'VALUE'    => $row['rank_id'],
					'SELECTED' => ($editplayer->getPlayerRankId() == $row['rank_id']) ? ' selected="selected"' : '',
					'OPTION'   => (!empty($row['rank_name'])) ? $row['rank_name'] : '(None)')
			);
		}
		// Race dropdown
		// reloading is done from ajax to prevent redraw
		$sql_array = array(
			'SELECT'   => '  r.race_id, l.name as race_name ',
			'FROM'     => array(
				$this->bb_races_table  => 'r',
				$this->bb_language_table => 'l'),
			'WHERE'    => " r.race_id = l.attribute_id
								AND r.game_id = '" . $editplayer->game_id . "'
								AND l.attribute='race'
								AND l.game_id = r.game_id
								AND l.language= '" . $config['bbguild_lang'] . "'",
			'ORDER_BY' => 'l.name asc');
		$sql       = $this->db->sql_build_query('SELECT', $sql_array);
		$result    = $this->db->sql_query($sql);
		if ($editplayer->player_id > 0)
		{
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->template->assign_block_vars(
					'race_row', array(
						'VALUE'    => $row['race_id'],
						'SELECTED' => ($editplayer->getPlayerRaceId() == $row['race_id']) ? ' selected="selected"' : '',
						'OPTION'   => (!empty($row['race_name'])) ? $row['race_name'] : '(None)')
				);
			}
		} else
		{
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->template->assign_block_vars(
					'race_row', array(
						'VALUE'    => $row['race_id'],
						'SELECTED' => '',
						'OPTION'   => (!empty($row['race_name'])) ? $row['race_name'] : '(None)')
				);
			}
		}
		$this->db->sql_freeresult($result);

		//
		// Class dropdown
		// reloading is done from ajax to prevent redraw
		$sql_array = array(
			'SELECT'   => ' c.class_id, l.name as class_name, c.class_hide,
									  c.class_min_level, class_max_level, c.class_armor_type , c.imagename ',
			'FROM'     => array(
				$this->bb_classes_table => 'c',
				$this->bb_language_table => 'l'),
			'WHERE'    => " l.game_id = c.game_id  AND c.game_id = '" . $editplayer->game_id . "'
					AND l.attribute_id = c.class_id  AND l.language= '" . $config['bbguild_lang'] . "' AND l.attribute = 'class' ",
			'ORDER_BY' => 'l.name asc'
		);

		$sql       = $this->db->sql_build_query('SELECT', $sql_array);
		$result    = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['class_min_level'] <= 1)
			{
				$option = (!empty($row['class_name'])) ? $row['class_name'] . '
						 Level (' . $row['class_min_level'] . ' - ' . $row['class_max_level'] . ')' : '(None)';
			} else
			{
				$option = (!empty($row['class_name'])) ? $row['class_name'] . '
						 Level ' . $row['class_min_level'] . '+' : '(None)';
			}
			if ($editplayer->player_id <> 0)
			{
				$this->template->assign_block_vars(
					'class_row', array(
						'VALUE'    => $row['class_id'],
						'SELECTED' => ($editplayer->getPlayerClassId() == $row['class_id']) ? ' selected="selected"' : '',
						'OPTION'   => $option)
				);
			} else
			{
				$this->template->assign_block_vars(
					'class_row', array(
						'VALUE'    => $row['class_id'],
						'SELECTED' => '',
						'OPTION'   => $option)
				);
			}
		}
		$this->db->sql_freeresult($result);

		// get roles
		$Roles = new roles($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bb_gameroles_table, $this->bb_language_table, $this->bb_games_table, $this->bb_classes_table);
		$Roles->game_id = $this->guild->getGameId();
		$Roles->guild_id = $editplayer->getPlayerGuildId();
		$listroles = $Roles->list_roles();
		foreach ($listroles as $roleid => $Role)
		{
			$this->template->assign_block_vars(
				'role_row', array(
					'VALUE' => $Role['role_id'] ,
					'SELECTED' => ($editplayer->getPlayerRole() == $Role['role_id']) ? ' selected="selected"' : '' ,
					'OPTION' => $Role['rolename'] )
			);
		}

		// build presets for joindate pulldowns
		$now                      = getdate();
		$s_playerjoin_day_options = '<option value="0"	>--</option>';
		for ($i = 1; $i < 32; $i++)
		{
			$day      = $editplayer->player_id > 0 ? $editplayer->getPlayerJoindateD() : $now['mday'];
			$selected = ($i == $day) ? ' selected="selected"' : '';
			$s_playerjoin_day_options .= "<option value=\"$i\"$selected>$i</option>";
		}
		$s_playerjoin_month_options = '<option value="0">--</option>';
		for ($i = 1; $i < 13; $i++)
		{
			$month    = $editplayer->player_id > 0 ? $editplayer->getPlayerJoindateMo() : $now['mon'];
			$selected = ($i == $month) ? ' selected="selected"' : '';
			$s_playerjoin_month_options .= "<option value=\"$i\"$selected>$i</option>";
		}
		$s_playerjoin_year_options = '<option value="0">--</option>';
		for ($i = $now['year'] - 10; $i <= $now['year']; $i++)
		{
			$yr       = $editplayer->player_id > 0 ? $editplayer->getPlayerJoindateY() : $now['year'];
			$selected = ($i == $yr) ? ' selected="selected"' : '';
			$s_playerjoin_year_options .= "<option value=\"$i\"$selected>$i</option>";
		}

		// build presets for outdate pulldowns
		$s_playerout_day_options = '<option value="0"' . ($editplayer->player_id > 0 ? (($editplayer->getPlayerOutdate() != 0) ? '' : ' selected="selected"') : ' selected="selected"') . '>--</option>';
		for ($i = 1; $i < 32; $i++)
		{
			if ($editplayer->player_id > 0 && $editplayer->getPlayerOutdate() != 0)
			{
				$day      = $editplayer->getPlayerOutdate();
				$selected = ($i == $day) ? ' selected="selected"' : '';
			} else
			{
				$selected = '';
			}
			$s_playerout_day_options .= "<option value=\"$i\"$selected>$i</option>";
		}
		$s_playerout_month_options = '<option value="0"' . ($editplayer->player_id > 0 ? (($editplayer->getPlayerOutdate() != 0) ? '' : ' selected="selected"') : ' selected="selected"') . '>--</option>';
		for ($i = 1; $i < 13; $i++)
		{
			if ($editplayer->player_id > 0 && $editplayer->getPlayerOutdate() != 0)
			{
				$month    = $editplayer->getPlayerOutdateMo();
				$selected = ($i == $month) ? ' selected="selected"' : '';
			} else
			{
				$selected = '';
			}
			$s_playerout_month_options .= "<option value=\"$i\"$selected>$i</option>";
		}
		$s_playerout_year_options = '<option value="0"' . ($editplayer->player_id > 0 ? (($editplayer->getPlayerOutdate() != 0) ? '' : ' selected="selected"') : ' selected="selected"') . '>--</option>';
		for ($i = $now['year'] - 10; $i <= $now['year'] + 10; $i++)
		{
			if ($editplayer->player_id > 0 && $editplayer->getPlayerOutdate() != 0)
			{
				$yr       = $editplayer->getPlayerOutdateY();
				$selected = ($i == $yr) ? ' selected="selected"' : '';
			} else
			{
				$selected = '';
			}
			$s_playerout_year_options .= "<option value=\"$i\"$selected>$i</option>";
		}

		// phpbb User dropdown
		$phpbb_user_id = $editplayer->player_id > 0 ? $editplayer->getPhpbbUserId() : 0;
		$sql_array     = array(
			'SELECT'   => ' u.user_id, u.username ',
			'FROM'     => array(
				USERS_TABLE => 'u'),
			// exclude bots and guests, order by name -- ticket  129
			'WHERE'    => ' u.group_id != 6 and u.group_id != 1 ',
			'ORDER_BY' => ' u.username ASC');
		$sql           = $this->db->sql_build_query('SELECT', $sql_array);
		$result        = $this->db->sql_query($sql);
		$s_phpbb_user  = '<option value="0"' . (($phpbb_user_id == 0) ? ' selected="selected"' : '') . '>--</option>';
		while ($row = $this->db->sql_fetchrow($result))
		{
			$selected = ($row['user_id'] == $phpbb_user_id) ? ' selected="selected"' : '';
			$s_phpbb_user .= '<option value="' . $row['user_id'] . '"' . $selected . '>' . $row['username'] . '</option>';
		}
		unset($now);

		$this->page_title = 'ACP_MM_ADDPLAYER';
		$this->template->assign_vars(
			array(
				'L_TITLE'                  => $this->user->lang['ACP_MM_ADDPLAYER'],
				'L_EXPLAIN'                => $this->user->lang['ACP_MM_ADDPLAYER_EXPLAIN'],
				'F_ADD_PLAYER'             => append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-player_module&amp;mode=addplayer&amp;'),
				'STATUS'                   => $editplayer->isPlayerStatus() == 1 ? 'checked="checked"' : '',
				'PLAYER_NAME'              => $editplayer->getPlayerName(),
				'PLAYER_ID'                => $editplayer->player_id,
				'PLAYER_LEVEL'             => $editplayer->getPlayerLevel(),
				'REALM'                    => $editplayer->getPlayerRealm(),
				'REGION'                   => $editplayer->getPlayerRegion(),
				'REGIONNAME'               => $editplayer->getRegionlist()[$editplayer->getPlayerRegion()],
				'DEACTIVATE_REASON'        => $editplayer->getDeactivateReason() == '' ? '' : $this->user->lang[$editplayer->getDeactivateReason()],
				'STATUS_LOCK'              => $editplayer->getDeactivateReason() == '' ? false : true,
				'PLAYER_ACHIEV'            => $editplayer->getPlayerAchiev(),
				'PLAYER_TITLE'             => $editplayer->getPlayerTitle(),
				'MALE_CHECKED'             => ($editplayer->getPlayerGenderId() == '0') ? ' checked="checked"' : '',
				'FEMALE_CHECKED'           => ($editplayer->getPlayerGenderId() == '1') ? ' checked="checked"' : '',
				'PLAYER_COMMENT'           => $editplayer->getPlayerComment(),
				'S_CAN_HAVE_ARMORY'        => $this->game_has_api($editplayer->game_id),
				'PLAYER_URL'               => $editplayer->getPlayerArmoryUrl(),
				'PLAYER_PORTRAIT'          => $this->asset_resolver->resolve_portrait_url((string) $editplayer->getPlayerPortraitUrl(), (int) $editplayer->getPlayerId()),
				'S_PLAYER_PORTRAIT_EXISTS' => (strlen($editplayer->getPlayerPortraitUrl()) > 1) ? true : false,
				'S_CAN_GENERATE_ARMORY'    => $this->game_has_api($editplayer->game_id),
				'COLORCODE'                => ($editplayer->getColorcode() == '') ? '#254689' : $editplayer->getColorcode(),
				'CLASS_IMAGE'              => $editplayer->getClassImage(),
				'S_CLASS_IMAGE_EXISTS'     => (strlen($editplayer->getClassImage()) > 1) ? true : false,
				'RACE_IMAGE'               => $editplayer->getRaceImage(),
				'S_RACE_IMAGE_EXISTS'      => (strlen($editplayer->getRaceImage()) > 1) ? true : false,
				'S_JOINDATE_DAY_OPTIONS'   => $s_playerjoin_day_options,
				'S_JOINDATE_MONTH_OPTIONS' => $s_playerjoin_month_options,
				'S_JOINDATE_YEAR_OPTIONS'  => $s_playerjoin_year_options,
				'S_OUTDATE_DAY_OPTIONS'    => $s_playerout_day_options,
				'S_OUTDATE_MONTH_OPTIONS'  => $s_playerout_month_options,
				'S_OUTDATE_YEAR_OPTIONS'   => $s_playerout_year_options,
				'S_PHPBBUSER_OPTIONS'      => $s_phpbb_user,
				'TITLE_NAME'               => (strpos((string) $editplayer->getPlayerTitle(), '%s') !== false) ? sprintf($editplayer->getPlayerTitle(), $editplayer->getPlayerName()) : (string) $editplayer->getPlayerTitle(),
				// javascript
				'LA_ALERT_AJAX'            => $this->user->lang['ALERT_AJAX'],
				'LA_ALERT_OLDBROWSER'      => $this->user->lang['ALERT_OLDBROWSER'],
				'LA_MSG_NAME_EMPTY'        => $this->user->lang['FV_REQUIRED_NAME'],
				'UA_FINDRANK'              => append_sid($phpbb_admin_path . "style/dkp/findrank.$phpEx"),
				'UA_FINDCLASSRACE'         => append_sid($phpbb_admin_path . "style/dkp/findclassrace.$phpEx"),
				'S_ADD'                    => $S_ADD)
		);
	}

}
