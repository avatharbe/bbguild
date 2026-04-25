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
use phpbb\user;
use avathar\bbguild\model\admin\constants;

/**
 * Class admin_controller
 */
class admin_main
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
	/** @var \phpbb\path_helper */
	protected $path_helper;
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

	/** @var \phpbb\event\dispatcher_interface */
	protected $dispatcher;

	/* @var \avathar\bbguild\model\admin\curl curl class */
	public $curl;

	/* @var \avathar\bbguild\model\admin\log logging class */
	public $bbguildlog;

	/* @var \avathar\bbguild\model\admin\util utility class */
	public $util;

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
		request $request, template $template, user $user,
		\phpbb\path_helper $path_helper,
		\phpbb\extension\manager $phpbb_extension_manager,
		$phpbb_root_path,
		$adm_relative_path,
		$phpEx,
		\phpbb\event\dispatcher_interface $dispatcher,
		\avathar\bbguild\model\admin\curl $curl,
		\avathar\bbguild\model\admin\log $bbguildlog,
		\avathar\bbguild\model\admin\util $util,
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
		$this->adm_relative_path = $adm_relative_path;

		$this->php_ext = $phpEx;
		$this->dispatcher = $dispatcher;
		$this->curl = $curl;

		$this->languagecodes = array(
			'de' => $this->language->lang('LANG_DE'),
			'en' => $this->language->lang('LANG_EN'),
			'es' => $this->language->lang('LANG_ES'),
			'fr' => $this->language->lang('LANG_FR'),
			'it' => $this->language->lang('LANG_IT'),
			'nl' => $this->language->lang('LANG_NL'),
			'pl' => $this->language->lang('LANG_PL'),
		);
	}

	/**
	 * request handler, called by ACP
	 *
	 * @return void
	 */
	public function handle()
	{

		if (! $this->auth->acl_get('a_bbguild'))
		{
			trigger_error($this->language->lang('NOAUTH_A_CONFIG_MAN'));
		}
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
	 * display Addminpanel
	 */
	public function DisplayPanel()
	{
		// get inactive players
		$sql = 'SELECT count(*) as player_count FROM ' . $this->bb_players_table . " WHERE player_status='0'";
		$result = $this->db->sql_query($sql);
		$total_players_inactive = (int) $this->db->sql_fetchfield('player_count');

		//get the active players
		$sql = 'SELECT count(*) as player_count FROM ' . $this->bb_players_table . " WHERE player_status='1'";
		$result = $this->db->sql_query($sql);
		$total_players_active = (int) $this->db->sql_fetchfield('player_count');

		// active player kpi
		$total_players = $total_players_active . ' / ' . $total_players_inactive;

		//number of guilds
		$sql = 'SELECT count(*) as guild_count FROM ' . $this->bb_guild_table;
		$result = $this->db->sql_query($sql);
		$total_guildcount = (int) $this->db->sql_fetchfield('guild_count');

		//start date
		$bbguild_started = '';
		$start_timestamp = (int) $this->config['bbguild_eqdkp_start'];
		if ($start_timestamp > 0)
		{
			$bbguild_started = $this->user->format_date($start_timestamp);
		}

		//get number of games
		$listgames = new \avathar\bbguild\model\games\game($this->db, $this->cache, $this->config, $this->user, $this->phpbb_extension_manager, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_factions_table, $this->bb_games_table);

		$sql = 'SELECT count(*) as recruitments_count FROM ' . $this->bb_recruit_table;
		$result = $this->db->sql_query($sql);
		$recruitments_count = (int) $this->db->sql_fetchfield('recruitments_count');

		//version check
		$ext_meta_manager = $this->phpbb_extension_manager->create_extension_metadata_manager('avathar/bbguild', $this->template);
		$meta_data  = $ext_meta_manager->get_metadata();
		$ext_version  = $meta_data['version'];

		$latest_version_info = $this->version_check($meta_data, $this->request->variable('versioncheck_force', false));
		if ($latest_version_info == false)
		{
			$this->template->assign_var('S_VERSIONCHECK_FAIL', true);
		}
		else
		{
			if (phpbb_version_compare($latest_version_info, $this->config['bbguild_version'], '='))
			{
				$this->template->assign_vars(
					array(
						'S_VERSION_UP_TO_DATE'    => true,
					)
				);
			}
			else if (phpbb_version_compare($latest_version_info, $this->config['bbguild_version'] , '>'))
			{
				// you have an old version
				$this->template->assign_vars(
					array(
						'BBGUILD_NOT_UP_TO_DATE_TITLE' => sprintf($this->language->lang('NOT_UP_TO_DATE_TITLE'), 'bbGuild'),
						'S_PRERELEASE'    => false,
						'BBGUILD_LATESTVERSION' => $latest_version_info,
						'BBGUILDVERSION' => $this->language->lang('BBGUILD_YOURVERSION') . $this->config['bbguild_version']  ,
						'UPDATEINSTR' => $this->language->lang('BBGUILD_LATESTVERSION') . $latest_version_info . ', <a href="' .
							$this->language->lang('WEBURL') . '">' . $this->language->lang('DOWNLOAD') . '</a>')
				);

			}
			else if (phpbb_version_compare($latest_version_info, $this->config['bbguild_version'] , '<'))
			{
				// you have a prerelease or development version
				$this->template->assign_vars(
					array(
						'BBGUILD_NOT_UP_TO_DATE_TITLE' => sprintf($this->language->lang('PRELELEASE_TITLE'), 'bbGuild'),
						'BBGUILD_LATESTVERSION' => $latest_version_info,
						'S_PRERELEASE'    => true,
						'BBGUILDVERSION' => $this->language->lang('BBGUILD_YOURVERSION') . $this->config['bbguild_version']  ,
						'UPDATEINSTR' => $this->language->lang('BBGUILD_LATESTVERSION') . $latest_version_info . ', <a href="' . $this->language->lang('WEBURL') . '">' . $this->language->lang('DOWNLOAD') . '</a>')
				);
			}
		}

		// read verbose log
		$listlogs = $this->bbguildlog->read_log('', false, true, '', '');
		if (isset($listlogs))
		{
			foreach ($listlogs as $key => $log)
			{
				$this->template->assign_block_vars(
					'actions_row', array(
						'U_VIEW_LOG'     => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-main_module&amp;mode=logs&amp;' . constants::URI_LOG . '=' . $log['log_id']) ,
						'LOGDATE'         => $log['datestamp'],
						'ACTION'         => $log['log_line'],
					)
				);
			}
		}

		$games = $listgames->games;
		unset($listgames);

		$this->template->assign_vars(
			array(
				'U_ACTION' =>  $this->u_action,
				'GLYPH' => $this->ext_path . 'adm/images/glyphs/view.gif',
				'NUMBER_OF_PLAYERS' => $total_players ,
				'NUMBER_OF_GUILDS' => $total_guildcount ,
				'BBGUILD_STARTED' => $bbguild_started,
				'BBGUILD_VERSION'    => $this->config['bbguild_version'] ,
				'U_VERSIONCHECK_FORCE' => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-main_module&amp;mode=panel&amp;action=versioncheck_force') ,
				'GAMES_INSTALLED' =>  (count($games) > 0) ? implode(', ', $games) : $this->user->lang['NA'],
			)
		);
	}

	/**
	 * display bbguild settings
	 */
	public function display_config()
	{

		$s_lang_options = '';
		foreach ($this->languagecodes as $lang => $langname)
		{
			$selected = ($this->config['bbguild_lang'] == $lang) ? ' selected="selected"' : '';
			$s_lang_options .= '<option value="' . $lang . '" ' . $selected . '> ' . $langname . '</option>';
		}

		//roster layout switch between grid and class list
		$rosterlayoutlist = array(
			0 =>  $this->language->lang('ARM_STAND') ,
			1 =>  $this->language->lang('ARM_CLASS')
		);

		foreach ($rosterlayoutlist as $lid => $lname)
		{
			$this->template->assign_block_vars(
				'rosterlayout_row', array(
					'VALUE' => $lid ,
					'SELECTED' => ($lid == $this->config['bbguild_roster_layout']) ? ' selected="selected"' : '' ,
					'OPTION' => $lname)
			);
		}

		// number of news and items to show on front page
		$n_news  = $this->config['bbguild_n_news'];
		$n_items = $this->config['bbguild_n_items'];

		$this->template->assign_vars(
			array(
				'EQDKP_START_DATE' => date('Y-m-d', (int) $this->config['bbguild_eqdkp_start']) ,
				'S_LANG_OPTIONS' => $s_lang_options,
				'USER_LLIMIT' => $this->config['bbguild_user_llimit'] ,
				'MAXCHARS' => $this->config['bbguild_maxchars'] ,
				'MINLEVEL' => $this->config['bbguild_minrosterlvl'],
				'HIDE_INACTIVE' => (int) $this->config['bbguild_hide_inactive'],
				'USER_NLIMIT' => $this->config['bbguild_user_nlimit'] ,
				'U_ACTION' => $this->u_action,
			)
		);

		/**
		 * Event to allow plugins to add settings to the bbGuild config page.
		 *
		 * @event avathar.bbguild.acp_config_display
		 * @since 2.0.0-b1
		 */
		$this->dispatcher->dispatch('avathar.bbguild.acp_config_display');

		// is gameworld extension installed ?
		if (isset($this->config['bbguild_gameworld_version']))
		{
			$this->template->assign_vars(
				array(
					'S_BP_SHOW' => true ,
					'SHOW_BOSS' => (int) $this->config['bbguild_portal_bossprogress'])
			);
		}
		else
		{
			$this->template->assign_var('S_BP_SHOW', false);
		}

		add_form_key('acp_bbguild');
	}

	/**
	 *
	 * save bbguild config
	 */
	public function update_config()
	{
		if (! check_form_key('acp_bbguild'))
		{
			trigger_error($this->language->lang('FV_FORMVALIDATION'), E_USER_WARNING);
		}

		$start_date = $this->request->variable('bbguild_start_date', '');
		$bbguild_start = !empty($start_date) ? (int) strtotime($start_date) : 0;
		$this->config->set('bbguild_eqdkp_start', $bbguild_start, true);
		$this->config->set('bbguild_lang', $this->request->variable('language', 'en'), true);
		$this->config->set('bbguild_user_nlimit', $this->request->variable('bbguild_user_nlimit', 0), true);
		$this->config->set('bbguild_user_llimit', $this->request->variable('bbguild_user_llimit', 0), true);
		$this->config->set('bbguild_maxchars', $this->request->variable('bbguild_maxchars', 2), true);
		$this->config->set('bbguild_minrosterlvl', $this->request->variable('bbguild_minrosterlvl', 0), true);
		$this->config->set('bbguild_roster_layout', $this->request->variable('bbguild_roster_layout', 0), true);
		$this->config->set('bbguild_hide_inactive', $this->request->variable('bbguild_hide_inactive', 0), true);

		/**
		 * Event to allow plugins to save settings from the bbGuild config page.
		 *
		 * @event avathar.bbguild.acp_config_submit
		 * @since 2.0.0-b1
		 */
		$this->dispatcher->dispatch('avathar.bbguild.acp_config_submit');

		//if the gameworld extension is installed
		if (isset($this->config['bbguild_gameworld_version']))
		{
			$this->config->set('bbguild_portal_bossprogress', $this->request->variable('show_bosspblock', 0), true);
		}

		// Purge config cache
		$this->cache->destroy('config');

		//
		// Logging
		//
		$this->bbguildlog->log_insert(
			array(
				'log_type'   => 'L_ACTION_SETTINGS_CHANGED',
				'log_action' => [],
			)
		);

		trigger_error($this->language->lang('ACTION_SETTINGS_CHANGED') . adm_back_link($this->u_action) , E_USER_NOTICE);

	}


	/**
	 * retrieve latest version
	 *
	 * @param  bool $force_update Ignores cached data. Defaults to false.
	 * @param  int  $ttl          Cache version information for $ttl seconds. Defaults to 86400 (24 hours).
	 * @return bool
	 */
	public final function version_check($meta_data, $force_update = false, $ttl = 86400)
	{
		$pemfile = '';
		$versionurl = ($meta_data['extra']['version-check']['ssl'] == '1' ? 'https://': 'http://') .
			$meta_data['extra']['version-check']['host'].$meta_data['extra']['version-check']['directory'].'/'.$meta_data['extra']['version-check']['filename'];
		$ssl = $meta_data['extra']['version-check']['ssl'] == '1' ? true: false;
		if ($ssl)
		{
			//https://davidwalsh.name/php-ssl-curl-error
			$pemfile = $this->phpbb_extension_manager->get_extension_path('avathar/bbguild', true) . 'controller/cacert.pem';
			if (!(file_exists($pemfile) && is_readable($pemfile)))
			{
				$ssl = false;
			}
		}

		//get latest productversion from cache
		$latest_version = $this->cache->get('bbguild_version_latest');

		//if cache expired or update is forced then make call to refresh latest productversion
		if ($latest_version == false || $force_update)
		{
			$data = $this->curl->curl($versionurl, $pemfile, $ssl, false, false, false);
			if (0 === count($data) )
			{
				$this->cache->destroy('bbguild_version_latest');
				return false;
			}

			$response = $data['response'];
			$latest_version = json_decode($response, true);
			$latest_version = $latest_version['unstable']['3.3']['current'] ?? $latest_version['stable']['3.3']['current'] ?? '';

			//put this info in the cache
			$this->cache->put('bbguild_version_latest', $latest_version, $ttl);

		}

		return $latest_version;
	}


	/***
	 * list logs
	 *
	 */
	public function listlogs()
	{

		$log_id = $this->request->variable(constants::URI_LOG, 0);
		if ($log_id)
		{
			$this->viewlog($log_id);
			return;
		}

		if ($this->request->is_set_post('delmarked'))
		{
			$this->deletelog();
			return;
		}

		$this->page_title = 'ACP_BBGUILD_LOGS';

		$start = $this->request->variable('start', 0);
		$verbose = true;

		$logcount = $this->bbguildlog->getTotalLogs();

		$pagination_url = append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-main_module&amp;mode=logs&amp;') .  '&amp';
		$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'page', $logcount, constants::USER_LLIMIT, $start);

		//header
		$this->template->assign_vars(
			array(
				'S_LIST'        => true ,
				'L_TITLE'       => $this->language->lang('ACP_BBGUILD_LOGS') ,
				'L_EXPLAIN'     => $this->language->lang('ACP_BBGUILD_LOGS_EXPLAIN') ,
				'U_LOGS'        => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-main_module&amp;mode=logs&amp;') . '&amp;start=' . $start ,
				'U_LOGS_SEARCH' => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-main_module&amp;mode=logs'),
				'START'         => $start ,
				'VIEWLOGS_FOOTCOUNT' => sprintf($this->language->lang('VIEWLOGS_FOOTCOUNT'), $logcount, constants::USER_LLIMIT) ,
				'PAGE_NUMBER'   => $this->pagination->on_page($logcount, constants::USER_LLIMIT, $start)
			)
		);

		$listlogs = $this->bbguildlog->read_log('', '', $verbose, '', $start);
		foreach ($listlogs as $key => $log)
		{
			$this->template->assign_block_vars(
				'logs_row', array(
					'ID'            => $log['log_id'],
					'DATE'          => $log['datestamp'],
					'TYPE'          => $log['log_type'],
					'U_VIEW_LOG'    => append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-main_module&amp;mode=logs&amp;' . constants::URI_LOG . '=' . $log['log_id'] . '&amp;start=' . $start) ,
					'VERBOSE'       => $verbose,
					'IMGPATH'       => $this->ext_path . 'adm/images/glyphs/view.gif',
					'USER'          => $log['username'],
					'ACTION'        => $log['log_line'],
					'IP'            => $log['log_ipaddress'],
					'RESULT'        => $log['log_result'],
					'C_RESULT'      => $log['cssresult'] ,
					'ENCODED_TYPE'  => urlencode($log['log_type']) ,
					'ENCODED_USER'  => urlencode($log['username']) ,
					'ENCODED_IP'    => urlencode($log['log_ipaddress']))
			);
		}

	}

	/***
	 * remove log entry
	 *
	 */
	public function deletelog()
	{
		$marked = $this->request->variable('mark', array(0));

		//if marked array isnt empty
		if (is_array($marked) && count($marked))
		{
			if (confirm_box(true))
			{
				$marked = $this->request->variable('mark', array(0));
				// deletion action and log
				$deleted_ids = $this->bbguildlog->delete_log($marked);
				$this->bbguildlog->log_insert(
					array(
						'log_type'   => 'L_ACTION_LOG_DELETED',
						'log_action' => [implode(',', $deleted_ids)],
					)
				);

				//redirect to listing
				$meta_info = append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-main_module&amp;mode=logs');
				meta_refresh(3, $meta_info);
				$message = '<a href="' . append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-main_module&amp;mode=logs') . '">' .
					$this->language->lang('RETURN_LOG') . '</a><br />' . sprintf($this->language->lang('ADMIN_LOG_DELETE_SUCCESS'), implode(', ', $marked));
				trigger_error($message, E_USER_WARNING);
			}
			else
			{
				// display confirmation
				confirm_box(
					false, $this->user->lang['CONFIRM_DELETE_BBDKPLOG'], build_hidden_fields(
						array(
							'delmarked' => true ,
							'mark'         => $marked)
					)
				);
			}
			// they hit no
			$message = '<a href="' . append_sid("index.$this->php_ext", 'i=-avathar-bbguild-acp-main_module&amp;mode=logs') . '">' .
				$this->language->lang('RETURN_LOG') . '</a><br />' . sprintf($this->language->lang('ADMIN_LOG_DELETE_FAIL'), implode(', ', $marked));
			trigger_error($message, E_USER_WARNING);
		}

	}

	/**
	 *
	 * view this log
	 *
	 * @param $log_id int
	 */
	public function viewlog($log_id)
	{
		$viewlog = $this->bbguildlog->get_logentry($log_id);
		$log_type = $viewlog['log_type_clean'];
		$log_actionstr = $this->bbguildlog->getLogMessage($log_type, false);

		// Parse log data and display as key/value rows
		$log_data = $this->bbguildlog->parse_log_action($viewlog['log_action']);
		foreach ($log_data as $key => $value)
		{
			if (is_numeric($key))
			{
				$label = $this->language->lang('LOG_PARAMETER') . ' ' . ($key + 1) . ':';
			}
			else
			{
				$skip = ['header', 'id', 'L_USERCOLOUR', 'L_ADDED_BY', 'L_UPDATED_BY', 'L_USER'];
				if (in_array($key, $skip))
				{
					continue;
				}
				$label = (null !== $this->language->lang($key)) ? $this->language->lang($key) . ':' : $key;
			}
			$this->template->assign_block_vars(
				'logaction_row', array(
					'KEY'   => $label,
					'VALUE' => $value,
				)
			);
		}

		$this->template->assign_vars(
			array(
				'S_LIST'         => false,
				'L_TITLE'        => $this->language->lang('ACP_BBGUILD_LOGS'),
				'L_EXPLAIN'      => $this->language->lang('ACP_BBGUILD_LOGS_EXPLAIN'),
				'LOG_DATE'       => (!empty($viewlog['log_date'])) ? $this->user->format_date($viewlog['log_date']) : '&nbsp;',
				'LOG_USERNAME'   => $viewlog['colouruser'],
				'LOG_IP_ADDRESS' => $viewlog['log_ipaddress'],
				'LOG_SESSION_ID' => $viewlog['log_sid'],
				'LOG_RESULT'     => $viewlog['log_result'],
				'LOG_ACTION'     => $log_actionstr,
			)
		);
	}

	/**
	 * Set u_action
	 *
	 * @param string $u_action Custom form action
	 * @return main_controller
	 */
	public function set_u_action($u_action)
	{
		$this->u_action = $u_action;
		return $this;
	}

}
