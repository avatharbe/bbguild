<?php
/**
 * bbGuild Extension — squashed migration for 2.0.0-b3
 *
 * Combines all schema, data-seeding, config, permissions, and module
 * registration from the former basics/, v200b2/, and v200b3/ migrations.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v200b3;

class release_2_0_0_b3 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'bb_guild');
	}

	/* ------------------------------------------------------------------ */
	/*  SCHEMA — 15 tables                                                 */
	/* ------------------------------------------------------------------ */

	public function update_schema()
	{
		return [
			'add_tables' => [
				/* 1 - news */
				$this->table_prefix . 'bb_news' => [
					'COLUMNS' => [
						'news_id'           => ['UINT', null, 'auto_increment'],
						'guild_id'          => ['UINT', 0],
						'news_headline'     => ['VCHAR_UNI', ''],
						'news_message'      => ['TEXT_UNI', ''],
						'news_date'         => ['TIMESTAMP', 0],
						'user_id'           => ['UINT', 0],
						'bbcode_bitfield'   => ['VCHAR:255', ''],
						'bbcode_uid'        => ['VCHAR:8', ''],
						'bbcode_options'    => ['UINT', 7],
					],
					'PRIMARY_KEY' => 'news_id',
					'KEYS' => [
						'guild_id' => ['INDEX', ['guild_id']],
					],
				],
				/* 2 - language */
				$this->table_prefix . 'bb_language' => [
					'COLUMNS' => [
						'id'                => ['UINT', null, 'auto_increment'],
						'game_id'           => ['VCHAR:10', ''],
						'attribute_id'      => ['UINT', 0],
						'language'          => ['CHAR:2', ''],
						'attribute'         => ['VCHAR:30', ''],
						'name'              => ['VCHAR_UNI:255', ''],
						'name_short'        => ['VCHAR_UNI:255', ''],
					],
					'PRIMARY_KEY' => ['id'],
					'KEYS'        => ['UQ01' => ['UNIQUE', ['game_id', 'attribute_id', 'language', 'attribute']]],
				],
				/* 3 - factions */
				$this->table_prefix . 'bb_factions' => [
					'COLUMNS' => [
						'game_id'           => ['VCHAR:10', ''],
						'f_index'           => ['USINT', null, 'auto_increment'],
						'faction_id'        => ['USINT', 0],
						'faction_name'      => ['VCHAR_UNI', ''],
						'faction_hide'      => ['BOOL', 0],
					],
					'PRIMARY_KEY' => 'f_index',
					'KEYS'        => ['UQ02' => ['UNIQUE', ['game_id', 'faction_id']]],
				],
				/* 4 - classes */
				$this->table_prefix . 'bb_classes' => [
					'COLUMNS' => [
						'c_index'           => ['USINT', null, 'auto_increment'],
						'game_id'           => ['VCHAR:10', ''],
						'class_id'          => ['USINT', 0],
						'class_faction_id'  => ['USINT', 0],
						'class_min_level'   => ['USINT', 0],
						'class_max_level'   => ['USINT', 0],
						'class_armor_type'  => ['VCHAR_UNI', ''],
						'class_hide'        => ['BOOL', 0],
						'imagename'         => ['VCHAR:255', ''],
						'colorcode'         => ['VCHAR:10', ''],
					],
					'PRIMARY_KEY' => 'c_index',
					'KEYS'        => ['UQ01' => ['UNIQUE', ['game_id', 'class_id']]],
				],
				/* 5 - races */
				$this->table_prefix . 'bb_races' => [
					'COLUMNS' => [
						'game_id'           => ['VCHAR:10', ''],
						'race_id'           => ['USINT', 0],
						'race_faction_id'   => ['USINT', 0],
						'race_hide'         => ['BOOL', 0],
						'image_female'      => ['VCHAR:255', ''],
						'image_male'        => ['VCHAR:255', ''],
					],
					'PRIMARY_KEY' => ['game_id', 'race_id'],
				],
				/* 6 - guilds */
				$this->table_prefix . 'bb_guild' => [
					'COLUMNS' => [
						'id'                => ['USINT', 0],
						'name'              => ['VCHAR_UNI:255', ''],
						'realm'             => ['VCHAR_UNI:255', ''],
						'region'            => ['VCHAR:3', ''],
						'roster'            => ['BOOL', 0],
						'players'           => ['UINT', 0],
						'emblemurl'         => ['VCHAR:255', ''],
						'game_id'           => ['VCHAR:10', ''],
						'game_edition'      => ['VCHAR:20', 'retail'],
						'min_armory'        => ['UINT', 90],
						'rec_status'        => ['BOOL', 0],
						'guilddefault'      => ['BOOL', 0],
						'armory_enabled'    => ['BOOL', 0],
						'armoryresult'      => ['VCHAR_UNI:255', ''],
						'recruitforum'      => ['UINT', 0],
						'faction'           => ['UINT', 0],
					],
					'PRIMARY_KEY' => ['id'],
				],
				/* 7 - ranks */
				$this->table_prefix . 'bb_ranks' => [
					'COLUMNS' => [
						'guild_id'          => ['USINT', 0],
						'rank_id'           => ['USINT', 0],
						'rank_name'         => ['VCHAR_UNI:50', ''],
						'rank_hide'         => ['BOOL', 0],
						'rank_prefix'       => ['VCHAR:75', ''],
						'rank_suffix'       => ['VCHAR:75', ''],
					],
					'PRIMARY_KEY' => ['rank_id', 'guild_id'],
				],
				/* 8 - players */
				$this->table_prefix . 'bb_players' => [
					'COLUMNS' => [
						'player_id'           => ['UINT', null, 'auto_increment'],
						'game_id'             => ['VCHAR:10', ''],
						'player_name'         => ['VCHAR_UNI:100', ''],
						'player_region'       => ['VCHAR', ''],
						'player_realm'        => ['VCHAR:30', ''],
						'player_title'        => ['VCHAR_UNI:100', ''],
						'player_level'        => ['USINT', 0],
						'player_race_id'      => ['USINT', 0],
						'player_class_id'     => ['USINT', 0],
						'player_rank_id'      => ['USINT', 0],
						'player_role'         => ['VCHAR:20', ''],
						'player_comment'      => ['TEXT_UNI', ''],
						'player_joindate'     => ['TIMESTAMP', 0],
						'player_outdate'      => ['TIMESTAMP', 0],
						'player_guild_id'     => ['USINT', 0],
						'player_gender_id'    => ['USINT', 0],
						'player_achiev'       => ['UINT', 0],
						'player_armory_url'   => ['VCHAR:255', ''],
						'player_portrait_url' => ['VCHAR', ''],
						'player_spec'         => ['VCHAR:100', ''],
						'phpbb_user_id'       => ['UINT', 0],
						'player_status'       => ['BOOL', 0],
						'deactivate_reason'   => ['VCHAR_UNI:255', ''],
						'last_update'         => ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY' => 'player_id',
					'KEYS'        => ['UQ01' => ['UNIQUE', ['player_guild_id', 'player_name', 'player_realm']]],
				],
				/* 9 - motd */
				$this->table_prefix . 'bb_motd' => [
					'COLUMNS' => [
						'motd_id'         => ['UINT', null, 'auto_increment'],
						'guild_id'        => ['UINT', 0],
						'motd_title'      => ['VCHAR_UNI', ''],
						'motd_msg'        => ['TEXT_UNI', ''],
						'motd_timestamp'  => ['TIMESTAMP', 0],
						'bbcode_bitfield' => ['VCHAR:255', ''],
						'bbcode_uid'      => ['VCHAR:8', ''],
						'user_id'         => ['UINT', 0],
						'bbcode_options'  => ['UINT', 7],
					],
					'PRIMARY_KEY' => 'motd_id',
					'KEYS' => [
						'guild_id' => ['INDEX', ['guild_id']],
					],
				],
				/* 10 - games */
				$this->table_prefix . 'bb_games' => [
					'COLUMNS' => [
						'id'             => ['UINT', null, 'auto_increment'],
						'game_id'        => ['VCHAR:10', ''],
						'game_name'      => ['VCHAR_UNI:255', ''],
						'region'         => ['VCHAR:3', ''],
						'status'         => ['VCHAR:30', ''],
						'imagename'      => ['VCHAR:20', ''],
						'armory_enabled' => ['UINT', 0],
						'bossbaseurl'    => ['VCHAR:255', ''],
						'zonebaseurl'    => ['VCHAR:255', ''],
						'apikey'         => ['VCHAR:255', ''],
						'apilocale'      => ['VCHAR:5', ''],
						'privkey'        => ['VCHAR:255', ''],
					],
					'PRIMARY_KEY' => ['id'],
					'KEYS'        => ['UQ01' => ['UNIQUE', ['game_id']]],
				],
				/* 11 - game roles */
				$this->table_prefix . 'bb_gameroles' => [
					'COLUMNS' => [
						'role_pkid'     => ['UINT', null, 'auto_increment'],
						'game_id'       => ['VCHAR:10', ''],
						'role_id'       => ['UINT', 0],
						'role_color'    => ['VCHAR', ''],
						'role_icon'     => ['VCHAR', ''],
						'role_cat_icon' => ['VCHAR', ''],
					],
					'PRIMARY_KEY' => 'role_pkid',
					'KEYS'        => ['UQ01' => ['UNIQUE', ['game_id', 'role_id']]],
				],
				/* 12 - recruitment */
				$this->table_prefix . 'bb_recruit' => [
					'COLUMNS' => [
						'id'               => ['UINT', null, 'auto_increment'],
						'guild_id'         => ['USINT', 0],
						'role_id'          => ['UINT', 0],
						'class_id'         => ['UINT', 0],
						'level'            => ['UINT', 0],
						'positions'        => ['USINT', 0],
						'applicants'       => ['USINT', 0],
						'status'           => ['USINT', 0],
						'applytemplate_id' => ['UINT', 0],
						'last_update'      => ['TIMESTAMP', 0],
						'note'             => ['TEXT_UNI', ''],
					],
					'PRIMARY_KEY' => 'id',
				],
				/* 13 - logs */
				$this->table_prefix . 'bb_logs' => [
					'COLUMNS' => [
						'log_id'        => ['UINT', null, 'auto_increment'],
						'log_date'      => ['TIMESTAMP', 0],
						'log_type'      => ['VCHAR_UNI:255', ''],
						'log_action'    => ['TEXT_UNI', ''],
						'log_ipaddress' => ['VCHAR:45', ''],
						'log_sid'       => ['VCHAR:32', ''],
						'log_result'    => ['VCHAR', ''],
						'log_userid'    => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'log_id',
					'KEYS'        => [
						'I01' => ['INDEX', 'log_userid'],
						'I02' => ['INDEX', 'log_type'],
						'I03' => ['INDEX', 'log_ipaddress'],
					],
				],
				/* 14 - portal modules */
				$this->table_prefix . 'bb_portal_modules' => [
					'COLUMNS' => [
						'module_id'           => ['UINT', null, 'auto_increment'],
						'guild_id'            => ['USINT', 0],
						'module_classname'    => ['VCHAR:255', ''],
						'module_column'       => ['TINT:3', 0],
						'module_order'        => ['TINT:3', 0],
						'module_name'         => ['VCHAR:255', ''],
						'module_image_src'    => ['VCHAR:255', ''],
						'module_icon'         => ['VCHAR:100', ''],
						'module_icon_size'    => ['USINT', 16],
						'module_image_width'  => ['USINT', 16],
						'module_image_height' => ['USINT', 16],
						'module_group_ids'    => ['VCHAR:255', ''],
						'module_status'       => ['TINT:1', 1],
					],
					'PRIMARY_KEY' => 'module_id',
					'KEYS' => [
						'guild_col_order' => ['INDEX', ['guild_id', 'module_column', 'module_order']],
					],
				],
				/* 15 - portal config */
				$this->table_prefix . 'bb_portal_config' => [
					'COLUMNS' => [
						'config_name'  => ['VCHAR:255', ''],
						'config_value' => ['MTEXT', ''],
						'guild_id'     => ['USINT', 0],
					],
					'PRIMARY_KEY' => ['config_name', 'guild_id'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'bb_news',
				$this->table_prefix . 'bb_language',
				$this->table_prefix . 'bb_factions',
				$this->table_prefix . 'bb_classes',
				$this->table_prefix . 'bb_races',
				$this->table_prefix . 'bb_guild',
				$this->table_prefix . 'bb_ranks',
				$this->table_prefix . 'bb_players',
				$this->table_prefix . 'bb_motd',
				$this->table_prefix . 'bb_games',
				$this->table_prefix . 'bb_gameroles',
				$this->table_prefix . 'bb_recruit',
				$this->table_prefix . 'bb_logs',
				$this->table_prefix . 'bb_portal_modules',
				$this->table_prefix . 'bb_portal_config',
			],
		];
	}

	/* ------------------------------------------------------------------ */
	/*  DATA — config, seed data, permissions, ACP/UCP modules, version    */
	/* ------------------------------------------------------------------ */

	public function update_data()
	{
		$data = [
			// Config
			['config.add', ['bbguild_default_game', '']],
			['config.add', ['bbguild_eqdkp_start', time()]],
			['config.add', ['bbguild_guild_faction', 1]],
			['config.add', ['bbguild_hide_inactive', 1]],
			['config.add', ['bbguild_lang', 'en']],
			['config.add', ['bbguild_maxchars', 5]],
			['config.add', ['bbguild_minrosterlvl', 50]],
			['config.add', ['bbguild_n_news', 5]],
			['config.add', ['bbguild_news_forumid', 2]],
			['config.add', ['bbguild_regid', 0]],
			['config.add', ['bbguild_roster_layout', 0]],
			['config.add', ['bbguild_user_llimit', 15]],
			['config.add', ['bbguild_user_nlimit', 15]],
			['config.add', ['bbguild_portal_links', 1]],
			['config.add', ['bbguild_portal_loot', 1]],
			['config.add', ['bbguild_portal_maxnewplayers', 5]],
			['config.add', ['bbguild_portal_menu', 1]],
			['config.add', ['bbguild_portal_newplayers', 1]],
			['config.add', ['bbguild_portal_onlineblockposition', 1]],
			['config.add', ['bbguild_portal_recent', 1]],
			['config.add', ['bbguild_portal_recruitments', 1]],
			['config.add', ['bbguild_portal_rtlen', 15]],
			['config.add', ['bbguild_portal_rtno', 5]],
			['config.add', ['bbguild_portal_showedits', 1]],
			['config.add', ['bbguild_motd', 1]],
			['config.add', ['bbguild_portal_whoisonline', 1]],
			['config.add', ['bbguild_portal_right_width', 200]],

			// Seed data
			['custom', [[$this, 'insert_sample_data']]],
			['custom', [[$this, 'seed_portal_layout']]],

			// Permissions
			['permission.add', ['a_bbguild', true]],
			['permission.add', ['u_bbguild', true]],
			['permission.add', ['u_charclaim', true]],
			['permission.add', ['u_charadd', true]],
			['permission.add', ['u_chardelete', true]],
			['permission.add', ['u_charupdate', true]],
		];

		// Admin roles
		if ($this->role_exists('ROLE_ADMIN_FULL'))
		{
			$data[] = ['permission.permission_set', ['ROLE_ADMIN_FULL', 'a_bbguild']];
		}
		if ($this->role_exists('ROLE_ADMIN_STANDARD'))
		{
			$data[] = ['permission.permission_set', ['ROLE_ADMIN_STANDARD', 'a_bbguild']];
		}
		if ($this->role_exists('ROLE_USER_STANDARD'))
		{
			$data[] = ['permission.permission_set', ['ROLE_USER_STANDARD', ['u_bbguild']]];
		}
		if ($this->role_exists('ROLE_USER_FULL'))
		{
			$data[] = ['permission.permission_set', ['ROLE_USER_FULL', ['u_bbguild', 'u_charclaim', 'u_charadd', 'u_chardelete', 'u_charupdate']]];
		}

		// Guest access to guild pages
		$data[] = ['permission.permission_set', ['GUESTS', 'u_bbguild', 'group']];

		// ACP categories
		$data[] = ['module.add', ['acp', 0, 'ACP_CAT_BBGUILD']];
		$data[] = ['module.add', ['acp', 'ACP_CAT_BBGUILD', 'ACP_BBGUILD_MAINPAGE']];
		$data[] = ['module.add', ['acp', 'ACP_CAT_BBGUILD', 'ACP_BBGUILD_PLAYER']];
		$data[] = ['module.add', ['ucp', 0, 'UCP_BBGUILD']];

		// ACP modules — main
		$data[] = ['module.add', ['acp', 'ACP_BBGUILD_MAINPAGE', [
			'module_basename' => '\avathar\bbguild\acp\main_module',
			'modes'           => ['panel', 'config', 'logs'],
		]]];
		$data[] = ['module.add', ['acp', 'ACP_BBGUILD_MAINPAGE', [
			'module_basename' => '\avathar\bbguild\acp\game_module',
			'modes'           => ['listgames', 'editgames', 'addfaction', 'addrace', 'addclass', 'addrole'],
		]]];

		// ACP modules — player
		$data[] = ['module.add', ['acp', 'ACP_BBGUILD_PLAYER', [
			'module_basename' => '\avathar\bbguild\acp\guild_module',
			'modes'           => ['addguild', 'editguild', 'listguilds'],
		]]];
		$data[] = ['module.add', ['acp', 'ACP_BBGUILD_PLAYER', [
			'module_basename' => '\avathar\bbguild\acp\player_module',
			'modes'           => ['addplayer', 'listplayers'],
		]]];

		// UCP module
		$data[] = ['module.add', ['ucp', 'UCP_BBGUILD', [
			'module_basename' => '\avathar\bbguild\ucp\bbguild_module',
			'modes'           => ['char', 'add'],
		]]];

		return $data;
	}

	public function revert_data()
	{
		return [
			// Guest permission
			['permission.permission_unset', ['GUESTS', 'u_bbguild', 'group']],

			// UCP module
			['module.remove', ['ucp', 'UCP_BBGUILD', [
				'module_basename' => '\avathar\bbguild\ucp\bbguild_module',
			]]],

			// ACP modules
			['module.remove', ['acp', 'ACP_BBGUILD_PLAYER', [
				'module_basename' => '\avathar\bbguild\acp\player_module',
			]]],
			['module.remove', ['acp', 'ACP_BBGUILD_PLAYER', [
				'module_basename' => '\avathar\bbguild\acp\guild_module',
			]]],
			['module.remove', ['acp', 'ACP_BBGUILD_MAINPAGE', [
				'module_basename' => '\avathar\bbguild\acp\game_module',
			]]],
			['module.remove', ['acp', 'ACP_BBGUILD_MAINPAGE', [
				'module_basename' => '\avathar\bbguild\acp\main_module',
			]]],

			// ACP categories
			['module.remove', ['ucp', 0, 'UCP_BBGUILD']],
			['module.remove', ['acp', 'ACP_CAT_BBGUILD', 'ACP_BBGUILD_PLAYER']],
			['module.remove', ['acp', 'ACP_CAT_BBGUILD', 'ACP_BBGUILD_MAINPAGE']],
			['module.remove', ['acp', 0, 'ACP_CAT_BBGUILD']],

			// Permissions
			['permission.remove', ['u_charupdate']],
			['permission.remove', ['u_chardelete']],
			['permission.remove', ['u_charadd']],
			['permission.remove', ['u_charclaim']],
			['permission.remove', ['u_bbguild']],
			['permission.remove', ['a_bbguild']],

			// Seed data
			['custom', [[$this, 'remove_sample_data']]],

			// Config
			['config.remove', ['bbguild_portal_right_width']],
			['config.remove', ['bbguild_default_game']],
			['config.remove', ['bbguild_eqdkp_start']],
			['config.remove', ['bbguild_guild_faction']],
			['config.remove', ['bbguild_hide_inactive']],
			['config.remove', ['bbguild_lang']],
			['config.remove', ['bbguild_maxchars']],
			['config.remove', ['bbguild_minrosterlvl']],
			['config.remove', ['bbguild_n_news']],
			['config.remove', ['bbguild_news_forumid']],
			['config.remove', ['bbguild_regid']],
			['config.remove', ['bbguild_roster_layout']],
			['config.remove', ['bbguild_user_llimit']],
			['config.remove', ['bbguild_user_nlimit']],
			['config.remove', ['bbguild_portal_links']],
			['config.remove', ['bbguild_portal_loot']],
			['config.remove', ['bbguild_portal_maxnewplayers']],
			['config.remove', ['bbguild_portal_menu']],
			['config.remove', ['bbguild_portal_newplayers']],
			['config.remove', ['bbguild_portal_onlineblockposition']],
			['config.remove', ['bbguild_portal_recent']],
			['config.remove', ['bbguild_portal_recruitments']],
			['config.remove', ['bbguild_portal_rtlen']],
			['config.remove', ['bbguild_portal_rtno']],
			['config.remove', ['bbguild_portal_showedits']],
			['config.remove', ['bbguild_motd']],
			['config.remove', ['bbguild_portal_whoisonline']],
		];
	}

	/* ------------------------------------------------------------------ */
	/*  Helpers                                                            */
	/* ------------------------------------------------------------------ */

	public function insert_sample_data()
	{
		$user = $this->container->get('user');
		$user->add_lang_ext('avathar/bbguild', 'admin');
		$welcome_message = $this->encode_message($user->lang['MOTD']);

		$games_table    = $this->table_prefix . 'bb_games';
		$faction_table  = $this->table_prefix . 'bb_factions';
		$class_table    = $this->table_prefix . 'bb_classes';
		$race_table     = $this->table_prefix . 'bb_races';
		$role_table     = $this->table_prefix . 'bb_gameroles';
		$lang_table     = $this->table_prefix . 'bb_language';
		$guild_table    = $this->table_prefix . 'bb_guild';
		$rank_table     = $this->table_prefix . 'bb_ranks';
		$player_table   = $this->table_prefix . 'bb_players';
		$motd_table     = $this->table_prefix . 'bb_motd';
		$news_table     = $this->table_prefix . 'bb_news';
		$recruit_table  = $this->table_prefix . 'bb_recruit';
		$logs_table     = $this->table_prefix . 'bb_logs';

		$now = (int) time();

		// Clean up any partial previous run
		$this->remove_sample_data();

		// -- bb_games: Custom game --
		if ($this->db_tools->sql_table_exists($games_table))
		{
			$this->db->sql_multi_insert($games_table, [
				[
					'game_id'        => 'custom',
					'game_name'      => 'Custom Game',
					'region'         => '',
					'status'         => 'active',
					'imagename'      => 'custom',
					'armory_enabled' => 0,
					'bossbaseurl'    => '',
					'zonebaseurl'    => '',
					'apikey'         => '',
					'apilocale'      => '',
					'privkey'        => '',
				],
			]);
		}

		// -- bb_factions: 2 factions --
		if ($this->db_tools->sql_table_exists($faction_table))
		{
			$this->db->sql_multi_insert($faction_table, [
				['game_id' => 'custom', 'faction_id' => 1, 'faction_name' => 'Alliance', 'faction_hide' => 0],
				['game_id' => 'custom', 'faction_id' => 2, 'faction_name' => 'Horde',    'faction_hide' => 0],
			]);
		}

		// -- bb_classes: 4 classes (0=Unknown + 3 real) --
		if ($this->db_tools->sql_table_exists($class_table))
		{
			$this->db->sql_multi_insert($class_table, [
				['game_id' => 'custom', 'class_id' => 0, 'class_faction_id' => 0, 'class_min_level' => 1, 'class_max_level' => 60, 'class_armor_type' => '',        'class_hide' => 1, 'imagename' => '', 'colorcode' => '#999999'],
				['game_id' => 'custom', 'class_id' => 1, 'class_faction_id' => 0, 'class_min_level' => 1, 'class_max_level' => 60, 'class_armor_type' => 'PLATE',   'class_hide' => 0, 'imagename' => 'custom_warrior', 'colorcode' => '#c69b6d'],
				['game_id' => 'custom', 'class_id' => 2, 'class_faction_id' => 0, 'class_min_level' => 1, 'class_max_level' => 60, 'class_armor_type' => 'CLOTH',   'class_hide' => 0, 'imagename' => 'custom_mage',    'colorcode' => '#3fc7eb'],
				['game_id' => 'custom', 'class_id' => 3, 'class_faction_id' => 0, 'class_min_level' => 1, 'class_max_level' => 60, 'class_armor_type' => 'CLOTH',   'class_hide' => 0, 'imagename' => 'custom_priest',  'colorcode' => '#ffffff'],
			]);
		}

		// -- bb_races: 4 races (0=Unknown + 3 real) --
		if ($this->db_tools->sql_table_exists($race_table))
		{
			$this->db->sql_multi_insert($race_table, [
				['game_id' => 'custom', 'race_id' => 0, 'race_faction_id' => 0, 'race_hide' => 1, 'image_female' => '', 'image_male' => ''],
				['game_id' => 'custom', 'race_id' => 1, 'race_faction_id' => 1, 'race_hide' => 0, 'image_female' => 'custom_human_female', 'image_male' => 'custom_human_male'],
				['game_id' => 'custom', 'race_id' => 2, 'race_faction_id' => 1, 'race_hide' => 0, 'image_female' => 'custom_elf_female',   'image_male' => 'custom_elf_male'],
				['game_id' => 'custom', 'race_id' => 3, 'race_faction_id' => 2, 'race_hide' => 0, 'image_female' => 'custom_orc_female',   'image_male' => 'custom_orc_male'],
			]);
		}

		// -- bb_gameroles: 3 default roles --
		if ($this->db_tools->sql_table_exists($role_table))
		{
			$this->db->sql_multi_insert($role_table, [
				['game_id' => 'custom', 'role_id' => 0, 'role_color' => '#FF4455', 'role_icon' => 'dps_icon',    'role_cat_icon' => ''],
				['game_id' => 'custom', 'role_id' => 1, 'role_color' => '#11FF77', 'role_icon' => 'healer_icon', 'role_cat_icon' => ''],
				['game_id' => 'custom', 'role_id' => 2, 'role_color' => '#c3834c', 'role_icon' => 'tank_icon',   'role_cat_icon' => ''],
			]);
		}

		// -- bb_language: English names for classes, races, roles --
		if ($this->db_tools->sql_table_exists($lang_table))
		{
			$this->db->sql_multi_insert($lang_table, [
				// Classes
				['game_id' => 'custom', 'attribute_id' => 0, 'language' => 'en', 'attribute' => 'class', 'name' => 'Unknown', 'name_short' => '??'],
				['game_id' => 'custom', 'attribute_id' => 1, 'language' => 'en', 'attribute' => 'class', 'name' => 'Warrior', 'name_short' => 'War'],
				['game_id' => 'custom', 'attribute_id' => 2, 'language' => 'en', 'attribute' => 'class', 'name' => 'Mage',    'name_short' => 'Mag'],
				['game_id' => 'custom', 'attribute_id' => 3, 'language' => 'en', 'attribute' => 'class', 'name' => 'Priest',  'name_short' => 'Pri'],
				// Races
				['game_id' => 'custom', 'attribute_id' => 0, 'language' => 'en', 'attribute' => 'race', 'name' => 'Unknown', 'name_short' => '??'],
				['game_id' => 'custom', 'attribute_id' => 1, 'language' => 'en', 'attribute' => 'race', 'name' => 'Human',   'name_short' => 'Hum'],
				['game_id' => 'custom', 'attribute_id' => 2, 'language' => 'en', 'attribute' => 'race', 'name' => 'Elf',     'name_short' => 'Elf'],
				['game_id' => 'custom', 'attribute_id' => 3, 'language' => 'en', 'attribute' => 'race', 'name' => 'Orc',     'name_short' => 'Orc'],
				// Roles
				['game_id' => 'custom', 'attribute_id' => 0, 'language' => 'en', 'attribute' => 'role', 'name' => 'Damage',  'name_short' => 'DPS'],
				['game_id' => 'custom', 'attribute_id' => 1, 'language' => 'en', 'attribute' => 'role', 'name' => 'Healer',  'name_short' => 'HPS'],
				['game_id' => 'custom', 'attribute_id' => 2, 'language' => 'en', 'attribute' => 'role', 'name' => 'Defense', 'name_short' => 'DEF'],
			]);
		}

		// -- bb_guild: Guildless placeholder + test guild --
		if ($this->db_tools->sql_table_exists($guild_table))
		{
			$this->db->sql_multi_insert($guild_table, [
				[
					'id'             => 0,
					'name'           => 'Guildless',
					'realm'          => 'default',
					'region'         => '',
					'roster'         => 0,
					'players'        => 0,
					'emblemurl'      => '',
					'game_id'        => '',
					'game_edition'   => 'retail',
					'min_armory'     => 0,
					'rec_status'     => 0,
					'guilddefault'   => 0,
					'armory_enabled' => 0,
					'armoryresult'   => '',
					'recruitforum'   => 0,
					'faction'        => 0,
				],
				[
					'id'             => 1,
					'name'           => 'Test Guild',
					'realm'          => 'Test Realm',
					'region'         => 'us',
					'roster'         => 1,
					'players'        => 3,
					'emblemurl'      => '',
					'game_id'        => 'custom',
					'game_edition'   => 'retail',
					'min_armory'     => 1,
					'rec_status'     => 1,
					'guilddefault'   => 1,
					'armory_enabled' => 0,
					'armoryresult'   => '',
					'recruitforum'   => 0,
					'faction'        => 1,
				],
			]);
		}

		// -- bb_ranks: Out rank + test guild ranks --
		if ($this->db_tools->sql_table_exists($rank_table))
		{
			$this->db->sql_multi_insert($rank_table, [
				['guild_id' => 0, 'rank_id' => 99, 'rank_name' => 'Out',          'rank_hide' => 1, 'rank_prefix' => '', 'rank_suffix' => ''],
				['guild_id' => 1, 'rank_id' => 0,  'rank_name' => 'Guild Master', 'rank_hide' => 0, 'rank_prefix' => '', 'rank_suffix' => ''],
				['guild_id' => 1, 'rank_id' => 1,  'rank_name' => 'Officer',      'rank_hide' => 0, 'rank_prefix' => '', 'rank_suffix' => ''],
				['guild_id' => 1, 'rank_id' => 2,  'rank_name' => 'Member',       'rank_hide' => 0, 'rank_prefix' => '', 'rank_suffix' => ''],
			]);
		}

		// -- bb_players: 3 test players --
		if ($this->db_tools->sql_table_exists($player_table))
		{
			$this->db->sql_multi_insert($player_table, [
				[
					'game_id'             => 'custom',
					'player_name'         => 'Testwarrior',
					'player_region'       => 'us',
					'player_realm'        => 'Test Realm',
					'player_title'        => '',
					'player_level'        => 60,
					'player_race_id'      => 1,
					'player_class_id'     => 1,
					'player_rank_id'      => 0,
					'player_role'         => 'DPS',
					'player_comment'      => '',
					'player_joindate'     => $now,
					'player_outdate'      => 0,
					'player_guild_id'     => 1,
					'player_gender_id'    => 0,
					'player_achiev'       => 0,
					'player_armory_url'   => '',
					'player_portrait_url' => '',
					'player_spec'         => '',
					'phpbb_user_id'       => 0,
					'player_status'       => 1,
					'deactivate_reason'   => '',
					'last_update'         => $now,
				],
				[
					'game_id'             => 'custom',
					'player_name'         => 'Testmage',
					'player_region'       => 'us',
					'player_realm'        => 'Test Realm',
					'player_title'        => '',
					'player_level'        => 55,
					'player_race_id'      => 2,
					'player_class_id'     => 2,
					'player_rank_id'      => 1,
					'player_role'         => 'DPS',
					'player_comment'      => '',
					'player_joindate'     => $now,
					'player_outdate'      => 0,
					'player_guild_id'     => 1,
					'player_gender_id'    => 1,
					'player_achiev'       => 0,
					'player_armory_url'   => '',
					'player_portrait_url' => '',
					'player_spec'         => '',
					'phpbb_user_id'       => 0,
					'player_status'       => 1,
					'deactivate_reason'   => '',
					'last_update'         => $now,
				],
				[
					'game_id'             => 'custom',
					'player_name'         => 'Testhealer',
					'player_region'       => 'us',
					'player_realm'        => 'Test Realm',
					'player_title'        => '',
					'player_level'        => 45,
					'player_race_id'      => 3,
					'player_class_id'     => 3,
					'player_rank_id'      => 2,
					'player_role'         => 'Healer',
					'player_comment'      => '',
					'player_joindate'     => $now,
					'player_outdate'      => 0,
					'player_guild_id'     => 1,
					'player_gender_id'    => 0,
					'player_achiev'       => 0,
					'player_armory_url'   => '',
					'player_portrait_url' => '',
					'player_spec'         => '',
					'phpbb_user_id'       => 0,
					'player_status'       => 1,
					'deactivate_reason'   => '',
					'last_update'         => $now,
				],
			]);
		}

		// -- bb_motd: Welcome message --
		if ($this->db_tools->sql_table_exists($motd_table))
		{
			$this->db->sql_multi_insert($motd_table, [
				[
					'guild_id'        => 1,
					'motd_title'      => $user->lang['MOTDGREETING'],
					'motd_timestamp'  => $now,
					'motd_msg'        => $welcome_message['text'],
					'bbcode_uid'      => $welcome_message['uid'],
					'bbcode_bitfield' => $welcome_message['bitfield'],
					'user_id'         => $user->data['user_id'],
				],
			]);
		}

		// -- bb_news: Sample news entry --
		if ($this->db_tools->sql_table_exists($news_table))
		{
			$news_message = $this->encode_message('Welcome to bbGuild! This is a sample news entry.');
			$this->db->sql_multi_insert($news_table, [
				[
					'guild_id'        => 1,
					'news_headline'   => 'bbGuild Installed',
					'news_message'    => $news_message['text'],
					'news_date'       => $now,
					'user_id'         => $user->data['user_id'],
					'bbcode_bitfield' => $news_message['bitfield'],
					'bbcode_uid'      => $news_message['uid'],
					'bbcode_options'  => 0,
				],
			]);
		}

		// -- bb_recruit: Sample recruitment entry --
		if ($this->db_tools->sql_table_exists($recruit_table))
		{
			$this->db->sql_multi_insert($recruit_table, [
				[
					'guild_id'         => 1,
					'role_id'          => 1,
					'class_id'         => 3,
					'level'            => 40,
					'positions'        => 2,
					'applicants'       => 0,
					'status'           => 1,
					'applytemplate_id' => 0,
					'last_update'      => $now,
					'note'             => 'Looking for healers!',
				],
			]);
		}

		// -- bb_logs: Installation log entry --
		if ($this->db_tools->sql_table_exists($logs_table))
		{
			$this->db->sql_multi_insert($logs_table, [
				[
					'log_date'      => $now,
					'log_type'      => 'install',
					'log_action'    => 'bbGuild installed with sample data',
					'log_ipaddress' => '127.0.0.1',
					'log_sid'       => '',
					'log_result'    => 'success',
					'log_userid'    => $user->data['user_id'],
				],
			]);
		}
	}

	/**
	 * Seed the default portal layout for the test guild.
	 * Column numbers: top=1, center=2, right=3, bottom=4
	 */
	public function seed_portal_layout()
	{
		$portal_table = $this->table_prefix . 'bb_portal_modules';
		if (!$this->db_tools->sql_table_exists($portal_table))
		{
			return;
		}

		// Clean up any partial previous run
		$this->db->sql_query('DELETE FROM ' . $portal_table . ' WHERE guild_id = 1');

		$modules = [
			['module_classname' => '\avathar\bbguild\portal\modules\motd',          'module_column' => 1, 'module_order' => 1, 'module_name' => 'BBGUILD_PORTAL_MOTD'],
			['module_classname' => '\avathar\bbguild\portal\modules\roster',        'module_column' => 2, 'module_order' => 1, 'module_name' => 'BBGUILD_PORTAL_ROSTER'],
			['module_classname' => '\avathar\bbguild\portal\modules\recruitment',   'module_column' => 3, 'module_order' => 1, 'module_name' => 'BBGUILD_PORTAL_RECRUITMENT'],
		];

		foreach ($modules as $module)
		{
			$sql_ary = array_merge($module, [
				'guild_id'            => 1,
				'module_image_src'    => '',
				'module_icon'         => '',
				'module_icon_size'    => 16,
				'module_image_width'  => 16,
				'module_image_height' => 16,
				'module_group_ids'    => '',
				'module_status'       => 1,
			]);

			$sql = 'INSERT INTO ' . $portal_table . ' ' .
				$this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}

	public function remove_sample_data()
	{
		$tables = [
			'bb_portal_modules' => null,
			'bb_portal_config'  => null,
			'bb_logs'      => "log_type = 'install'",
			'bb_recruit'   => 'guild_id = 1',
			'bb_news'      => "news_headline = 'bbGuild Installed'",
			'bb_motd'      => null,
			'bb_players'   => 'player_guild_id IN (0, 1)',
			'bb_ranks'     => 'guild_id IN (0, 1)',
			'bb_guild'     => 'id IN (0, 1)',
			'bb_language'  => "game_id = 'custom'",
			'bb_gameroles' => "game_id = 'custom'",
			'bb_races'     => "game_id = 'custom'",
			'bb_classes'   => "game_id = 'custom'",
			'bb_factions'  => "game_id = 'custom'",
			'bb_games'     => "game_id = 'custom'",
		];

		foreach ($tables as $table => $where)
		{
			$full_table = $this->table_prefix . $table;
			if ($this->db_tools->sql_table_exists($full_table))
			{
				$sql = 'DELETE FROM ' . $full_table;
				if ($where !== null)
				{
					$sql .= ' WHERE ' . $where;
				}
				$this->db->sql_query($sql);
			}
		}
	}

	protected function role_exists($role)
	{
		$sql = 'SELECT COUNT(role_id) AS role_count
			FROM ' . ACL_ROLES_TABLE . "
			WHERE role_name = '" . $this->db->sql_escape($role) . "'";
		$result = $this->db->sql_query_limit($sql, 1);
		$role_count = $this->db->sql_fetchfield('role_count');
		$this->db->sql_freeresult($result);

		return $role_count > 0;
	}

	private function encode_message($text)
	{
		$uid = $bitfield = $options = '';
		$allow_bbcode = $allow_urls = $allow_smilies = true;
		generate_text_for_storage($text, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

		return [
			'text'     => $text,
			'uid'      => $uid,
			'bitfield' => $bitfield,
		];
	}
}
