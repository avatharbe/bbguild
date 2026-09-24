<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Roster portal module.
 * Displays the guild roster with layout switcher (listing / grid),
 * filters, sorting and pagination.
 */

namespace avathar\bbguild\portal\modules;

use avathar\bbguild\model\admin\asset_url_resolver;
use avathar\bbguild\model\admin\constants;
use avathar\bbguild\model\admin\log;
use avathar\bbguild\model\admin\util;
use avathar\bbguild\model\games\game_registry;
use avathar\bbguild\model\player\player;
use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\event\dispatcher_interface;
use phpbb\extension\manager;
use phpbb\pagination;
use phpbb\path_helper;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;

class roster extends module_base
{
	protected int $columns = 4; // center only
	protected string $name = 'BBGUILD_PORTAL_ROSTER';
	protected string $image_src = '';

	protected driver_interface $db;
	protected template $template;
	protected user $user;
	protected config $config;
	protected cache_interface $cache;
	protected manager $ext_manager;
	protected log $bbguild_log;
	protected util $bbguild_util;
	protected pagination $pagination;
	protected request $request;
	protected path_helper $path_helper;
	protected helper $helper;
	protected game_registry $game_registry;
	protected asset_url_resolver $asset_resolver;
	protected dispatcher_interface $dispatcher;
	protected string $players_table;
	protected string $ranks_table;
	protected string $classes_table;
	protected string $races_table;
	protected string $language_table;
	protected string $guild_table;
	protected string $factions_table;
	protected string $games_table;
	protected string $specializations_table = '';

