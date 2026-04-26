<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Specialization Provider Interface — issue #331
 *
 * Optional companion to game_provider_interface. Plugins that have
 * specs (subclasses) implement this interface in addition to the main
 * game_provider_interface; plugins without specs simply do not. Core
 * detects support via `instanceof` and falls back to no-spec behavior
 * for plugins that don't opt in.
 *
 */

namespace avathar\bbguild\model\games;

interface specialization_provider_interface
{
	/**
	 * Return the spec catalog for this game, keyed by class_id.
	 *
	 * Each value is a list of spec rows with the fields:
	 *   - spec_name    (string, canonical name)
	 *   - role_id      (int, FK into bb_gameroles for this game)
	 *   - spec_icon    (string, icon filename without extension)
	 *   - spec_order   (int, display ordering within the class)
	 *
	 * Example for WoW Mage:
	 *   [62 => [
	 *     ['spec_name' => 'Arcane', 'role_id' => 3, 'spec_icon' => 'mage_arcane', 'spec_order' => 1],
	 *     ['spec_name' => 'Fire',   'role_id' => 3, 'spec_icon' => 'mage_fire',   'spec_order' => 2],
	 *     ['spec_name' => 'Frost',  'role_id' => 3, 'spec_icon' => 'mage_frost',  'spec_order' => 3],
	 *   ]]
	 *
	 * Core's seeding logic walks this map and inserts a row in
	 * bb_specializations for each spec entry. Plugins that already
	 * seeded specs return [] (or are skipped by the seeder).
	 *
	 * @return array<int, list<array{spec_name:string,role_id:int,spec_icon:string,spec_order:int}>>
	 */
	public function get_specializations(): array;

	/**
	 * Display label for the spec concept in this game's UI. Different
	 * games use different terms: WoW says "Specialization", FFXIV says
	 * "Job", GW2 says "Elite Specialization". Default for plain phpBB:
	 * "Specialization".
	 */
	public function get_spec_label(): string;
}
