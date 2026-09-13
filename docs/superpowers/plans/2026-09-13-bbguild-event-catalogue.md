# bbGuild Event Catalogue Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `trigger_event()` calls at 12 concrete, currently-unhooked domain-action call sites in bbguild core, retroactively document the 10 events that already fire, and ship a `contrib/Events.md` catalogue — the in-process half of issue #370's API surface.

**Architecture:** Every new event follows the exact pattern already used by the 8 existing `trigger_event()`-based events in this codebase: a docblock with `@event`/`@var`/`@since`, `$vars = [...]; extract($this->dispatcher->trigger_event('avathar.bbguild.X', compact($vars)));`, firing the specific IDs involved (not full row/object dumps).

**Tech Stack:** PHP 8.1+, phpBB 3.3 extension framework (`phpbb\event\dispatcher_interface`), PHPUnit for unit tests.

**Spec:** `docs/superpowers/specs/2026-09-13-bbguild-event-catalogue-design.md`

## Global Constraints

- New events use `@since 2.3.0` (the spec's target milestone).
- Payload convention: `compact($vars)` with specific IDs only — never full row/object dumps (spec §4).
- No new event-dispatch mechanism — use `phpbb\event\dispatcher_interface::trigger_event()` exactly as the 8 existing call sites do.
- `contrib/Events.md` mirrors `avathar/recenttopics/contrib/Events.md`'s structure exactly (preamble, 3 numbered sections, stability note) — do not invent a different structure.
- Do not add `avathar.bbguild.news_posted` — `bb_news` has no working save path anywhere in the codebase (spec §3.3); this is confirmed dead, not a placeholder to fill in later.
- **Shared-repo coordination — RESOLVED:** forum-f2's #375 core work merged via PR #376 (`198ec0fb`) before this plan started executing, and forum-f2 has since moved on to a follow-up in the separate `avathar/bbguildwow` repo. No claim is currently held on any file in this plan. The `avathar.bbguild.cron.task.character_sync` block in `config/services.yml` (Task 2) was verified unchanged after the #375 merge — the exact argument list in Task 2 Step 5 below is current. If starting this plan much later, re-verify with `git log` / `ListAgents` that no new claim has appeared before touching `config/services.yml`, `config/routing.yml`, or `controller/view_controller.php` — none of this plan's other tasks touch the latter two anyway.

---

## Task 1: Character lifecycle events (`ucp/bbguild_module.php`)

**Files:**
- Modify: `ucp/bbguild_module.php:177-510` (the `main()` method — 5 new `trigger_event()` calls, no signature changes)
- Test: none (see rationale below) — manual verification only

**Interfaces:**
- Consumes: `$phpbb_container` (global, already used in this file to pull `cache.driver`, `avathar.bbguild.log`, etc. — same pattern, no services.yml change needed since this file is a phpBB "module" class, not a DI service)
- Produces: 5 new events for later tasks/consumers to reference: `avathar.bbguild.character_claim`, `avathar.bbguild.character_unclaim`, `avathar.bbguild.character_add`, `avathar.bbguild.character_edit`, `avathar.bbguild.character_delete` — each firing `player_id` (int), `guild_id` (int), `user_id` (int)

**No automated test for this task.** `ucp/bbguild_module.php` has zero existing test coverage (`tests/functional/` is empty except a `.gitkeep`; no `tests/ucp/` directory exists). Its `main()` method reads `global $phpbb_container`, calls phpBB's `trigger_error()` (which phpBB expects to terminate the request — not something PHPUnit's error-to-exception conversion models cleanly), and directly `new`s the `player` model (itself DB-touching). Building a test harness for this file from scratch is a separate, much larger undertaking than "add 5 trigger_event calls," and is out of scope for this plan. Verification is manual (Step 6 below), matching the spec's own Testing section (§6).

- [ ] **Step 1: Add the `dispatcher` pull alongside the other container services**

In `ucp/bbguild_module.php`, inside `main()`, find this block (~line 184-189):

```php
		$this->bbguild_cache = $phpbb_container->get('cache.driver');
		$this->bbguild_log = $phpbb_container->get('avathar.bbguild.log');
		$this->bbguild_util = $phpbb_container->get('avathar.bbguild.util');
		$this->bbguild_ext_manager = $phpbb_container->get('ext.manager');
		$this->bbguild_game_registry = $phpbb_container->get('avathar.bbguild.game_registry');
		$this->asset_resolver = $phpbb_container->get('avathar.bbguild.asset_url_resolver');
```

Add one line directly after it:

```php
		$dispatcher = $phpbb_container->get('dispatcher');
```

- [ ] **Step 2: Fire `character_unclaim` on successful unclaim**

Find (~line 259-264):

```php
					if ($player->Unclaim_Player())
					{
						meta_refresh(2, $this->u_action);
						$message = sprintf($this->user->lang['CHARACTER_UNCLAIMED'], $player_name) . '<br /><br />' . sprintf($this->user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a>');
						trigger_error($message);
					}
```

Replace with:

```php
					if ($player->Unclaim_Player())
					{
						/**
						 * Fired when a user unclaims a character from their forum account.
						 *
						 * @event avathar.bbguild.character_unclaim
						 * @var int player_id The character that was unclaimed
						 * @var int guild_id  The guild the character belongs to
						 * @var int user_id   The forum user who performed the unclaim
						 * @since 2.3.0
						 */
						extract($dispatcher->trigger_event('avathar.bbguild.character_unclaim', [
							'player_id' => $player_id,
							'guild_id'  => (int) $player->getPlayerGuildId(),
							'user_id'   => (int) $this->user->data['user_id'],
						]));

						meta_refresh(2, $this->u_action);
						$message = sprintf($this->user->lang['CHARACTER_UNCLAIMED'], $player_name) . '<br /><br />' . sprintf($this->user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a>');
						trigger_error($message);
					}
```

(Using an explicit `['player_id' => ..., ...]` array rather than `compact($vars)` here, unlike the other four character events below — `$player_id` is already in scope with a value that's correct for this event, but using bare `compact('player_id', 'guild_id', 'user_id')` would require also naming a local `$guild_id`/`$user_id` variable that doesn't otherwise need to exist in this branch. The explicit array form is equally valid — every existing `trigger_event()` call site in this codebase uses `compact($vars)` purely as a convenience for cases where the local variable names already match; the event contract is the same either way.)

- [ ] **Step 3: Fire `character_claim` on successful claim**

Find (~line 286-293):

```php
					$player_id = (int) $this->request->variable('playerlist', 0);
					$player->player_id = $player_id;
					$player->Getplayer();
					$player->Claim_Player();
					meta_refresh(2, $this->u_action);
					$message = sprintf($this->user->lang['CHARACTERS_UPDATED'], $player->getPlayerName()) . '<br /><br />' . sprintf($this->user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a>');
					unset($player);
					trigger_error($message);
```

Replace with:

```php
					$player_id = (int) $this->request->variable('playerlist', 0);
					$player->player_id = $player_id;
					$player->Getplayer();
					$player->Claim_Player();

					/**
					 * Fired when a user claims an existing (unclaimed) character.
					 *
					 * @event avathar.bbguild.character_claim
					 * @var int player_id The character that was claimed
					 * @var int guild_id  The guild the character belongs to
					 * @var int user_id   The forum user who claimed it
					 * @since 2.3.0
					 */
					extract($dispatcher->trigger_event('avathar.bbguild.character_claim', [
						'player_id' => $player_id,
						'guild_id'  => (int) $player->getPlayerGuildId(),
						'user_id'   => (int) $this->user->data['user_id'],
					]));

					meta_refresh(2, $this->u_action);
					$message = sprintf($this->user->lang['CHARACTERS_UPDATED'], $player->getPlayerName()) . '<br /><br />' . sprintf($this->user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a>');
					unset($player);
					trigger_error($message);
```

