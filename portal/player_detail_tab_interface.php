<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player Detail Tab Interface
 * Implemented by game plugins to register an additional tab on the
 * player-detail page, alongside the built-in Character tab. Tagged
 * services implementing this interface are collected via
 * player_detail_tab_registry (#375).
 */

namespace avathar\bbguild\portal;

interface player_detail_tab_interface
{
	/**
	 * Lang key for the tab's label in the tab bar.
	 */
	public function get_tab_name(): string;

	/**
	 * URL segment for this tab, e.g. 'talents'. Must be unique across all
	 * registered tabs and must not be empty (empty is reserved for Character).
	 */
	public function get_tab_slug(): string;

	/**
	 * Position in the tab bar, ascending. Character is implicitly 0.
	 */
	public function get_tab_order(): int;

	/**
	 * Whether this tab applies to the given player/game. Lets a WoW-only
	 * tab hide itself for a non-WoW character, for example.
	 */
	public function is_available(int $player_id, string $game_id): bool;

	/**
	 * Template path to render this tab's content (e.g.
	 * '@avathar_bbguildwow/portal/talents_tab.html'), or null if there is
	 * nothing to show for this player. The path MUST use phpBB's Twig
	 * namespace format (`@namespace/...`), not a relative path, so it
	 * resolves correctly via Twig's loader regardless of which template
	 * is including it.
	 */
	public function render(int $player_id): ?string;
}
