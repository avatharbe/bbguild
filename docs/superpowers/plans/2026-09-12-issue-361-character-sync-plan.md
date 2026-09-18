# Issue #361 Character-Sync Scheduler Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give bbGuild a game-agnostic `character_sync_interface` contract plus a phpBB cron task that syncs a small batch of the stalest characters each run, delegating to whichever game plugin implements the contract for that character's game.

**Architecture:** New `character_sync_interface`/`character_sync_registry` pair (structural copy of the existing `game_provider_interface`/`game_registry`), a new `player_last_synced` column + `get_stalest_players()`/`update_last_synced()` on the `player` model, a new `character_sync` cron task extending `\phpbb\cron\task\base` (mirroring `bbpatreon\cron\task\sync`), and a new `CHARACTER_SYNC_FAILED` entry in bbGuild's own `bb_logs`-backed admin log.

**Tech Stack:** phpBB 3.3 extension (PHP 8.1+), PHPUnit 9.5 (mock-based; DB-backed tests defer to CI — see Global Constraints).

**Spec:** [`2026-09-12-issue-361-character-sync-design.md`](2026-09-12-issue-361-character-sync-design.md)

## Global Constraints

- phpBB >= 3.3.0, PHP >= 8.1 (extension floor, unchanged by this work).
- Working-copy-first: edit in `/Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild`, then `rsync -a --exclude='.git'` into `/Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync` (branch `feature/361-character-sync`) before staging/committing — only stage files this plan touches, never the pre-existing unrelated uncommitted diff on `model/admin/log.php` (adds `$limit`/`$order` params to `read_log()`, unrelated to this work).
- Local test execution: `/Users/Andreas/.local/share/phpbb-ext-test-harness` (`./vendor/bin/phpunit -c phpunit.xml --testdox`, editing `phpunit.xml`'s `<directory>` to point at this extension). Only mock-based tests (extending plain `PHPUnit\Framework\TestCase`) run there; nothing in this plan needs `phpbb_database_test_case`, so every test task should be locally runnable.
- No `!tagged_iterator` — this codebase rejected it already (unsupported by phpBB 3.3); always `phpbb\di\service_collection` + a registry class.
- No ACP UI, no bbguildwow implementation work (#362) — out of scope here.
- Constants numbering in `model/admin/constants.php` is sequential and must not collide — next free value is `61` (last is `PORTRAITS_SYNCED = 60`).
- New language keys get identical English text seeded into all 7 locales (`en fr de it nl es_x_tu pl`) — established convention (confirmed: `ACTION_ARMORY_DOWN`/`VLOG_ARMORY_DOWN` are byte-identical English across all 7 today; untranslated copies are the norm, not a gap).

---

### Task 1: `character_sync_interface` + `character_sync_registry`

**Files:**
- Create: `model/games/character_sync_interface.php`
- Create: `model/games/character_sync_registry.php`
- Test: `tests/games/character_sync_registry_test.php`
- Modify: `config/services.yml` (append new service block after the existing `avathar.bbguild.game_registry` entry, ~line 243)

**Interfaces:**
- Consumes: nothing new.
- Produces: `avathar\bbguild\model\games\character_sync_interface` (`get_game_id(): string`, `sync_character(array $player_row): bool`), consumed by Task 5's cron task and (later, out of scope) by #362. `avathar\bbguild\model\games\character_sync_registry` with `get(string $game_id): ?character_sync_interface`, `has(string $game_id): bool`, `get_supported_game_ids(): array` — consumed by Task 5.

- [ ] **Step 1: Write the failing registry test**

Create `tests/games/character_sync_registry_test.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run (from the test harness, per Global Constraints, with `phpunit.xml`'s `<directory>` pointed at `/Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild`):
```bash
./vendor/bin/phpunit -c phpunit.xml --filter character_sync_registry_test
```
Expected: FAIL — `Class "avathar\bbguild\model\games\character_sync_interface" not found`.

- [ ] **Step 3: Create the interface**

Create `model/games/character_sync_interface.php`:

```php
<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Character Sync Interface
 * Contract that a game plugin implements to let the core character-sync
 * cron task (avathar\bbguild\cron\task\character_sync) delegate syncing
 * a single character to that plugin.
 *
 */

namespace avathar\bbguild\model\games;

/**
 * Interface character_sync_interface
 *
 * @package avathar\bbguild\model\games
 */
interface character_sync_interface
{
	/**
	 * Get the unique game identifier this handler syncs (e.g. 'wow').
	 * Matches bb_players.game_id.
	 *
	 * @return string
	 */
	public function get_game_id(): string;

	/**
	 * Sync a single character from this game's external data source.
	 *
	 * @param array $player_row The full bb_players row for this character
	 * @return bool True on success, false on failure
	 */
	public function sync_character(array $player_row): bool;
}
```

- [ ] **Step 4: Create the registry**

Create `model/games/character_sync_registry.php`:

```php
<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Character Sync Registry
 * Collects all character sync handlers registered via tagged services.
 * Game plugins register by tagging their service with 'bbguild.character_sync'.
 *
 */

namespace avathar\bbguild\model\games;

/**
 * Class character_sync_registry
 *
 * Central registry that collects all character_sync_interface implementations
 * injected via a phpbb\di\service_collection tagged 'bbguild.character_sync'.
 *
 * @package avathar\bbguild\model\games
 */
class character_sync_registry
{
	/** @var character_sync_interface[] Indexed by game_id */
	private $handlers = [];

	/**
	 * Constructor.
	 *
	 * @param iterable $handlers Tagged character sync handler services
	 */
	public function __construct(iterable $handlers)
	{
		foreach ($handlers as $handler)
		{
			$this->handlers[$handler->get_game_id()] = $handler;
		}
	}

	/**
	 * Get a character sync handler by its game_id.
	 *
	 * @param string $game_id The unique game identifier
	 * @return character_sync_interface|null The handler, or null if not registered
	 */
	public function get(string $game_id): ?character_sync_interface
	{
		return $this->handlers[$game_id] ?? null;
	}

	/**
	 * Check if a character sync handler is registered for a game.
	 *
	 * @param string $game_id The unique game identifier
	 * @return bool
	 */
	public function has(string $game_id): bool
	{
		return isset($this->handlers[$game_id]);
	}

	/**
	 * Get the game_ids of all registered character sync handlers.
	 *
	 * @return string[]
	 */
	public function get_supported_game_ids(): array
	{
		return array_keys($this->handlers);
	}
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
./vendor/bin/phpunit -c phpunit.xml --filter character_sync_registry_test
```
Expected: PASS, 5 tests.

- [ ] **Step 6: Wire the tagged-service pair into `config/services.yml`**

In `config/services.yml`, immediately after the existing block (currently ending `- '@avathar.bbguild.game_provider_collection'`):
```yaml
    avathar.bbguild.game_provider_collection:
        class: phpbb\di\service_collection
        arguments:
            - '@service_container'
        tags:
            - { name: service_collection, tag: bbguild.game_provider }
    avathar.bbguild.game_registry:
        class: avathar\bbguild\model\games\game_registry
        arguments:
            - '@avathar.bbguild.game_provider_collection'
```
add:
```yaml
    avathar.bbguild.character_sync_collection:
        class: phpbb\di\service_collection
        arguments:
            - '@service_container'
        tags:
            - { name: service_collection, tag: bbguild.character_sync }
    avathar.bbguild.character_sync_registry:
        class: avathar\bbguild\model\games\character_sync_registry
        arguments:
            - '@avathar.bbguild.character_sync_collection'
```

- [ ] **Step 7: Lint and commit**

```bash
php -l model/games/character_sync_interface.php
php -l model/games/character_sync_registry.php
php -l tests/games/character_sync_registry_test.php
rsync -a --exclude='.git' \
  model/games/character_sync_interface.php model/games/character_sync_registry.php \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/model/games/
rsync -a --exclude='.git' \
  tests/games/character_sync_registry_test.php \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/tests/games/
rsync -a --exclude='.git' config/services.yml \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/config/services.yml
cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync
git add model/games/character_sync_interface.php model/games/character_sync_registry.php \
  tests/games/character_sync_registry_test.php config/services.yml
git commit -m "Add character_sync_interface + character_sync_registry (#361)"
```

---

### Task 2: Migration — `player_last_synced` column + `bbguild_sync_last_run` config

**Files:**
- Create: `migrations/v210b1/release_2_1_0_b1.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `bb_players.player_last_synced` (UINT, default 0) — consumed by Task 3's `get_stalest_players()`/`update_last_synced()`. Config key `bbguild_sync_last_run` (default 0) — consumed by Task 5's `should_run()`.

- [ ] **Step 1: Create the migration**

Create `migrations/v210b1/release_2_1_0_b1.php`:

```php
<?php
/**
 * bbGuild Extension — 2.1.0-b1 migration
 *
 * Adds character-sync scheduler support (#361):
 *   - player_last_synced column on bb_players, tracking when a character
 *     was last synced by the character-sync cron task. Deliberately
 *     separate from the existing generic last_update column, which is
 *     bumped on manual ACP edits too and would otherwise let a manual
 *     edit make a genuinely-stale character look freshly synced.
 *   - bbguild_sync_last_run config key, tracking when the cron task itself
 *     last ran (independent of any individual character's sync time).
 *
 * Canonical version lives in ext::BBGUILD_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v210b1;

class release_2_1_0_b1 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild\migrations\v200rc4\release_2_0_0_rc4'];
	}

	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'bb_players', 'player_last_synced');
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'bb_players' => [
					'player_last_synced' => ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'bb_players' => ['player_last_synced'],
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['bbguild_sync_last_run', 0]],
		];
	}
}
```

- [ ] **Step 2: Lint and commit**

No local DB to run the migrator against (per Global Constraints); syntax-check only here, real execution happens in Task 6 against the Sites install.

```bash
php -l migrations/v210b1/release_2_1_0_b1.php
mkdir -p /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/migrations/v210b1
rsync -a --exclude='.git' migrations/v210b1/release_2_1_0_b1.php \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/migrations/v210b1/
cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync
git add migrations/v210b1/release_2_1_0_b1.php
git commit -m "Add v210b1 migration: player_last_synced column + bbguild_sync_last_run config (#361)"
```

---

### Task 3: `player::get_stalest_players()` + `player::update_last_synced()`

**Files:**
- Modify: `model/player/player.php` (insert two new public methods immediately before the class's final closing `}`, i.e. right after the existing `resolve_game_image_path()` private method at the end of the file)
- Test: `tests/player/character_sync_query_test.php`

**Interfaces:**
- Consumes: `bb_players.player_last_synced` (Task 2).
- Produces: `player::get_stalest_players(array $game_ids, int $limit): array`, `player::update_last_synced(int $player_id): void` — both consumed by Task 5's cron task.

- [ ] **Step 1: Write the failing test**

Create `tests/player/character_sync_query_test.php`:

```php
<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player character-sync query tests — issue #361
 */

namespace avathar\bbguild\tests\player;

use avathar\bbguild\model\player\player;
use PHPUnit\Framework\TestCase;

class character_sync_query_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\driver\driver_interface */
	private $db;

	protected function setUp(): void
	{
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
	}

	private function make_player(): player
	{
		$reflection = new \ReflectionClass(player::class);
		/** @var player $p */
		$p = $reflection->newInstanceWithoutConstructor();
		$p->bb_players_table = 'bb_players';

		$db_prop = $reflection->getProperty('db');
		$db_prop->setAccessible(true);
		$db_prop->setValue($p, $this->db);

		return $p;
	}

	public function test_get_stalest_players_returns_empty_array_for_no_game_ids(): void
	{
		$p = $this->make_player();

		$this->db->expects($this->never())->method('sql_query_limit');

		$this->assertSame([], $p->get_stalest_players([], 10));
	}

	public function test_get_stalest_players_filters_by_game_ids_and_active_status(): void
	{
		$p = $this->make_player();

		$captured_sql = '';
		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow', 'gw2')");
		$this->db->expects($this->once())
			->method('sql_query_limit')
			->with($this->callback(function ($sql) use (&$captured_sql) {
				$captured_sql = $sql;
				return true;
			}), 10)
			->willReturn('fake_result');
		$this->db->method('sql_fetchrow')->willReturn(false);

		$p->get_stalest_players(['wow', 'gw2'], 10);

		$this->assertStringContainsString('player_status = 1', $captured_sql);
		$this->assertStringContainsString("game_id IN ('wow', 'gw2')", $captured_sql);
		$this->assertStringContainsString('ORDER BY player_last_synced ASC', $captured_sql);
	}

	public function test_get_stalest_players_returns_all_fetched_rows(): void
	{
		$p = $this->make_player();

		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow')");
		$this->db->method('sql_query_limit')->willReturn('fake_result');
		$rows = [
			['player_id' => 1, 'game_id' => 'wow'],
			['player_id' => 2, 'game_id' => 'wow'],
		];
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(...$rows, false);

		$result = $p->get_stalest_players(['wow'], 10);

		$this->assertSame($rows, $result);
	}

	public function test_update_last_synced_updates_correct_player(): void
	{
		$p = $this->make_player();

		$captured_sql = '';
		$this->db->expects($this->once())
			->method('sql_query')
			->with($this->callback(function ($sql) use (&$captured_sql) {
				$captured_sql = $sql;
				return true;
			}));

		$p->update_last_synced(42);

		$this->assertStringContainsString('UPDATE bb_players', $captured_sql);
		$this->assertStringContainsString('player_last_synced', $captured_sql);
		$this->assertStringContainsString('WHERE player_id = 42', $captured_sql);
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit -c phpunit.xml --filter character_sync_query_test
```
Expected: FAIL — `Call to undefined method avathar\bbguild\model\player\player::get_stalest_players()`.

- [ ] **Step 3: Add the two methods to `player.php`**

In `model/player/player.php`, immediately before the class's final closing `}` (right after the existing `resolve_game_image_path()` method), add:

```php

	/**
	 * Get the N stalest active characters across the given games, ordered by
	 * player_last_synced ascending (never-synced characters, at 0, sort first).
	 *
	 * @param array $game_ids Game identifiers to include (e.g. from
	 *                        character_sync_registry::get_supported_game_ids())
	 * @param int   $limit    Maximum number of characters to return
	 * @return array Rows from bb_players
	 */
	public function get_stalest_players(array $game_ids, int $limit): array
	{
		if (empty($game_ids))
		{
			return [];
		}

		$sql = 'SELECT * FROM ' . $this->bb_players_table . '
			WHERE player_status = 1
				AND ' . $this->db->sql_in_set('game_id', $game_ids) . '
			ORDER BY player_last_synced ASC';
		$result = $this->db->sql_query_limit($sql, $limit);

		$players = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$players[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $players;
	}

	/**
	 * Mark a character as just synced.
	 *
	 * @param int $player_id
	 * @return void
	 */
	public function update_last_synced(int $player_id): void
	{
		$sql = 'UPDATE ' . $this->bb_players_table . '
			SET player_last_synced = ' . time() . '
			WHERE player_id = ' . $player_id;
		$this->db->sql_query($sql);
	}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit -c phpunit.xml --filter character_sync_query_test
```
Expected: PASS, 4 tests.

- [ ] **Step 5: Run the full existing player test suite to check for regressions**

```bash
./vendor/bin/phpunit -c phpunit.xml --testsuite "Extension Test Suite"
```
Expected: PASS, no regressions (in particular `tests/player/player_claim_test.php`, which touches the same class).

- [ ] **Step 6: Lint and commit**

```bash
php -l model/player/player.php
php -l tests/player/character_sync_query_test.php
rsync -a --exclude='.git' model/player/player.php \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/model/player/player.php
rsync -a --exclude='.git' tests/player/character_sync_query_test.php \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/tests/player/
cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync
git add model/player/player.php tests/player/character_sync_query_test.php
git commit -m "Add player::get_stalest_players()/update_last_synced() (#361)"
```

---

### Task 4: Admin log entry for sync failures

**Files:**
- Modify: `model/admin/constants.php` (append new constant after `PORTRAITS_SYNCED = 60`)
- Modify: `model/admin/log.php` (register the new constant in `valid_action_types`)
- Modify: `language/en/admin.php`, `language/fr/admin.php`, `language/de/admin.php`, `language/it/admin.php`, `language/nl/admin.php`, `language/es_x_tu/admin.php`, `language/pl/admin.php` (add `ACTION_CHARACTER_SYNC_FAILED` / `VLOG_CHARACTER_SYNC_FAILED`, identical English text in all 7, per Global Constraints)

**Interfaces:**
- Consumes: nothing.
- Produces: log type string `'L_ERROR_CHARACTER_SYNC_FAILED'` (validated against `avathar\bbguild\model\admin\log::valid_action_types` via its `CHARACTER_SYNC_FAILED` entry) — consumed by Task 5's cron task via `avathar\bbguild\model\admin\log::log_insert()`.

No dedicated unit test for this task — matches existing convention (none of `ARMORY_DOWN`/`ROSTER_SYNCED`/`SPECS_SYNCED`/`PORTRAITS_SYNCED` have tests either; this is pure data/config wiring, verified by lint + the Task 5 cron task test exercising the real constant name).

- [ ] **Step 1: Add the constant**

In `model/admin/constants.php`, after:
```php
	/**
	 * character portraits synced from API
	 */
	const PORTRAITS_SYNCED = 60;

}
```
change to:
```php
	/**
	 * character portraits synced from API
	 */
	const PORTRAITS_SYNCED = 60;
	/**
	 * character-sync cron task failed to sync a character (#361)
	 */
	const CHARACTER_SYNC_FAILED = 61;

}
```

- [ ] **Step 2: Register it in the log whitelist**

In `model/admin/log.php`, after:
```php
		constants::PORTRAITS_SYNCED => 'PORTRAITS_SYNCED',
	);
```
change to:
```php
		constants::PORTRAITS_SYNCED => 'PORTRAITS_SYNCED',
		constants::CHARACTER_SYNC_FAILED => 'CHARACTER_SYNC_FAILED',
	);
```

- [ ] **Step 3: Add language keys to all 7 locales**

In each of `language/en/admin.php`, `language/fr/admin.php`, `language/de/admin.php`, `language/it/admin.php`, `language/nl/admin.php`, `language/es_x_tu/admin.php`, `language/pl/admin.php`:

After:
```php
	'ACTION_PORTRAITS_SYNCED' => 'Portraits synced',
```
add:
```php
	'ACTION_CHARACTER_SYNC_FAILED' => 'Character sync failed',
```

After:
```php
	'VLOG_PORTRAITS_SYNCED' => '%s synced portraits for guild %s: %s',
```
add:
```php
	'VLOG_CHARACTER_SYNC_FAILED' => '%s: character sync failed for %s (game: %s)',
```

- [ ] **Step 4: Lint and commit**

```bash
php -l model/admin/constants.php
php -l model/admin/log.php
for lang in en fr de it nl es_x_tu pl; do php -l language/$lang/admin.php; done

for f in model/admin/constants.php model/admin/log.php \
  language/en/admin.php language/fr/admin.php language/de/admin.php \
  language/it/admin.php language/nl/admin.php language/es_x_tu/admin.php language/pl/admin.php; do
  rsync -a --exclude='.git' "$f" \
    "/Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/$f"
done

cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync
git add model/admin/constants.php model/admin/log.php \
  language/en/admin.php language/fr/admin.php language/de/admin.php \
  language/it/admin.php language/nl/admin.php language/es_x_tu/admin.php language/pl/admin.php
git commit -m "Add CHARACTER_SYNC_FAILED admin-log type (#361)"
```

---

### Task 5: `character_sync` cron task

**Files:**
- Create: `cron/task/character_sync.php`
- Test: `tests/cron/character_sync_test.php`
- Modify: `config/services.yml` (append cron task service block)

**Interfaces:**
- Consumes: `character_sync_registry::get_supported_game_ids()`/`get()` (Task 1), `player::get_stalest_players()`/`update_last_synced()` (Task 3), `log::log_insert()` with `log_type => 'L_ERROR_CHARACTER_SYNC_FAILED'` (Task 4), config key `bbguild_sync_last_run` (Task 2).
- Produces: `avathar\bbguild\cron\task\character_sync` tagged `cron.task` — no further consumers within this plan (bbguildwow's #362 will later tag its own handler into `bbguild.character_sync`, not into this task).

- [ ] **Step 1: Write the failing test**

Create `tests/cron/character_sync_test.php`:

```php
<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Character sync cron task tests — issue #361
 */

namespace avathar\bbguild\tests\cron;

use avathar\bbguild\cron\task\character_sync;
use avathar\bbguild\model\games\character_sync_interface;
use avathar\bbguild\model\games\character_sync_registry;
use PHPUnit\Framework\TestCase;

class character_sync_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\config\config */
	private $config;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\driver\driver_interface */
	private $db;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\avathar\bbguild\model\admin\log */
	private $bbguild_log;

	protected function setUp(): void
	{
		$this->config = $this->getMockBuilder(\phpbb\config\config::class)
			->disableOriginalConstructor()
			->getMock();
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$this->bbguild_log = $this->getMockBuilder(\avathar\bbguild\model\admin\log::class)
			->disableOriginalConstructor()
			->getMock();
	}

	private function make_handler(string $game_id, \Closure $sync): character_sync_interface
	{
		return new class($game_id, $sync) implements character_sync_interface {
			private $game_id;
			private $sync;

			public function __construct(string $game_id, \Closure $sync)
			{
				$this->game_id = $game_id;
				$this->sync = $sync;
			}

			public function get_game_id(): string
			{
				return $this->game_id;
			}

			public function sync_character(array $player_row): bool
			{
				return ($this->sync)($player_row);
			}
		};
	}

	private function make_task(character_sync_registry $registry): character_sync
	{
		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);
		$user = $this->createMock(\phpbb\user::class);
		$ext_manager = $this->getMockBuilder(\phpbb\extension\manager::class)
			->disableOriginalConstructor()
			->getMock();
		$util = $this->getMockBuilder(\avathar\bbguild\model\admin\util::class)
			->disableOriginalConstructor()
			->getMock();

		$task = new character_sync(
			$this->config,
			$registry,
			$this->db,
			$cache,
			$user,
			$ext_manager,
			$this->bbguild_log,
			$util,
			'bb_players', 'bb_ranks', 'bb_classes', 'bb_races', 'bb_language', 'bb_guild', 'bb_factions', 'bb_games'
		);
		$task->set_name('avathar.bbguild.cron.task.character_sync');

		return $task;
	}

	public function test_is_runnable_false_when_no_game_ids_supported(): void
	{
		$task = $this->make_task(new character_sync_registry([]));

		$this->assertFalse($task->is_runnable());
	}

	public function test_is_runnable_true_when_a_game_id_is_supported(): void
	{
		$task = $this->make_task(new character_sync_registry([
			$this->make_handler('wow', fn ($row) => true),
		]));

		$this->assertTrue($task->is_runnable());
	}

	public function test_should_run_false_within_min_interval(): void
	{
		$this->config->expects($this->once())
			->method('offsetGet')
			->with('bbguild_sync_last_run')
			->willReturn((string) (time() - 60));

		$task = $this->make_task(new character_sync_registry([]));

		$this->assertFalse($task->should_run());
	}

	public function test_should_run_true_after_min_interval(): void
	{
		$this->config->expects($this->once())
			->method('offsetGet')
			->with('bbguild_sync_last_run')
			->willReturn((string) (time() - 3600));

		$task = $this->make_task(new character_sync_registry([]));

		$this->assertTrue($task->should_run());
	}

	public function test_run_delegates_to_handler_and_always_bumps_last_synced_on_success(): void
	{
		$seen = [];
		$registry = new character_sync_registry([
			$this->make_handler('wow', function ($row) use (&$seen) {
				$seen[] = $row['player_id'];
				return true;
			}),
		]);
		$task = $this->make_task($registry);

		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow')");
		$this->db->method('sql_query_limit')->willReturn('fake_result');
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['player_id' => 1, 'game_id' => 'wow', 'player_name' => 'Alice'],
			false
		);
		$this->bbguild_log->expects($this->never())->method('log_insert');
		$this->config->expects($this->once())->method('set')->with('bbguild_sync_last_run', $this->isType('int'));
		$update_sql = '';
		$this->db->expects($this->once())
			->method('sql_query')
			->with($this->callback(function ($sql) use (&$update_sql) {
				$update_sql = $sql;
				return true;
			}));

		$task->run();

		$this->assertSame([1], $seen);
		$this->assertStringContainsString('WHERE player_id = 1', $update_sql);
	}

	public function test_run_logs_failure_and_still_bumps_last_synced_when_handler_returns_false(): void
	{
		$registry = new character_sync_registry([
			$this->make_handler('wow', fn ($row) => false),
		]);
		$task = $this->make_task($registry);

		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow')");
		$this->db->method('sql_query_limit')->willReturn('fake_result');
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['player_id' => 2, 'game_id' => 'wow', 'player_name' => 'Bob'],
			false
		);
		$this->bbguild_log->expects($this->once())
			->method('log_insert')
			->with($this->callback(function ($values) {
				return $values['log_type'] === 'L_ERROR_CHARACTER_SYNC_FAILED'
					&& $values['log_action'] === ['Bob', 'wow'];
			}));
		$this->db->expects($this->once())->method('sql_query');

		$task->run();
	}

	public function test_run_logs_failure_when_handler_throws(): void
	{
		$registry = new character_sync_registry([
			$this->make_handler('wow', function ($row) {
				throw new \RuntimeException('API down');
			}),
		]);
		$task = $this->make_task($registry);

		$this->db->method('sql_in_set')->willReturn("game_id IN ('wow')");
		$this->db->method('sql_query_limit')->willReturn('fake_result');
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			['player_id' => 3, 'game_id' => 'wow', 'player_name' => 'Carol'],
			false
		);
		$this->bbguild_log->expects($this->once())->method('log_insert');
		$this->db->expects($this->once())->method('sql_query');

		$task->run();
	}

	public function test_run_is_noop_when_no_game_ids_supported(): void
	{
		$task = $this->make_task(new character_sync_registry([]));

		$this->db->expects($this->never())->method('sql_query_limit');
		$this->config->expects($this->never())->method('set');

		$task->run();
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit -c phpunit.xml --filter character_sync_test
```
Expected: FAIL — `Class "avathar\bbguild\cron\task\character_sync" not found`.

- [ ] **Step 3: Create the cron task**

Create `cron/task/character_sync.php`:

```php
<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Character Sync Cron Task
 * Picks a small batch of the stalest characters across all games with a
 * registered character_sync_interface handler, and delegates each to its
 * owning game plugin. Runs under phpBB's default web-embedded cron or
 * system cron with no special-casing (both converge on cron.manager).
 *
 */

namespace avathar\bbguild\cron\task;

use avathar\bbguild\model\admin\log;
use avathar\bbguild\model\admin\util;
use avathar\bbguild\model\games\character_sync_registry;
use avathar\bbguild\model\player\player;

class character_sync extends \phpbb\cron\task\base
{
	/** Characters synced per cron run */
	const BATCH_SIZE = 10;

	/** Minimum seconds between runs */
	const MIN_INTERVAL = 300;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var character_sync_registry */
	protected $registry;

	/** @var log */
	protected $bbguild_log;

	/** @var player */
	protected $player;

	/**
	 * Constructor. The trailing block of raw dependencies + table names
	 * mirrors every other manual `new player(...)` call site in this
	 * codebase (e.g. acp/player_module.php) — bbGuild has no DI service
	 * for `player` itself, so each consumer threads its own construction.
	 */
	public function __construct(
		\phpbb\config\config $config,
		character_sync_registry $registry,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\user $user,
		\phpbb\extension\manager $ext_manager,
		log $bbguild_log,
		util $util,
		string $bb_players_table,
		string $bb_ranks_table,
		string $bb_classes_table,
		string $bb_races_table,
		string $bb_language_table,
		string $bb_guild_table,
		string $bb_factions_table,
		string $bb_games_table
	)
	{
		$this->config = $config;
		$this->registry = $registry;
		$this->bbguild_log = $bbguild_log;
		$this->player = new player(
			$db, $config, $cache, $user, $ext_manager, $bbguild_log, $util,
			$bb_players_table, $bb_ranks_table, $bb_classes_table, $bb_races_table,
			$bb_language_table, $bb_guild_table, $bb_factions_table, $bb_games_table
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_runnable()
	{
		return !empty($this->registry->get_supported_game_ids());
	}

	/**
	 * {@inheritdoc}
	 */
	public function should_run()
	{
		return (time() - (int) $this->config['bbguild_sync_last_run']) > self::MIN_INTERVAL;
	}

	/**
	 * {@inheritdoc}
	 */
	public function run()
	{
		$game_ids = $this->registry->get_supported_game_ids();

		if (empty($game_ids))
		{
			return;
		}

		$stale_players = $this->player->get_stalest_players($game_ids, self::BATCH_SIZE);

		foreach ($stale_players as $player_row)
		{
			$handler = $this->registry->get($player_row['game_id']);

			$success = false;
			if ($handler !== null)
			{
				try
				{
					$success = $handler->sync_character($player_row);
				}
				catch (\Exception $e)
				{
					$success = false;
				}
			}

			if (!$success)
			{
				$this->bbguild_log->log_insert([
					'log_type'   => 'L_ERROR_CHARACTER_SYNC_FAILED',
					'log_result' => 'L_ERROR',
					'log_action' => [$player_row['player_name'], $player_row['game_id']],
				]);
			}

			$this->player->update_last_synced((int) $player_row['player_id']);
		}

		$this->config->set('bbguild_sync_last_run', time());
	}
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit -c phpunit.xml --filter character_sync_test
```
Expected: PASS, 8 tests. If `test_should_run_*` fails because `\phpbb\config\config` doesn't support array-offset mocking via `offsetGet` on a `disableOriginalConstructor()` mock, fall back to constructing a real `\phpbb\config\config` with an in-memory array: `new \phpbb\config\config(['bbguild_sync_last_run' => (string) (time() - 60)])` for the "within interval" case and `time() - 3600` for the "after interval" case, and drop the `offsetGet` expectation — `\phpbb\config\config` implements `ArrayAccess` over a plain constructor-injected array with no other dependencies, so this is safe to construct directly in a test.

- [ ] **Step 5: Wire the cron task into `config/services.yml`**

Append to `config/services.yml` (after the `character_sync_registry` block added in Task 1):
```yaml
    avathar.bbguild.cron.task.character_sync:
        class: avathar\bbguild\cron\task\character_sync
        arguments:
            - '@config'
            - '@avathar.bbguild.character_sync_registry'
            - '@dbal.conn'
            - '@cache.driver'
            - '@user'
            - '@ext.manager'
            - '@avathar.bbguild.log'
            - '@avathar.bbguild.util'
            - '%avathar.bbguild.tables.bb_players%'
            - '%avathar.bbguild.tables.bb_ranks%'
            - '%avathar.bbguild.tables.bb_classes%'
            - '%avathar.bbguild.tables.bb_races%'
            - '%avathar.bbguild.tables.bb_language%'
            - '%avathar.bbguild.tables.bb_guild%'
            - '%avathar.bbguild.tables.bb_factions%'
            - '%avathar.bbguild.tables.bb_games%'
        calls:
            - [set_name, ['avathar.bbguild.cron.task.character_sync']]
        tags:
            - { name: cron.task }
```

- [ ] **Step 6: Run the full local test suite to check for regressions**

```bash
./vendor/bin/phpunit -c phpunit.xml --testsuite "Extension Test Suite"
```
Expected: PASS, all tests including the new `character_sync_registry_test`, `character_sync_query_test`, `character_sync_test`.

- [ ] **Step 7: Lint and commit**

```bash
php -l cron/task/character_sync.php
php -l tests/cron/character_sync_test.php
mkdir -p /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/cron/task
mkdir -p /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/tests/cron
rsync -a --exclude='.git' cron/task/character_sync.php \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/cron/task/
rsync -a --exclude='.git' tests/cron/character_sync_test.php \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/tests/cron/
rsync -a --exclude='.git' config/services.yml \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync/config/services.yml
cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync
git add cron/task/character_sync.php tests/cron/character_sync_test.php config/services.yml
git commit -m "Add character_sync cron task (#361)"
```

---

### Task 6: Final verification against the real phpBB tree

**Files:** none created/modified — verification only.

**Interfaces:** none.

- [ ] **Step 1: Confirm the Sites working copy and the git worktree are in sync**

```bash
diff -rq --exclude='.git' --exclude='.claude' --exclude='.DS_Store' --exclude='.phpunit.result.cache' \
  /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild \
  /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync
```
Expected: no output beyond expected generated/ignored files (`.worktrees`, `composer.lock`, etc. — same shape as the Task-0 baseline diff).

- [ ] **Step 2: Run the full extension test suite one more time via the local harness**

```bash
cd /Users/Andreas/.local/share/phpbb-ext-test-harness
./vendor/bin/phpunit -c phpunit.xml --testdox
```
Expected: all tests pass, including every new test from Tasks 1/3/5.

- [ ] **Step 3: Run the phpBB extension enable/migration flow against the real Sites install**

Via the ACP (Sites install is the live board — this is the actual DB the migration will run against):
1. Log into `https://<local-sites-board>/adm/` (or wherever the local board is served from).
2. ACP → Customise → Manage extensions → bbGuild → "Details", confirm the new migration `v210b1\release_2_1_0_b1` is picked up (phpBB auto-runs pending migrations for an already-enabled extension on the next page load, or via ACP → Customise → Extensions → bbGuild → re-run).
3. Confirm no fatal errors on any bbGuild ACP/UCP/portal page load afterward (guild portal page, ACP → bbGuild → Game settings, UCP → bbGuild).
4. Verify the column exists: `DESCRIBE phpbb_bb_players;` (or the board's actual table prefix) should list `player_last_synced`.
5. Verify the config key exists: `SELECT * FROM phpbb_config WHERE config_name = 'bbguild_sync_last_run';` should return one row, value `0`.

- [ ] **Step 4: Confirm the cron task is registered and inert with no sync-capable plugin installed**

Since bbguildwow doesn't yet implement `character_sync_interface` (#362, not in this plan), `character_sync_registry::get_supported_game_ids()` is empty on this board today, so `is_runnable()` returns `false` and the task never fires. Confirm this doesn't error: load any page on the Sites board (triggers the web-cron dispatch check) and confirm no PHP errors in the phpBB error log or webserver log.

- [ ] **Step 5: Push the branch**

```bash
cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/.worktrees/feature-361-character-sync
git log --oneline main..HEAD
git push -u origin feature/361-character-sync
```
(Ask before pushing if not already covered by standing permission — this is a new remote branch, not a force-push, low risk, but still a shared-visibility action.)

- [ ] **Step 6: Open the PR**

```bash
gh pr create --repo avatharbe/bbguild --base main --head feature/361-character-sync \
  --title "Core: character-sync scheduler contract + cron (#361)" \
  --body "$(cat <<'EOF'
## Summary
- New game-agnostic `character_sync_interface` + `character_sync_registry` tagged-service pair (mirrors the existing `game_provider`/`game_registry` shape).
- New `player_last_synced` column (migration `v210b1`) and `player::get_stalest_players()`/`update_last_synced()`.
- New `character_sync` cron task (`BATCH_SIZE=10`, `MIN_INTERVAL=300s`), tagged `cron.task`, works under both web-cron and system-cron.
- New `CHARACTER_SYNC_FAILED` admin-log type across all 7 locales.

No behavior change yet on a board without a game plugin implementing the contract — bbguildwow's implementation is #362.

Closes #361.

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

---

## Self-Review

**Spec coverage:** Section 1 (contract & registry) → Task 1. Section 2 (data model & staleness) → Tasks 2 + 3. Section 3 (cron task) → Task 5, including all six `run()` sub-steps and the `is_runnable`/`should_run` gates. Admin-log failure visibility (spec Section 3, step 4) → Task 4. "Out of scope" items (bbguildwow's implementation, ACP UI, other adopters) are not present in any task — confirmed absent.

**Placeholder scan:** No TBD/TODO; every step has runnable code. The one open judgment call flagged in the spec (migration folder naming) is resolved concretely here (`v210b1`), not left open.

**Type consistency:** `character_sync_interface::sync_character(array $player_row): bool` used identically in the interface (Task 1), the cron task's call site (Task 5), and the test doubles (Tasks 1 and 5). `character_sync_registry::get_supported_game_ids(): array` / `get(string): ?character_sync_interface` used identically in Task 5's cron task and its test. `player::get_stalest_players(array $game_ids, int $limit): array` / `update_last_synced(int $player_id): void` match between Task 3's definition, its test, and Task 5's call sites.
