<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * bbGuild Mainpage ACP
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

use avathar\bbguild\model\admin\constants;
use avathar\bbguild\model\games\game;
use avathar\bbguild\model\games\game_registry;
use avathar\bbguild\model\games\rpg\classes;
use avathar\bbguild\model\games\rpg\faction;
use avathar\bbguild\model\games\rpg\races;
use avathar\bbguild\model\games\rpg\roles;

/**
 * Class admin_controller
 */
class admin_games
{
	/**
	 * @var \phpbb\auth\auth
	 */
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
	protected $pagination;
	/** @var request */
	protected $request;
	/** @var template */
	protected $template;
	/** @var user */
	protected $user;
	/**
	 * @var \phpbb\extension\manager
	 */
	protected $phpbb_extension_manager;
	/**
	 * @var string
	 */
	protected $ext_path;
	/**
	 * @var string
	 */
	protected $ext_path_web;
	/** @var string phpBB root path */
	protected $root_path;
	/** @var string phpBB admin path */
	protected $adm_relative_path;
	/** @var string PHP extension */
	protected $php_ext;
	/** @var string Form key used for form validation */
	protected $form_key;
	/** @var string Custom form action */
	protected $u_action;

	/* @var \avathar\bbguild\model\admin\curl curl class */
	public $curl;

	/* @var \avathar\bbguild\model\admin\log logging class */
	public $bbguildlog;

	/* @var \avathar\bbguild\model\admin\util utility class */
	public $util;

	/** @var game_registry */
	protected $game_registry;

	/** @var dispatcher_interface */
	protected $dispatcher;

	/**
	 * supported languages. The game related texts (class names etc) are not stored in language files but in the database.
	 * supported languages are en, fr, de : to add a new language you need to a) make language files b) make db installers in new language c) adapt this array
	 *
	 * @var array
	 */
	public $languagecodes;

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

	/**
	 * partly installed games
	 *
	 * @var string
	 */
	private $gamelist;

	/** @var \phpbb\path_helper */
	protected $path_helper;
	public $link;
	public $page_title;
	public $tpl_name;



	/**
	 * admin_main constructor.
	 * @param \phpbb\auth\auth                  $auth
	 * @param \phpbb\cache\driver\driver_interface  $cache
	 * @param config $config
	 * @param \phpbb\db\driver\driver_interface     $db
	 * @param language $language
	 * @param log $log
	 * @param pagination $pagination
	 * @param request $request
	 * @param template $template
	 * @param user $user
	 * @param \phpbb\path_helper                $path_helper
	 * @param \phpbb\extension\manager          $phpbb_extension_manager
	 * @param \avathar\bbguild\model\admin\curl $curl
	 * @param \avathar\bbguild\model\admin\log $log
	 * @param \avathar\bbguild\model\admin\util $util
	 * @param string $phpbb_root_path
	 * @param string $phpEx
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
	public function __construct(\phpbb\auth\auth $auth,
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
		game_registry $game_registry,
		dispatcher_interface $dispatcher,
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
		$this->bb_zonetable =  $bb_zonetable;
		$this->bb_news = $bb_news;

		$this->auth = $auth;
		$this->cache = $cache;
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->log = $log;
		$this->log->set_log_table($this->bb_logs_table);
		$this->bbguildlog = $bbguildlog;
		$this->util = $util;
		$this->pagination = $pagination;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->path_helper  = $path_helper;
		$this->phpbb_extension_manager = $phpbb_extension_manager;
		$this->ext_path     = $this->phpbb_extension_manager->get_extension_path('avathar/bbguild', true);
		$this->ext_path_web = $this->path_helper->update_web_root_path($this->ext_path);
		$this->root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;
		$this->curl = $curl;
		$this->game_registry = $game_registry;
		$this->dispatcher = $dispatcher;

		$this->languagecodes = array(
			'de' => $this->language->lang('LANG_DE'),
			'en' => $this->language->lang('LANG_EN'),
			'fr' => $this->language->lang('LANG_FR'),
			'it' => $this->language->lang('LANG_IT'),
		);

		//get number of games
		$listgames = new \avathar\bbguild\model\games\game($this->db, $this->cache, $this->config, $this->user, $this->phpbb_extension_manager, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_factions_table, $this->bb_games_table);
		//list installed games

		$sort_order = array(
			0 => array(    'id' , 'id desc') ,
			1 => array('game_id' , 'game_id desc') ,
			2 => array('game_name' , 'game_name desc'));

		$current_order = $util->switch_order($sort_order);

		$this->gamelist = $listgames->list_games($current_order['sql']);

		$installed = array();
		foreach ($this->gamelist as $game)
		{
			$installed[$game['game_id']] = $game['name'];
		}

	}

	/**
	 * request handler, called by ACP
	 *
	 * @return void
	 */
	public function handle()
	{
		$this->u_action = $this->request->variable('action', '');

		switch ($this->request->variable('mode', ''))
		{
			case 'config':
				if ($this->request->is_set_post('submit'))
				{
					$this->update_config();
				}
				break;
		}

		//switch css trigger
		$this->template->assign_vars(
			array (
				'S_BBGUILD' => true,
			)
		);

	}

