<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Character Sync Registry
 * Collects all character sync handlers registered via tagged services.
 * Game plugins register by tagging their service with 'bbguild.character_sync'.
 *
 */

namespace avathar\bbguild\model\games;

/**
 * Class character_sync_registry
 *
 * Central registry that collects all character_sync_interface implementations
 * injected via a phpbb\di\service_collection tagged 'bbguild.character_sync'.
 *
 * @package avathar\bbguild\model\games
 */
class character_sync_registry
{
	/** @var character_sync_interface[] Indexed by game_id */
	private $handlers = [];

	/**
	 * Constructor.
	 *
	 * @param iterable $handlers Tagged character sync handler services
	 */
	public function __construct(iterable $handlers)
	{
		foreach ($handlers as $handler)
		{
			$this->handlers[$handler->get_game_id()] = $handler;
		}
	}

	/**
	 * Get a character sync handler by its game_id.
	 *
	 * @param string $game_id The unique game identifier
	 * @return character_sync_interface|null The handler, or null if not registered
	 */
	public function get(string $game_id): ?character_sync_interface
	{
		return $this->handlers[$game_id] ?? null;
	}

	/**
	 * Check if a character sync handler is registered for a game.
	 *
	 * @param string $game_id The unique game identifier
	 * @return bool
	 */
	public function has(string $game_id): bool
	{
		return isset($this->handlers[$game_id]);
	}

	/**
	 * Get the game_ids of all registered character sync handlers.
	 *
	 * @return string[]
	 */
	public function get_supported_game_ids(): array
	{
		return array_keys($this->handlers);
	}
}
