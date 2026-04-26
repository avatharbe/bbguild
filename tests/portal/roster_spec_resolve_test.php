<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Roster spec resolution tests — issue #331 Phase 3b
 */

namespace avathar\bbguild\tests\portal;

use avathar\bbguild\portal\modules\roster;
use PHPUnit\Framework\TestCase;

/**
 * Exercises resolve_spec() — the function that maps a roster row to
 * its display-ready spec name + icon URL. Behaviour matrix:
 *  - structured spec_id present + in lookup  → use lookup name + icon
 *  - structured spec_id present + missing    → fall back to legacy text
 *  - structured spec_id zero/unset           → fall back to legacy text
 *  - legacy text also empty                  → empty pair
 *  - lookup row has empty icon               → name returned, icon ''
 */
class roster_spec_resolve_test extends TestCase
{
	private roster $roster;

	protected function setUp(): void
	{
		parent::setUp();
		// Bypass the wide constructor; resolve_spec doesn't touch any
		// dependency, so an unconstructed roster is enough.
		$this->roster = (new \ReflectionClass(roster::class))->newInstanceWithoutConstructor();
	}

	private function call(array $char, array $lookup, string $images = '/images/'): array
	{
		$method = (new \ReflectionClass(roster::class))->getMethod('resolve_spec');
		$method->setAccessible(true);
		return $method->invoke($this->roster, $char, $lookup, $images);
	}

	public function test_returns_lookup_name_and_icon_when_spec_id_resolves(): void
	{
		$result = $this->call(
			['player_spec_id' => 7, 'player_spec' => 'legacy text'],
			[7 => ['name' => 'Frost', 'icon' => 'mage_frost', 'class_id' => 8]]
		);
		$this->assertSame('Frost', $result['name']);
		$this->assertSame('/images/spec_icons/mage_frost.png', $result['icon']);
	}

	public function test_falls_back_to_legacy_text_when_spec_id_unknown(): void
	{
		$result = $this->call(
			['player_spec_id' => 999, 'player_spec' => 'Frost (legacy)'],
			[7 => ['name' => 'Frost', 'icon' => 'mage_frost', 'class_id' => 8]]
		);
		$this->assertSame('Frost (legacy)', $result['name']);
		$this->assertSame('', $result['icon']);
	}

	public function test_falls_back_to_legacy_text_when_spec_id_zero(): void
	{
		$result = $this->call(
			['player_spec_id' => 0, 'player_spec' => 'Frost (legacy)'],
			[7 => ['name' => 'Frost', 'icon' => 'mage_frost', 'class_id' => 8]]
		);
		$this->assertSame('Frost (legacy)', $result['name']);
		$this->assertSame('', $result['icon']);
	}

	public function test_returns_empty_when_no_spec_id_and_no_legacy_text(): void
	{
		$result = $this->call([], []);
		$this->assertSame('', $result['name']);
		$this->assertSame('', $result['icon']);
	}

	public function test_lookup_row_with_empty_icon_yields_empty_icon_url(): void
	{
		$result = $this->call(
			['player_spec_id' => 5],
			[5 => ['name' => 'Custom', 'icon' => '', 'class_id' => 1]]
		);
		$this->assertSame('Custom', $result['name']);
		$this->assertSame('', $result['icon']);
	}
}