	/**
	 * list games
	 */
	public function listgames()
	{
		$link = '<br /><a href="' . append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=listgames') . '"><h3>' . $this->language->lang('RETURN_GAMELIST') . '</h3></a>';
		//fetch installed games
		$games = new game($this->db, $this->cache, $this->config, $this->user, $this->phpbb_extension_manager, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_factions_table, $this->bb_games_table);

		$sort_order = array(
			0 => array(    'id' , 'id desc') ,
			1 => array('game_id' , 'game_id desc') ,
			2 => array('game_name' , 'game_name desc'));

		$current_order = $this->util->switch_order($sort_order);

		$this->gamelist = $games->list_games($current_order['sql']);

		$installed = array();
		foreach ($this->gamelist as $game)
		{
			$installed[$game['game_id']] = $game['name'];
		}

		// Installed games table
		$row_count = 0;
		foreach ($this->gamelist as $game)
		{
			// Check if the game's plugin is available in the registry
			// 'custom' is built-in and always available; other games need their plugin enabled
			$plugin_available = ($game['game_id'] === 'custom' || $this->game_registry->has($game['game_id']));

			if ($plugin_available)
			{
				$status_text = $game['status'] ? $this->language->lang('ACTIVE') : $this->language->lang('INACTIVE');
			}
			else
			{
				$status_text = $this->language->lang('PLUGIN_DISABLED');
			}

			$this->template->assign_block_vars('gamerow', array(
				'ID'               => $game['id'],
				'GAME_ID'          => $game['game_id'],
				'NAME'             => $game['name'],
				'STATUS'           => $status_text,
				'PLUGIN_AVAILABLE' => $plugin_available,
				'U_VIEW_GAME'      => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;game_id=' . $game['game_id']),
				'S_ROW_COUNT'      => $row_count++,
			));
		}

		// Default game dropdown — only show games with active plugins
		foreach ($this->gamelist as $game)
		{
			$plugin_avail = ($game['game_id'] === 'custom' || $this->game_registry->has($game['game_id']));
			if (!$plugin_avail)
			{
				continue;
			}
			$this->template->assign_block_vars('defaultgame_row', array(
				'VALUE'    => $game['game_id'],
				'SELECTED' => ($this->config['bbguild_default_game'] == $game['game_id']) ? ' selected="selected"' : '',
				'OPTION'   => $game['name'],
			));
		}

		// Installable games dropdown (from game plugin registry, excluding already installed)
		$installable = $this->game_registry->get_installable_games();
		foreach ($installable as $game_id => $game_name)
		{
			if (!isset($installed[$game_id]))
			{
				$this->template->assign_block_vars('gamelistrow', array(
					'VALUE'    => $game_id,
					'SELECTED' => '',
					'OPTION'   => $game_name,
				));
			}
		}

		// Region dropdown
		$regions = array(
			'eu'  => $this->language->lang('REGIONEU'),
			'kr'  => $this->language->lang('REGIONKR'),
			'sea' => $this->language->lang('REGIONSEA'),
			'tw'  => $this->language->lang('REGIONTW'),
			'us'  => $this->language->lang('REGIONUS'),
		);
		foreach ($regions as $key => $regionname)
		{
			$this->template->assign_block_vars('region_row', array(
				'VALUE'    => $key,
				'SELECTED' => '',
				'OPTION'   => $regionname,
			));
		}

		$u_list_game = append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=listgames');

		// Build list of available game plugin names for the info text
		$plugin_names = implode(', ', $installable);

		$this->template->assign_vars(array(
			'S_INSTALLED'  => !empty($this->gamelist),
			'CANINSTALL'   => !empty($installable),
			'PREINSTALLED' => $this->language->lang('PREINSTALLED', $plugin_names ?: $this->language->lang('NA')),
			'U_ACTION'     => $u_list_game,
			'U_LIST_GAME'  => $u_list_game,
			'O_ID'         => $current_order['uri'][0],
			'O_GAMEID'     => $current_order['uri'][1],
			'O_GAMENAME'   => $current_order['uri'][2],
		));
	}


	/***
	 *
	 *
	 */
	public function gamelist()
	{
		$editgame = new game($this->db, $this->cache, $this->config, $this->user, $this->phpbb_extension_manager, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_factions_table, $this->bb_games_table, $this->game_registry);
		$editgame->game_id = $this->request->variable(constants::URI_GAME, $this->request->variable('hidden_game_id', ''));
		$editgame->get_game();

		$this->link = '<br /><a href="' . append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "={$editgame->game_id}") . '"><h3>' . $this->language->lang('RETURN_GAMEVIEW') . '</h3></a>';
		$gamereset = $this->request->is_set_post('gamereset');
		$gamedelete = $this->request->is_set_post('gamedelete');
		$gamesettings = $this->request->is_set_post('gamesettings');
		$addrole = $this->request->is_set_post('showrolesadd');
		$action = $this->request->variable('action', '');
		$addfaction = $this->request->is_set_post('showfactionadd');
		$addrace = $this->request->is_set_post('showraceadd');
		$raceedit = (isset($_GET['raceedit'])) ? true : false;
		$racedelete = (isset($_GET['racedelete'])) ? true : false;
		$addclass = $this->request->is_set_post('showclassadd');
		$classedit = (isset($_GET['classedit'])) ? true : false;
		$classdelete = (isset($_GET['classdelete'])) ? true : false;

		// Save handlers for add/edit forms
		$save_faction_add = $this->request->is_set_post('factionadd');
		$save_faction_edit = $this->request->is_set_post('factionedit');
		$save_role_add = $this->request->is_set_post('addrole');
		$save_role_edit = $this->request->is_set_post('editrole');
		$save_race_or_class = $this->request->is_set_post('add');
		$update_race_or_class = $this->request->is_set_post('update');

