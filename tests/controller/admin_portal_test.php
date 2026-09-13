<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Final whole-branch review fix #1(a) — sanitize_slug() must reject any
 * slug that would fail the avathar_bbguild_00 route's `page` requirement
 * ('[^\d/][a-zA-Z0-9_\-]*', i.e. no leading digit). Symfony's URL
 * generator has strictRequirements = true by default, so a tab whose slug
 * starts with a digit made portal_renderer::assign_tab_bar() throw for
 * every tab on the page — a live 500. sanitize_slug() is exercised via
 * reflection since it's a protected pure-logic helper with no
 * dependencies; the constructor is intentionally not invoked.
 */

namespace avathar\bbguild\tests\controller;

use PHPUnit\Framework\TestCase;
use avathar\bbguild\controller\admin_portal;

class admin_portal_test extends TestCase
{
	private function sanitize_slug(string $slug): string
	{
		$reflection = new \ReflectionClass(admin_portal::class);
		$controller = $reflection->newInstanceWithoutConstructor();

		$method = $reflection->getMethod('sanitize_slug');
		$method->setAccessible(true);

		return $method->invoke($controller, $slug);
	}

	public function test_digit_leading_slug_is_rejected()
	{
		$this->assertSame('', $this->sanitize_slug('2026'));
	}

	public function test_digit_leading_alnum_slug_is_rejected()
	{
		$this->assertSame('', $this->sanitize_slug('10man'));
	}

	public function test_letter_leading_slug_is_kept()
	{
		$this->assertSame('raids', $this->sanitize_slug('raids'));
	}

	public function test_letter_leading_slug_with_later_digits_is_kept()
	{
		$this->assertSame('raid-10', $this->sanitize_slug('raid-10'));
	}

	public function test_underscore_leading_slug_is_kept()
	{
		// '_' is not a digit, so it satisfies the route's [^\d/] requirement.
		$this->assertSame('_raids', $this->sanitize_slug('_raids'));
	}

	public function test_disallowed_characters_are_stripped_before_the_leading_digit_check()
	{
		$this->assertSame('raids', $this->sanitize_slug('ra!ids'));
	}

	public function test_slug_reduced_to_all_disallowed_characters_is_rejected()
	{
		$this->assertSame('', $this->sanitize_slug('!!!'));
	}

	public function test_empty_slug_stays_empty()
	{
		$this->assertSame('', $this->sanitize_slug(''));
	}
}