- [ ] **Step 4: Fire `character_delete` on successful delete**

Find (~line 384-393):

```php
						if (confirm_box(true))
						{
							$deleteplayer = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry);
							$deleteplayer->player_id = $this->request->variable('del_player_id', 0);
							$deleteplayer->Getplayer();
							$deleteplayer->Deleteplayer();

							$success_message = sprintf($this->user->lang['ADMIN_DELETE_PLAYERS_SUCCESS'], $deleteplayer->getPlayerName());
							trigger_error($success_message);
						}
```

Replace with:

```php
						if (confirm_box(true))
						{
							$deleteplayer = new player($this->db, $this->config, $this->bbguild_cache, $this->user, $this->bbguild_ext_manager, $this->bbguild_log, $this->bbguild_util, $this->bb_players_table, $this->bb_ranks_table, $this->bb_classes_table, $this->bb_races_table, $this->bb_language_table, $this->bb_guild_table, $this->bb_factions_table, $this->bb_games_table, $this->bbguild_game_registry);
							$deleteplayer->player_id = $this->request->variable('del_player_id', 0);
							$deleteplayer->Getplayer();
							$deleted_player_id = $deleteplayer->player_id;
							$deleted_guild_id = (int) $deleteplayer->getPlayerGuildId();
							$deleted_user_id = (int) $deleteplayer->getPhpbbUserId();
							$deleteplayer->Deleteplayer();

							/**
							 * Fired when a character is deleted from the UCP.
							 *
							 * @event avathar.bbguild.character_delete
							 * @var int player_id The character that was deleted
							 * @var int guild_id  The guild the character belonged to
							 * @var int user_id   The forum user account it was linked to (0 if unclaimed)
							 * @since 2.3.0
							 */
							extract($dispatcher->trigger_event('avathar.bbguild.character_delete', [
								'player_id' => $deleted_player_id,
								'guild_id'  => $deleted_guild_id,
								'user_id'   => $deleted_user_id,
							]));

							$success_message = sprintf($this->user->lang['ADMIN_DELETE_PLAYERS_SUCCESS'], $deleteplayer->getPlayerName());
							trigger_error($success_message);
						}
```

- [ ] **Step 5: Fire `character_add` and `character_edit`**

Find the add-success branch (~line 454-463):

```php
						if ($newplayer->player_id > 0)
						{
							// record added.
							$newplayer->setPlayerComment(sprintf($this->user->lang['ADMIN_ADD_PLAYER_SUCCESS'], ucwords($newplayer->getPlayerName()), date('F j, Y, g:i a')));
							$newplayer->Armory_getplayer($this->get_game_provider($newplayer->game_id));
							$newplayer->Updateplayer($newplayer);
							meta_refresh(1, $this->u_action . '&amp;player_id=' . $newplayer->player_id);
							$success_message = sprintf($this->user->lang['ADMIN_ADD_PLAYER_SUCCESS'], ucwords($newplayer->getPlayerName()), date('F j, Y, g:i a'));
							trigger_error($success_message, E_USER_NOTICE);
						}
```

Replace with:

```php
						if ($newplayer->player_id > 0)
						{
							// record added.
							$newplayer->setPlayerComment(sprintf($this->user->lang['ADMIN_ADD_PLAYER_SUCCESS'], ucwords($newplayer->getPlayerName()), date('F j, Y, g:i a')));
							$newplayer->Armory_getplayer($this->get_game_provider($newplayer->game_id));
							$newplayer->Updateplayer($newplayer);

							/**
							 * Fired when a character is successfully added via the UCP.
							 *
							 * @event avathar.bbguild.character_add
							 * @var int player_id The newly created character's id
							 * @var int guild_id  The guild the character belongs to
							 * @var int user_id   The forum user who added it
							 * @since 2.3.0
							 */
							extract($dispatcher->trigger_event('avathar.bbguild.character_add', [
								'player_id' => $newplayer->player_id,
								'guild_id'  => (int) $newplayer->getPlayerGuildId(),
								'user_id'   => (int) $newplayer->getPhpbbUserId(),
							]));

							meta_refresh(1, $this->u_action . '&amp;player_id=' . $newplayer->player_id);
							$success_message = sprintf($this->user->lang['ADMIN_ADD_PLAYER_SUCCESS'], ucwords($newplayer->getPlayerName()), date('F j, Y, g:i a'));
							trigger_error($success_message, E_USER_NOTICE);
						}
```

Find the update branch (~line 472-491):

```php
					if ($update)
					{
						//update
						if (!check_form_key('characteradd'))
						{
							trigger_error('FORM_INVALID');
						}

						// check if user can update character
						if (!$this->auth->acl_get('u_charupdate') )
						{
							trigger_error($this->user->lang['NOUCPUPDCHARS']);
						}
						$updateplayer = $this->UpdateMyCharacter($player_id);

						meta_refresh(1, $this->u_action . '&amp;player_id=' . $updateplayer->player_id);
```

Replace the `$updateplayer = ...` line and what follows it with:

```php
						$updateplayer = $this->UpdateMyCharacter($player_id);

						/**
						 * Fired when a character's details are updated via the UCP.
						 *
						 * @event avathar.bbguild.character_edit
						 * @var int player_id The character that was updated
						 * @var int guild_id  The guild the character belongs to
						 * @var int user_id   The forum user who owns it
						 * @since 2.3.0
						 */
						extract($dispatcher->trigger_event('avathar.bbguild.character_edit', [
							'player_id' => $updateplayer->player_id,
							'guild_id'  => (int) $updateplayer->getPlayerGuildId(),
							'user_id'   => (int) $updateplayer->getPhpbbUserId(),
						]));

						meta_refresh(1, $this->u_action . '&amp;player_id=' . $updateplayer->player_id);
```

- [ ] **Step 6: Manual verification**

1. Sync `ucp/bbguild_module.php` into `forum/ext/avathar/bbguild/ucp/bbguild_module.php`, then `php bin/phpbbcli.php cache:purge`.
2. `php -l ucp/bbguild_module.php` — must report no syntax errors.
3. In the running board, as a user with `u_charclaim`/`u_charadd`/`u_charupdate`/`u_chardelete`: claim a character, unclaim it, add a new character, edit it, delete it. Each action must still complete exactly as before (same success messages, same redirects) — the event calls are additive and must not change any existing behavior or throw.
4. Temporarily add a `var_dump` inside one `trigger_event` payload build line (e.g. print `$player_id` before firing `character_add`) to confirm the block is actually reached during a real add — then remove the var_dump. (No listener exists yet to observe the event any other way.)

- [ ] **Step 7: Commit**

```bash
git add ucp/bbguild_module.php
git commit -m "feat(events): add character lifecycle events to UCP module [#370]"
```

---

## Task 2: `character_sync_completed` event (`cron/task/character_sync.php`)

