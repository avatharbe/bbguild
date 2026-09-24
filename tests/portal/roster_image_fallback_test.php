<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Roster image fallback tests — roster grid / listing icon resolution
 */

namespace avathar\bbguild\tests\portal;

use avathar\bbguild\portal\modules\roster;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the roster's class-image resolution.
 *
 * The grid used to build roster_classes/<imagename>.png unconditionally,
 * so any game plugin missing that one asset rendered a broken image with
 * no server-side check — the shared root cause behind the icon tickets in
 * bbguildeq/eq2/gw2/lineage2/lotro/swtor. Behaviour matrix:
 *  - roster_classes/ has the file         → use it
 *  - only class_images/ has it            → fall back to the detail icon
 *  - neither, but <prefix>_unknown exists → fall back to the game's unknown
 *  - nothing resolves                     → '' (template omits the <img>)
 *
 * Resolution is checked against core's own images/{roster_classes,
 * class_images}/custom_*.png, which happen to cover every branch:
 * custom_warrior is in both dirs, custom_unknown only in class_images/.
 * No game plugin is involved, so this holds in core's CI where none are
 * installed.
 */
class roster_image_fallback_test extends TestCase
{
	private roster $roster;

	/** Web path in the shape get_game_images_path() returns. */
	private const IMAGES = './ext/avathar/bbguild/images/';

	protected function setUp(): void
	{
		parent::setUp();
		// Bypass the wide constructor; image resolution touches no
		// dependency beyond $phpbb_root_path.
		$this->roster = (new \ReflectionClass(roster::class))->newInstanceWithoutConstructor();

		// tests/portal → tests → bbguild → avathar → ext → board root
		global $phpbb_root_path;
		$phpbb_root_path = dirname(__DIR__, 5) . '/';
	}

	private function invoke(string $method, ...$args)
	{
		$m = (new \ReflectionClass(roster::class))->getMethod($method);
		$m->setAccessible(true);
		return $m->invoke($this->roster, ...$args);
	}

	private function resolve(string $imagename): string
	{
		return $this->invoke('resolve_game_image', self::IMAGES, $this->invoke('class_image_candidates', $imagename));
	}

	public function test_candidates_prefer_roster_art_then_detail_icon_then_unknown(): void
	{
		$this->assertSame(
			[
				'roster_classes/gw2_thief.png',
				'class_images/gw2_thief.png',
				'roster_classes/gw2_unknown.png',
				'class_images/gw2_unknown.png',
			],
			$this->invoke('class_image_candidates', 'gw2_thief')
		);
	}

	public function test_candidates_for_an_unprefixed_imagename_skip_the_unknown_pair(): void
	{
		$this->assertSame(
			[
				'roster_classes/warrior.png',
				'class_images/warrior.png',
			],
			$this->invoke('class_image_candidates', 'warrior')
		);
	}

	public function test_candidates_strip_path_traversal_from_imagename(): void
	{
		$this->assertSame(
			[
				'roster_classes/passwd.png',
				'class_images/passwd.png',
			],
			$this->invoke('class_image_candidates', '../../../etc/passwd')
		);
	}

	public function test_candidates_are_empty_for_an_empty_imagename(): void
	{
		$this->assertSame([], $this->invoke('class_image_candidates', ''));
	}

	public function test_uses_roster_art_when_present(): void
	{
		$this->assertSame(self::IMAGES . 'roster_classes/custom_warrior.png', $this->resolve('custom_warrior'));
	}

	public function test_falls_back_to_class_images_when_roster_art_is_missing(): void
	{
		$this->assertSame(self::IMAGES . 'class_images/custom_unknown.png', $this->resolve('custom_unknown'));
	}

	public function test_falls_back_to_the_games_unknown_icon_when_the_class_has_no_art(): void
	{
		$this->assertSame(self::IMAGES . 'class_images/custom_unknown.png', $this->resolve('custom_nosuchclass'));
	}

	public function test_is_empty_when_not_even_an_unknown_icon_exists(): void
	{
		$this->assertSame('', $this->resolve('zzz_nosuchclass'));
	}

	public function test_character_candidates_fall_back_to_the_unknown_icon_in_the_same_dir(): void
	{
		$this->assertSame(
			[
				'race_images/ffxiv_viera_female.png',
				'race_images/ffxiv_unknown.png',
			],
			$this->invoke('character_image_candidates', 'race_images', 'ffxiv_viera_female.png')
		);
	}

	public function test_character_candidates_for_an_unprefixed_file_skip_the_unknown(): void
	{
		$this->assertSame(
			['class_images/warrior.png'],
			$this->invoke('character_image_candidates', 'class_images', 'warrior.png')
		);
	}

	public function test_character_candidates_are_empty_for_an_empty_filename(): void
	{
		$this->assertSame([], $this->invoke('character_image_candidates', 'class_images', ''));
	}

	public function test_character_image_falls_back_to_the_unknown_icon_on_disk(): void
	{
		$this->assertSame(
			self::IMAGES . 'class_images/custom_unknown.png',
			$this->invoke('resolve_game_image', self::IMAGES, $this->invoke('character_image_candidates', 'class_images', 'custom_nosuchclass.png'))
		);
	}

	public function test_is_empty_for_a_path_without_an_ext_segment(): void
	{
		$this->assertSame('', $this->invoke('resolve_game_image', '/not/an/extension/path/', ['roster_classes/custom_warrior.png']));
	}
}
