<?php
namespace avathar\bbguild\tests\games;

use avathar\bbguild\model\games\player_detail_tab_registry;
use avathar\bbguild\portal\player_detail_tab_interface;
use PHPUnit\Framework\TestCase;

class fake_tab implements player_detail_tab_interface
{
	public function __construct(
		private string $name,
		private string $slug,
		private int $order,
		private bool $available = true
	) {}

	public function get_tab_name(): string { return $this->name; }
	public function get_tab_slug(): string { return $this->slug; }
	public function get_tab_order(): int { return $this->order; }
	public function is_available(int $player_id, string $game_id): bool { return $this->available; }
	public function render(int $player_id): ?string { return '@ext/tab_' . $this->slug . '.html'; }
}

class player_detail_tab_registry_test extends TestCase
{
	public function test_get_available_tabs_sorts_by_order(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('B', 'b', 2),
			new fake_tab('A', 'a', 1),
		]);

		$tabs = $registry->get_available_tabs(1, 'wow');

		$this->assertCount(2, $tabs);
		$this->assertSame('a', $tabs[0]->get_tab_slug());
		$this->assertSame('b', $tabs[1]->get_tab_slug());
	}

	public function test_get_available_tabs_excludes_unavailable(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('A', 'a', 1, true),
			new fake_tab('B', 'b', 2, false),
		]);

		$tabs = $registry->get_available_tabs(1, 'wow');

		$this->assertCount(1, $tabs);
		$this->assertSame('a', $tabs[0]->get_tab_slug());
	}

	public function test_find_returns_matching_available_tab(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('A', 'a', 1, true),
		]);

		$tab = $registry->find('a', 1, 'wow');

		$this->assertNotNull($tab);
		$this->assertSame('a', $tab->get_tab_slug());
	}

	public function test_find_returns_null_for_unavailable_tab(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('A', 'a', 1, false),
		]);

		$this->assertNull($registry->find('a', 1, 'wow'));
	}

	public function test_find_returns_null_for_unknown_slug(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('A', 'a', 1, true),
		]);

		$this->assertNull($registry->find('nonexistent', 1, 'wow'));
	}
}
