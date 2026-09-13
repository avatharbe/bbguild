<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player Detail Tab Registry
 * Collects all player_detail_tab_interface implementations registered via
 * tagged services. Game plugins register by tagging their service with
 * 'bbguild.player_detail_tab' (#375).
 */

namespace avathar\bbguild\model\games;

use avathar\bbguild\portal\player_detail_tab_interface;

class player_detail_tab_registry
{
	/** @var player_detail_tab_interface[] */
	private $tabs;

	/**
	 * @param iterable $tabs Tagged player_detail_tab_interface services
	 */
	public function __construct(iterable $tabs)
	{
		$this->tabs = is_array($tabs) ? $tabs : iterator_to_array($tabs);
	}

	/**
	 * All tabs available for this player/game, sorted by tab_order.
	 *
	 * @return player_detail_tab_interface[]
	 */
	public function get_available_tabs(int $player_id, string $game_id): array
	{
		$available = array_values(array_filter(
			$this->tabs,
			fn (player_detail_tab_interface $tab) => $tab->is_available($player_id, $game_id)
		));

		usort($available, fn ($a, $b) => $a->get_tab_order() <=> $b->get_tab_order());

		return $available;
	}

	/**
	 * Find a specific available tab by slug.
	 */
	public function find(string $slug, int $player_id, string $game_id): ?player_detail_tab_interface
	{
		foreach ($this->get_available_tabs($player_id, $game_id) as $tab)
		{
			if ($tab->get_tab_slug() === $slug)
			{
				return $tab;
			}
		}

		return null;
	}
}