		if ($save_faction_add || $save_faction_edit)
		{
			$this->SaveFaction($editgame, $save_faction_add);
		}
		else if ($save_role_add || $save_role_edit)
		{
			$this->SaveRole($editgame, $save_role_add);
		}
		else if ($save_race_or_class || $update_race_or_class)
		{
			// Determine whether this is a race or class save by checking which form fields exist
			if ($this->request->is_set_post('racename') || $this->request->is_set_post('hidden_race_id'))
			{
				$this->SaveRace($editgame, $save_race_or_class);
			}
			else
			{
				$this->SaveClass($editgame, $save_race_or_class);
			}
		}
		else if ($gamereset)
		{
			$this->ResetGame($editgame);
		}
		else if ($gamesettings)
		{
			$editgame = $this->SaveGameSettings();
			$success_message = sprintf($this->language->lang('ADMIN_UPDATED_GAME_SUCCESS'), $editgame->game_id, $editgame->getName());
			meta_refresh(3, append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "={$editgame->game_id}"));
			trigger_error($success_message . $this->link, E_USER_NOTICE);
		}
		else if ($gamedelete)
		{
			$this->DeleteGame($editgame);
		}
		else if ($addrole)
		{
			$this->BuildTemplateRole($editgame);
			return;
		}
		else if ($action=='deleterole')
		{
			$this->DeleteRole($editgame);
		}
		else if ($action=='editrole')
		{
			$this->BuildTemplateRole($editgame);
			return;
		}
		else if ($addfaction)
		{
			$this->BuildTemplateFaction($editgame);
			return;
		}
		else if ($action=='deletefaction')
		{
			$this->DeleteFaction($editgame);
		}
		else if ($action=='editfaction')
		{
			$this->BuildTemplateFaction($editgame);
			return;
		}
		else if ($raceedit)
		{
			$this->BuildTemplateEditRace($editgame);
			return;
		}
		else if ($addrace)
		{
			$this->BuildTemplateAddRace($editgame);
			return;
		}
		else if ($racedelete)
		{
			$this->DeleteRace($editgame);
		}
		else if ($classedit)
		{
			$this->BuildTemplateEditClass($editgame);
			return;
		}
		else if ($addclass)
		{
			$this->BuildTemplateAddClass($editgame);
			return;
		}
		else if ($classdelete)
		{
			$this->DeleteClass($editgame);
		}

		$this->showgame($editgame);
		$this->page_title = 'ACP_ADDGAME';

	}

	/**
	 * Build template for adding/editing a faction.
	 *
	 * @param game $editgame
	 */
	private function BuildTemplateFaction(game $editgame)
	{
		$game_id = $editgame->game_id;
		$faction_id = $this->request->variable('f_index', 0);
		$action = $this->request->variable('action', '');
		$is_add = ($action !== 'editfaction');

		$faction_name = '';
		if (!$is_add)
		{
			$fac = new faction($this->db, $this->cache, $this->user, $game_id, $this->bb_factions_table, $this->bb_races_table);
			$fac->faction_id = $faction_id;
			$fac->get();
			$faction_name = $fac->faction_name;
		}

		$this->tpl_name = 'acp_addfaction';
		$this->page_title = $is_add ? 'ACP_ADDFACTION' : 'EDIT_FACTION';

		$this->template->assign_vars(array(
			'IS_ADD'       => $is_add,
			'GAME_ID'      => $game_id,
			'GAME_NAME'    => $editgame->getName(),
			'FACTION_ID'   => $faction_id,
			'FACTION_NAME' => $faction_name,
			'U_BACK'       => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"),
			'U_ACTION2'    => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"),
			'MSG_NAME_EMPTY' => $this->language->lang('FV_REQUIRED_FACTION_NAME'),
		));
	}

	/**
	 * Delete a faction.
	 *
	 * @param game $editgame
	 */
	private function DeleteFaction(game $editgame)
	{
		$faction_id = $this->request->variable('f_index', 0);
		$fac = new faction($this->db, $this->cache, $this->user, $editgame->game_id, $this->bb_factions_table, $this->bb_races_table);
		$fac->faction_id = $faction_id;
		$fac->get();
		$fac->delete_faction();
	}

	/**
	 * Build template for adding/editing a role.
	 *
	 * @param game $editgame
	 */
	private function BuildTemplateRole(game $editgame)
	{
		$game_id = $editgame->game_id;
		$role_id = $this->request->variable('role_id', $this->request->variable('hidden_role_id', 0));
		$action = $this->request->variable('action', '');
		$is_add = ($action !== 'editrole');

		$role = new roles($this->db, $this->config, $this->cache, $this->user, $this->bb_gameroles_table, $this->bb_language_table, $this->bb_games_table, $this->bb_classes_table);
		$role->game_id = $game_id;
		$role->role_id = $role_id;

		$role_name = '';
		$role_color = '';
		$role_icon = '';
		$role_cat_icon = '';

		if (!$is_add)
		{
			$role->get();
			$role_name = $role->rolename;
			$role_color = $role->role_color;
			$role_icon = $role->role_icon;
			$role_cat_icon = $role->role_cat_icon;
		}

		$provider = $this->game_registry->get($game_id);
		if ($provider)
		{
			$role_images_web = $this->path_helper->update_web_root_path($provider->get_images_path());
		}
		else
		{
			$role_images_web = $this->ext_path_web . 'images/' . $game_id . '/';
		}
		$role_icon_img = $role_images_web . 'role_icons/' . $role_icon . '.png';

		$this->tpl_name = 'acp_addrole';
		$this->page_title = $is_add ? 'ACP_ADDROLE' : 'EDIT_ROLES';

		$this->template->assign_vars(array(
			'IS_ADD'        => $is_add,
			'GAME_ID'       => $game_id,
			'GAME_NAME'     => $editgame->getName(),
			'ROLE_ID'       => $role_id,
			'ROLE_NAME'     => $role_name,
			'ROLE_COLOR'    => $role_color,
			'ROLE_ICON'     => $role_icon,
			'ROLE_CAT_ICON' => $role_cat_icon,
			'ROLE_ICON_IMG' => $role_icon_img,
			'U_BACK'        => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"),
			'U_ACTION2'     => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"),
			'MSG_NAME_EMPTY' => $this->language->lang('FV_REQUIRED_ROLE_NAME'),
		));
	}

	/**
	 * Delete a role.
	 *
	 * @param game $editgame
	 */
	private function DeleteRole(game $editgame)
	{
		$role_id = $this->request->variable('role_id', 0);
		$role = new roles($this->db, $this->config, $this->cache, $this->user, $this->bb_gameroles_table, $this->bb_language_table, $this->bb_games_table, $this->bb_classes_table);
		$role->game_id = $editgame->game_id;
		$role->role_id = $role_id;
		$role->get();
		$role->delete_role();
	}

	/**
	 * Build template for adding a race.
	 *
	 * @param game $editgame
	 */
	private function BuildTemplateAddRace(game $editgame)
	{
		$this->BuildTemplateRace($editgame, true);
	}

	/**
	 * Build template for editing a race.
	 *
	 * @param game $editgame
	 */
	private function BuildTemplateEditRace(game $editgame)
	{
		$this->BuildTemplateRace($editgame, false);
	}

	/**
	 * Build template for adding or editing a race.
	 *
	 * @param game  $editgame
	 * @param bool  $is_add
	 */
	private function BuildTemplateRace(game $editgame, $is_add)
	{
		$game_id = $editgame->game_id;
		$race_id = $this->request->variable('race_id', $this->request->variable('hidden_race_id', 0));

		$race = new races($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_races_table, $this->bb_factions_table);
		$race->game_id = $game_id;
		$race->race_id = $race_id;

		$race_name = '';
		$image_male = '';
		$image_female = '';
		$race_faction_id = 0;

		if (!$is_add)
		{
			$race->get_race();
			$race_name = $race->race_name;
			$image_male = $race->image_male;
			$image_female = $race->image_female;
			$race_faction_id = $race->race_faction_id;
		}

		// Faction dropdown
		$fac = new faction($this->db, $this->cache, $this->user, $game_id, $this->bb_factions_table, $this->bb_races_table);
		$factions = $fac->get_factions();
		$faction_options = '';
		foreach ($factions as $fid => $fdata)
		{
			$selected = ($fid == $race_faction_id) ? ' selected="selected"' : '';
			$faction_options .= '<option value="' . $fid . '"' . $selected . '>' . $fdata['faction_name'] . '</option>';
		}

		// Image paths - use game plugin's own path if provider exists
		$provider = $this->game_registry->get($game_id);
		if ($provider)
		{
			$img_path = $provider->get_images_path();
			$img_web  = $this->path_helper->update_web_root_path($img_path);
		}
		else
		{
			$img_path = $this->ext_path . 'images/' . $game_id . '/';
			$img_web  = $this->ext_path_web . 'images/' . $game_id . '/';
		}
		$race_image_m = $img_web . $image_male . '.png';
		$race_image_f = $img_web . $image_female . '.png';
		$race_image_m_file = $img_path . $image_male . '.png';
		$race_image_f_file = $img_path . $image_female . '.png';

		$this->tpl_name = 'acp_addrace';
		$this->page_title = $is_add ? 'ACP_ADDRACE' : 'ACP_EDITRACE';

		$this->template->assign_vars(array(
			'S_ADD'                => $is_add,
			'GAME_ID'              => $game_id,
			'GAME_NAME'            => $editgame->getName(),
			'RACE_ID'              => $race_id,
			'RACE_NAME'            => $race_name,
			'RACE_IMAGENAME_M'     => $image_male,
			'RACE_IMAGENAME_F'     => $image_female,
			'RACE_IMAGE_M'         => $race_image_m,
			'RACE_IMAGE_F'         => $race_image_f,
			'S_RACE_IMAGE_M_EXISTS' => @file_exists($race_image_m_file),
			'S_RACE_IMAGE_F_EXISTS' => @file_exists($race_image_f_file),
			'S_FACTIONLIST_OPTIONS' => $faction_options,
			'U_BACK'               => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"),
			'U_ACTION2'            => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"),
			'MSG_NAME_EMPTY'       => $this->language->lang('FV_REQUIRED_RACE_NAME'),
			'MSG_RACE_EMPTY'       => $this->language->lang('FV_REQUIRED_RACEID'),
			'MIMAGEWARNING'        => '',
			'FIMAGEWARNING'        => '',
		));
	}

	/**
	 * Delete a race.
	 *
	 * @param game $editgame
	 */
	private function DeleteRace(game $editgame)
	{
		$race_id = $this->request->variable('race_id', 0);
		$race = new races($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_races_table, $this->bb_factions_table);
		$race->game_id = $editgame->game_id;
		$race->race_id = $race_id;
		$race->get_race();
		$race->delete_race();
	}

	/**
	 * Build template for adding a class.
	 *
	 * @param game $editgame
	 */
	private function BuildTemplateAddClass(game $editgame)
	{
		$this->BuildTemplateClass($editgame, true);
	}

	/**
	 * Build template for editing a class.
	 *
	 * @param game $editgame
	 */
	private function BuildTemplateEditClass(game $editgame)
	{
		$this->BuildTemplateClass($editgame, false);
	}

	/**
	 * Build template for adding or editing a class.
	 *
	 * @param game  $editgame
	 * @param bool  $is_add
	 */
	private function BuildTemplateClass(game $editgame, $is_add)
	{
		$game_id = $editgame->game_id;
		$class_id = $this->request->variable('class_id', $this->request->variable('hidden_class_id', 0));

		$editclass = new classes($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_classes_table);
		$editclass->game_id = $game_id;
		$editclass->class_id = $class_id;

		$class_name = '';
		$class_min = 1;
		$class_max = 60;
		$armor_type = '';
		$imagename = '';
		$colorcode = '#999999';
		$c_index = 0;

		if (!$is_add)
		{
			$editclass->get_class();
			$class_name = $editclass->classname;
			$class_min = $editclass->min_level;
			$class_max = $editclass->max_level;
			$armor_type = $editclass->armor_type;
			$imagename = $editclass->imagename;
			$colorcode = $editclass->colorcode;
			$c_index = $editclass->c_index;
		}

		// Armor type dropdown — prefer game provider, fall back to core defaults
		$provider = $this->game_registry->get($game_id);
		$armor_types = ($provider !== null) ? $provider->get_armor_types() : $editclass->armortypes;
		$armor_options = '';
		foreach ($armor_types as $key => $name)
		{
			$selected = ($key == $armor_type) ? ' selected="selected"' : '';
			$armor_options .= '<option value="' . $key . '"' . $selected . '>' . $name . '</option>';
		}

		// Image path - use game plugin's own path if provider exists
		if ($provider)
		{
			$img_path = $provider->get_images_path();
			$img_web  = $this->path_helper->update_web_root_path($img_path);
		}
		else
		{
			$img_path = $this->ext_path . 'images/' . $game_id . '/';
			$img_web  = $this->ext_path_web . 'images/' . $game_id . '/';
		}
		$class_image = $img_web . $imagename . '.png';
		$class_image_file = $img_path . $imagename . '.png';

		$this->tpl_name = 'acp_addclass';
		$this->page_title = $is_add ? 'ACP_ADDCLASS' : 'ACP_EDITCLASS';

		$this->template->assign_vars(array(
			'S_ADD'               => $is_add,
			'GAME_ID'             => $game_id,
			'GAME_NAME'           => $editgame->getName(),
			'CLASS_ID'            => $class_id,
			'C_INDEX'             => $c_index,
			'CLASS_NAME'          => $class_name,
			'CLASS_MIN'           => $class_min,
			'CLASS_MAX'           => $class_max,
			'CLASS_IMAGENAME'     => $imagename,
			'CLASS_IMAGE'         => $class_image,
			'S_CLASS_IMAGE_EXISTS' => @file_exists($class_image_file),
			'COLORCODE'           => $colorcode,
			'S_ARMOR_OPTIONS'     => $armor_options,
			'U_BACK'              => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"),
			'U_ACTION2'           => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"),
			'IMAGEWARNING'        => '',
		));
	}

	/**
	 * Delete a class.
	 *
	 * @param game $editgame
	 */
	private function DeleteClass(game $editgame)
	{
		$class_id = $this->request->variable('class_id', 0);
		$editclass = new classes($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_classes_table);
		$editclass->game_id = $editgame->game_id;
		$editclass->class_id = $class_id;
		$editclass->get_class();
		$editclass->delete_class();
	}

	/**
	 * Reset (reinstall) a game: uninstall then install again.
	 *
	 * @param game $editgame The game to reset
	 */
	private function ResetGame(game $editgame)
	{
		$editgame->delete_game();
		$editgame->install_game();

		$success_message = sprintf($this->language->lang('ADMIN_RESET_GAME_SUCCESS'), $editgame->getName());
		meta_refresh(3, append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "={$editgame->game_id}"));
		trigger_error($success_message . $this->link, E_USER_NOTICE);
	}

	/**
	 * Delete a game and redirect to the game list.
	 *
	 * @param game $editgame The game to delete
	 */
	private function DeleteGame(game $editgame)
	{
		$game_id = $editgame->game_id;
		$game_name = $editgame->getName();
		$editgame->delete_game();

		$success_message = sprintf($this->language->lang('ADMIN_DELETE_GAME_SUCCESS'), $game_name);
		meta_refresh(3, append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=listgames'));
		trigger_error($success_message . $this->link, E_USER_NOTICE);
	}

	/**
	 * Save game settings from the edit game form.
	 *
	 * @return game The updated game object
	 */
	private function SaveGameSettings()
	{
		$editgame = new game(
			$this->db, $this->cache, $this->config, $this->user, $this->phpbb_extension_manager,
			$this->bb_classes_table, $this->bb_races_table,
			$this->bb_language_table, $this->bb_factions_table,
			$this->bb_games_table, $this->game_registry
		);
		$editgame->game_id = $this->request->variable(constants::URI_GAME, $this->request->variable('hidden_game_id', ''));
		$editgame->get_game();

		$editgame->setName($this->request->variable('game_name', '', true));
		$editgame->setImagename($this->request->variable('imagename', '', true));
		$editgame->setBossbaseurl($this->request->variable('bossbaseurl', '', true));
		$editgame->setZonebaseurl($this->request->variable('zonebaseurl', '', true));

		// Check if this game has API support via the game registry
		$game_id = $editgame->game_id;
		$provider = $this->game_registry->get($game_id);
		$has_api = ($provider !== null && $provider->has_api());

		if ($has_api)
		{
			$editgame->setArmoryEnabled($this->request->variable('enable_armory', 0));
		}

		/**
		 * Event dispatched when game settings are submitted.
		 * Allows game plugins to read their own form values and set them on the game object.
		 *
		 * @event avathar.bbguild.acp_editgames_submit
		 * @var game   editgame  The game object being saved
		 * @var string game_id   The game identifier
		 * @var bool   has_api   Whether this game has API support
		 * @since 2.0.0-b2
		 */
		$vars = array('editgame', 'game_id', 'has_api');
		extract($this->dispatcher->trigger_event('avathar.bbguild.acp_editgames_submit', compact($vars)));

		$editgame->update_game();

		return $editgame;
	}

	/**
	 * Populate template variables for the edit game page.
	 *
	 * @param game $editgame The game being displayed
	 */
	private function showgame(game $editgame)
	{
		$game_id = $editgame->game_id;
		$provider = $this->game_registry->get($game_id);
		$has_api = ($provider !== null && $provider->has_api());

		// Region dropdown
		$regions = $editgame->getRegions();
		if (is_array($regions))
		{
			foreach ($regions as $key => $regionname)
			{
				$this->template->assign_block_vars('region_row', array(
					'VALUE'    => $key,
					'SELECTED' => ($editgame->getRegion() == $key) ? ' selected="selected"' : '',
					'OPTION'   => (!empty($regionname)) ? $regionname : '(None)',
				));
			}
		}

		// Resolve image paths: use game plugin's own path if provider exists, else fall back to core
		if ($provider)
		{
			$game_images_path = $provider->get_images_path();
			$game_images_web  = $this->path_helper->update_web_root_path($game_images_path);
		}
		else
		{
			$game_images_path = $this->ext_path . 'images/' . $game_id . '/';
			$game_images_web  = $this->ext_path_web . 'images/' . $game_id . '/';
		}

		// Game image path
		$imagename = $editgame->getImagename();
		$gamepath = $game_images_web . $imagename . '.png';
		$gamepath_file = $game_images_path . $imagename . '.png';

		$this->template->assign_vars(array(
			'URI_GAME'           => constants::URI_GAME,
			'GAME_ID'            => $game_id,
			'GAME_NAME'          => $editgame->getName(),
			'GAMEIMAGE'          => $imagename,
			'GAMEIMAGEEXPLAIN'   => $this->language->lang('CLASS_IMAGE_EXPLAIN'),
			'GAMEPATH'           => $gamepath,
			'S_GAMEIMAGE_EXISTS' => @file_exists($gamepath_file),
			'F_ENABLEARMORY'     => $editgame->getArmoryEnabled(),
			'HAS_API'            => $has_api,
			'BOSSBASEURL'        => $editgame->getBossbaseurl(),
			'ZONEBASEURL'        => $editgame->getZonebaseurl(),
			'EDITGAME'           => sprintf($this->language->lang('ACP_EDITGAME'), $editgame->getName()),
		));

		/**
		 * Event dispatched when the edit game template is being built.
		 * Allows game plugins to inject their own template variables.
		 *
		 * @event avathar.bbguild.acp_editgames_display
		 * @var game   editgame  The game object being displayed
		 * @var string game_id   The game identifier
		 * @var bool   has_api   Whether this game has API support
		 * @since 2.0.0-b2
		 */
		$vars = array('editgame', 'game_id', 'has_api');
		extract($this->dispatcher->trigger_event('avathar.bbguild.acp_editgames_display', compact($vars)));

		$u_edit_game = append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id");

		// Factions
		$factions_obj = new faction($this->db, $this->cache, $this->user, $game_id, $this->bb_factions_table, $this->bb_races_table);
		$factions = $factions_obj->get_factions();
		$row_count = 0;
		foreach ($factions as $fac)
		{
			$this->template->assign_block_vars('faction_row', array(
				'FACTIONID'   => $fac['faction_id'],
				'FACTIONNAME' => $fac['faction_name'],
				'U_DELETE'    => $u_edit_game . '&amp;action=deletefaction&amp;f_index=' . $fac['f_index'],
				'U_EDIT'      => $u_edit_game . '&amp;action=editfaction&amp;f_index=' . $fac['f_index'],
				'S_ROW_COUNT' => $row_count++,
			));
		}
		$this->template->assign_var('LISTFACTION_FOOTCOUNT', sprintf($this->language->lang('LISTFACTION_FOOTCOUNT'), count($factions)));

		// Resolve image paths: use game plugin's own path if provider exists, else fall back to core
		$provider = $this->game_registry->get($game_id);
		if ($provider)
		{
			$game_images_path = $provider->get_images_path();
			$game_images_web  = $this->path_helper->update_web_root_path($game_images_path);
		}
		else
		{
			$game_images_path = $this->ext_path . 'images/' . $game_id . '/';
			$game_images_web  = $this->ext_path_web . 'images/' . $game_id . '/';
		}

		// Races
		$races_obj = new races($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_races_table, $this->bb_factions_table);
		$races_obj->game_id = $game_id;
		$race_list = $races_obj->list_races();
		$row_count = 0;
		foreach ($race_list as $race)
		{
			$image_male = !empty($race['image_male']) ? $game_images_web . 'race_images/' . $race['image_male'] . '.png' : '';
			$image_female = !empty($race['image_female']) ? $game_images_web . 'race_images/' . $race['image_female'] . '.png' : '';

			$this->template->assign_block_vars('race_row', array(
				'RACEID'               => $race['race_id'],
				'RACENAME'             => $race['race_name'],
				'RACE_IMAGE_M'         => $image_male,
				'S_RACE_IMAGE_M_EXISTS'=> !empty($race['image_male']) && @file_exists($game_images_path . 'race_images/' . $race['image_male'] . '.png'),
				'RACE_IMAGE_F'         => $image_female,
				'S_RACE_IMAGE_F_EXISTS'=> !empty($race['image_female']) && @file_exists($game_images_path . 'race_images/' . $race['image_female'] . '.png'),
				'FACTIONNAME'          => $race['faction_name'],
				'U_DELETE'             => $u_edit_game . '&amp;racedelete=1&amp;race_id=' . $race['race_id'],
				'U_EDIT'               => $u_edit_game . '&amp;raceedit=1&amp;race_id=' . $race['race_id'],
				'S_ROW_COUNT'          => $row_count++,
			));
		}
		$this->template->assign_var('LISTRACE_FOOTCOUNT', sprintf($this->language->lang('LISTRACE_FOOTCOUNT'), count($race_list)));

		// Roles
		$roles_obj = new roles($this->db, $this->config, $this->cache, $this->user, $this->bb_gameroles_table, $this->bb_language_table, $this->bb_games_table, $this->bb_classes_table);
		$roles_obj->game_id = $game_id;
		$role_list = $roles_obj->list_roles();
		$row_count = 0;
		foreach ($role_list as $role)
		{
			$role_icon_path = !empty($role['role_icon']) ? $game_images_web . 'role_icons/' . $role['role_icon'] . '.png' : '';
			$role_cat_icon_path = !empty($role['role_cat_icon']) ? $game_images_web . 'role_icons/' . $role['role_cat_icon'] . '.png' : '';

			$this->template->assign_block_vars('role_row', array(
				'ROLE_ID'               => $role['role_id'],
				'ROLE_NAME'             => $role['rolename'],
				'ROLE_COLOR'            => $role['role_color'],
				'ROLE_ICON'             => $role['role_icon'],
				'U_ROLE_ICON'           => $role_icon_path,
				'S_ROLE_ICON_EXISTS'    => !empty($role['role_icon']) && @file_exists($game_images_path . 'role_icons/' . $role['role_icon'] . '.png'),
				'U_ROLE_CAT_ICON'       => $role_cat_icon_path,
				'S_ROLE_CAT_ICON_EXISTS'=> !empty($role['role_cat_icon']) && @file_exists($game_images_path . 'role_icons/' . $role['role_cat_icon'] . '.png'),
				'U_DELETE'              => $u_edit_game . '&amp;action=deleterole&amp;role_id=' . $role['role_id'],
				'U_EDIT'                => $u_edit_game . '&amp;action=editrole&amp;role_id=' . $role['role_id'],
				'S_ROW_COUNT'           => $row_count++,
			));
		}
		$this->template->assign_var('LISTROLES_FOOTCOUNT', sprintf($this->language->lang('LISTROLES_FOOTCOUNT'), count($role_list)));

		// Classes
		$classes_obj = new classes($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_classes_table);
		$classes_obj->game_id = $game_id;
		$class_list = $classes_obj->list_classes('class_id', 1);
		$row_count = 0;
		foreach ($class_list as $cls)
		{
			$class_image_path = !empty($cls['imagename']) ? $game_images_web . 'class_images/' . $cls['imagename'] . '.png' : '';

			$this->template->assign_block_vars('class_row', array(
				'CLASSID'             => $cls['class_id'],
				'CLASSNAME'           => $cls['class_name'],
				'COLORCODE'           => $cls['colorcode'],
				'CLASSIMAGE'          => $class_image_path,
				'S_CLASS_IMAGE_EXISTS'=> !empty($cls['imagename']) && @file_exists($game_images_path . 'class_images/' . $cls['imagename'] . '.png'),
				'CLASSARMOR'          => $cls['class_armor_type'],
				'CLASSMIN'            => $cls['class_min_level'],
				'CLASSMAX'            => $cls['class_max_level'],
				'U_DELETE'            => $u_edit_game . '&amp;classdelete=1&amp;class_id=' . $cls['class_id'],
				'U_EDIT'              => $u_edit_game . '&amp;classedit=1&amp;class_id=' . $cls['class_id'],
				'S_ROW_COUNT'         => $row_count++,
			));
		}
		$this->template->assign_var('LISTCLASS_FOOTCOUNT', sprintf($this->language->lang('LISTCLASS_FOOTCOUNT'), count($class_list)));
	}

	/**
	 * Save a faction (add or edit).
	 */
	private function SaveFaction(game $editgame, bool $is_add)
	{
		$game_id = $editgame->game_id;
		$fac = new faction($this->db, $this->cache, $this->user, $game_id, $this->bb_factions_table, $this->bb_races_table);
		$fac->faction_name = $this->request->variable('factionname', '', true);

		if ($is_add)
		{
			$fac->make_faction();
			$success_message = sprintf($this->language->lang('ADMIN_ADD_FACTION_SUCCESS'), $fac->faction_name);
		}
		else
		{
			$fac->faction_id = $this->request->variable('hidden_faction_id', 0);
			$fac->update_faction();
			$success_message = sprintf($this->language->lang('ADMIN_UPDATE_FACTION_SUCCESS'), $fac->faction_name);
		}

		meta_refresh(3, append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"));
		trigger_error($success_message . $this->link, E_USER_NOTICE);
	}

	/**
	 * Save a role (add or edit).
	 */
	private function SaveRole(game $editgame, bool $is_add)
	{
		$game_id = $editgame->game_id;
		$role = new roles($this->db, $this->config, $this->cache, $this->user, $this->bb_gameroles_table, $this->bb_language_table, $this->bb_games_table, $this->bb_classes_table);
		$role->game_id = $game_id;
		$role->rolename = $this->request->variable('rolename', '', true);
		$role->role_color = $this->request->variable('role_color', '', true);
		$role->role_icon = $this->request->variable('role_icon', '', true);
		$role->role_cat_icon = $this->request->variable('role_cat_icon', '', true);

		if ($is_add)
		{
			$role->make_role();
			$success_message = sprintf($this->language->lang('ADMIN_ADD_ROLE_SUCCESS'), $role->rolename);
		}
		else
		{
			$role->role_id = $this->request->variable('hidden_role_id', 0);
			$role->role_pkid = $this->request->variable('hidden_role_id', 0);
			$oldrole = new roles($this->db, $this->config, $this->cache, $this->user, $this->bb_gameroles_table, $this->bb_language_table, $this->bb_games_table, $this->bb_classes_table);
			$oldrole->game_id = $game_id;
			$oldrole->role_id = $role->role_id;
			$oldrole->get();
			$role->role_pkid = $oldrole->role_pkid;
			$role->update_role($oldrole);
			$success_message = sprintf($this->language->lang('ADMIN_UPDATE_ROLE_SUCCESS'), $role->rolename);
		}

		meta_refresh(3, append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"));
		trigger_error($success_message . $this->link, E_USER_NOTICE);
	}

	/**
	 * Save a race (add or edit).
	 */
	private function SaveRace(game $editgame, bool $is_add)
	{
		$game_id = $editgame->game_id;
		$race = new races($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_races_table, $this->bb_factions_table);
		$race->game_id = $game_id;
		$race->race_name = $this->request->variable('racename', '', true);
		$race->race_faction_id = $this->request->variable('faction', 0);
		$race->image_male = $this->request->variable('image_male', '', true);
		$race->image_female = $this->request->variable('image_female', '', true);

		if ($is_add)
		{
			$race->race_id = $this->request->variable('race_id', 0);
			$race->make_race();
			$success_message = sprintf($this->language->lang('ADMIN_ADD_RACE_SUCCESS'), $race->race_name);
		}
		else
		{
			$race->race_id = $this->request->variable('hidden_race_id', 0);
			$old_race = new races($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_races_table, $this->bb_factions_table);
			$old_race->game_id = $game_id;
			$old_race->race_id = $race->race_id;
			$old_race->get_race();
			$race->update_race($old_race);
			$success_message = sprintf($this->language->lang('ADMIN_UPDATE_RACE_SUCCESS'), $race->race_name);
		}

		meta_refresh(3, append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"));
		trigger_error($success_message . $this->link, E_USER_NOTICE);
	}

	/**
	 * Save a class (add or edit).
	 */
	private function SaveClass(game $editgame, bool $is_add)
	{
		$game_id = $editgame->game_id;
		$cls = new classes($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_classes_table);
		$cls->game_id = $game_id;
		$cls->classname = $this->request->variable('class_name', '', true);
		$cls->min_level = $this->request->variable('class_level_min', 1);
		$cls->max_level = $this->request->variable('class_level_max', 60);
		$cls->armor_type = $this->request->variable('armory', '', true);
		$cls->imagename = $this->request->variable('image', '', true);
		$cls->colorcode = $this->request->variable('classcolor', '#999999', true);

		if ($is_add)
		{
			$cls->class_id = $this->request->variable('class_id', 0);
			$cls->make_class();
			$success_message = sprintf($this->language->lang('ADMIN_ADD_CLASS_SUCCESS'), $cls->classname);
		}
		else
		{
			$cls->class_id = $this->request->variable('class_id', 0);
			$cls->c_index = $this->request->variable('c_index', 0);
			$oldclass = new classes($this->db, $this->config, $this->cache, $this->user, $this->bb_language_table, $this->bb_players_table, $this->bb_games_table, $this->bb_classes_table);
			$oldclass->game_id = $game_id;
			$oldclass->class_id = $this->request->variable('class_id0', 0);
			$cls->update_class($oldclass);
			$success_message = sprintf($this->language->lang('ADMIN_UPDATE_CLASS_SUCCESS'), $cls->classname);
		}

		meta_refresh(3, append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;' . constants::URI_GAME . "=$game_id"));
		trigger_error($success_message . $this->link, E_USER_NOTICE);
	}
}
