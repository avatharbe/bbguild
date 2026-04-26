<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Guild admin controller
 *
 */

namespace avathar\bbguild\controller;

use phpbb\config\config;
use phpbb\language\language;
use phpbb\log\log;
use phpbb\pagination;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\event\dispatcher_interface;
use phpbb\user;

use avathar\bbguild\model\admin\asset_url_resolver;
use avathar\bbguild\model\admin\constants;
use avathar\bbguild\model\games\game;
use avathar\bbguild\model\games\game_registry;
use avathar\bbguild\model\games\rpg\faction;
use avathar\bbguild\model\player\guilds;
use avathar\bbguild\model\player\ranks;
use avathar\bbguild\portal\modules\database_handler as portal_database_handler;

class admin_guild
{
	/** @var \phpbb\auth\auth */
	public $auth;
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;
	/** @var config */
	protected $config;
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;
	/** @var language */
	protected $language;
	/** @var log */
	protected $log;
	/** @var pagination */
	protected $pagination;
	/** @var request */
	protected $request;
	/** @var template */
	protected $template;
	/** @var user */
	protected $user;
	/** @var \phpbb\path_helper */
	protected $path_helper;
	/** @var \phpbb\extension\manager */
	protected $phpbb_extension_manager;
	/** @var string phpBB root path */
	protected $root_path;
	/** @var string phpBB admin relative path */
	protected $adm_relative_path;
	/** @var string PHP extension */
	protected $php_ext;
	/** @var string Extension path */
	protected $ext_path;
	/** @var string Extension web path */
	protected $ext_path_web;

	/** @var \avathar\bbguild\model\admin\curl */
	public $curl;
	/** @var \avathar\bbguild\model\admin\log */
	public $bbguildlog;
	/** @var \avathar\bbguild\model\admin\util */
	public $util;
	/** @var \phpbb\controller\helper */
	protected $helper;
	/** @var game_registry */
	protected $game_registry;
	/** @var dispatcher_interface */
	protected $dispatcher;
	/** @var portal_database_handler */
	protected $portal_db_handler;
	/** @var admin_portal */
	protected $admin_portal;
	/** @var asset_url_resolver */
	protected $asset_resolver;

	/* @var string */
	public $u_action;
	/* @var string */
	public $link = ' ';
	/** @var string */
	public $page_title;
	/** @var string */
	public $tpl_name;

	public $bb_games_table;
	public $bb_logs_table;
	public $bb_ranks_table;
	public $bb_guild_table;
	public $bb_players_table;
	public $bb_classes_table;
	public $bb_races_table;
	public $bb_gameroles_table;
	public $bb_factions_table;
	public $bb_language_table;
	public $bb_motd_table;
	public $bb_recruit_table;
	public $bb_bosstable;
	public $bb_zonetable;
	public $bb_news;

	/** @var int Guild ID from request */
	protected $url_id;
	/** @var game */
	private $game;
	/** @var array Installed games map [game_id => game_name] */
	private $games;
	/** @var string AJAX faction route */
	private $factionroute;