	public function __construct(
		driver_interface $db,
		template $template,
		user $user,
		config $config,
		cache_interface $cache,
		manager $ext_manager,
		log $bbguild_log,
		util $bbguild_util,
		pagination $pagination,
		request $request,
		path_helper $path_helper,
		helper $helper,
		game_registry $game_registry,
		asset_url_resolver $asset_resolver,
		dispatcher_interface $dispatcher,
		string $players_table,
		string $ranks_table,
		string $classes_table,
		string $races_table,
		string $language_table,
		string $guild_table,
		string $factions_table,
		string $games_table,
		string $specializations_table = ''
	)
	{
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->config = $config;
		$this->cache = $cache;
		$this->ext_manager = $ext_manager;
		$this->bbguild_log = $bbguild_log;
		$this->bbguild_util = $bbguild_util;
		$this->pagination = $pagination;
		$this->request = $request;
		$this->path_helper = $path_helper;
		$this->helper = $helper;
		$this->game_registry = $game_registry;
		$this->asset_resolver = $asset_resolver;
		$this->dispatcher = $dispatcher;
		$this->players_table = $players_table;
		$this->ranks_table = $ranks_table;
		$this->classes_table = $classes_table;
		$this->races_table = $races_table;
		$this->language_table = $language_table;
		$this->guild_table = $guild_table;
		$this->factions_table = $factions_table;
		$this->games_table = $games_table;
		$this->specializations_table = $specializations_table;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_template_center(int $module_id)
	{
		$game_id = $this->get_guild_game_id();
		$ext_path_images = $this->get_game_images_path($game_id);

		// Read request values
		$start = $this->request->variable('start', 0);
		$mode = $this->request->variable('rosterlayout', 0);
		$class_filter = $this->request->variable('class_filter', $this->user->lang['ALL']);
		$armor_filter = $this->request->variable('armor_filter', $this->user->lang['ALL']);
		$player_filter = $this->request->variable('player_name', '', true);

		// Determine filter type. Class and armor type are independent filters
		// (armor type is a WoW/trinity-game concept, not something every game
		// plugin has a meaningful notion of) — kept as two separate dropdowns
		// and two separate request params rather than one combined pulldown
		// disambiguated by value, which previously made it possible for a
		// selected class filter to silently do nothing.
		$query_by_armor = false;
		$query_by_class = false;
		$class_id = 0;
		$armor_value = '';
		$armor_types = $this->get_armor_types();
		$class_names = $this->get_class_names($game_id);

		if ($armor_filter != $this->user->lang['ALL'] && array_key_exists($armor_filter, $armor_types))
		{
			$armor_value = $armor_filter;
			$query_by_armor = true;
		}

		if ($class_filter != $this->user->lang['ALL'] && array_key_exists($class_filter, $class_names))
		{
			$query_by_class = true;
			$t = explode('_', $class_filter);
			$class_id = count($t) > 1 ? (int) $t[2] : 0;
		}

		// Build filter + layout dropdowns
		$this->build_class_filter_dropdown($class_filter, $class_names);
		$this->build_armor_filter_dropdown($armor_filter, $armor_types);
		$this->build_layout_dropdown($mode);

		// Get player list
		$players = new player(
			$this->db, $this->config, $this->cache, $this->user,
			$this->ext_manager, $this->bbguild_log, $this->bbguild_util,
			$this->players_table, $this->ranks_table,
			$this->classes_table, $this->races_table,
			$this->language_table, $this->guild_table,
			$this->factions_table, $this->games_table,
			$this->game_registry
		);
		$players->game_id = $game_id;

		$characters = $players->getplayerlist(
			$start, $mode, $query_by_armor, $query_by_class, $armor_value,
			$game_id, $this->guild_id, $class_id, 0, 0, 200, false, $player_filter, 0
		);

		// Route-based pagination URL
		$base_url = $this->helper->route('avathar_bbguild_00', [
			'guild_id' => $this->guild_id,
			'page'     => 'roster',
		]);

		// Specialization lookup (#331). Empty when the game has no specs;
		// the SPEC column / cell is hidden via S_SHOWSPEC.
		$spec_lookup = $this->load_spec_lookup($game_id);
		$show_spec = !empty($spec_lookup);

		if ($mode == 0)
		{
			$this->display_listing($characters, $ext_path_images, $base_url, $start, $spec_lookup, $show_spec);
		}
		else
		{
			$this->display_grid($players, $characters, $ext_path_images, $base_url, $start, $armor_value, $query_by_armor, $class_id, $spec_lookup);
		}

		$this->template->assign_vars([
			'S_PORTAL_HAS_ROSTER'  => count($characters[0]) > 0,
			'S_SHOWACH'            => $this->config['bbguild_show_achiev'],
			'S_SHOWSPEC'           => $show_spec,
			'S_RSTYLE'             => (string) $mode,
			'ROSTER_FOOTCOUNT'     => count($characters[0]),
			'ROSTER_PLAYER_NAME'   => $player_filter,
			'ROSTER_GUILD_ID'      => $this->guild_id,
			'F_ROSTER'             => $base_url,
		]);

		return 'roster_center.html';
	}

	/**
	 * Load all specs for the given game keyed by spec_id.
	 *
	 * Returns [] when no specializations table is wired (older portal
	 * configs) or when the game has no specs seeded — callers treat the
	 * empty array as "feature inert".
	 *
	 * @return array<int, array{name:string,icon:string,class_id:int}>
	 */
	protected function load_spec_lookup(string $game_id): array
	{
		if ($this->specializations_table === '')
		{
			return [];
		}

		$spec = new \avathar\bbguild\model\games\rpg\specialization($this->db, $this->cache, $this->specializations_table, $this->language_table);
		$user_lang = isset($this->user->lang_name) ? (string) $this->user->lang_name : '';

		return $spec->build_lookup($game_id, $user_lang);
	}

	/**
	 * Resolve display-ready spec name + icon URL for a single roster row.
	 *
	 * Falls back to the legacy free-text `player_spec` column when the
	 * structured `player_spec_id` is unset or doesn't match a known spec.
	 *
	 * @return array{name:string,icon:string}
	 */
	protected function resolve_spec(array $char, array $spec_lookup, string $ext_path_images): array
	{
		$spec = \avathar\bbguild\model\games\rpg\specialization::resolve_name_and_icon(
			(int) ($char['player_spec_id'] ?? 0),
			(string) ($char['player_spec'] ?? ''),
			$spec_lookup
		);

		if ($spec['icon'] !== '')
		{
			$spec['icon'] = $ext_path_images . 'spec_icons/' . basename($spec['icon']) . '.png';
		}

		return $spec;
	}

	/**
	 * Fires avathar.bbguild.roster_display for one character row.
	 *
	 * Shared by display_listing() (list layout) and display_grid() (grid
	 * layout) so the event is triggered from exactly one call site — EPV
	 * requires every event name to be documented and fired from a single
	 * place in the codebase.
	 *
	 * @event avathar.bbguild.roster_display
	 * @var int    player_id The character being displayed
	 * @var string game_id   The game the character belongs to
	 * @var int    guild_id  The guild whose roster is rendering
	 * @var array  tpl_ary   The template block-vars array for this row. Writable — add keys to inject a column.
	 * @since 2.1.0
	 */
	private function fire_roster_display_event(array $char, array $tpl_ary): array
	{
		$player_id = (int) $char['player_id'];
		$game_id = (string) $char['game_id'];
		$guild_id = (int) $this->guild_id;
		$vars = ['player_id', 'game_id', 'guild_id', 'tpl_ary'];
		extract($this->dispatcher->trigger_event('avathar.bbguild.roster_display', compact($vars)));

		return $tpl_ary;
	}

	/**
	 * Display the listing (table) view.
	 */
	protected function display_listing(array $characters, string $ext_path_images, string $base_url, int $start, array $spec_lookup = [], bool $show_spec = false): void
	{
		foreach ($characters[0] as $char)
		{
			$spec = $this->resolve_spec($char, $spec_lookup, $ext_path_images);
			$tpl_ary = [
				'PLAYER_ID'   => $char['player_id'],
				'GAME'        => $char['game_id'],
				'COLORCODE'   => $char['colorcode'],
				'CLASS'       => $char['class_name'],
				'NAME'        => $char['player_name'],
				'RACE'        => $char['race_name'],
				'RANK'        => $char['player_rank'],
				'LVL'         => $char['player_level'],
				'ARMORY'      => $char['player_armory_url'],
				'PHPBBUID'    => $char['username'],
				'ACHIEVPTS'   => $char['player_achiev'],
				'CLASS_IMAGE' => $this->resolve_game_image($ext_path_images, $this->character_image_candidates('class_images', (string) $char['class_image'])),
				'RACE_IMAGE'  => $this->resolve_game_image($ext_path_images, $this->character_image_candidates('race_images', (string) $char['race_image'])),
				'SPEC'        => $spec['name'],
				'SPEC_ICON'   => $spec['icon'],
				'U_PLAYER_DETAIL' => $this->helper->route('avathar_bbguild_player', [
					'guild_id'  => $this->guild_id,
					'player_id' => $char['player_id'],
				]),
			];

			$tpl_ary = $this->fire_roster_display_event($char, $tpl_ary);

			$this->template->assign_block_vars('portal_roster_row', $tpl_ary);
		}

		// Pagination
		$player_count = (int) $characters[2];
		$per_page = (int) $this->config['bbguild_user_llimit'];

		if ($per_page > 0)
		{
			$this->pagination->generate_template_pagination(
				$base_url, 'pagination', 'start',
				$player_count, $per_page, $start, true
			);

			$this->template->assign_vars([
				'PAGE_NUMBER' => $this->pagination->on_page($player_count, $per_page, $start),
			]);
		}

		// Sort column URLs
		if (isset($characters[1]))
		{
			$this->template->assign_vars([
				'O_NAME'  => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][0],
				'O_CLASS' => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][2],
				'O_RANK'  => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][3],
				'O_LEVEL' => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][4],
				'O_PHPBB' => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][5],
				'O_ACHI'  => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][6],
			]);
		}

		$this->template->assign_vars([
			'LISTPLAYERS_FOOTCOUNT'   => $this->user->lang['MEMBERS'] . ': ' . count($characters[0]),
			'S_DISPLAY_ROSTERLISTING' => true,
		]);
	}

	/**
	 * Display the grid (grouped by class) view.
	 */
	protected function display_grid(player $players, array $characters, string $ext_path_images, string $base_url, int $start, string $armor_value, bool $query_by_armor, int $class_id = 0, array $spec_lookup = []): void
	{
		$classgroup = $players->get_classes(
			$armor_value, $query_by_armor,
			$class_id, $players->game_id, $this->guild_id, 0, 0, 200
		);

		if (count($classgroup) > 0)
		{
			$classes = [];
			foreach ($classgroup as $row)
			{
				$classes[$row['class_id']]['name']      = $row['class_name'];
				$classes[$row['class_id']]['imagename'] = $row['imagename'];
				$classes[$row['class_id']]['colorcode'] = $row['colorcode'];
			}

			foreach ($classes as $classid => $class)
			{
				$classimgurl = $this->resolve_game_image($ext_path_images, $this->class_image_candidates($class['imagename']));

				$this->template->assign_block_vars('class', [
					'CLASSNAME' => $class['name'],
					'CLASSIMG'  => $classimgurl,
					'COLORCODE' => $class['colorcode'],
				]);

				foreach ($characters[0] as $char)
				{
					if ($char['player_class_id'] == $classid)
					{
						$grid_spec = $this->resolve_spec($char, $spec_lookup, $ext_path_images);
						$tpl_ary = [
							'PLAYER_ID' => $char['player_id'],
							'GAME'      => $char['game_id'],
							'COLORCODE' => $char['colorcode'],
							'CLASS'     => $char['class_name'],
							'NAME'      => $char['player_name'],
							'RACE'      => $char['race_name'],
							'RANK'      => $char['player_rank'],
							'LVL'       => $char['player_level'],
							'ARMORY'    => $char['player_armory_url'],
							'PHPBBUID'  => $char['username'],
							'PORTRAIT'  => $this->resolve_portrait_with_fallback($char, $ext_path_images),
							'SPEC'      => $grid_spec['name'],
							'SPEC_ICON' => $grid_spec['icon'],
							'ACHIEVPTS' => $char['player_achiev'],
							'CLASS_IMAGE' => $this->resolve_game_image($ext_path_images, $this->character_image_candidates('class_images', (string) $char['class_image'])),
							'RACE_IMAGE'  => $this->resolve_game_image($ext_path_images, $this->character_image_candidates('race_images', (string) $char['race_image'])),
							'U_PLAYER_DETAIL' => $this->helper->route('avathar_bbguild_player', [
								'guild_id'  => $this->guild_id,
								'player_id' => $char['player_id'],
							]),
						];

						$tpl_ary = $this->fire_roster_display_event($char, $tpl_ary);

						$this->template->assign_block_vars('class.players_row', $tpl_ary);
					}
				}
			}

			// Sort column URLs for grid
			if (isset($characters[1]))
			{
				$this->template->assign_vars([
					'U_LIST_PLAYERS0' => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][0],
					'U_LIST_PLAYERS1' => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][1],
					'U_LIST_PLAYERS2' => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][2],
					'U_LIST_PLAYERS3' => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][3],
					'U_LIST_PLAYERS4' => $base_url . '?' . constants::URI_ORDER . '=' . $characters[1]['uri'][4],
				]);
			}
		}

		// Pagination
		$player_count = count($characters[0]);
		$per_page = (int) $this->config['bbguild_user_llimit'];

		if ($per_page > 0 && $start > $player_count)
		{
			$start = 0;
		}

		if ($per_page > 0)
		{
			$this->pagination->generate_template_pagination(
				$base_url, 'pagination', 'start',
				$player_count, $per_page, $start, true
			);
		}

		$this->template->assign_vars([
			'LISTPLAYERS_FOOTCOUNT' => $this->user->lang['MEMBERS'] . ': ' . $player_count,
			'S_DISPLAY_ROSTERGRID'  => true,
		]);
	}

	/**
	 * Get the game_id for the current guild.
	 */
	protected function get_guild_game_id(): string
	{
		$sql = 'SELECT game_id FROM ' . $this->guild_table . ' WHERE id = ' . (int) $this->guild_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ? (string) $row['game_id'] : '';
	}

	/**
	 * Resolve portrait URL with race/gender/class fallback.
	 *
	 * If the player has no API portrait, falls back to a static
	 * roster portrait based on gender-race-class (if available).
	 *
	 * @param array  $char           Player row from getplayerlist
	 * @param string $ext_path_images Web path to game images
	 * @return string Portrait URL or empty
	 */
	protected function resolve_portrait_with_fallback(array $char, string $ext_path_images): string
	{
		$player_id = isset($char['player_id']) ? (int) $char['player_id'] : 0;
		$url = $this->asset_resolver->resolve_portrait_url($char['player_portrait_url'] ?? '', $player_id);
		if (!empty($url))
		{
			return $url;
		}

		// Fallback: static roster portrait from race/gender/class
		$gender = isset($char['player_gender_id']) ? (int) $char['player_gender_id'] : 0;
		$race = isset($char['player_race_id']) ? (int) $char['player_race_id'] : 0;
		$class = isset($char['player_class_id']) ? (int) $char['player_class_id'] : 0;

		if ($race > 0 && $class > 0)
		{
			$filename = $gender . '-' . $race . '-' . $class . '.gif';
			$provider = $this->game_registry->get($char['game_id'] ?? '');
			if ($provider !== null)
			{
				$provider_path = $provider->get_images_path();
				$pos = strpos($provider_path, 'ext/');
				if ($pos !== false)
				{
					$rel_path = substr($provider_path, $pos) . 'roster_portraits/' . $char['game_id'] . '/' . $filename;
					// Check file exists relative to phpBB root
					global $phpbb_root_path;
					if (file_exists($phpbb_root_path . $rel_path))
					{
						return $this->path_helper->get_web_root_path() . $rel_path;
					}
				}
			}
		}

		return '';
	}

	/**
	 * Ordered candidate paths for a class's roster image, relative to a
	 * game's images/ directory.
	 *
	 * The grid's preferred asset is the large roster_classes/ artwork, but
	 * the smaller class_images/ detail icon is a better fallback than a
	 * broken image, and a game's <prefix>_unknown icon is better than
	 * nothing at all. Plugins name every asset <game>_<thing>, so the
	 * prefix of the class's own imagename identifies its unknown icon.
	 *
	 * @param string $imagename Class imagename as stored in the DB
	 * @return list<string> Candidate paths, best first; empty if unusable
	 */
	protected function class_image_candidates(string $imagename): array
	{
		// DB-sourced value used in a path: keep the basename only.
		$name = basename(trim($imagename));

		if ($name === '' || $name === '.' || $name === '..')
		{
			return [];
		}

		$candidates = [
			'roster_classes/' . $name . '.png',
			'class_images/' . $name . '.png',
		];

		$pos = strpos($name, '_');

		if ($pos !== false && $pos > 0)
		{
			$prefix = substr($name, 0, $pos);
			$candidates[] = 'roster_classes/' . $prefix . '_unknown.png';
			$candidates[] = 'class_images/' . $prefix . '_unknown.png';
		}

		return $candidates;
	}

	/**
	 * Ordered candidate paths for a character's own class/race image.
	 *
	 * Unlike class_image_candidates() the DB already stores a filename with
	 * its extension here, and there is no second directory to fall back to
	 * — so the only fallback is the game's unknown icon in the same
	 * directory, which still beats a broken image.
	 *
	 * @param string $dir      Image directory, e.g. class_images
	 * @param string $filename Filename as stored in the DB, with extension
	 * @return list<string> Candidate paths, best first; empty if unusable
	 */
	protected function character_image_candidates(string $dir, string $filename): array
	{
		$name = basename(trim($filename));

		if ($name === '' || $name === '.' || $name === '..')
		{
			return [];
		}

		$candidates = [$dir . '/' . $name];

		$pos = strpos($name, '_');

		if ($pos !== false && $pos > 0)
		{
			$candidates[] = $dir . '/' . substr($name, 0, $pos) . '_unknown.png';
		}

		return $candidates;
	}

	/**
	 * First candidate that exists on disk, as a web URL.
	 *
	 * $ext_path_images is a web path, so it is re-anchored at its ext/
	 * segment to test the file on disk — same approach as
	 * resolve_portrait_with_fallback().
	 *
	 * @param string       $ext_path_images Web path to a game's images/
	 * @param list<string> $rel_candidates  Paths relative to that directory
	 * @return string Web URL of the first existing file, or ''
	 */
	protected function resolve_game_image(string $ext_path_images, array $rel_candidates): string
	{
		$pos = strpos($ext_path_images, 'ext/');

		if ($pos === false)
		{
			return '';
		}

		global $phpbb_root_path;
		$fs_base = $phpbb_root_path . substr($ext_path_images, $pos);

		foreach ($rel_candidates as $candidate)
		{
			if (file_exists($fs_base . $candidate))
			{
				return $ext_path_images . $candidate;
			}
		}

		return '';
	}

	protected function get_game_images_path(string $game_id): string
	{
		$web_root = $this->path_helper->get_web_root_path();
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
		return $web_root . 'ext/avathar/bbguild/images/';
	}

	/**
	 * Get armor types from classes table.
	 */
	protected function get_armor_types(): array
	{
		$types = [];
		$sql = 'SELECT class_armor_type FROM ' . $this->classes_table . ' GROUP BY class_armor_type';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$key = strtoupper($row['class_armor_type']);
			if ($key !== '')
			{
				$types[$key] = $this->user->lang[$key] ?? $key;
			}
		}
		$this->db->sql_freeresult($result);
		return $types;
	}

	/**
	 * Get class names for filter dropdown.
	 */
	protected function get_class_names(string $game_id): array
	{
		$names = [];
		$sql_array = [
			'SELECT'   => 'c.game_id, c.class_id, l.name as class_name',
			'FROM'     => [
				$this->classes_table  => 'c',
				$this->language_table => 'l',
				$this->players_table  => 'i',
			],
			'WHERE'    => "c.class_id > 0 AND l.attribute_id = c.class_id AND c.game_id = l.game_id
				AND l.language = '" . $this->db->sql_escape($this->config['bbguild_lang']) . "' AND l.attribute = 'class'
				AND i.player_class_id = c.class_id AND i.game_id = c.game_id
				AND i.game_id = '" . $this->db->sql_escape($game_id) . "'
				AND i.player_guild_id = " . (int) $this->guild_id,
			'GROUP_BY' => 'c.game_id, c.class_id, l.name',
			'ORDER_BY' => 'c.game_id, c.class_id',
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$names[$row['game_id'] . '_class_' . $row['class_id']] = $row['class_name'];
		}
		$this->db->sql_freeresult($result);
		return $names;
	}

	/**
	 * Build layout switcher dropdown template vars.
	 */
	protected function build_layout_dropdown(int $mode): void
	{
		$layouts = [
			0 => $this->user->lang['ARM_STAND'],
			1 => $this->user->lang['ARM_CLASS'],
		];

		foreach ($layouts as $lid => $lname)
		{
			$this->template->assign_block_vars('rosterlayout_row', [
				'VALUE'    => $lid,
				'SELECTED' => ($lid == $mode) ? ' selected="selected"' : '',
				'OPTION'   => $lname,
			]);
		}
	}

	/**
	 * Build the class filter dropdown template vars.
	 */
	protected function build_class_filter_dropdown(string $selected, array $class_names): void
	{
		$values = ['all' => $this->user->lang['ALL']];
		foreach ($class_names as $key => $label)
		{
			$values[$key] = $label;
		}

		foreach ($values as $fid => $fname)
		{
			$this->template->assign_block_vars('roster_class_filter_row', [
				'VALUE'    => $fid,
				'SELECTED' => ($fid == $selected) ? ' selected="selected"' : '',
				'OPTION'   => $fname,
			]);
		}
	}

	/**
	 * Build the armor type filter dropdown template vars.
	 */
	protected function build_armor_filter_dropdown(string $selected, array $armor_types): void
	{
		$values = ['all' => $this->user->lang['ALL']];
		foreach ($armor_types as $key => $label)
		{
			$values[$key] = $label;
		}

		foreach ($values as $fid => $fname)
		{
			$this->template->assign_block_vars('roster_armor_filter_row', [
				'VALUE'    => $fid,
				'SELECTED' => ($fid == $selected) ? ' selected="selected"' : '',
				'OPTION'   => $fname,
			]);
		}
	}
}
