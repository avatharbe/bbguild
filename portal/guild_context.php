<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Guild context — resolves guild from request, loads guild data,
 * assigns guild header template variables.
 */

namespace avathar\bbguild\portal;

use avathar\bbguild\model\admin\asset_url_resolver;
use avathar\bbguild\model\admin\constants;
use avathar\bbguild\model\admin\log;
use avathar\bbguild\model\player\guilds;
use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\extension\manager;
use phpbb\path_helper;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;

class guild_context
{
	/** @var driver_interface */
	protected $db;

	/** @var user */
	protected $user;

	/** @var config */
	protected $config;

	/** @var template */
	protected $template;

	/** @var request */
	protected $request;

	/** @var cache_interface */
	protected $cache;

	/** @var log */
	protected $log;

	/** @var path_helper */
	protected $path_helper;

	/** @var manager */
	protected $ext_manager;

	/** @var asset_url_resolver */
	protected $asset_resolver;

	/** @var string */
	protected $players_table;

	/** @var string */
	protected $ranks_table;

	/** @var string */
	protected $classes_table;

	/** @var string */
	protected $races_table;

	/** @var string */
	protected $language_table;

	/** @var string */
	protected $guild_table;

	/** @var string */
	protected $factions_table;

	/** @var int */
	public $guild_id = 0;

	/** @var guilds */
	public $guild;

	/** @var string */
	protected $ext_path;

	/** @var string */
	protected $ext_path_images;

	public function __construct(
		driver_interface $db,
		user $user,
		config $config,
		template $template,
		request $request,
		cache_interface $cache,
		log $log,
		path_helper $path_helper,
		manager $ext_manager,
		asset_url_resolver $asset_resolver,
		string $players_table,
		string $ranks_table,
		string $classes_table,
		string $races_table,
		string $language_table,
		string $guild_table,
		string $factions_table
	)
	{
		$this->db = $db;
		$this->user = $user;
		$this->config = $config;
		$this->template = $template;
		$this->request = $request;
		$this->cache = $cache;
		$this->log = $log;
		$this->path_helper = $path_helper;
		$this->ext_manager = $ext_manager;
		$this->asset_resolver = $asset_resolver;
		$this->players_table = $players_table;
		$this->ranks_table = $ranks_table;
		$this->classes_table = $classes_table;
		$this->races_table = $races_table;
		$this->language_table = $language_table;
		$this->guild_table = $guild_table;
		$this->factions_table = $factions_table;

		$this->ext_path = $ext_manager->get_extension_path('avathar/bbguild', true);
		$this->ext_path_images = $path_helper->get_web_root_path() . 'ext/avathar/bbguild/images/';
	}

	/**
	 * Initialize the guild context for a given guild_id.
	 * Resolves the guild from request, loads data, assigns template vars.
	 *
	 * @param int $guild_id Route parameter
	 */
	public function init(int $guild_id): void
	{
		$this->guild_id = $guild_id;
		$this->resolve_guild();
		$this->build_template_vars();
	}

	/**
	 * Resolve the guild from request or default.
	 */
	protected function resolve_guild(): void
	{
		$this->guild_id = $this->request->variable(
			constants::URI_GUILD,
			$this->request->variable('hidden_guild_id', $this->guild_id)
		);

		$this->guild = new guilds(
			$this->db,
			$this->user,
			$this->config,
			$this->cache,
			$this->log,
			$this->players_table,
			$this->ranks_table,
			$this->classes_table,
			$this->races_table,
			$this->language_table,
			$this->guild_table,
			$this->factions_table,
			$this->guild_id
		);

		$guildlist = $this->guild->guildlist(1);

		if (count($guildlist) > 0)
		{
			foreach ($guildlist as $g)
			{
				if ($this->guild_id == 0)
				{
					if ($g['guilddefault'] == 1)
					{
						$this->guild_id = $g['id'];
					}
					else if ($g['playercount'] > 1)
					{
						$this->guild_id = $g['id'];
					}
					if ($this->guild_id == 0 && $g['id'] > 0)
					{
						$this->guild_id = $g['id'];
					}
				}

				if ($g['id'] > 0)
				{
					$this->template->assign_block_vars('guild_row', [
						'VALUE'    => $g['id'],
						'SELECTED' => ($g['id'] == $this->guild_id) ? ' selected="selected"' : '',
						'OPTION'   => $g['name'],
					]);
				}
			}
		}
		else
		{
			trigger_error('ERROR_NOGUILD', E_USER_WARNING);
		}

		$this->guild->setGuildid($this->guild_id);
		$this->guild->get_guild();
	}

	/**
	 * Assign template variables for guild header.
	 */
	protected function build_template_vars(): void
	{
		$this->template->assign_vars([
			'FACTION'         => $this->guild->getFaction(),
			'FACTION_NAME'    => $this->guild->getFactionname(),
			'FACTION_CSS'     => preg_replace('/[^a-z0-9]/', '', strtolower((string) $this->guild->getFactionname())) ?: 'other',
			'GAME_ID'         => $this->guild->getGameId(),
			'GAME_EDITION'    => $this->guild->getGameEdition(),
			'GUILD_ID'        => $this->guild_id,
			'GUILD_NAME'      => $this->guild->getName(),
			'REALM'           => $this->guild->getRealm(),
			'REGION'          => $this->guild->getRegion(),
			'PLAYERCOUNT'     => $this->guild->getPlayercount(),
			'ARMORY_URL'      => '',
			'MIN_ARMORYLEVEL' => $this->guild->getMinArmory(),
			'SHOW_ROSTER'     => $this->guild->getShowroster(),
			'EMBLEM'          => $this->asset_resolver->resolve_emblem_url((string) $this->guild->getEmblempath(), $this->guild_id),
			'EMBLEMFILE'      => basename((string) $this->guild->getEmblempath()),
			'S_EMBLEM_EXISTS' => $this->emblem_exists((string) $this->guild->getEmblempath()),
			'ARMORY'          => '',
			'ACHIEV'          => '',
		]);
	}

	/**
	 * Check if an emblem file exists on disk.
	 *
	 * @param string $emblempath Stored emblem path
	 * @return bool
	 */
	private function emblem_exists(string $emblempath): bool
	{
		if (empty($emblempath))
		{
			return false;
		}

		// New format: relative path — resolve from phpBB root
		if (strpos($emblempath, 'bbguildwow/emblems/') !== false)
		{
			global $phpbb_root_path;
			return file_exists($phpbb_root_path . $emblempath);
		}

		// Legacy format
		return file_exists($this->ext_path . 'images/guildemblem/' . basename($emblempath));
	}
}
