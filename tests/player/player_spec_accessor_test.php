<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player model spec accessor tests — issue #331 Phase 3a
 */

namespace avathar\bbguild\tests\player;

use avathar\bbguild\model\player\player;
use PHPUnit\Framework\TestCase;

class player_spec_accessor_test extends TestCase
{
	private function make_player(): player
	{
		// Bypass the player constructor (which boots a game lookup against a
		// real-ish DB) since we only need to exercise the spec accessor pair.
		$reflection = new \ReflectionClass(player::class);
		return $reflection->newInstanceWithoutConstructor();
	}

	public function test_default_player_spec_id_is_zero(): void
	{
		$p = $this->make_player();
		$this->assertSame(0, $p->getPlayerSpecId());
	}

	public function test_setter_persists_int_value(): void
	{
		$p = $this->make_player();
		$p->setPlayerSpecId(42);
		$this->assertSame(42, $p->getPlayerSpecId());
	}

	public function test_setter_coerces_string_to_int(): void
	{
		$p = $this->make_player();
		$p->setPlayerSpecId('17');
		$this->assertSame(17, $p->getPlayerSpecId());
	}

	public function test_setter_coerces_zero_string_to_zero_int(): void
	{
		$p = $this->make_player();
		$p->setPlayerSpecId(99);
		$p->setPlayerSpecId('0');
		$this->assertSame(0, $p->getPlayerSpecId());
	}
}
