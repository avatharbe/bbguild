<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Specialization model unit tests — issue #331
 */

namespace avathar\bbguild\tests\games\rpg;

use avathar\bbguild\model\games\rpg\specialization;
use PHPUnit\Framework\TestCase;

class specialization_test extends TestCase
{
	private const TABLE = 'phpbb_bb_specializations';

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\driver\driver_interface */
	private $db;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\cache\driver\driver_interface */
	private $cache;

	/** @var specialization */
	private $spec;

	protected function setUp(): void
	{
		parent::setUp();
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$this->cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);
		$this->spec = new specialization($this->db, $this->cache, self::TABLE);
	}

	public function test_constructor_initialises_to_zero_state(): void
	{
		$this->assertSame(0, $this->spec->spec_id);
		$this->assertSame('', $this->spec->game_id);
		$this->assertSame(0, $this->spec->class_id);
		$this->assertSame(0, $this->spec->role_id);
		$this->assertSame('', $this->spec->spec_name);
		$this->assertSame('', $this->spec->spec_icon);
		$this->assertSame(0, $this->spec->spec_order);
	}

	public function test_load_returns_false_when_row_missing(): void
	{
		$this->db->method('sql_query')->willReturn(true);
		$this->db->method('sql_fetchrow')->willReturn(false);
		$this->db->expects($this->once())->method('sql_freeresult');

		$this->assertFalse($this->spec->load(99));
		$this->assertSame(0, $this->spec->spec_id);
	}

	public function test_load_populates_fields_from_row(): void
	{
		$this->db->method('sql_query')->willReturn(true);
		$this->db->method('sql_fetchrow')->willReturn([
			'spec_id'    => '7',
			'game_id'    => 'wow',
			'class_id'   => '8',
			'role_id'    => '3',
			'spec_name'  => 'Frost',
			'spec_icon'  => 'mage_frost',
			'spec_order' => '3',
		]);

		$this->assertTrue($this->spec->load(7));
		$this->assertSame(7, $this->spec->spec_id);
		$this->assertSame('wow', $this->spec->game_id);
		$this->assertSame(8, $this->spec->class_id);
		$this->assertSame(3, $this->spec->role_id);
		$this->assertSame('Frost', $this->spec->spec_name);
		$this->assertSame('mage_frost', $this->spec->spec_icon);
		$this->assertSame(3, $this->spec->spec_order);
	}

	public function test_save_inserts_when_spec_id_zero_and_assigns_id(): void
	{
		$this->spec->game_id = 'wow';
		$this->spec->class_id = 8;
		$this->spec->role_id = 3;
		$this->spec->spec_name = 'Frost';

		$this->db->method('sql_build_array')
			->with('INSERT', $this->anything())
			->willReturn(" (game_id, class_id, role_id, spec_name, spec_icon, spec_order) VALUES ('wow', 8, 3, 'Frost', '', 0)");

		$capturedSql = '';
		$this->db->method('sql_query')->willReturnCallback(function ($sql) use (&$capturedSql) {
			$capturedSql = $sql;
			return true;
		});
		$this->db->method('sql_nextid')->willReturn(42);
		$this->cache->expects($this->once())->method('destroy')->with('sql', self::TABLE);

		$this->spec->save();

		$this->assertSame(42, $this->spec->spec_id);
		$this->assertStringStartsWith('INSERT INTO ' . self::TABLE, $capturedSql);
	}

	public function test_save_updates_when_spec_id_nonzero(): void
	{
		$this->spec->spec_id = 5;
		$this->spec->game_id = 'wow';
		$this->spec->spec_name = 'Frost';

		$this->db->method('sql_build_array')
			->with('UPDATE', $this->anything())
			->willReturn("game_id = 'wow', spec_name = 'Frost'");

		$capturedSql = '';
		$this->db->method('sql_query')->willReturnCallback(function ($sql) use (&$capturedSql) {
			$capturedSql = $sql;
			return true;
		});
		$this->db->expects($this->never())->method('sql_nextid');

		$this->spec->save();

		$this->assertSame(5, $this->spec->spec_id);
		$this->assertStringStartsWith('UPDATE ' . self::TABLE, $capturedSql);
		$this->assertStringContainsString('WHERE spec_id = 5', $capturedSql);
	}

	public function test_delete_is_noop_when_spec_id_zero(): void
	{
		$this->db->expects($this->never())->method('sql_query');
		$this->cache->expects($this->never())->method('destroy');

		$this->spec->delete();
	}

	public function test_delete_runs_when_spec_id_nonzero(): void
	{
		$this->spec->spec_id = 5;
		$capturedSql = '';
		$this->db->method('sql_query')->willReturnCallback(function ($sql) use (&$capturedSql) {
			$capturedSql = $sql;
			return true;
		});
		$this->cache->expects($this->once())->method('destroy')->with('sql', self::TABLE);

		$this->spec->delete();
		$this->assertStringStartsWith('DELETE FROM ' . self::TABLE, $capturedSql);
		$this->assertStringContainsString('WHERE spec_id = 5', $capturedSql);
	}

	public function test_get_for_class_returns_typed_rows_filtered_by_class(): void
	{
		$this->db->method('sql_escape')->willReturnCallback(fn ($v) => addslashes($v));

		$capturedSql = '';
		$this->db->method('sql_query')->willReturnCallback(function ($sql) use (&$capturedSql) {
			$capturedSql = $sql;
			return true;
		});

		$rows = [
			['spec_id' => '1', 'game_id' => 'wow', 'class_id' => '8', 'role_id' => '3', 'spec_name' => 'Arcane', 'spec_icon' => 'a', 'spec_order' => '1'],
			['spec_id' => '2', 'game_id' => 'wow', 'class_id' => '8', 'role_id' => '3', 'spec_name' => 'Fire',   'spec_icon' => 'b', 'spec_order' => '2'],
			false,
		];
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(...$rows);

		$result = $this->spec->get_for_class('wow', 8);

		$this->assertCount(2, $result);
		$this->assertSame(1, $result[0]['spec_id']);
		$this->assertSame('Arcane', $result[0]['spec_name']);
		$this->assertSame(2, $result[1]['spec_id']);
		$this->assertStringContainsString("game_id = 'wow'", $capturedSql);
		$this->assertStringContainsString('class_id = 8', $capturedSql);
		$this->assertStringContainsString('ORDER BY spec_order ASC, spec_name ASC', $capturedSql);
	}

	public function test_get_for_class_omits_class_filter_when_null(): void
	{
		$this->db->method('sql_escape')->willReturnCallback(fn ($v) => addslashes($v));

		$capturedSql = '';
		$this->db->method('sql_query')->willReturnCallback(function ($sql) use (&$capturedSql) {
			$capturedSql = $sql;
			return true;
		});
		$this->db->method('sql_fetchrow')->willReturn(false);

		$this->spec->get_for_class('wow');

		$this->assertStringContainsString("game_id = 'wow'", $capturedSql);
		$this->assertStringNotContainsString('class_id', $capturedSql);
	}

	public function test_get_translations_returns_empty_when_language_table_unwired(): void
	{
		// Default constructor leaves bb_language_table = ''
		$this->db->expects($this->never())->method('sql_query');
		$this->assertSame([], $this->spec->get_translations('wow', 'de'));
	}

	public function test_get_translations_returns_empty_when_locale_blank(): void
	{
		$spec = new specialization($this->db, $this->cache, self::TABLE, 'phpbb_bb_language');
		$this->db->expects($this->never())->method('sql_query');
		$this->assertSame([], $spec->get_translations('wow', ''));
	}

	public function test_get_translations_maps_attribute_id_to_translated_name(): void
	{
		$spec = new specialization($this->db, $this->cache, self::TABLE, 'phpbb_bb_language');
		$this->db->method('sql_escape')->willReturnCallback(fn ($v) => addslashes($v));

		$capturedSql = '';
		$this->db->method('sql_query')->willReturnCallback(function ($sql) use (&$capturedSql) {
			$capturedSql = $sql;
			return true;
		});
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['attribute_id' => '7', 'name' => 'Frost'],
			['attribute_id' => '8', 'name' => 'Givre'],
			false
		);

		$result = $spec->get_translations('wow', 'fr');

		$this->assertSame([7 => 'Frost', 8 => 'Givre'], $result);
		$this->assertStringContainsString("attribute = 'spec'", $capturedSql);
		$this->assertStringContainsString("game_id = 'wow'", $capturedSql);
		$this->assertStringContainsString("language = 'fr'", $capturedSql);
	}
}