**⚠️ Coordination required before Step 1:** this task adds a new constructor argument to the `avathar.bbguild.cron.task.character_sync` service in `config/services.yml`, which forum-f2 has claimed (for an unrelated, additive change to a *different* service block in the same file, `avathar.bbguild.controller`). Before starting, send: *"CLAIM config/services.yml (one line, `avathar.bbguild.cron.task.character_sync` block only, adding `'@dispatcher'`) — for #370 Task 2. Object if this conflicts."* Wait for an explicit no-objection or a negotiated ordering before editing the file.

**Files:**
- Modify: `cron/task/character_sync.php:100-135` (constructor — new `$dispatcher` param), `cron/task/character_sync.php:178-221` (`run()` — new event fire)
- Modify: `config/services.yml` (one new argument line — see coordination note above)
- Test: `tests/cron/character_sync_test.php` (update `make_task()` helper for the new constructor arg; add one new test)

**Interfaces:**
- Consumes: `\phpbb\event\dispatcher_interface` (new constructor param, position: append after `util $util` and before the table-name string params, to keep all typed-object params before the trailing string params — matching this constructor's existing convention)
- Produces: `avathar.bbguild.character_sync_completed`, firing `player_id` (int), `game_id` (string), `success` (bool)

- [ ] **Step 1: Add the failing test first**

In `tests/cron/character_sync_test.php`, update `make_task()` to accept and pass a dispatcher mock:

```php
	private function make_task(character_sync_registry $registry, $dispatcher = null): character_sync
	{
		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);
		$user = $this->createMock(\phpbb\user::class);
		$user->lang = [
			'REGIONEU'  => 'Europe',
			'REGIONKR'  => 'Korea',
			'REGIONSEA' => 'South-East Asia',
			'REGIONTW'  => 'Taiwan',
			'REGIONUS'  => 'United States',
			'CLOSED'    => 'Closed',
			'OPEN'      => 'Open',
		];
		$ext_manager = $this->getMockBuilder(\phpbb\extension\manager::class)
			->disableOriginalConstructor()
			->getMock();
		$util = $this->getMockBuilder(\avathar\bbguild\model\admin\util::class)
			->disableOriginalConstructor()
			->getMock();
		$dispatcher = $dispatcher ?? $this->createMock(\phpbb\event\dispatcher_interface::class);
		$dispatcher->method('trigger_event')->willReturnArgument(1);

		$task = new character_sync(
			$this->config,
			$registry,
			$this->db,
			$cache,
			$user,
			$ext_manager,
			$this->bbguild_log,
			$util,
			$dispatcher,
			'bb_players', 'bb_ranks', 'bb_classes', 'bb_races', 'bb_language', 'bb_guild', 'bb_factions', 'bb_games'
		);
		$task->set_name('avathar.bbguild.cron.task.character_sync');

		return $task;
	}
```

Add a new test method:

```php
	public function test_run_dispatches_character_sync_completed_event_on_success(): void
	{
		$registry = new character_sync_registry([
			$this->make_handler('wow', fn ($row) => true),
		]);

		$dispatcher = $this->createMock(\phpbb\event\dispatcher_interface::class);
		$dispatcher->expects($this->once())
			->method('trigger_event')
			->with(
				'avathar.bbguild.character_sync_completed',
				$this->callback(function ($vars) {
					return $vars['player_id'] === 1 && $vars['game_id'] === 'wow' && $vars['success'] === true;
				})
			)
			->willReturnArgument(1);

		$task = $this->make_task($registry, $dispatcher);

		$get_update = $this->stub_db_for_run(['player_id' => 1, 'game_id' => 'wow', 'player_name' => 'Alice']);
		$task->run();

		[$update_calls] = $get_update();
		$this->assertSame(1, $update_calls);
	}
```

- [ ] **Step 2: Run the test suite to confirm it fails**

Run: `vendor/bin/phpunit tests/cron/character_sync_test.php`
Expected: every existing test in this file now FAILS with a constructor argument-count error (the `character_sync` constructor doesn't accept a dispatcher yet) — this confirms the test file is exercising the real constructor signature.

- [ ] **Step 3: Add the `$dispatcher` constructor param**

In `cron/task/character_sync.php`, add the import:

```php
use phpbb\event\dispatcher_interface;
```

Add the property (after `protected $util;`, ~line 58):

```php
	/** @var dispatcher_interface */
	protected $dispatcher;
```

Update the constructor signature (insert after `util $util,`, before the table-name string params):

```php
	public function __construct(
		\phpbb\config\config $config,
		character_sync_registry $registry,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\user $user,
		\phpbb\extension\manager $ext_manager,
		log $bbguild_log,
		util $util,
		dispatcher_interface $dispatcher,
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
		$this->db = $db;
		$this->cache = $cache;
		$this->user = $user;
		$this->ext_manager = $ext_manager;
		$this->util = $util;
		$this->dispatcher = $dispatcher;
		$this->bb_players_table = $bb_players_table;
		$this->bb_ranks_table = $bb_ranks_table;
		$this->bb_classes_table = $bb_classes_table;
		$this->bb_races_table = $bb_races_table;
		$this->bb_language_table = $bb_language_table;
		$this->bb_guild_table = $bb_guild_table;
		$this->bb_factions_table = $bb_factions_table;
		$this->bb_games_table = $bb_games_table;
	}
```

- [ ] **Step 4: Fire the event in `run()`**

Find (~line 191-218):

```php
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
				catch (\Throwable $e)
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

			$player->update_last_synced((int) $player_row['player_id']);
		}
```

Replace with:

```php
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
				catch (\Throwable $e)
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

			/**
			 * Fired after a game-API sync attempt finishes for one character,
			 * whether it succeeded or failed.
			 *
			 * @event avathar.bbguild.character_sync_completed
			 * @var int    player_id The character that was synced
			 * @var string game_id   The game the character belongs to
			 * @var bool   success   Whether the sync succeeded
			 * @since 2.3.0
			 */
			extract($this->dispatcher->trigger_event('avathar.bbguild.character_sync_completed', [
				'player_id' => (int) $player_row['player_id'],
				'game_id'   => (string) $player_row['game_id'],
				'success'   => $success,
			]));

			$player->update_last_synced((int) $player_row['player_id']);
		}
```

- [ ] **Step 5: Add `'@dispatcher'` to `config/services.yml`**

Only after the claim-check from the coordination note above has cleared. Find the `avathar.bbguild.cron.task.character_sync` service block and add `'@dispatcher'` as a new argument, in the same position as the constructor param (after `'@avathar.bbguild.util'`, before the table-name parameters):

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
            - '@dispatcher'
            - '%avathar.bbguild.tables.bb_players%'
            - '%avathar.bbguild.tables.bb_ranks%'
            - '%avathar.bbguild.tables.bb_classes%'
            - '%avathar.bbguild.tables.bb_races%'
            - '%avathar.bbguild.tables.bb_language%'
            - '%avathar.bbguild.tables.bb_guild%'
            - '%avathar.bbguild.tables.bb_factions%'
            - '%avathar.bbguild.tables.bb_games%'
```

(Read the actual current block first with `grep -n -A20 "avathar.bbguild.cron.task.character_sync:" config/services.yml` and insert `'@dispatcher'` at the matching position — the exact surrounding argument list may have shifted since this plan was written if forum-f2's edit to the neighboring `avathar.bbguild.controller` block landed first.)

- [ ] **Step 6: Run the tests to confirm they pass**

Run: `vendor/bin/phpunit tests/cron/character_sync_test.php`
Expected: all tests PASS, including the new `test_run_dispatches_character_sync_completed_event_on_success`.

- [ ] **Step 7: Lint and sync**

```bash
php -l cron/task/character_sync.php
python3 -c "import yaml; yaml.safe_load(open('config/services.yml'))"
```

Sync both files to `forum/ext/avathar/bbguild/`, then `php bin/phpbbcli.php cache:purge`. Trigger the cron task manually (or wait for phpBB's web-cron) with at least one game plugin's sync handler registered, and confirm no fatal errors in the board.

- [ ] **Step 8: Commit and release the services.yml claim**

```bash
git add cron/task/character_sync.php config/services.yml tests/cron/character_sync_test.php
git commit -m "feat(events): add character_sync_completed event [#370]"
```

Message forum-f2: `"RELEASE config/services.yml (character_sync block) — committed as <sha>."`

---

## Task 3: Roster and portal module render events

**Files:**
- Modify: `portal/modules/roster.php:277-338` (`display_listing()`), `portal/modules/roster.php:340+` (`display_grid()` — same pattern, see Step 3)
- Modify: `portal/portal_renderer.php:79-102` (`render()`)
- Modify: `config/portal_services.yml` (both services already exist here — add `'@dispatcher'` to each; **not** claimed by forum-f2, no coordination needed)
- Test: `tests/portal/portal_renderer_test.php` (update `get_renderer()` for the new constructor arg; add one new test), new test file `tests/portal/modules/roster_test.php`

**Interfaces:**
- Consumes: `\phpbb\event\dispatcher_interface` (new constructor param on both `roster` and `portal_renderer`, appended as the last argument on each)
- Produces: `avathar.bbguild.roster_display` (per character row: `player_id` int, `game_id` string, `guild_id` int), `avathar.bbguild.portal_module_display` (`guild_id` int, `row` array — the full portal-module DB row — write-back allowed, matching the existing `modify_tpl_ary`-style convention)

- [ ] **Step 1: Update the existing `portal_renderer_test.php` for the new constructor arg (failing first)**

In `tests/portal/portal_renderer_test.php`, update `get_renderer()`:

```php
	protected function get_renderer()
	{
		$portal_columns = $this->createMock(\avathar\bbguild\portal\columns::class);
		$module_helper = $this->createMock(\avathar\bbguild\portal\module_helper::class);
		$this->database_handler = $this->createMock(\avathar\bbguild\portal\modules\database_handler::class);
		$config = new \phpbb\config\config([]);
		$this->template = $this->createMock(\phpbb\template\template::class);
		$user = $this->createMock(\phpbb\user::class);
		$this->helper = $this->createMock(\phpbb\controller\helper::class);
		$this->dispatcher = $this->createMock(\phpbb\event\dispatcher_interface::class);
		$this->dispatcher->method('trigger_event')->willReturnArgument(1);

		$this->block_calls = [];
		$this->template->method('assign_block_vars')
			->willReturnCallback(function ($blockname, $vars) {
				$this->block_calls[] = [$blockname, $vars];
			});
		$this->template->method('assign_vars')->willReturn(null);

		$this->helper->method('route')->willReturn('/guild/welcome/5');
		$this->database_handler->method('get_modules')->willReturn([]);

		return new \avathar\bbguild\portal\portal_renderer(
			$portal_columns, $module_helper, $this->database_handler, $config, $this->template, $user, $this->helper, $this->dispatcher
		);
	}
```

Add the property declaration alongside the others at the top of the class:

```php
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $dispatcher;
```

Add a new test:

```php
	public function test_render_fires_portal_module_display_per_module()
	{
		$renderer = $this->get_renderer();
		$this->database_handler->method('get_tabs')->willReturn([
			['tab_id' => 1, 'tab_name' => 'Overview', 'tab_slug' => 'welcome'],
		]);
		$this->database_handler->method('get_modules')->willReturn([
			['module_id' => 7, 'module_type' => 'roster'],
		]);

		$module = $this->createMock(\avathar\bbguild\portal\modules\module_interface::class);
		$module_helper = $this->getMockBuilder(\avathar\bbguild\portal\module_helper::class)
			->disableOriginalConstructor()
			->getMock();

		// Rebuild the renderer with a real module_helper mock wired to return
		// our $module, since get_renderer() uses a bare createMock() whose
		// get_portal_module() returns null by default (making the loop skip
		// via the `if (!$module) { continue; }` guard).
		$portal_columns = $this->createMock(\avathar\bbguild\portal\columns::class);
		$config = new \phpbb\config\config([]);
		$module_helper->method('get_portal_module')->willReturn($module);
		$module_helper->method('load_module_language')->willReturn(null);
		$module_helper->method('assign_module_vars')->willReturn(null);
		$module->method('get_template_center')->willReturn('some_template.html');

		$this->dispatcher->expects($this->once())
			->method('trigger_event')
			->with(
				'avathar.bbguild.portal_module_display',
				$this->callback(function ($vars) {
					return $vars['guild_id'] === 5 && $vars['row']['module_id'] === 7;
				})
			)
			->willReturnArgument(1);

		$renderer = new \avathar\bbguild\portal\portal_renderer(
			$portal_columns, $module_helper, $this->database_handler, $config, $this->template, $this->createMock(\phpbb\user::class), $this->helper, $this->dispatcher
		);

		$renderer->render(5, '');
	}
```

- [ ] **Step 2: Run the test suite to confirm it fails**

Run: `vendor/bin/phpunit tests/portal/portal_renderer_test.php`
Expected: all 6 tests (5 existing + 1 new) FAIL — the existing 5 fail on constructor argument count, the new one fails because `portal_module_display` is never fired yet.

- [ ] **Step 3: Add the `$dispatcher` param and event to `portal_renderer.php`**

Add the import and property:

```php
use phpbb\event\dispatcher_interface;
```

```php
	protected dispatcher_interface $dispatcher;
```

Update the constructor:

```php
	public function __construct(
		columns $portal_columns,
		module_helper $module_helper,
		database_handler $database_handler,
		config $config,
		template $template,
		user $user,
		helper $helper,
		dispatcher_interface $dispatcher
	)
	{
		$this->portal_columns = $portal_columns;
		$this->module_helper = $module_helper;
		$this->database_handler = $database_handler;
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->dispatcher = $dispatcher;
	}
```

In `render()`, find (~line 94-101):

```php
			// Get template based on column type
			$template_module = $this->get_module_template($row, $module);
			if (empty($template_module))
			{
				continue;
			}

			// Assign to template block
			$this->module_helper->assign_module_vars($row, $template_module);
```

Replace with:

```php
			// Get template based on column type
			$template_module = $this->get_module_template($row, $module);
			if (empty($template_module))
			{
				continue;
			}

			/**
			 * Fired for each portal module as it renders. Allows a sibling
			 * extension to observe or override which template is used for a
			 * given module row.
			 *
			 * @event avathar.bbguild.portal_module_display
			 * @var int   guild_id       The guild whose portal is rendering
			 * @var array row            The portal module's database row (module_id, module_type, etc.)
			 * @var mixed template_module The resolved template file/name for this module — writable
			 * @since 2.3.0
			 */
			$vars = ['guild_id', 'row', 'template_module'];
			extract($this->dispatcher->trigger_event('avathar.bbguild.portal_module_display', compact($vars)));

			// Assign to template block
			$this->module_helper->assign_module_vars($row, $template_module);
```

- [ ] **Step 4: Add `'@dispatcher'` to `portal_renderer`'s definition in `config/portal_services.yml`**

Find:

```yaml
    avathar.bbguild.portal.renderer:
        class: avathar\bbguild\portal\portal_renderer
        arguments:
            - '@avathar.bbguild.portal.columns'
            - '@avathar.bbguild.portal.module_helper'
            - '@avathar.bbguild.portal.modules.database_handler'
            - '@config'
            - '@template'
            - '@user'
            - '@controller.helper'
```

(Check the exact service id with `grep -n -B3 "class: avathar..portal.portal_renderer" config/portal_services.yml` first — use whatever id is actually there.) Add one line:

```yaml
            - '@controller.helper'
            - '@dispatcher'
```

- [ ] **Step 5: Run the portal_renderer tests again to confirm they pass**

Run: `vendor/bin/phpunit tests/portal/portal_renderer_test.php`
Expected: all 6 tests PASS.

- [ ] **Step 6: Write the failing test for `roster_display`**

Create `tests/portal/modules/roster_test.php`:

```php
<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild\tests\portal\modules;

use avathar\bbguild\portal\modules\roster;
use PHPUnit\Framework\TestCase;

class roster_test extends TestCase
{
	private function make_roster($dispatcher): roster
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$template = $this->createMock(\phpbb\template\template::class);
		$user = $this->createMock(\phpbb\user::class);
		$user->lang = ['MEMBERS' => 'Members'];
		$config = new \phpbb\config\config(['bbguild_user_llimit' => 0]);
		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);
		$ext_manager = $this->getMockBuilder(\phpbb\extension\manager::class)
			->disableOriginalConstructor()
			->getMock();
		$bbguild_log = $this->getMockBuilder(\avathar\bbguild\model\admin\log::class)
			->disableOriginalConstructor()
			->getMock();
		$bbguild_util = $this->getMockBuilder(\avathar\bbguild\model\admin\util::class)
			->disableOriginalConstructor()
			->getMock();
		$pagination = $this->getMockBuilder(\phpbb\pagination::class)
			->disableOriginalConstructor()
			->getMock();
		$request = $this->createMock(\phpbb\request\request::class);
		$path_helper = $this->getMockBuilder(\phpbb\path_helper::class)
			->disableOriginalConstructor()
			->getMock();
		$helper = $this->createMock(\phpbb\controller\helper::class);
		$helper->method('route')->willReturn('/guild/1/player/1');
		$game_registry = $this->getMockBuilder(\avathar\bbguild\model\games\game_registry::class)
			->disableOriginalConstructor()
			->getMock();
		$asset_resolver = $this->getMockBuilder(\avathar\bbguild\model\admin\asset_url_resolver::class)
			->disableOriginalConstructor()
			->getMock();

		return new roster(
			$db, $template, $user, $config, $cache, $ext_manager, $bbguild_log, $bbguild_util,
			$pagination, $request, $path_helper, $helper, $game_registry, $asset_resolver, $dispatcher,
			'bb_players', 'bb_ranks', 'bb_classes', 'bb_races', 'bb_language', 'bb_guild', 'bb_factions', 'bb_games'
		);
	}

	public function test_display_listing_fires_roster_display_per_character(): void
	{
		$dispatcher = $this->createMock(\phpbb\event\dispatcher_interface::class);
		$dispatcher->expects($this->exactly(2))
			->method('trigger_event')
			->with(
				'avathar.bbguild.roster_display',
				$this->callback(fn ($vars) => in_array($vars['player_id'], [10, 11], true))
			)
			->willReturnArgument(1);

		$roster = $this->make_roster($dispatcher);

		$characters = [
			0 => [
				['player_id' => 10, 'game_id' => 'wow', 'colorcode' => '#fff', 'class_name' => 'Warrior', 'player_name' => 'Alice', 'race_name' => 'Human', 'player_rank' => 'Member', 'player_level' => 60, 'player_armory_url' => '', 'username' => 'alice', 'player_achiev' => 0, 'class_image' => 'x.png', 'race_image' => 'y.png'],
				['player_id' => 11, 'game_id' => 'wow', 'colorcode' => '#fff', 'class_name' => 'Mage', 'player_name' => 'Bob', 'race_name' => 'Gnome', 'player_rank' => 'Member', 'player_level' => 60, 'player_armory_url' => '', 'username' => 'bob', 'player_achiev' => 0, 'class_image' => 'x.png', 'race_image' => 'y.png'],
			],
			2 => 2,
		];

		$reflection = new \ReflectionMethod($roster, 'display_listing');
		$reflection->setAccessible(true);
		$reflection->invoke($roster, $characters, 'images/', '/guild/1/roster', 0, [], false);
	}
}
```

- [ ] **Step 7: Run it to confirm it fails**

Run: `vendor/bin/phpunit tests/portal/modules/roster_test.php`
Expected: FAIL — `avathar.bbguild.roster_display` is never fired yet (and the constructor doesn't accept a dispatcher arg yet).

- [ ] **Step 8: Add the `$dispatcher` param and event to `roster.php`**

Add the import:

```php
use phpbb\event\dispatcher_interface;
```

Add the property (after `protected asset_url_resolver $asset_resolver;`):

```php
	protected dispatcher_interface $dispatcher;
