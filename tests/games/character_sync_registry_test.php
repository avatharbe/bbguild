<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Character sync registry tests — issue #361
 */

namespace avathar\bbguild\tests\games;

use avathar\bbguild\model\games\character_sync_interface;
use avathar\bbguild\model\games\character_sync_registry;
use PHPUnit\Framework\TestCase;

class character_sync_registry_test extends TestCase
{
	private function make_handler(string $game_id): character_sync_interface
	{
		return new class($game_id) implements character_sync_interface {
			private $game_id;

			public function __construct(string $game_id)
			{
				$this->game_id = $game_id;
			}

			public function get_game_id(): string
			{
				return $this->game_id;
			}

			public function sync_character(array $player_row): bool
			{
				return true;
			}
		};
	}

	public function test_get_returns_handler_registered_for_game_id(): void
	{
		$wow = $this->make_handler('wow');
		$registry = new character_sync_registry([$wow]);

		$this->assertSame($wow, $registry->get('wow'));
	}

	public function test_get_returns_null_for_unregistered_game_id(): void
	{
		$registry = new character_sync_registry([$this->make_handler('wow')]);

		$this->assertNull($registry->get('gw2'));
	}

	public function test_has_reflects_registration(): void
	{
		$registry = new character_sync_registry([$this->make_handler('wow')]);

		$this->assertTrue($registry->has('wow'));
		$this->assertFalse($registry->has('gw2'));
	}

	public function test_get_supported_game_ids_returns_all_registered_ids(): void
	{
		$registry = new character_sync_registry([
			$this->make_handler('wow'),
			$this->make_handler('gw2'),
		]);

		$this->assertSame(['wow', 'gw2'], $registry->get_supported_game_ids());
	}

	public function test_empty_registry_reports_no_supported_games(): void
	{
		$registry = new character_sync_registry([]);

		$this->assertSame([], $registry->get_supported_game_ids());
		$this->assertFalse($registry->has('wow'));
	}
}