	/**
	 * admin_guild constructor.
	 *
	 * @param \phpbb\auth\auth $auth
	 * @param \phpbb\cache\driver\driver_interface $cache
	 * @param config $config
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param language $language
	 * @param log $log
	 * @param pagination $pagination
	 * @param request $request
	 * @param template $template
	 * @param user $user
	 * @param \phpbb\path_helper $path_helper
	 * @param \phpbb\extension\manager $phpbb_extension_manager
	 * @param string $phpbb_root_path
	 * @param string $adm_relative_path
	 * @param string $phpEx
	 * @param \avathar\bbguild\model\admin\curl $curl
	 * @param \avathar\bbguild\model\admin\log $bbguildlog
	 * @param \avathar\bbguild\model\admin\util $util
	 * @param \phpbb\controller\helper $helper
	 * @param game_registry $game_registry
	 * @param portal_database_handler $portal_db_handler
	 * @param string $bb_games_table
	 * @param string $bb_logs_table
	 * @param string $bb_ranks_table
	 * @param string $bb_guild_table
	 * @param string $bb_players_table
	 * @param string $bb_classes_table
	 * @param string $bb_races_table
	 * @param string $bb_gameroles_table
	 * @param string $bb_factions_table
	 * @param string $bb_language_table
	 * @param string $bb_motd_table
	 * @param string $bb_recruit_table
	 * @param string $bb_bosstable
	 * @param string $bb_zonetable
	 * @param string $bb_news
	 */
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\cache\driver\driver_interface $cache,
		config $config,
		\phpbb\db\driver\driver_interface $db,
		language $language,
		log $log,
		pagination $pagination,
		request $request,
		template $template,
		user $user,
		\phpbb\path_helper $path_helper,
		\phpbb\extension\manager $phpbb_extension_manager,
		$phpbb_root_path,
		$adm_relative_path,
		$phpEx,
		\avathar\bbguild\model\admin\curl $curl,
		\avathar\bbguild\model\admin\log $bbguildlog,
		\avathar\bbguild\model\admin\util $util,
		\phpbb\controller\helper $helper,
		game_registry $game_registry,
		dispatcher_interface $dispatcher,
		portal_database_handler $portal_db_handler,
		admin_portal $admin_portal,
		asset_url_resolver $asset_resolver,
		$bb_games_table,
		$bb_logs_table,
		$bb_ranks_table,
		$bb_guild_table,
		$bb_players_table,
		$bb_classes_table,
		$bb_races_table,
		$bb_gameroles_table,
		$bb_factions_table,
		$bb_language_table,
		$bb_motd_table,
		$bb_recruit_table,
		$bb_bosstable,
		$bb_zonetable,
		$bb_news)
	{
		$this->auth = $auth;
		$this->cache = $cache;
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->log = $log;
		$this->log->set_log_table($bb_logs_table);
		$this->bbguildlog = $bbguildlog;
		$this->util = $util;
		$this->pagination = $pagination;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->path_helper = $path_helper;
		$this->phpbb_extension_manager = $phpbb_extension_manager;
		$this->ext_path = $this->phpbb_extension_manager->get_extension_path('avathar/bbguild', true);
		$this->ext_path_web = $this->path_helper->update_web_root_path($this->ext_path);
		$this->root_path = $phpbb_root_path;
		$this->adm_relative_path = $adm_relative_path;
		$this->php_ext = $phpEx;
		$this->curl = $curl;
		$this->helper = $helper;
		$this->game_registry = $game_registry;
		$this->dispatcher = $dispatcher;
		$this->portal_db_handler = $portal_db_handler;
		$this->admin_portal = $admin_portal;
		$this->asset_resolver = $asset_resolver;

		$this->bb_games_table = $bb_games_table;
		$this->bb_logs_table = $bb_logs_table;
		$this->bb_ranks_table = $bb_ranks_table;
		$this->bb_guild_table = $bb_guild_table;
		$this->bb_players_table = $bb_players_table;
		$this->bb_classes_table = $bb_classes_table;
		$this->bb_races_table = $bb_races_table;
		$this->bb_gameroles_table = $bb_gameroles_table;
		$this->bb_factions_table = $bb_factions_table;
		$this->bb_language_table = $bb_language_table;
		$this->bb_motd_table = $bb_motd_table;
		$this->bb_recruit_table = $bb_recruit_table;
		$this->bb_bosstable = $bb_bosstable;
		$this->bb_zonetable = $bb_zonetable;
		$this->bb_news = $bb_news;

		// Build faction AJAX route
		$this->factionroute = $this->helper->route('avathar_bbguild_01', array());

		// Build installed games map [game_id => game_name]
		$gameObj = new game(
			$this->db, $this->cache, $this->config, $this->user, $this->phpbb_extension_manager,
			$this->bb_classes_table, $this->bb_races_table,
			$this->bb_language_table, $this->bb_factions_table,
			$this->bb_games_table, $this->game_registry
		);
		$gamelist = $gameObj->list_games();
		$this->games = array();
		foreach ($gamelist as $game_id => $data)
		{
			// Only include games whose plugin is enabled (or the built-in 'custom' game)
			if ($game_id === 'custom' || $this->game_registry->has($game_id))
			{
				$this->games[$game_id] = $data['name'];
			}
		}

		// CSS trigger
		$this->template->assign_vars(array(
			'S_BBGUILD' => true,
		));
	}

	/**
	 * Create a guilds model instance with full DI args
	 *
	 * @param int $guild_id
	 * @return guilds
	 */
	private function makeGuilds($guild_id = 0)
	{
		return new guilds(
			$this->db, $this->user, $this->config, $this->cache, $this->bbguildlog,
			$this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table,
			$this->bb_races_table, $this->bb_language_table, $this->bb_guild_table,
			$this->bb_factions_table, $guild_id
		);
	}

	/**
	 * Create a ranks model instance with full DI args
	 *
	 * @param int $guild_id
	 * @param int $rank_id
	 * @return ranks
	 */
	private function makeRanks($guild_id, $rank_id = 0)
	{
		return new ranks(
			$this->db, $this->user, $this->cache, $this->bbguildlog,
			$this->bb_players_table, $this->bb_ranks_table,
			$guild_id, $rank_id
		);
	}

	/**
	 * Create a game model instance with full DI args
	 *
	 * @return game
	 */
	private function makeGame()
	{
		return new game(
			$this->db, $this->cache, $this->config, $this->user, $this->phpbb_extension_manager,
			$this->bb_classes_table, $this->bb_races_table,
			$this->bb_language_table, $this->bb_factions_table,
			$this->bb_games_table, $this->game_registry
		);
	}

	/**
	 * Build an ACP URL for the guild module
	 *
	 * @param string $params
	 * @return string
	 */
	private function acp_url($params = '')
	{
		$phpbb_admin_path = $this->root_path . $this->adm_relative_path;
		return append_sid("{$phpbb_admin_path}index.{$this->php_ext}", $params);
	}

	/**
	 * Dispatcher for the editguild mode. Called from guild_module.
	 * Handles the action sub-switch (editguild / guildranks).
	 */
	public function show_editguild_dispatch()
	{
		$phpbb_admin_path = $this->root_path . $this->adm_relative_path;
		$this->url_id = $this->request->variable(constants::URI_GUILD, 0);
		$updateguild = $this->makeGuilds($this->url_id);

		$this->game = $this->makeGame();
		$this->game->game_id = $updateguild->getGameId();
		$this->game->get_game();

		if ($this->request->is_set_post('playeradd'))
		{
			redirect($this->acp_url('i=-avathar-bbguild-acp-mm_module&amp;mode=addplayer&amp;' . constants::URI_GUILD . '=' . $this->url_id));
		}

		$action = $this->request->variable('action', '');
		switch ($action)
		{
			case 'guildranks':
				$this->show_editguildranks($updateguild);
				break;

			case 'guildportal':
				$this->show_editguildportal($updateguild);
				break;

			case 'guildrecruitment':
				$this->show_editguildrecruitment($updateguild);
				break;

			case 'editguild':
			default:
				$this->show_editguild($updateguild);
				break;
		}
	}

	/**
	 * Update the default guild flag
	 *
	 * @param guilds $updateguild
	 */
	private function UpdateDefaultGuild(guilds $updateguild)
	{
		$id = $this->request->variable('defaultguild', 0);
		$updateguild->update_guilddefault($id);
		$success_message = sprintf($this->user->lang['ADMIN_UPDATE_GUILD_SUCCESS'], $id);
		trigger_error($success_message . $this->link, E_USER_NOTICE);
	}

	/**
	 * Add a guild
	 *
	 * @param guilds $addguild
	 */
	private function AddGuild(guilds $addguild)
	{
		if (!check_form_key('avathar/bbguild'))
		{
			trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action));
		}

		$this->game = $this->makeGame();
		$this->game->game_id = $this->request->variable('game_id', '');
		$this->game->get_game();

		$addguild->setGameId($this->request->variable('game_id', ''));
		$addguild->setName($this->request->variable('guild_name', '', true));
		$addguild->setRealm($this->request->variable('realm', '', true));
		$addguild->setRegion($this->request->variable('region_id', ''));
		$addguild->setFaction($this->request->variable('faction_id', 0));
		$addguild->setShowroster($this->request->is_set_post('showroster'));
		$addguild->setMinArmory($this->request->variable('min_armorylevel', 0));
		$addguild->setArmoryEnabled($this->request->variable('armory_enabled', 0));
		$addguild->setRecstatus($this->request->variable('switchon_recruitment', 0));
		$addguild->setRecruitforum($this->request->variable('recruitforum', 0));
		$addguild->setEmblempath($this->ext_path . 'images/guildemblem/' . $this->request->variable('guild_emblem', '', true));
		$addguild->setStartdate(time());
		$addguild->setArmoryresult('KO');

		$updateguild = $addguild;
		$game_id = $addguild->getGameId();

		/**
		 * Event dispatched when a guild is being saved from the ACP edit form.
		 * Allows game plugins to mutate the guild object before persistence.
		 *
		 * @event avathar.bbguild.acp_editguild_submit
		 * @var guilds updateguild The guild object being created
		 * @var string game_id     The game identifier from the form
		 * @since 2.0.0-b2
		 */
		$vars = array('updateguild', 'game_id');
		extract($this->dispatcher->trigger_event('avathar.bbguild.acp_editguild_submit', compact($vars)));
		$addguild = $updateguild;

		$addguild->make_guild();

		// Seed default portal layout for the new guild
		$this->portal_db_handler->seed_guild_layout($addguild->getGuildid());

		// If BattleNet connection is on then fetch info from API
		if ($addguild->isArmoryEnabled())
		{
			$this->BattleNetUpdate($addguild);
		}

		$success_message = sprintf($this->user->lang['ADMIN_ADD_GUILD_SUCCESS'], $addguild->getName());
		trigger_error($success_message . $this->link, E_USER_NOTICE);
	}

	/**
	 * Update a guild
	 *
	 * @param guilds $updateguild
	 */
	private function UpdateGuild(guilds $updateguild)
	{
		$updateguild->setGuildid($this->url_id);
		$updateguild->get_guild();
		$old_guild = $this->makeGuilds($this->url_id);
		$old_guild->get_guild();

		$updateguild->setGameId($this->request->variable('game_id', ''));
		$updateguild->setName($this->request->variable('guild_name', '', true));
		$updateguild->setRealm($this->request->variable('realm', '', true));
		$updateguild->setRegion($this->request->variable('region_id', ' '));
		$updateguild->setFaction($this->request->variable('faction_id', 0));
		$updateguild->setShowroster($this->request->variable('showroster', 0));
		$updateguild->setMinArmory($this->request->variable('min_armorylevel', 0));
		$updateguild->setArmoryEnabled($this->request->variable('armory_enabled', 0));
		$updateguild->setRecstatus($this->request->variable('switchon_recruitment', 0));
		$updateguild->setRecruitforum($this->request->variable('recruitforum', 0));
		$updateguild->setEmblempath($this->ext_path . 'images/guildemblem/' . $this->request->variable('guild_emblem', '', true));

		$game_id = $updateguild->getGameId();

		/**
		 * Event dispatched after form values are read, before guild is saved.
		 * Allows game plugins to set edition or other game-specific fields.
		 *
		 * @event avathar.bbguild.acp_editguild_submit
		 * @var guilds updateguild The guild object being updated
		 * @var string game_id     The game identifier from the form
		 * @since 2.0.0-b2
		 */
		$vars = array('updateguild', 'game_id');
		extract($this->dispatcher->trigger_event('avathar.bbguild.acp_editguild_submit', compact($vars)));

		if ($updateguild->isArmoryEnabled())
		{
			$this->BattleNetUpdate($updateguild);
		}

		// Save MOTD for this guild
		$welcometext = $this->request->variable('message_of_the_day', '', true);
		$uid = $bitfield = $options = '';
		$allow_bbcode = $allow_urls = $allow_smilies = true;
		generate_text_for_storage($welcometext, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

		$guild_id = (int) $updateguild->getGuildid();

		// Check if MOTD row exists for this guild
		$sql = 'SELECT motd_id FROM ' . $this->bb_motd_table . ' WHERE guild_id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$motd_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($motd_row)
		{
			$sql = 'UPDATE ' . $this->bb_motd_table . ' SET ' . $this->db->sql_build_array('UPDATE', [
				'motd_msg'        => $welcometext,
				'motd_timestamp'  => time(),
				'bbcode_bitfield' => $bitfield,
				'bbcode_uid'      => $uid,
			]) . ' WHERE guild_id = ' . $guild_id;
		}
		else
		{
			$sql = 'INSERT INTO ' . $this->bb_motd_table . ' ' . $this->db->sql_build_array('INSERT', [
				'guild_id'        => $guild_id,
				'motd_msg'        => $welcometext,
				'motd_timestamp'  => time(),
				'bbcode_bitfield' => $bitfield,
				'bbcode_uid'      => $uid,
			]);
		}
		$this->db->sql_query($sql);

		if ($updateguild->update_guild($old_guild))
		{
			$success_message = sprintf($this->user->lang['ADMIN_UPDATE_GUILD_SUCCESS'], $this->url_id);
			trigger_error($success_message . $this->link, E_USER_NOTICE);
		}
		else
		{
			$success_message = sprintf($this->user->lang['ADMIN_UPDATE_GUILD_FAILED'], $this->url_id);
			trigger_error($success_message . $this->link, E_USER_WARNING);
		}
	}

	/**
	 * Call the BattleNet API
	 *
	 * @param guilds $updateguild
	 * @param array $parameters
	 */
	private function BattleNetUpdate(guilds $updateguild, $parameters = array())
	{
		$provider = $this->game_registry->get($updateguild->getGameId());
		$data = $updateguild->Call_Guild_API($parameters, $this->game, $provider);
		if ($updateguild->getArmoryresult() == 'OK')
		{
			$updateguild->update_guild_battleNet($data, $parameters, $provider);
		}
	}

	/**
	 * Delete a guild
	 *
	 * @param guilds $updateguild
	 */
	private function DeleteGuild(guilds $updateguild)
	{
		if (confirm_box(true))
		{
			$deleteguild = $this->makeGuilds($this->request->variable('guildid', 0));
			$deleteguild->get_guild();
			$deleteguild->delete_guild();
			$success_message = sprintf($this->user->lang['ADMIN_DELETE_GUILD_SUCCESS'], $deleteguild->getGuildid());
			trigger_error($success_message . $this->link, E_USER_NOTICE);
		}
		else
		{
			$s_hidden_fields = build_hidden_fields(array(
				'deleteguild' => true,
				'guildid'     => $updateguild->getGuildid(),
			));

			$this->template->assign_vars(array(
				'S_HIDDEN_FIELDS' => $s_hidden_fields,
			));

			confirm_box(false, $this->user->lang['CONFIRM_DELETE_GUILD'], $s_hidden_fields);
		}
	}

	/**
	 * Add a guild rank
	 *
	 * @param guilds $updateguild
	 */
	private function AddRank(guilds $updateguild)
	{
		$newrank = $this->makeRanks($updateguild->getGuildid());
		$newrank->RankName  = $this->request->variable('nrankname', '', true);
		$newrank->RankId    = $this->request->variable('nrankid', 0);
		$newrank->RankGuild = $updateguild->getGuildid();
		$newrank->RankHide  = $this->request->is_set_post('nhide');
		$newrank->RankPrefix = $this->request->variable('nprefix', '', true);
		$newrank->RankSuffix = $this->request->variable('nsuffix', '', true);
		$newrank->Makerank();
		$success_message = $this->user->lang['ADMIN_RANKS_ADDED_SUCCESS'];
		trigger_error($success_message . $this->link);
	}

	/**
	 * Update a rank
	 *
	 * @param guilds $updateguild
	 * @return int
	 */
	private function UpdateRank(guilds $updateguild)
	{
		$newrank = $this->makeRanks($updateguild->getGuildid());
		$oldrank = $this->makeRanks($updateguild->getGuildid());

		$rank_id = 0;
		$modrank = $this->request->variable('ranks', array(0 => ''), true);
		foreach ($modrank as $rank_id => $rank_name)
		{
			$old = clone $oldrank;
			$old->RankId    = $rank_id;
			$old->RankGuild = $updateguild->getGuildid();
			$old->Getrank();

			$new = clone $newrank;
			$new->RankId     = $rank_id;
			$new->RankGuild  = $old->RankGuild;
			$new->RankName   = $rank_name;
			$RankHide        = $this->request->variable('hide', array((int) $rank_id => ''));
			$new->RankHide   = count($RankHide) > 0 ? (isset($RankHide[$rank_id]) ? 1 : 0) : 0;

			$rank_prefix     = $this->request->variable('prefix', array((int) $rank_id => ''), true);
			$new->RankPrefix = $rank_prefix[$rank_id];

			$rank_suffix     = $this->request->variable('suffix', array((int) $rank_id => ''), true);
			$new->RankSuffix = $rank_suffix[$rank_id];

			if ($old != $new)
			{
				$new->Rankupdate($old);
			}
		}

		$success_message = $this->user->lang['ADMIN_RANKS_UPDATE_SUCCESS'];
		trigger_error($success_message . $this->link);
		return $rank_id;
	}

	/**
	 * Delete a guild rank
	 */
	private function DeleteRank()
	{
		if (confirm_box(true))
		{
			$guildid    = $this->request->variable('hidden_guildid', 0);
			$rank_id    = $this->request->variable('hidden_rank_id', 999);
			$deleterank = $this->makeRanks($guildid, $rank_id);
			$deleterank->Rankdelete(false);
		}
		else
		{
			$rank_id    = $this->request->variable('ranktodelete', 999);
			$guildid    = $this->request->variable(constants::URI_GUILD, 0);
			$old_guild  = $this->makeGuilds($guildid);
			$deleterank = $this->makeRanks($guildid, $rank_id);

			$s_hidden_fields = build_hidden_fields(array(
				'deleterank'     => true,
				'hidden_rank_id' => $rank_id,
				'hidden_guildid' => $guildid,
			));

			confirm_box(false, sprintf($this->user->lang['CONFIRM_DELETE_RANKS'], $deleterank->RankName, $old_guild->getName()), $s_hidden_fields);
		}
	}

	/**
	 * List the guilds (ACP guild list page)
	 */
	public function BuildTemplateListGuilds()
	{
		if (count($this->games) == 0)
		{
			trigger_error($this->user->lang['ERROR_NOGAMES'], E_USER_WARNING);
		}

		$updateguild = $this->makeGuilds();
		$guildlist   = $updateguild->guildlist(1);
		foreach ($guildlist as $g)
		{
			$this->template->assign_block_vars('defaultguild_row', array(
				'VALUE'    => $g['id'],
				'SELECTED' => ($g['guilddefault'] == '1') ? ' selected="selected"' : '',
				'OPTION'   => (!empty($g['name'])) ? $g['name'] : '(None)',
			));
		}

		$guilddefaultupdate = $this->request->is_set_post('upddefaultguild');
		if ($guilddefaultupdate)
		{
			$this->UpdateDefaultGuild($updateguild);
		}

		$guildadd = $this->request->is_set_post('addguild');
		if ($guildadd)
		{
			redirect($this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=addguild'));
		}

		$sort_order = array(
			0 => array('id', 'id desc'),
			1 => array('name', 'name desc'),
			2 => array('realm desc', 'realm desc'),
			3 => array('region', 'region desc'),
			4 => array('roster', 'roster desc'),
		);
		$current_order = $this->util->switch_order($sort_order);
		$guild_count   = 0;
		$sort_index    = explode('.', $current_order['uri']['current']);

		$sql = 'SELECT id, name, realm, region, roster, game_id FROM ' . $this->bb_guild_table . ' WHERE id > 0 ORDER BY ' . $current_order['sql'];
		if (!($guild_result = $this->db->sql_query($sql)))
		{
			trigger_error($this->user->lang['ERROR_GUILDNOTFOUND'], E_USER_WARNING);
		}

		while ($row = $this->db->sql_fetchrow($guild_result))
		{
			$guild_count++;
			$listguild = $this->makeGuilds($row['id']);
			$this->template->assign_block_vars('guild_row', array(
				'ID'           => $listguild->getGuildid(),
				'NAME'         => $listguild->getName(),
				'REALM'        => $listguild->getRealm(),
				'REGION'       => $listguild->getRegion(),
				'GAME'         => $listguild->getGameId(),
				'PLAYERCOUNT'  => $listguild->getPlayercount(),
				'SHOW_ROSTER'  => $listguild->getShowroster() == 1 ? 'yes' : 'no',
				'U_VIEW_GUILD' => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;' . constants::URI_GUILD . '=' . $listguild->getGuildid()),
			));
		}

		$this->template->assign_vars(array(
			'U_GUILDLIST'            => $this->acp_url('i=-avathar-bbguild-acp-guild_module') . '&amp;mode=listguilds',
			'U_ADDGUILD'            => $this->acp_url('i=-avathar-bbguild-acp-guild_module') . '&amp;mode=addguild',
			'U_GUILD'               => $this->acp_url('i=-avathar-bbguild-acp-guild_module') . '&amp;mode=editguild',
			'L_TITLE'               => $this->user->lang['ACP_LISTGUILDS'],
			'L_EXPLAIN'             => $this->user->lang['ACP_LISTGUILDS_EXPLAIN'],
			'BUTTON_VALUE'          => $this->user->lang['DELETE_SELECTED_GUILDS'],
			'O_ID'                  => $current_order['uri'][0],
			'O_NAME'                => $current_order['uri'][1],
			'O_REALM'               => $current_order['uri'][2],
			'O_REGION'              => $current_order['uri'][3],
			'O_ROSTER'              => $current_order['uri'][4],
			'U_LIST_GUILD'          => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=listguilds'),
			'GUILDPLAYERS_FOOTCOUNT' => sprintf($this->user->lang['GUILD_FOOTCOUNT'], $guild_count),
		));
		$this->page_title = 'ACP_LISTGUILDS';
	}

	/**
	 * Show the add-guild form
	 */
	public function show_addguild()
	{
		$addguild = $this->makeGuilds();

		foreach ($this->games as $key => $value)
		{
			if ($addguild->getGameId() == '')
			{
				$addguild->setGameId($key);
			}

			$this->template->assign_block_vars('game_row', array(
				'VALUE'    => $key,
				'SELECTED' => ($addguild->getGameId() == $key) ? ' selected="selected"' : '',
				'OPTION'   => (!empty($value)) ? $value : '(None)',
			));
		}

		if ($this->request->is_set_post('newguild'))
		{
			$this->AddGuild($addguild);
		}

		$this->game = $this->makeGame();
		$this->game->game_id = $addguild->getGameId();
		$this->game->get_game();

		// Reset armory_enabled to false if game has armory but no API key configured
		if ($this->game->getArmoryEnabled() && trim((string) $this->game->getApikey()) == '')
		{
			$addguild->setArmoryEnabled(false);
		}

		foreach ($this->game->getRegions() as $key => $regionname)
		{
			$this->template->assign_block_vars('region_row', array(
				'VALUE'    => $key,
				'SELECTED' => ($this->game->getRegion() == $key) ? ' selected="selected"' : '',
				'OPTION'   => (!empty($regionname)) ? $regionname : '(None)',
			));
		}

		$factions = new faction($this->db, $this->cache, $this->user, $this->game->game_id, $this->bb_factions_table, $this->bb_races_table);
		$listfactions = $factions->get_factions();
		if (isset($listfactions))
		{
			foreach ($listfactions as $key => $faction)
			{
				$this->template->assign_block_vars('faction_row', array(
					'VALUE'    => $key,
					'SELECTED' => ($addguild->getFaction() == $key) ? ' selected="selected"' : '',
					'OPTION'   => (!empty($faction['faction_name'])) ? $faction['faction_name'] : '(None)',
				));
			}
		}

		$game_id = $addguild->getGameId();
		$provider = $this->game_registry->get($game_id);
		$has_api = ($provider !== null && $provider->has_api());

		$this->template->assign_vars(array(
			'U_FACTION'       => $this->factionroute,
			'GUILD_NAME'      => $addguild->getName(),
			'REALM_NAME'      => $addguild->getRealm(),
			'F_ENABLEARMORY'  => $addguild->isArmoryEnabled(),
			'DEFAULTREALM'    => ($this->config['bbguild_default_realm'] == '') ? $addguild->getRealm() : $this->config['bbguild_default_realm'],
			'RECSTATUS'       => true,
			'MIN_ARMORYLEVEL' => $this->config['bbguild_minrosterlvl'],
			'GAME_ID'         => $game_id,
			'HAS_API'         => $has_api,
		));

		$updateguild = $addguild;

		/**
		 * Event dispatched when the add-guild template is being built.
		 * Allows game plugins to inject their own template variables.
		 *
		 * @event avathar.bbguild.acp_editguild_display
		 * @var guilds updateguild The guild object being displayed
		 * @var string game_id     The game identifier
		 * @var bool   has_api     Whether this game has API support
		 * @since 2.0.0-b2
		 */
		$vars = array('updateguild', 'game_id', 'has_api');
		extract($this->dispatcher->trigger_event('avathar.bbguild.acp_editguild_display', compact($vars)));

		$this->page_title = $this->user->lang['ACP_ADDGUILD'];
	}

	/**
	 * Show the edit-guild form (General tab)
	 *
	 * @param guilds $updateguild
	 */
	public function show_editguild(guilds $updateguild)
	{
		$this->link = '<br /><a href="' . $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildedit&amp;' . constants::URI_GUILD . '=' .
			$updateguild->getGuildid()) . '"><h3>' . $this->user->lang['RETURN_GUILDLIST'] . '</h3></a>';

		$submit       = $this->request->is_set_post('updateguild');
		$delete       = $this->request->is_set_post('deleteguild');
		$updatearmory = $this->request->is_set_post('armory');

		if ($submit)
		{
			if (!check_form_key('avathar/bbguild'))
			{
				trigger_error('FORM_INVALID', E_USER_NOTICE);
			}
			$this->UpdateGuild($updateguild);
		}
		if ($updatearmory)
		{
			$this->BattleNetUpdate($updateguild, array('members'));
		}
		if ($delete)
		{
			$this->DeleteGuild($updateguild);
		}

		$this->BuildTemplateEditGuild($updateguild);
	}

	/**
	 * Show the edit-guild ranks tab
	 *
	 * @param guilds $updateguild
	 */
	public function show_editguildranks(guilds $updateguild)
	{
		$this->link = '<br /><a href="' . $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildranks&amp;' . constants::URI_GUILD . '=' .
			$updateguild->getGuildid()) . '"><h3>' . $this->user->lang['RETURN_GUILDLIST'] . '</h3></a>';

		$updaterank = $this->request->is_set_post('updaterank');
		$deleterank = $this->request->variable('deleterank', '') != '' ? true : false;
		$addrank    = $this->request->is_set_post('addrank');

		if (($updaterank || $addrank) && (!check_form_key('avathar/bbguild')))
		{
			trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action));
		}
		if ($addrank)
		{
			$this->AddRank($updateguild);
		}
		if ($updaterank)
		{
			$this->UpdateRank($updateguild);
		}
		if ($deleterank)
		{
			$this->DeleteRank();
		}

		$this->tpl_name = 'acp_editguild_ranks';
		$this->BuildTemplateEditGuildRanks($updateguild);
	}

	/**
	 * Show the edit-guild portal tab.
	 * Delegates to admin_portal for actions and template rendering.
	 */
	public function show_editguildportal(guilds $updateguild): void
	{
		$guild_id = $updateguild->getGuildid();

		// Set up the portal controller with the guild edit base URL
		$base_url = $this->acp_url('i=-avathar-bbguild-acp-guild_module&mode=editguild&action=guildportal&' . constants::URI_GUILD . '=' . $guild_id);
		$this->admin_portal->set_u_action($base_url);

		$portal_tpl = $this->admin_portal->display('portal_action');

		// Portal configure returns a different template (e.g. acp_portal_config)
		if ($portal_tpl !== 'acp_editguild_portal')
		{
			$this->tpl_name = $portal_tpl;
			return;
		}

		// Assign tab URLs
		$this->template->assign_vars([
			'L_TITLE'                => $this->user->lang['ACP_EDITGUILD'],
			'L_EXPLAIN'              => $this->user->lang['ACP_EDITGUILD_EXPLAIN'],
			'U_EDIT_GUILD'           => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=editguild&amp;' . constants::URI_GUILD . '=' . $guild_id),
			'U_EDIT_GUILDRANKS'      => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildranks&amp;' . constants::URI_GUILD . '=' . $guild_id),
			'U_EDIT_GUILDPORTAL'     => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildportal&amp;' . constants::URI_GUILD . '=' . $guild_id),
			'U_EDIT_GUILDRECRUITMENT' => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildrecruitment&amp;' . constants::URI_GUILD . '=' . $guild_id),
			'GUILD_NAME'             => $updateguild->getName(),
			'GUILDID'                => $guild_id,
		]);

		$this->tpl_name = 'acp_editguild_portal';
		$this->page_title = $this->user->lang['ACP_EDITGUILD'];
	}

	/**
	 * Show the edit-guild recruitment tab.
	 */
	public function show_editguildrecruitment(guilds $updateguild): void
	{
		$guild_id = (int) $updateguild->getGuildid();
		$this->link = '<br /><a href="' . $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildrecruitment&amp;' . constants::URI_GUILD . '=' . $guild_id) . '"><h3>' . $this->user->lang['RETURN_GUILDLIST'] . '</h3></a>';

		$form_key = 'avathar/bbguild';

		// Handle actions
		$action = $this->request->variable('recruit_action', '');
		$add = $this->request->is_set_post('add_recruit');
		$update = $this->request->is_set_post('update_recruit');

		if ($action === 'delete')
		{
			$recruit_id = $this->request->variable('id', 0);
			if ($recruit_id)
			{
				$sql = 'DELETE FROM ' . $this->bb_recruit_table . ' WHERE id = ' . (int) $recruit_id;
				$this->db->sql_query($sql);
				$success_message = sprintf($this->user->lang['ADMIN_DELETE_RECRUITMENT_SUCCESS'], $recruit_id);
				trigger_error($success_message . $this->link, E_USER_NOTICE);
			}
		}

		if (($add || $update) && check_form_key($form_key))
		{
			$recruit_data = [
				'guild_id'    => $guild_id,
				'role_id'     => $this->request->variable('role', 0),
				'class_id'    => $this->request->variable('class_id', 0),
				'positions'   => $this->request->variable('numpositions', 1),
				'level'       => $this->request->variable('recruit_level', 0),
				'status'      => $this->request->variable('recruitstatus', '') === 'on' ? 1 : 0,
				'note'        => utf8_normalize_nfc($this->request->variable('note', '', true)),
				'last_update' => time(),
			];

			if ($add)
			{
				$recruit_data['applicants'] = 0;
				$recruit_data['applytemplate_id'] = 0;
				$sql = 'INSERT INTO ' . $this->bb_recruit_table . ' ' . $this->db->sql_build_array('INSERT', $recruit_data);
				$this->db->sql_query($sql);
				$new_id = $this->db->sql_nextid();
				$success_message = sprintf($this->user->lang['ADMIN_ADD_RECRUITMENT_SUCCESS'], $new_id);
				trigger_error($success_message . $this->link, E_USER_NOTICE);
			}
			else if ($update)
			{
				$recruit_id = $this->request->variable('hidden_recruit_id', 0);
				$recruit_data['applicants'] = $this->request->variable('applicants', 0);
				$sql = 'UPDATE ' . $this->bb_recruit_table . ' SET ' . $this->db->sql_build_array('UPDATE', $recruit_data) . ' WHERE id = ' . (int) $recruit_id;
				$this->db->sql_query($sql);
				$success_message = sprintf($this->user->lang['ADMIN_UPDATE_RECRUITMENT_SUCCESS'], $recruit_id);
				trigger_error($success_message . $this->link, E_USER_NOTICE);
			}
		}

		// Build edit form if action=edit
		$edit_id = 0;
		$edit_recruit = [];
		if ($action === 'edit')
		{
			$edit_id = $this->request->variable('id', 0);
			$sql = 'SELECT * FROM ' . $this->bb_recruit_table . ' WHERE id = ' . (int) $edit_id;
			$result = $this->db->sql_query($sql);
			$edit_recruit = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
		}

		// List existing recruitments
		$lang_code = $this->config['bbguild_lang'] ?? 'en';
		$game_id = $this->db->sql_escape($updateguild->getGameId());
		$sql = 'SELECT r.id, r.role_id, r.class_id, r.level, r.positions, r.applicants, r.status, r.note,
				l_c.name as class_name, c.colorcode, c.imagename,
				l_r.name as role_name, gr.role_color
			FROM ' . $this->bb_recruit_table . ' r
			LEFT JOIN ' . $this->bb_classes_table . " c ON r.class_id = c.class_id AND c.game_id = '" . $game_id . "'
			LEFT JOIN " . $this->bb_language_table . " l_c ON r.class_id = l_c.attribute_id
				AND l_c.attribute = 'class' AND l_c.language = '" . $this->db->sql_escape($lang_code) . "'
				AND l_c.game_id = '" . $game_id . "'
			LEFT JOIN " . $this->bb_gameroles_table . " gr ON r.role_id = gr.role_id AND gr.game_id = '" . $game_id . "'
			LEFT JOIN " . $this->bb_language_table . " l_r ON r.role_id = l_r.attribute_id
				AND l_r.attribute = 'role' AND l_r.language = '" . $this->db->sql_escape($lang_code) . "'
				AND l_r.game_id = '" . $game_id . "'
			WHERE r.guild_id = " . $guild_id . '
			ORDER BY r.role_id, r.class_id';
		$result = $this->db->sql_query($sql);

		$recruit_count = 0;
		while ($row = $this->db->sql_fetchrow($result))
		{
			$recruit_count++;
			$class_img = '';
			if (!empty($row['imagename']))
			{
				$img_path = $this->ext_path . 'images/class_images/' . $row['imagename'] . '.png';
				if (file_exists($this->root_path . $img_path))
				{
					$class_img = $img_path;
				}
			}

			$this->template->assign_block_vars('recruit_row', [
				'ID'                    => $row['id'],
				'ROLE_NAME'             => $row['role_name'] ?? '?',
				'ROLE_COLOR'            => $row['role_color'] ?? '',
				'CLASS_NAME'            => $row['class_name'] ?? '?',
				'COLOR_CODE'            => $row['colorcode'] ?? '',
				'S_CLASS_IMAGE_EXISTS'  => !empty($class_img),
				'CLASS_IMAGE'           => $class_img,
				'LEVEL'                 => $row['level'],
				'POSITIONS'             => $row['positions'],
				'APPLICANTS'            => $row['applicants'],
				'S_OPEN'                => $row['status'] == 1,
				'NOTE'                  => $row['note'],
				'U_EDIT'                => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildrecruitment&amp;recruit_action=edit&amp;id=' . $row['id'] . '&amp;' . constants::URI_GUILD . '=' . $guild_id),
				'U_DELETE'              => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildrecruitment&amp;recruit_action=delete&amp;id=' . $row['id'] . '&amp;' . constants::URI_GUILD . '=' . $guild_id),
			]);
		}
		$this->db->sql_freeresult($result);

		// Build class dropdown
		$game_id = $updateguild->getGameId();
		$sql = 'SELECT c.class_id, l.name as class_name
			FROM ' . $this->bb_classes_table . ' c
			INNER JOIN ' . $this->bb_language_table . " l
				ON l.game_id = c.game_id AND l.attribute_id = c.class_id AND l.attribute = 'class'
				AND l.language = '" . $this->db->sql_escape($lang_code) . "'
			WHERE c.game_id = '" . $this->db->sql_escape($game_id) . "'
			ORDER BY l.name";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('class_row', [
				'VALUE'    => $row['class_id'],
				'SELECTED' => (!empty($edit_recruit) && $edit_recruit['class_id'] == $row['class_id']) ? ' selected="selected"' : '',
				'OPTION'   => $row['class_name'],
			]);
		}
		$this->db->sql_freeresult($result);

		// Build role dropdown
		$sql = 'SELECT gr.role_id, l.name as role_name
			FROM ' . $this->bb_gameroles_table . ' gr
			INNER JOIN ' . $this->bb_language_table . " l
				ON l.game_id = gr.game_id AND l.attribute_id = gr.role_id AND l.attribute = 'role'
				AND l.language = '" . $this->db->sql_escape($lang_code) . "'
			WHERE gr.game_id = '" . $this->db->sql_escape($game_id) . "'
			ORDER BY l.name";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('role_row', [
				'VALUE'    => $row['role_id'],
				'SELECTED' => (!empty($edit_recruit) && $edit_recruit['role_id'] == $row['role_id']) ? ' selected="selected"' : '',
				'OPTION'   => $row['role_name'],
			]);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'L_TITLE'                => $this->user->lang['ACP_EDITGUILD'],
			'L_EXPLAIN'              => $this->user->lang['ACP_EDITGUILD_EXPLAIN'],
			'GUILD_NAME'             => $updateguild->getName(),
			'GUILDID'                => $guild_id,
			'RECRUIT_FOOTCOUNT'      => sprintf($this->user->lang['RECRUIT_FOOTCOUNT'], $recruit_count),
			'S_EDIT_RECRUIT'         => !empty($edit_recruit),
			'RECRUIT_ID'             => !empty($edit_recruit) ? $edit_recruit['id'] : 0,
			'RECRUIT_POSITIONS'      => !empty($edit_recruit) ? $edit_recruit['positions'] : 1,
			'RECRUIT_LEVEL'          => !empty($edit_recruit) ? $edit_recruit['level'] : $updateguild->getMinArmory(),
			'RECRUIT_APPLICANTS'     => !empty($edit_recruit) ? $edit_recruit['applicants'] : 0,
			'RECRUIT_STATUS_CHECKED' => (!empty($edit_recruit) ? $edit_recruit['status'] == 1 : true) ? 'checked="checked"' : '',
			'RECRUIT_NOTE'           => !empty($edit_recruit) ? $edit_recruit['note'] : '',
			'U_EDIT_GUILD'           => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=editguild&amp;' . constants::URI_GUILD . '=' . $guild_id),
			'U_EDIT_GUILDRANKS'      => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildranks&amp;' . constants::URI_GUILD . '=' . $guild_id),
			'U_EDIT_GUILDPORTAL'     => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildportal&amp;' . constants::URI_GUILD . '=' . $guild_id),
			'U_EDIT_GUILDRECRUITMENT' => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildrecruitment&amp;' . constants::URI_GUILD . '=' . $guild_id),
		]);

		$this->tpl_name = 'acp_editguild_recruitment';
		$this->page_title = $this->user->lang['ACP_EDITGUILD'];
	}

	/**
	 * Populate the edit-guild form template
	 * Ported from old bbDKP acp_dkp_guild.php
	 *
	 * @param guilds $updateguild
	 */
	private function BuildTemplateEditGuild(guilds $updateguild)
	{
		// Region dropdown
		foreach ($this->game->getRegions() as $key => $regionname)
		{
			$this->template->assign_block_vars('region_row', array(
				'VALUE'    => $key,
				'SELECTED' => ($updateguild->getRegion() == $key) ? ' selected="selected"' : '',
				'OPTION'   => (!empty($regionname)) ? $regionname : '(None)',
			));
		}

		// Game dropdown — include the guild's current game even if its plugin is disabled
		$current_game_id = $updateguild->getGameId();
		$game_options = $this->games;
		if ($current_game_id !== '' && !isset($game_options[$current_game_id]))
		{
			$game_options[$current_game_id] = $current_game_id . ' (' . $this->language->lang('PLUGIN_DISABLED') . ')';
		}

		if (count($game_options) > 0)
		{
			foreach ($game_options as $key => $gamename)
			{
				$this->template->assign_block_vars('game_row', array(
					'VALUE'    => $key,
					'SELECTED' => ($current_game_id == $key) ? ' selected="selected"' : '',
					'OPTION'   => (!empty($gamename)) ? $gamename : '(None)',
				));
			}
		}
		else
		{
			trigger_error('ERROR_NOGAMES', E_USER_WARNING);
		}

		// Faction dropdown
		$factions = new faction($this->db, $this->cache, $this->user, $updateguild->getGameId(), $this->bb_factions_table, $this->bb_races_table);
		$listfactions = $factions->get_factions();
		if (isset($listfactions))
		{
			foreach ($listfactions as $key => $faction)
			{
				$this->template->assign_block_vars('faction_row', array(
					'VALUE'    => $key,
					'SELECTED' => ($updateguild->getFaction() == $key) ? ' selected="selected"' : '',
					'OPTION'   => (!empty($faction['faction_name'])) ? $faction['faction_name'] : '(None)',
				));
			}
		}

		// Check if this game has API support
		$game_id = $updateguild->getGameId();
		$provider = $this->game_registry->get($game_id);
		$has_api = ($provider !== null && $provider->has_api());

		// Load MOTD for this guild
		$welcometext = $uid = $bitfield = '';
		$sql = 'SELECT motd_msg, bbcode_bitfield, bbcode_uid
			FROM ' . $this->bb_motd_table . '
			WHERE guild_id = ' . (int) $updateguild->getGuildid();
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		if ($row)
		{
			$welcometext = $row['motd_msg'];
			$bitfield = $row['bbcode_bitfield'];
			$uid = $row['bbcode_uid'];
		}
		$textarr = generate_text_for_edit($welcometext, $uid, (int) $bitfield);

		$this->template->assign_vars(array(
			'U_FACTION'              => $this->factionroute,
			'F_ENABLGAMEEARMORY'     => $this->game->getArmoryEnabled(),
			'F_ENABLEARMORY'         => $updateguild->isArmoryEnabled(),
			'RECSTATUS'              => $updateguild->getRecstatus(),
			'GAME_ID'                => $game_id,
			'HAS_API'                => $has_api,
			'GUILDID'                => $updateguild->getGuildid(),
			'GUILD_NAME'             => $updateguild->getName(),
			'REALM'                  => $updateguild->getRealm(),
			'REGION'                 => $updateguild->getRegion(),
			'MEMBERCOUNT'            => $updateguild->getPlayercount(),
			'MIN_ARMORYLEVEL'        => $updateguild->getMinArmory(),
			'SHOW_ROSTER'            => ($updateguild->getShowroster() == 1) ? 'checked="checked"' : '',
			'ARMORYSTATUS'           => $updateguild->getArmoryresult(),
			'L_TITLE'                => $this->user->lang['ACP_EDITGUILD'],
			'L_EXPLAIN'              => $this->user->lang['ACP_EDITGUILD_EXPLAIN'],
			'L_EDIT_GUILD_TITLE'     => $this->user->lang['EDIT_GUILD'],
			'MSG_NAME_EMPTY'         => $this->user->lang['FV_REQUIRED_NAME'],
			'EMBLEM'                 => $this->asset_resolver->resolve_emblem_url((string) $updateguild->getEmblempath(), (int) $updateguild->getGuildid()),
			'EMBLEMFILE'             => basename((string) $updateguild->getEmblempath()),
			'S_EMBLEM_EXISTS'        => !empty($updateguild->getEmblempath()) && file_exists($this->root_path . $updateguild->getEmblempath()),
			'WELCOME_MESSAGE'        => $textarr['text'],
			'U_EDIT_GUILD'           => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=editguild&amp;' . constants::URI_GUILD . '=' . $updateguild->getGuildid()),
			'U_EDIT_GUILDRANKS'      => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildranks&amp;' . constants::URI_GUILD . '=' . $updateguild->getGuildid()),
			'U_EDIT_GUILDPORTAL'     => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildportal&amp;' . constants::URI_GUILD . '=' . $updateguild->getGuildid()),
			'U_EDIT_GUILDRECRUITMENT' => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildrecruitment&amp;' . constants::URI_GUILD . '=' . $updateguild->getGuildid()),
		));

		/**
		 * Event dispatched when the edit guild template is being built.
		 * Allows game plugins to inject their own template variables.
		 *
		 * @event avathar.bbguild.acp_editguild_display
		 * @var guilds updateguild The guild object being displayed
		 * @var string game_id     The game identifier
		 * @var bool   has_api     Whether this game has API support
		 * @since 2.0.0-b2
		 */
		$vars = array('updateguild', 'game_id', 'has_api');
		extract($this->dispatcher->trigger_event('avathar.bbguild.acp_editguild_display', compact($vars)));

		$this->page_title = $this->user->lang['ACP_EDITGUILD'];
	}

	/**
	 * Populate the guild ranks template
	 * Ported from old bbDKP acp_dkp_guild.php
	 *
	 * @param guilds $updateguild
	 */
	private function BuildTemplateEditGuildRanks(guilds $updateguild)
	{
		// Rank 90+ is readonly
		$listranks = $this->makeRanks($updateguild->getGuildid());
		$result = $listranks->listranks();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('ranks_row', array(
				'RANK_ID'       => $row['rank_id'],
				'RANK_NAME'     => $row['rank_name'],
				'RANK_PREFIX'   => $row['rank_prefix'],
				'RANK_SUFFIX'   => $row['rank_suffix'],
				'HIDE_CHECKED'  => ($row['rank_hide'] == 1) ? 'checked="checked"' : '',
				'S_READONLY'    => ($row['rank_id'] >= 90) ? true : false,
				'U_DELETE_RANK' => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;deleterank=1&amp;ranktodelete=' . $row['rank_id'] . '&amp;' . constants::URI_GUILD . '=' . $updateguild->getGuildid()),
			));
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'S_GUILDLESS'            => ($updateguild->getGuildid() == 0) ? true : false,
			'F_ENABLGAMEEARMORY'     => $this->game->getArmoryEnabled(),
			'F_ENABLEARMORY'         => $updateguild->isArmoryEnabled(),
			'GAME_ID'                => $updateguild->getGameId(),
			'GUILDID'                => $updateguild->getGuildid(),
			'GUILD_NAME'             => $updateguild->getName(),
			'L_TITLE'                => $this->user->lang['ACP_EDITGUILD'],
			'L_EXPLAIN'              => $this->user->lang['ACP_EDITGUILD_EXPLAIN'],
			'L_ADD_GUILD_TITLE'      => $this->user->lang['EDIT_GUILD'],
			'MSG_NAME_EMPTY'         => $this->user->lang['FV_REQUIRED_NAME'],
			'EMBLEM'                 => $this->asset_resolver->resolve_emblem_url((string) $updateguild->getEmblempath(), (int) $updateguild->getGuildid()),
			'EMBLEMFILE'             => basename((string) $updateguild->getEmblempath()),
			'S_EMBLEM_EXISTS'        => !empty($updateguild->getEmblempath()) && file_exists($this->root_path . $updateguild->getEmblempath()),
			'U_EDIT_GUILD'           => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=editguild&amp;' . constants::URI_GUILD . '=' . $updateguild->getGuildid()),
			'U_EDIT_GUILDRANKS'      => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildranks&amp;' . constants::URI_GUILD . '=' . $updateguild->getGuildid()),
			'U_EDIT_GUILDPORTAL'     => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildportal&amp;' . constants::URI_GUILD . '=' . $updateguild->getGuildid()),
			'U_EDIT_GUILDRECRUITMENT' => $this->acp_url('i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=guildrecruitment&amp;' . constants::URI_GUILD . '=' . $updateguild->getGuildid()),
		));

		$this->page_title = $this->user->lang['ACP_EDITGUILD'];
	}

}
