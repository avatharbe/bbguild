# Issue #361 Design: Core character-sync scheduler contract + cron

**GitHub:** [avatharbe/bbguild#361](https://github.com/avatharbe/bbguild/issues/361), milestone `2.1.0` (due 2026-10-15).

**Goal:** Give bbGuild a thin, game-agnostic way to keep character data fresh in the background — a tagged-service contract any game plugin can implement, plus a phpBB cron task that picks a small batch of the stalest characters each run and delegates each to its owning game plugin. No synchronous bulk API calls on page load, ever.

**Why now:** Today the only sync path is bbguildwow's ACP "armory" button, which drives four sequential AJAX batches from the browser (roster → specs → portraits → equipment), each self-limited to a 20-second wall-clock budget. It only runs when an admin clicks it. #362 (bbguildwow's actual sync handler, next in the roadmap) needs a stable contract to implement against — this issue defines that contract and the scheduler that drives it. #369 (character avatars) and #363 (gear tooltips) both depend on #362, which depends on this.

**Scope:** bbGuild core only. bbguildwow's implementation of the contract is #362, out of scope here. No ACP UI changes.

## Architecture

### 1. The contract & registry

- New interface `avathar\bbguild\model\games\character_sync_interface`, alongside the existing `game_provider_interface` in `model/games/`:
  - `get_game_id(): string` — matches `bb_players.game_id`; used as the registry key.
  - `sync_character(array $player_row): bool` — perform one full sync for a single character, return success/failure. Takes the whole player row (so a plugin has `player_realm`/`player_region`/`player_name` etc. without re-fetching). Deliberately thin: how a "full sync" is broken into sub-steps (WoW's roster/specs/portraits/equipment) is entirely the implementing plugin's business (#362), not this contract's.
- New registry `avathar\bbguild\model\games\character_sync_registry`, a structural copy of the existing `game_registry` (`model/games/game_registry.php`): constructor takes a `phpbb\di\service_collection`, indexes services by `get_game_id()`, exposes `get(string $game_id)`, `has(string $game_id): bool`, `get_supported_game_ids(): array`.
- `config/services.yml` additions, mirroring the existing `bbguild.game_provider` pair exactly (no `!tagged_iterator` — confirmed unsupported by phpBB 3.3, already rejected once for `game_provider`):
  ```yaml
  avathar.bbguild.character_sync_collection:
      class: phpbb\di\service_collection
      arguments: ['@service_container']
      tags:
          - { name: service_collection, tag: bbguild.character_sync }
  avathar.bbguild.character_sync_registry:
      class: avathar\bbguild\model\games\character_sync_registry
      arguments: ['@avathar.bbguild.character_sync_collection']
  ```
  A future implementer (bbguildwow's `#362`, later bbguildgw2) tags its sync-handler service `{ name: bbguild.character_sync }`, the same way `wow_provider` tags into `bbguild.game_provider` today.

### 2. Data model & staleness tracking

- New migration adds `player_last_synced` (`UINT`, default `0`) to `bb_players`. Deliberately **not** the same as the existing generic `last_update` column — `last_update` is bumped on any manual ACP edit too, and reusing it would let a manual edit make a genuinely-stale character look freshly synced, starving it from the queue. Default `0` means never-synced characters naturally sort first.
- New method `avathar\bbguild\model\player\player::get_stalest_players(array $game_ids, int $limit): array` — `WHERE player_status = 1 AND game_id IN (:game_ids) ORDER BY player_last_synced ASC LIMIT :limit`. `$game_ids` comes from the registry's `get_supported_game_ids()`, so characters belonging to games with no registered sync handler (everything except WoW today) are never selected — no wasted rows, no permanently-stale entries skewing the ordering.
- New method `avathar\bbguild\model\player\player::update_last_synced(int $player_id): void` — sets `player_last_synced = time()`. Called by the cron task after **every** `sync_character()` call, success or failure.

### 3. The cron task

- `avathar\bbguild\cron\task\character_sync` extends `\phpbb\cron\task\base`, tagged `cron.task` with `calls: [set_name, [...]]` — the same shape as the existing `avathar\bbpatreon\cron\task\sync` example in this codebase. Works under both phpBB's default web-embedded cron and system-cron with zero special-casing (both dispatch paths converge on `cron.manager`/`cron.task_collection`; a task only needs the standard tag).
- Constants (hardcoded, not ACP-configurable, per YAGNI — cheap to promote later since phpBB config is a plain key-value store): `BATCH_SIZE = 10`, `MIN_INTERVAL = 300` (5 minutes).
- `should_run(): bool` — `(time() - (int) $this->config['bbguild_sync_last_run']) > self::MIN_INTERVAL`. New config key `bbguild_sync_last_run`, same pattern as bbpatreon's `patreon_last_cron_sync`.
- `is_runnable(): bool` — `true` only if `character_sync_registry::get_supported_game_ids()` is non-empty. The whole task is a no-op on a board with no sync-capable game plugin installed (today: no bbguildwow).
- `run(): void`:
  1. `$game_ids = $registry->get_supported_game_ids();` — if empty, return (defensive; `is_runnable()` should already have caught this).
  2. `$stale = $player_model->get_stalest_players($game_ids, self::BATCH_SIZE);`
  3. For each row: resolve `$handler = $registry->get($row['game_id']);`, call `sync_character($row)` inside a try/catch (any thrown exception from the plugin is treated as failure, not a fatal abort of the whole batch).
  4. On failure (returned `false` or caught exception): write a phpBB admin-log entry via a new `LOG_BBGUILD_SYNC_FAILED` language key — nothing is watching a cron run live, so this is the only failure visibility.
  5. Always call `update_last_synced($row['player_id'])`, regardless of outcome — keeps the queue moving even if an API is down, at the cost of some staleness accuracy during an outage.
  6. `$this->config->set('bbguild_sync_last_run', time());`

## Testing

Per this codebase's TDD convention (`tests/games/rpg/specialization_test.php` etc. — all mock-based, no DB), new coverage needed:
- `character_sync_registry`: indexing by `get_game_id()`, `get()`/`has()`/`get_supported_game_ids()` behavior, including the empty-registry case.
- `player::get_stalest_players()`: game-id filtering, `LIMIT`, ordering — can follow the existing `guilds_crud_test.php` pattern for DB-backed model tests.
- `character_sync` cron task: `should_run()`/`is_runnable()` gating logic, and `run()`'s per-character try/catch + always-bump-timestamp behavior, using a fake `character_sync_interface` test double (success case, thrown-exception case, `false`-return case).
- These can run locally via the `/Users/Andreas/.local/share/phpbb-ext-test-harness` workaround (the Sites release install has no `tests/` dir) as long as they don't extend `phpbb_database_test_case`; DB-backed tests defer to CI.

## Out of scope / explicitly deferred

- bbguildwow's actual `character_sync_interface` implementation — #362.
- Any ACP UI for tuning batch size / interval — revisit only if the hardcoded defaults prove wrong in practice.
- bbguildgw2 or any other second adopter of the contract — no such plugin exists yet (confirmed: only `wow_provider::has_api()` returns `true` among all 9 game plugins today).
- Exact migration filename/`depends_on()` chain — resolved at implementation time by checking the live migration chain (`migrations/v200rc4` is the latest seen; naming for 2.1.0 not yet established).