```

Update the constructor — insert `dispatcher_interface $dispatcher,` right after `asset_url_resolver $asset_resolver,` and before `string $players_table,`, and add `$this->dispatcher = $dispatcher;` after `$this->asset_resolver = $asset_resolver;` in the body.

In `display_listing()`, find (~line 279-303):

```php
		foreach ($characters[0] as $char)
		{
			$spec = $this->resolve_spec($char, $spec_lookup, $ext_path_images);
			$this->template->assign_block_vars('portal_roster_row', [
```

Replace with:

```php
		foreach ($characters[0] as $char)
		{
			/**
			 * Fired for each character row as the roster module renders.
			 *
			 * @event avathar.bbguild.roster_display
			 * @var int    player_id The character being displayed
			 * @var string game_id   The game the character belongs to
			 * @var int    guild_id  The guild whose roster is rendering
			 * @since 2.3.0
			 */
			$player_id = (int) $char['player_id'];
			$game_id = (string) $char['game_id'];
			$guild_id = (int) $this->guild_id;
			$vars = ['player_id', 'game_id', 'guild_id'];
			extract($this->dispatcher->trigger_event('avathar.bbguild.roster_display', compact($vars)));

			$spec = $this->resolve_spec($char, $spec_lookup, $ext_path_images);
			$this->template->assign_block_vars('portal_roster_row', [
```

- [ ] **Step 9: Apply the same pattern to `display_grid()`**

Find (~line 370-374):

```php
					foreach ($characters[0] as $char)
					{
						if ($char['player_class_id'] == $classid)
						{
							$grid_spec = $this->resolve_spec($char, $spec_lookup, $ext_path_images);
							$this->template->assign_block_vars('class.players_row', [
```

Replace with:

```php
					foreach ($characters[0] as $char)
					{
						if ($char['player_class_id'] == $classid)
						{
							/**
							 * Fired for each character row as the roster module renders.
							 *
							 * @event avathar.bbguild.roster_display
							 * @var int    player_id The character being displayed
							 * @var string game_id   The game the character belongs to
							 * @var int    guild_id  The guild whose roster is rendering
							 * @since 2.3.0
							 */
							$player_id = (int) $char['player_id'];
							$game_id = (string) $char['game_id'];
							$guild_id = (int) $this->guild_id;
							$vars = ['player_id', 'game_id', 'guild_id'];
							extract($this->dispatcher->trigger_event('avathar.bbguild.roster_display', compact($vars)));

							$grid_spec = $this->resolve_spec($char, $spec_lookup, $ext_path_images);
							$this->template->assign_block_vars('class.players_row', [
```

(This duplicates the Step 8 event block verbatim — `display_listing()` and `display_grid()` are two independent rendering paths with no shared per-character loop to factor the event into without a larger, unrelated refactor of this file's structure. Firing the same event from both call sites, each fully documented at its own site, matches this codebase's existing convention of per-call-site docblocks rather than a single shared comment.)

- [ ] **Step 10: Add `'@dispatcher'` to `roster`'s definition in `config/portal_services.yml`**

Find the `avathar.bbguild.portal.module.roster` block (confirmed at time of writing this plan to end with `- '@avathar.bbguild.asset_url_resolver'` before the table-name parameters) and insert `- '@dispatcher'` immediately after it:

```yaml
    avathar.bbguild.portal.module.roster:
        class: avathar\bbguild\portal\modules\roster
        arguments:
            - '@dbal.conn'
            - '@template'
            - '@user'
            - '@config'
            - '@cache.driver'
            - '@ext.manager'
            - '@avathar.bbguild.log'
            - '@avathar.bbguild.util'
            - '@pagination'
            - '@request'
            - '@path_helper'
            - '@controller.helper'
            - '@avathar.bbguild.game_registry'
            - '@avathar.bbguild.asset_url_resolver'
            - '@dispatcher'
            - '%avathar.bbguild.tables.bb_players%'
            # ...rest of the table-name parameters unchanged
```

- [ ] **Step 11: Run the roster test to confirm it passes**

Run: `vendor/bin/phpunit tests/portal/modules/roster_test.php`
Expected: PASS.

- [ ] **Step 12: Lint, sync, and manually verify**

```bash
php -l portal/modules/roster.php portal/portal_renderer.php
python3 -c "import yaml; yaml.safe_load(open('config/portal_services.yml'))"
```

Sync all three changed files to the board, purge cache, and load a guild's roster page (both listing and grid layout toggles) — confirm it renders exactly as before with no fatal errors.

- [ ] **Step 13: Commit**

```bash
git add portal/modules/roster.php portal/portal_renderer.php config/portal_services.yml tests/portal/portal_renderer_test.php tests/portal/modules/roster_test.php
git commit -m "feat(events): add roster_display and portal_module_display events [#370]"
```

---

## Task 4: Recruitment and MOTD events (`controller/admin_guild.php`)

**Files:**
- Modify: `controller/admin_guild.php:969-1025` (`show_editguildrecruitment()` — 3 new events), `controller/admin_guild.php:453-531` (`UpdateGuild()` — 1 new event)
- Test: none — see rationale below

**Interfaces:**
- Consumes: `$this->dispatcher` (already injected — this controller already fires 4 existing events, no constructor/services.yml change needed)
- Produces: `avathar.bbguild.recruitment_posted` (`recruit_id` int, `guild_id` int), `avathar.bbguild.recruitment_updated` (`recruit_id` int, `guild_id` int), `avathar.bbguild.recruitment_deleted` (`recruit_id` int, `guild_id` int), `avathar.bbguild.motd_updated` (`guild_id` int)

**No automated test for this task.** `admin_guild.php` has no existing test file, and both target methods are `public`/`private` procedural ACP form-handlers reading directly from `$this->request`/`$this->db` with no seams for isolated construction (22+ constructor dependencies, several built via `new` internally) — the same shape of problem as Task 1's `ucp/bbguild_module.php`, and for the same reason: building an isolation harness for this class is a separate undertaking, not part of "add 4 trigger_event calls." Verification is manual (Step 4).

- [ ] **Step 1: Fire `recruitment_deleted`, `recruitment_posted`, `recruitment_updated`**

In `show_editguildrecruitment()`, find the delete branch (~line 981-991):

```php
		if ($action === 'delete')
		{
			$recruit_id = $this->request->variable('id', 0);
			if ($recruit_id)
			{
				$sql = 'DELETE FROM ' . $this->bb_recruit_table . ' WHERE id = ' . (int) $recruit_id;
				$this->db->sql_query($sql);
				$success_message = sprintf($this->user->lang['ADMIN_DELETE_RECRUITMENT_SUCCESS'], $recruit_id);
				trigger_error($success_message . $this->link, E_USER_NOTICE);
			}
		}
```

Replace with:

```php
		if ($action === 'delete')
		{
			$recruit_id = $this->request->variable('id', 0);
			if ($recruit_id)
			{
				$sql = 'DELETE FROM ' . $this->bb_recruit_table . ' WHERE id = ' . (int) $recruit_id;
				$this->db->sql_query($sql);

				/**
				 * Fired when a recruitment posting is deleted.
				 *
				 * @event avathar.bbguild.recruitment_deleted
				 * @var int recruit_id The deleted recruitment posting's id
				 * @var int guild_id   The guild it belonged to
				 * @since 2.3.0
				 */
				extract($this->dispatcher->trigger_event('avathar.bbguild.recruitment_deleted', [
					'recruit_id' => (int) $recruit_id,
					'guild_id'   => $guild_id,
				]));

				$success_message = sprintf($this->user->lang['ADMIN_DELETE_RECRUITMENT_SUCCESS'], $recruit_id);
				trigger_error($success_message . $this->link, E_USER_NOTICE);
			}
		}
```

Find the add/update branches (~line 1006-1024):

```php
			if ($add)
			{
				$recruit_data['applicants'] = 0;
				$recruit_data['applytemplate_id'] = 0;
				$sql = 'INSERT INTO ' . $this->bb_recruit_table . ' ' . $this->db->sql_build_array('INSERT', $recruit_data);
				$this->db->sql_query($sql);
				$new_id = $this->db->sql_nextid();
				$success_message = sprintf($this->user->lang['ADMIN_ADD_RECRUITMENT_SUCCESS'], $new_id);
				trigger_error($success_message . $this->link, E_USER_NOTICE);
			}
			else if ($update)
			{
				$recruit_id = $this->request->variable('hidden_recruit_id', 0);
				$recruit_data['applicants'] = $this->request->variable('applicants', 0);
				$sql = 'UPDATE ' . $this->bb_recruit_table . ' SET ' . $this->db->sql_build_array('UPDATE', $recruit_data) . ' WHERE id = ' . (int) $recruit_id;
				$this->db->sql_query($sql);
				$success_message = sprintf($this->user->lang['ADMIN_UPDATE_RECRUITMENT_SUCCESS'], $recruit_id);
				trigger_error($success_message . $this->link, E_USER_NOTICE);
			}
```

Replace with:

```php
			if ($add)
			{
				$recruit_data['applicants'] = 0;
				$recruit_data['applytemplate_id'] = 0;
				$sql = 'INSERT INTO ' . $this->bb_recruit_table . ' ' . $this->db->sql_build_array('INSERT', $recruit_data);
				$this->db->sql_query($sql);
				$new_id = $this->db->sql_nextid();

				/**
				 * Fired when a new recruitment posting is created.
				 *
				 * @event avathar.bbguild.recruitment_posted
				 * @var int recruit_id The newly created recruitment posting's id
				 * @var int guild_id   The guild it belongs to
				 * @since 2.3.0
				 */
				extract($this->dispatcher->trigger_event('avathar.bbguild.recruitment_posted', [
					'recruit_id' => (int) $new_id,
					'guild_id'   => $guild_id,
				]));

				$success_message = sprintf($this->user->lang['ADMIN_ADD_RECRUITMENT_SUCCESS'], $new_id);
				trigger_error($success_message . $this->link, E_USER_NOTICE);
			}
			else if ($update)
			{
				$recruit_id = $this->request->variable('hidden_recruit_id', 0);
				$recruit_data['applicants'] = $this->request->variable('applicants', 0);
				$sql = 'UPDATE ' . $this->bb_recruit_table . ' SET ' . $this->db->sql_build_array('UPDATE', $recruit_data) . ' WHERE id = ' . (int) $recruit_id;
				$this->db->sql_query($sql);

				/**
				 * Fired when an existing recruitment posting is updated.
				 *
				 * @event avathar.bbguild.recruitment_updated
				 * @var int recruit_id The updated recruitment posting's id
				 * @var int guild_id   The guild it belongs to
				 * @since 2.3.0
				 */
				extract($this->dispatcher->trigger_event('avathar.bbguild.recruitment_updated', [
					'recruit_id' => (int) $recruit_id,
					'guild_id'   => $guild_id,
				]));

				$success_message = sprintf($this->user->lang['ADMIN_UPDATE_RECRUITMENT_SUCCESS'], $recruit_id);
				trigger_error($success_message . $this->link, E_USER_NOTICE);
			}
```

- [ ] **Step 2: Fire `motd_updated`**

In `UpdateGuild()`, find (~line 512-531):

```php
		if ($motd_row)
		{
			$sql = 'UPDATE ' . $this->bb_motd_table . ' SET ' . $this->db->sql_build_array('UPDATE', [
				'motd_msg'        => $welcometext,
				'motd_timestamp'  => time(),
				'bbcode_bitfield' => $bitfield,
				'bbcode_uid'      => $uid,
			]) . ' WHERE guild_id = ' . $guild_id;
		}
		else
		{
			$sql = 'INSERT INTO ' . $this->bb_motd_table . ' ' . $this->db->sql_build_array('INSERT', [
				'guild_id'        => $guild_id,
				'motd_msg'        => $welcometext,
				'motd_timestamp'  => time(),
				'bbcode_bitfield' => $bitfield,
				'bbcode_uid'      => $uid,
			]);
		}
		$this->db->sql_query($sql);
```

Replace the final line with:

```php
		$this->db->sql_query($sql);

		/**
		 * Fired when a guild's Message of the Day is saved.
		 *
		 * @event avathar.bbguild.motd_updated
		 * @var int guild_id The guild whose MOTD was updated
		 * @since 2.3.0
		 */
		extract($this->dispatcher->trigger_event('avathar.bbguild.motd_updated', [
			'guild_id' => $guild_id,
		]));
```

- [ ] **Step 3: Lint**

```bash
php -l controller/admin_guild.php
```

- [ ] **Step 4: Manual verification**

Sync to the board, purge cache. As an ACP admin: post a new recruitment entry, edit it, delete it, and save a guild's MOTD text. Confirm all four flows show the same success messages as before and no fatal errors occur.

- [ ] **Step 5: Commit**

```bash
git add controller/admin_guild.php
git commit -m "feat(events): add recruitment and MOTD events [#370]"
```

---

## Task 5: `contrib/Events.md` catalogue + README link

**Files:**
- Create: `contrib/Events.md`
- Modify: `README.md` (one line)

**Interfaces:**
- Consumes: nothing (documentation only) — but depends on Tasks 1-4 being complete, since it documents their final event names/argument names verbatim
- Produces: the discoverable catalogue itself

- [ ] **Step 1: Write `contrib/Events.md`**

Copy the structure and preamble from `../recenttopics/contrib/Events.md` sections "What are phpBB events?" and "What is the DI container?" verbatim (same educational text — this is intentional, matching the user's established convention across extensions), then write bbGuild-specific sections:

```markdown
# bbGuild Extension — Events & Integration Points

## What are phpBB events?

phpBB is built around an *event system*. At hundreds of specific moments during a page request — when a topic list is about to be rendered, when the board index is loading, when an admin saves a forum setting — phpBB fires a named event and passes a bag of data along with it. Extensions register *listeners* that subscribe to these events by name. When the event fires, phpBB calls each listener in turn, letting it read and modify the data bag before the next step runs.

This means extensions never need to modify phpBB core files. An extension that wants to react to a character being added simply listens to the right event, does its work, and moves on.

There are two kinds of events:

**PHP events** fire inside phpBB's PHP code. Your extension subscribes by writing a listener class that implements `EventSubscriberInterface` and declaring which event names map to which methods. When the event fires, your method receives a `\phpbb\event\data` object — an array-like container of variables you can read and write back.

**Template events** fire inside phpBB's Twig templates. Your extension hooks in simply by placing an HTML file at `styles/all/template/event/<event_name>.html`. phpBB automatically includes that file at the matching point in the page, with no PHP code needed.

---

## What is the DI container?

The *dependency injection (DI) container* is phpBB's system for wiring services together. A *service* is any PHP object that does a specific job. Services are registered by name in `config/services.yml` (or `config/portal_services.yml`) files and phpBB automatically creates them and passes them to other services that need them.

When an extension wants to use a service from *another* extension, it can declare the dependency as **nullable** using the `@?` prefix in services.yml. bbGuild core does not currently soft-couple to any sibling extension this way — see section 2 below.

---

## 1. Own Events Emitted (Public API)

This section is the **public API contract** for the bbGuild extension. These are the events bbGuild fires so that sibling extensions (game plugins, bbAccounts, a future Discord integration, etc.) can hook in without patching bbGuild files.

**Changing anything listed here is a breaking change and requires a major version bump.**

---

### 1.1 `avathar.bbguild.player_detail_display`

**What this event is for:** Fires when an individual player's detail page renders. Lets game plugins (e.g. bbguildwow) inject API-specific content such as gear, talents, achievements or pet collections.

- **Placement:** `controller/view_controller.php::playerdetail()`
- **Since:** 2.0.0-b2
- **Arguments:**
  - `player_id` (int) — The player being displayed
  - `guild_id` (int) — The guild the player belongs to
- **Known listeners:** `avathar/bbguildwow` (equipment, talents), other game plugins as they add support

---

### 1.2 `avathar.bbguild.acp_addguild_submit` / `acp_editguild_submit`

**What this event is for:** Fires after the guild-add/guild-edit form values are read, before the guild is saved. Lets game plugins set edition or other game-specific fields on the guild object before it persists.

- **Placement:** `controller/admin_guild.php`
- **Since:** 2.0.0-b2
- **Arguments:**
  - `updateguild` / `addguild` (guilds) — The guild object being saved. Writable.
  - `game_id` (string) — The game identifier from the form
- **Known listeners:** none

---

### 1.3 `avathar.bbguild.acp_addguild_display` / `acp_editguild_display`

**What this event is for:** Fires while building the guild-add/guild-edit ACP form, letting a game plugin add its own fields to the form template.

- **Placement:** `controller/admin_guild.php`
- **Since:** 2.0.0-b2
- **Arguments:** varies by call site — see the docblock at each `trigger_event()` call for the exact variable names in use
- **Known listeners:** none

---

### 1.4 `avathar.bbguild.acp_editgames_submit` / `acp_editgames_display`

**What this event is for:** Same pattern as 1.2/1.3, for the ACP "edit game" form (classes, races, factions, specializations).

- **Placement:** `controller/admin_games.php`
- **Since:** 2.0.0-b2
- **Arguments:** see the docblock at each `trigger_event()` call site
- **Known listeners:** none

---

### 1.5 `avathar.bbguild.acp_config_display` / `acp_config_submit`

**What this event is for:** Fires while building/saving the main bbGuild config page, letting a sibling extension add its own config fields. Unlike every other event in this catalogue, these two fire via the raw Symfony `dispatcher->dispatch('event.name')` call with **no payload** — there is nothing to read or write, only a notification that the page is being built/saved.

- **Placement:** `controller/admin_main.php`
- **Since:** 2.0.0-b1
- **Arguments:** none
- **Known listeners:** none

---

### 1.6 `avathar.bbguild.acp_listplayers_display`

**What this event is for:** Fires while building the ACP player-list page.

- **Placement:** `acp/player_module.php`
- **Since:** 2.0.0-b2
- **Arguments:** see the docblock at the `trigger_event()` call site
- **Known listeners:** none

---

### 1.7 `avathar.bbguild.character_add` / `character_edit` / `character_delete` / `character_claim` / `character_unclaim`

**What this event is for:** Fire on the corresponding character lifecycle action in the UCP. The most concrete integration point for account-linking extensions (e.g. bbAccounts) and game plugins that need to react when a character enters, leaves, or changes hands.

**Example use case:** bbAccounts could listen to `character_claim`/`character_unclaim` to keep its own account-linking table in sync without bbGuild knowing bbAccounts exists.

- **Placement:** `ucp/bbguild_module.php::main()`
- **Since:** 2.3.0
- **Arguments:**
  - `player_id` (int) — The character involved
  - `guild_id` (int) — The guild it belongs/belonged to
  - `user_id` (int) — The forum user who performed the action (0 for `character_delete` if the character was never claimed)
- **Known listeners:** none

---

### 1.8 `avathar.bbguild.character_sync_completed`

**What this event is for:** Fires after a game-API sync attempt finishes for one character, whether it succeeded or failed. Lets game plugins or a Discord integration react to fresh data (e.g. detect a gear change) without polling.

- **Placement:** `cron/task/character_sync.php::run()`
- **Since:** 2.3.0
- **Arguments:**
  - `player_id` (int) — The character that was synced
  - `game_id` (string) — The game it belongs to
  - `success` (bool) — Whether the sync succeeded
- **Known listeners:** none

---

### 1.9 `avathar.bbguild.roster_display`

**What this event is for:** Fires once per character row as the roster portal module renders (both listing and grid layouts). Lets a game plugin inject a game-specific column.

- **Placement:** `portal/modules/roster.php::display_listing()` and `::display_grid()`
- **Since:** 2.3.0
- **Arguments:**
  - `player_id` (int), `game_id` (string), `guild_id` (int)
- **Known listeners:** none

---

### 1.10 `avathar.bbguild.portal_module_display`

**What this event is for:** Fires once per portal module as the guild portal renders, after the module's template has been resolved. A listener can read or override which template is used for a given module.

- **Placement:** `portal/portal_renderer.php::render()`
- **Since:** 2.3.0
- **Arguments:**
  - `guild_id` (int)
  - `row` (array) — The portal module's database row (`module_id`, `module_type`, etc.)
  - `template_module` (mixed) — The resolved template. Writable.
- **Known listeners:** none

---

### 1.11 `avathar.bbguild.recruitment_posted` / `recruitment_updated` / `recruitment_deleted`

**What this event is for:** Fire on the corresponding recruitment-posting action in the ACP. Intended primarily for a future Discord integration to announce recruitment changes.

- **Placement:** `controller/admin_guild.php::show_editguildrecruitment()`
- **Since:** 2.3.0
- **Arguments:**
  - `recruit_id` (int), `guild_id` (int)
- **Known listeners:** none

---

### 1.12 `avathar.bbguild.motd_updated`

**What this event is for:** Fires when a guild's Message of the Day is saved. Intended primarily for a future Discord integration.

- **Placement:** `controller/admin_guild.php::UpdateGuild()`
- **Since:** 2.3.0
- **Arguments:**
  - `guild_id` (int)
- **Known listeners:** none

---

## 2. Events & Services Consumed from Other Extensions

None today. bbGuild core has no `@?`-nullable soft-coupled DI reference to any sibling extension. When a real integration lands (e.g. bbAccounts, Discord), it will follow the same soft-coupling pattern `avathar/recenttopics` uses with `avathar/postlove`: a nullable `@?` service reference, checked for `null` before use, so bbGuild keeps working normally when the other extension is absent.

---

## 3. phpBB Core Events Used Internally

This section lists every phpBB core event that bbGuild subscribes to in order to deliver its own functionality. These are not part of the public API — they are internal implementation details.

### 3.1 PHP Events — Main listener (`event/main_listener.php`)

| phpBB Core Event | Handler method | What it does |
|---|---|---|
| `core.common` | `global_calls()` | Assigns `S_BBGUILD_ENABLED` on every page |
| `core.user_setup` | `load_language_on_setup()` | Loads bbGuild's language files on every page |
| `core.page_header` | `add_page_header_link()` | Builds the guild-switcher nav dropdown and the about-page footer link/version, on every page |
| `core.permissions` | `add_permission_cat()` | Registers bbGuild's permission category and ACL entries |
```

- [ ] **Step 2: Add the README link**

In `README.md`, find a natural spot near the top (e.g. after the feature list or near "Contributing") and add:

```markdown
For extension developers: custom PHP events and integration details are documented in [contrib/Events.md](contrib/Events.md).
```

- [ ] **Step 3: Self-check the doc against the actual code**

For each event entry in `contrib/Events.md`, grep the corresponding file for the exact event name string to confirm it matches what Tasks 1-4 actually shipped (names, arg names) — do not trust this plan's text over the real code if Tasks 1-4 deviated during implementation:

```bash
grep -rn "avathar.bbguild.character_add\|avathar.bbguild.character_edit\|avathar.bbguild.character_delete\|avathar.bbguild.character_claim\|avathar.bbguild.character_unclaim" ucp/bbguild_module.php
grep -rn "avathar.bbguild.character_sync_completed" cron/task/character_sync.php
grep -rn "avathar.bbguild.roster_display" portal/modules/roster.php
grep -rn "avathar.bbguild.portal_module_display" portal/portal_renderer.php
grep -rn "avathar.bbguild.recruitment_posted\|avathar.bbguild.recruitment_updated\|avathar.bbguild.recruitment_deleted\|avathar.bbguild.motd_updated" controller/admin_guild.php
```

Fix any mismatch in the doc, not the code.

- [ ] **Step 4: Commit**

```bash
git add contrib/Events.md README.md
git commit -m "docs: add contrib/Events.md event catalogue [#370]"
```

---

## Final check (after all 5 tasks)

- [ ] Run the full test suite: `vendor/bin/phpunit`
- [ ] `php -l` every file touched across all 5 tasks
- [ ] Confirm `git log origin/main..HEAD` shows exactly 5 commits (one per task) with no unrelated changes mixed in
- [ ] Push, and update the spec's Status line from "ready for implementation plan" to "shipped"
