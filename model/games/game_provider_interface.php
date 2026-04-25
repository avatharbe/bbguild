<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Game Provider Interface
 * Contract that every game plugin must implement to register with bbGuild core.
 *
 */

namespace avathar\bbguild\model\games;

/**
 * Interface game_provider_interface
 *
 * @package avathar\bbguild\model\games
 */
interface game_provider_interface
{
	/**
	 * Get the unique game identifier (e.g. 'wow', 'aion', 'gw2')
	 *
	 * @return string
	 */
	public function get_game_id(): string;

	/**
	 * Get the display name of the game (e.g. 'World of Warcraft')
	 *
	 * @return string
	 */
	public function get_game_name(): string;

	/**
	 * Get the game installer instance
	 *
	 * @return game_install_interface
	 */
	public function get_installer(): game_install_interface;

	/**
	 * Get the base URL for boss database links (sprintf format with %s placeholder)
	 *
	 * @return string
	 */
	public function get_boss_base_url(): string;

	/**
	 * Get the base URL for zone database links (sprintf format with %s placeholder)
	 *
	 * @return string
	 */
	public function get_zone_base_url(): string;

	/**
	 * Get the absolute path to the plugin's images directory
	 *
	 * @return string
	 */
	public function get_images_path(): string;

	/**
	 * Whether this game has an external API integration (e.g. Battle.net)
	 *
	 * @return bool
	 */
	public function has_api(): bool;

	/**
	 * Get the API integration, or null if this game has no API
	 *
	 * @return game_api_interface|null
	 */
	public function get_api(): ?game_api_interface;

	/**
	 * Get the available regions for this game
	 *
	 * @return array associative array of region_code => region_name
	 */
	public function get_regions(): array;

	/**
	 * Get the available API locale strings per region
	 *
	 * @return array associative array of region_code => array of locale strings
	 */
	public function get_api_locales(): array;

	/**
	 * Get the armor types available in this game
	 *
	 * @return array associative array of ARMOR_KEY => display name
	 */
	public function get_armor_types(): array;
}
