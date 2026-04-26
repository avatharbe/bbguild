<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Centralizes URL resolution for assets stored in phpBB's files/ directory
 * (emblems, portraits). Generates controller route URLs when the serving
 * extension (bbguildwow) is installed, falls back gracefully when it's not.
 */

namespace avathar\bbguild\model\admin;

use phpbb\controller\helper;
use phpbb\extension\manager;
use phpbb\path_helper;

class asset_url_resolver
{
	/** @var helper */
	protected $helper;

	/** @var path_helper */
	protected $path_helper;

	/** @var manager */
	protected $ext_manager;

	/** @var string */
	protected $ext_path_images;

	public function __construct(
		helper $helper,
		path_helper $path_helper,
		manager $ext_manager
	)
	{
		$this->helper = $helper;
		$this->path_helper = $path_helper;
		$this->ext_manager = $ext_manager;

		$this->ext_path_images = $path_helper->get_web_root_path() . 'ext/avathar/bbguild/images/';
	}

	/**
	 * Resolve an emblem path to a web URL.
	 *
	 * @param string $emblempath Stored emblem path (from DB)
	 * @param int    $guild_id   Guild ID (for route generation)
	 * @return string Web-accessible URL, or empty string
	 */
	public function resolve_emblem_url(string $emblempath, int $guild_id): string
	{
		if (empty($emblempath))
		{
			return '';
		}

		// New format: files stored in files/bbguildwow/emblems/ — serve via controller
		if (strpos($emblempath, 'bbguildwow/emblems/') !== false)
		{
			try
			{
				return $this->helper->route('avathar_bbguildwow_emblem', array('guild_id' => $guild_id));
			}
			catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $e)
			{
				return '';
			}
		}

		// Legacy format: filename in ext/avathar/bbguild/images/guildemblem/
		return $this->ext_path_images . 'guildemblem/' . basename($emblempath);
	}

	/**
	 * Resolve a portrait URL for template use.
	 *
	 * @param string $url       Stored portrait URL/path (from DB)
	 * @param int    $player_id Player ID (for route generation)
	 * @return string Web-accessible URL, or empty string
	 */
	public function resolve_portrait_url(string $url, int $player_id): string
	{
		if (empty($url) || $url === 'N/A')
		{
			return '';
		}

		// External URL — pass through
		if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0)
		{
			return $url;
		}

		// Local file in files/bbguildwow/ — serve via controller
		if (strpos($url, 'bbguildwow/') !== false)
		{
			try
			{
				return $this->helper->route('avathar_bbguildwow_portrait', array('player_id' => $player_id));
			}
			catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $e)
			{
				return '';
			}
		}

		// Legacy or other game path — use direct web path
		return $this->path_helper->get_web_root_path() . $url;
	}
}
