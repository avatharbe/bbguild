# bbGuild - Extension Analysis

## Overview

**bbGuild** is a Guild Management System for phpBB 3.3+ designed for World of Warcraft gaming communities. It provides guild roster management, character tracking, achievements, recruitment, and integration with the Battle.net API.

- **Author:** Andreas Vandenberghe (Sajaki)
- **Version:** 2.0.0 (stable; version lives in `ext::BBGUILD_VERSION`, not `phpbb_config`)
- **License:** GPL-2.0-only
- **Repository:** https://github.com/avatharbe/bbguild

## Project Status

| Metric | Value |
|--------|-------|
| First commit | May 28, 2010 |
| Status | 2.0.0 stable shipped 2026-09-12; 2.1.0 (guild page overhaul) in progress |
| Tracking issue | [#303](https://github.com/avatharbe/bbguild/issues/303) |

## Roadmap (2.x) — planned 2026-07

Full plan: **`contrib/roadmap-2.x.md`** (parity matrix + release plan); BBCode forum status post: `contrib/roadmap-2.x-forum-post.txt`.

- **North star:** feature parity vs. phpBB native / legacy bbDKP MOD / the extension family / guildsofwow.com. Releases are a **coordinated train** across core + all 9 game plugins — shipped together on the same milestone dates and **pairing-locked** (every plugin hard-requires the current core version via `ext.php::is_enableable()`). **2.0.0 shipped stable 2026-09-12** (core `2.0.0`; bbguildwow's pairing bumped to require `>=2.0.0`) — the rc5→stable step was a version-number/pairing-lock bump only, no schema or code change (#244, the sole 2.0.0 blocker, closed 2026-09-12). Other plugins' 2.0.0 status not yet re-verified as part of this pass — see the live roadmap doc. **Features now at 2.1.0.**
- **Milestone scheme (GitHub, milestone-driven versioning — no version labels):**
  - `2.0.0` (due 2026-08-31, shipped 2026-09-12) — stabilisation + unit tests (#244, closed); plugin test suites + icon fixes.
  - `2.1.0` (due 2026-10-15, in progress) — **guild page overhaul**: page-level portal tabs (#360, **shipped**, closed 2026-09-13), character-sync scheduler contract (#361, **shipped**) + WoW handler (#362, **shipped**), gear tooltips via bbTips + bonus IDs (#363, **shipped** — implemented under bbguildwow's `2.1.0-b1` line, closed 2026-09-12), character page polish (#364, PR open), per-character achievements (#365), guild statistics portal module (#366, subsumes #279, **shipped**), character-based forum avatars (#369, restores pbwowext#10 pt1, **shipped**); unit test coverage follow-up (#372, split from #244); plugin spec data (#367) + **GW2 API v2 sync** (bbguildgw2#9).
  - `2.2.0` (due 2026-11-30) — Events/RSVP calendar (new ext), roster↔profile fields (#231), profile-field character info (#368, pbwowext#10 pt2), professions (#230), player stats (#289).
  - `2.3.0` (due 2027-01-15) — **bbGuild API surface (#370: phpBB events + read API)** — the integration layer for the family (revives 2012 bbDKP-API idea), Discord (new ext), Gameworld (new ext), Battle.net API modernisation, spec build analysis (#286).
- **Separate track (own roadmap):** DKP & Accounting — bbAccounts (~RC), bbDKP v2 (~0%), raid logging (0%), bbPoints v2.
- **Labels:** version labels removed repo-wide; versioning is milestone-only. Functional + MoSCoW (`Must/Should/Could have`) label set replicated across core + all 9 plugin repos.
- **Game-API docs** (forum f=70, refreshed): only WoW (done) and GW2 (API v2, next) have first-class APIs; FFXIV via fragile third-party parsers; other games have no API path.

## Requirements

- phpBB >= 3.3.0
- PHP >= 8.1.0
- GD extension
- cURL extension

## Architecture

### Directory Structure

```
bbguild/
├── acp/                    # Admin Control Panel modules
├── controller/             # Request controllers
├── ucp/                    # User Control Panel modules
├── model/                  # Business logic
│   ├── admin/             # Utilities (curl, log, constants)
│   ├── api/               # Battle.net API client
│   ├── games/             # Game definitions + installers
│   └── player/            # Player, guild, rank management
├── portal/                # Portal block engine + guild context
│   └── modules/          # Module infrastructure + built-in modules
├── event/                 # Event listeners
├── migrations/            # Database migrations
│   ├── v200b3/           # Squashed base install (schema, data, config, permissions, modules)
│   ├── v200b4/           # Specialization system (bb_specializations, player_spec_id)
│   ├── v200rc1/          # Permission fixes, bb_language column widen
│   └── v200rc2/          # ADMINISTRATORS char-management permissions
├── config/                # Services and routing (YAML)
├── styles/                # Templates
├── language/              # Localization (en, fr, de, it, nl, es_x_tu, pl)
├── images/                # Game icons
├── contrib/               # Changelog, docs, diagrams
└── tests/                 # Unit tests
```

### Controllers

| File | Purpose |
|------|---------|
| `view_controller.php` | Frontend (portal page, delegates to guild_context + portal_renderer) |
| `admin_main.php` | ACP panel, config, logs |
| `admin_games.php` | Game management |
| `admin_guild.php` | Guild management |
| `admin_portal.php` | Portal module management |
| `ajax_controller.php` | AJAX endpoints (faction, rank, player, class/race selectors) |
| `validator.php` | Input validation |

### Database Tables (17 in core — see `contrib/database.md` for the full Mermaid ER diagram)

**Core:** bb_games, bb_guild, bb_players, bb_ranks, bb_logs, bb_news, bb_motd, bb_recruit, bb_specializations

**Portal:** bb_portal_modules, bb_portal_config, bb_portal_tabs (#360, added v210b2)

**Game Content:** bb_classes, bb_races, bb_factions, bb_gameroles, bb_language

**Not yet created (roadmap):** bb_bosstable, bb_zonetable (defined in tables.yml, schema TBD)

Achievement tables (`bb_achievement`, `bb_achievement_track`, `bb_achievement_criteria`, `bb_achievement_rewards`, `bb_relations_table`, `bb_criteria_track`) are **not** core tables — they belong to the `bbguildwow` game plugin's own migrations.

### Supported Games (via plugins)

WoW (Battle.net API), GW2, LOTRO, EQ, EQ2, FFXI, FFXIV, SWTOR, Lineage 2, Custom

Game plugins live at `ext/avathar/bbguild<game>/` (no separator — composer name, directory, PHP namespace, and GitHub repo all match; dropped in 2.0.0-b4 since phpBB's extension class loader can't handle a hyphenated directory name and EPV rejects underscores in the composer name). Each provides a provider + installer; game data is seeded on-demand from ACP (not via migrations). DB-stored config/cache keys (`bbguild_<game>_version`, `bbguild_wow_oauth_token_*`, ...) keep the original underscore form to avoid orphaning rows. Archived: AION, DAOC, Rift, TERA, Vanguard, Warhammer.

### Routes

- `/guild/{guild_id}` - Main guild portal page (primary route)
- `/guild/{page}/{guild_id}` - Legacy compat route ($page ignored)
- `/getfaction` - AJAX faction selector
- `/getguildrank/{guild_id}` - AJAX rank selector
- `/getplayerList/{game_id}` - AJAX player list
- `/getclassrace/{game_id}` - AJAX class/race selector

### Permissions

Managed in ACP > Permissions > Group/User permissions under the "bbGuild" category.

| Permission | Type | Default | Description |
|---|---|---|---|
| `a_bbguild` | Admin | ROLE_ADMIN_FULL, ROLE_ADMIN_STANDARD | Full access to bbGuild ACP (guilds, players, games, portal, logs, settings) |
| `u_bbguild` | User | ROLE_USER_STANDARD, ROLE_USER_FULL, Guests | View guild portal pages and player profiles. Granted to guests by default so guild pages are publicly visible; revoke from Guests group to make guild pages members-only |
| `u_charclaim` | User | ROLE_USER_FULL | Claim or unclaim an existing guild character as own phpBB account |
| `u_charadd` | User | ROLE_USER_FULL | Add new characters to a guild via UCP |
| `u_charupdate` | User | ROLE_USER_FULL | Edit own characters (name, class, level, etc.) via UCP |
| `u_chardelete` | User | ROLE_USER_FULL | Delete own characters via UCP |

Additionally, the config setting `bbguild_maxchars` limits how many characters each user can own.

Default grants are a mix of role-based (`ROLE_USER_STANDARD`/`ROLE_USER_FULL`, `ROLE_ADMIN_FULL`/`ROLE_ADMIN_STANDARD`) and direct per-group grants. Direct grants exist because installs that manage `u_` permissions via direct per-group checkboxes instead of the stock role templates never inherit a role-based grant at all — REGISTERED, ADMINISTRATORS, GLOBAL_MODERATORS, and GUESTS each get an explicit direct `u_bbguild` grant for this reason (`v200rc1`); ADMINISTRATORS additionally gets the full `u_char*` set so the UCP "bbGuild" tab isn't hidden for an admin who isn't also in REGISTERED (`v200rc2`). GLOBAL_MODERATORS intentionally stays view-only.

## Known Bugs

## Completed Fixes

- **#301** - "Module not accessible" after adding guild: typo `aavathar` → `avathar` in `acp/guild_module.php:85`
- **#299** - ACP achievement list error: wrong array index in `acp/achievement_module.php:265`, `$GuildAchievements[0]` → `$GuildAchievements[2]`
- **#298** - ACP region and UCP errors: added null guards in `model/api/battlenet_resource.php`
- **Permission label swap** - `u_chardelete` / `u_charupdate` labels were swapped in `event/main_listener.php`
- **phpBB 3.3 compatibility** - version gates, extension manager fallback, removed `$user->theme['template_path']`, fixed language loading
- **PHP 8.x null-safety** - added `(string)` casts in `util.php`, `battlenet_resource.php`, `ucp/bbguild_module.php`
- **DKP guard** - UCP DKP query wrapped in `defined('PLAYER_DKP_TABLE')` check
- **Migration rewrite** - Reorganized into `basics/` + `v200a10/`, fixed ROLE_USER_FULL permission bug, removed hardcoded `game_id='wow'` from seed data, removed AION columns and bb_plugins table
- **Migration seed data** - `data.php` now populates all 13 core tables with Custom game test data (factions, classes, races, roles, language, guild, ranks, players, motd, news, recruit, logs)
- **Service collection fix** - Replaced `!tagged_iterator` (unsupported by phpBB 3.3) with `phpbb\di\service_collection` for game provider injection in `config/services.yml`
- **cURL fix** - `CURLOPT_FOLLOWLOCATION, true` → `CURLOPT_FOLLOWLOCATION => true` (comma instead of `=>`) in `model/admin/curl.php:65`
- **Version check fix** - `admin_main.php` now reads `unstable` branch first (was hardcoded to `stable`) in version_check()
- **Wrong namespace** - `\bbdkp\bbguild` → `\avathar\bbguild` in `controller/view_controller.php:256`
- **Wrong column names** - `rank_id`/`guild_id` → `player_rank_id`/`player_guild_id` in `model/player/guilds.php:1151` (bb_players table)
- **Missing constructor args** - `model/player/player.php:1016` was calling `guilds()` without `$db, $user, $config, $cache, $log` arguments
- **Missing `switch_order` call** - `player.php:2063` called `$this->switch_order()` but method lives in `util` service
- **Missing properties** - `$this->ext_path` and `$this->games` not initialized in `player.php` constructor; `$this->games` not set in `viewnavigation.php`
- **Missing `ALL` lang key** - Added to all 4 language files (was removed during language cleanup)
- **#336** - Assignment instead of comparison in ranks.php condition (`$RankId = 0` → `$RankId == 0`)
- **#337** - Wrong cache invalidation in roles.php delete_role() (bb_classes_table → bb_gameroles_table)
- **#341** - faction_id cast to string instead of int in classes.php
- **#343** - bb_news bbcode columns aligned to phpBB standard types
- **#344** - Type mismatch in module_helper group_id check (strict int comparison)
- **#346** - view_controller slimmed from 29 to 4 args; guild_context moved to portal/ as DI service
- **#345** - Roster rewritten as portal module with grid/listing layout switcher and pagination
- **Missing DI args** - new game() calls in admin_main.php and admin_games.php were missing db, cache, config, user, ext_manager
- **ACP/UCP service locator** - player_module.php and bbguild_module.php no longer depend on view_controller for table names; resolve from container parameters
- **Dead code cleanup** - Removed viewwelcome.php, viewroster.php, viewnavigation.php, iviews.php, admin_player.php, model/blocks/
- **#352 SQL injection** - `game_id` interpolated unescaped into raw-concatenated queries across `game.php` and `rpg/{classes,races,roles,faction}` models (~33 sites), now `sql_escape()`'d; follow-up cast to `(int)` for `class_id`/`race_id`/`role_id`/`faction_id` at remaining raw-concatenated sites
- **9 raw `$_GET` reads** - `admin_games.php`/`acp/player_module.php` now use `$this->request->is_set(..., GET)`
- **`@unlink()` filesystem bypass** - `delete_guild()` emblem cleanup now uses the phpBB filesystem service (`exists()`/`remove()`)
- **PHP 8.2+ dynamic property deprecations** - `ext_path`/`time`/`games` declared as properties on `player` instead of ad-hoc constructor assignment
- **#353 bbguild_version refactor** - version moved out of `phpbb_config` entirely into `ext::BBGUILD_VERSION`, matching `avatharbe/recenttopics`'s `ext::RT_VERSION` pattern; fixed a fresh-install failure where `bbguild_version` was never `config.add`'d, only ever updated
- **Missing `u_bbguild`/`u_char*` grants** (rc1) - REGISTERED/ADMINISTRATORS/GLOBAL_MODERATORS defaulted to "No" on installs managing permissions via direct per-group grants instead of role templates
- **`bb_language.language` too narrow** (rc1) - `CHAR:2` widened to `VCHAR:10` for `es_x_tu`-style locale codes; previously crashed bbguildwow's install
- **UCP "bbGuild" tab hidden for admins** (rc2) - ADMINISTRATORS only had the `u_bbguild` view-only floor from rc1; UCP modes require `u_charclaim`/`u_charadd` specifically, now granted
- **Roster search form disappearing** (rc2) - form was nested inside the same conditional as the results listing, so a zero-result search hid the form itself
- **Roster class filter broken in grid view** (rc2) - `display_grid()` passed a hardcoded `0` for class id to `player::get_classes()` instead of the selected class
- **Roster combined class/armor filter** (rc2) - split the legacy single `filter` pulldown (server-side disambiguated by lookup-array match) into independent `class_filter`/`armor_filter` dropdowns
- **Roster search box CSS** (rc2) - reused phpBB's reserved `search-box` class (oversized); renamed to `roster-search-box` with its own compact sizing and box-model alignment fixes
- **#354 `getPlayerId()` fatal error** (rc3) - UCP character-add form, UCP character-edit form, and ACP roster's character-edit form all called a nonexistent `player::getPlayerId()` method when resolving the portrait URL; `player` only ever exposed `player_id` as a public property, now read directly
- **#371 dead ACP game buttons** (rc5) - three buttons on the ACP **Games → List games** page had no POST handler in `listgames()` (it only assigned template vars): "Install from game plugin" (`addgame1`, also redundant — plugins auto-register their game on enable via `migrations/basics/data.php`), "Create custom game" (`addgame2`), and "Default game" Confirm (`upddefaultgame`). Removed the redundant preconfigured-install control, renamed the fieldset "Custom game installation" with Region reordered last, and wired `addgame2` → `game::install_game()` (custom path, form-token + length + duplicate-id guards) and `upddefaultgame` → `game::update_gamedefault()`

## Incomplete Features (Must Have)

- #288 - Individual player page as a portal module (deferred to 2.1.0; legacy view ships in rc2)

## Compatibility Status

### phpBB 3.3 - Done
- Version gates updated
- Extension manager container fallback added
- Removed references to `$user->theme['template_path']`
- Language loading fixed (`add_lang_ext` instead of `mods/`)

### PHP 8.x - Done
- Null-safety fixes applied to key files
- Constructor and property initialization fixes in player.php, guilds.php, viewnavigation.php

### Battle.net API - Not started
- API endpoints changed since 2016-2019
- OAuth 2.0 authentication updates needed
- Data structure modifications needed

## Modernization Path

### Phase 1: Core compatibility — COMPLETE (2.0.0-a11)
1. ~~Fix critical bugs (#301, #299, #298)~~ All fixed
2. ~~Update PHP syntax for 8.x compatibility~~ Done
3. ~~Test/fix phpBB 3.3 compatibility~~ Done
4. ~~Migration rewrite~~ Done
5. ~~Game plugin extraction~~ Done
6. ~~Language cleanup~~ Done
7. ~~ACP UI modernization~~ Done

### Phase 1.5: Portal feature — COMPLETE (2.0.0-b1)
1. ~~Portal block engine (forked from Board3 design)~~ Done
2. ~~Built-in modules: MOTD, Guild News, Recruitment, Activity Feed, Custom Block~~ Done
3. ~~Welcome page rewrite to use portal renderer~~ Done
4. ~~ACP Portal Management (add/remove/reorder/toggle modules per guild)~~ Done
5. ~~Portal language files (7 languages)~~ Done

### Phase 1.6: Refactor and cleanup — COMPLETE (2.0.0-b2)
1. ~~Slim view_controller (29→4 args), guild_context as DI service~~ Done
2. ~~Roster rewritten as portal module with grid/listing switcher~~ Done
3. ~~Remove dead code (views/, blocks/, empty stubs)~~ Done
4. ~~Remove ACP/UCP dependency on view_controller as service locator~~ Done
5. ~~Fix multiple minor bugs (#336, #337, #341, #343, #344)~~ Done
6. ~~Migrations squashed to b2~~ Done

### Phase 1.7: Polish, namespace cleanup, EPV CI green — COMPLETE (2.0.0-b3)
1. ~~Game edition field (#15) — WoW Classic support flowing through plugins via template events~~ Done
2. ~~Auto-disable child plugins when bbguild core is disabled~~ Done
3. ~~Block game deletion when guilds/players reference it~~ Done
4. ~~Namespace drop-separator across the family (`bbguild_<game>` → `bbguild<game>`)~~ Done. Composer name = dir name = PHP namespace = repo name. DB-stored config/cache keys preserved with original underscore form to avoid orphaning rows.
5. ~~EPV CI green: event docblock `@since`, unique event names (`acp_addguild_*` split from `acp_editguild_*`), `unserialize` → `json_decode` in log model~~ Done
6. ~~Test workflow: dual-checkout (bbguild + bbguildwow) so plugin tests resolve core interfaces~~ Done

### Phase 1.8: Specialization system — COMPLETE (2.0.0-b4)
Issue #331. Adds a layer between class and role.
1. ~~Migration `v200b4`: `bb_specializations` table + `player_spec_id` column~~ Done
2. ~~`specialization` model with `load`/`save`/`delete`/`get_for_class`/`get_translations`~~ Done
3. ~~ACP CRUD on Edit Game page (specs panel + Add/Edit form)~~ Done
4. ~~ACP and UCP player edit dropdown filtered by class~~ Done
5. ~~Roster Spec column (listing + grid views) with i18n via bb_language~~ Done
6. ~~Optional `specialization_provider_interface` for plugins~~ Done
7. ~~`install_specs()` hook in `abstract_game_install`~~ Done
8. ~~bbguildwow Phase 4: 39 specs + icons + de/fr/it/es_x_tu translations~~ Done

### Phase 1.9: Security/standards audit, RC1 — COMPLETE (2.0.0-rc1, 2026-07-24)
1. ~~SQL injection hardening (#352): `sql_escape()`/`(int)` cast across game.php + rpg/{classes,races,roles,faction} models~~ Done
2. ~~Raw `$_GET` reads replaced with `request->is_set()`~~ Done
3. ~~`@unlink()`/`@file_exists()` replaced with phpBB filesystem service~~ Done
4. ~~PHP 8.2+ dynamic property deprecations fixed on `player`~~ Done
5. ~~`bbguild_version` moved out of `phpbb_config` into `ext::BBGUILD_VERSION` (#353)~~ Done
6. ~~Missing `u_bbguild`/`u_char*` direct-grants for REGISTERED/ADMINISTRATORS/GLOBAL_MODERATORS~~ Done
7. ~~`bb_language.language` widened `CHAR:2` → `VCHAR:10`~~ Done
8. ~~Tagged and released `v2.0.0-rc1` on GitHub~~ Done
9. ~~Forum-announced: all 9 game plugins + core published as individual posts~~ Done (2026-07-24, https://www.avathar.be/forum/viewforum.php?f=2) — first public forum announcement since `2.0.0-b1`; b2/b3/b4/rc2/rc3 were GitHub-only

### Phase 2: RC2 — bug fixes from manual testing — COMPLETE (2.0.0-rc2, tagged 2026-07-24)
1. ~~UCP "bbGuild" tab hidden for ADMINISTRATORS-only accounts — granted `u_charclaim`/`u_charadd`/`u_chardelete`/`u_charupdate`~~ Done
2. ~~Roster search form disappearing on zero-result search~~ Done
3. ~~Roster class filter broken in grid view~~ Done
4. ~~Roster class/armor filter split into independent dropdowns~~ Done
5. ~~Roster search box CSS (class collision, box-model alignment)~~ Done
6. ~~Documentation sweep: composer.json, README, CHANGELOG, architecture.md, CLAUDE.md, cleanup.sql~~ Done

### Phase 2.5: RC3 — bug fixes from manual testing (2.0.0-rc3)
1. ~~Fatal error `getPlayerId()` undefined method on UCP character-add, UCP character-edit, and ACP roster character-edit forms (#354)~~ Done

### Phase 2.6: RC4 — bug fixes + ACP restructure (2.0.0-rc4)
1. ~~ACP restructured: new **Game settings** category (`ACP_BBGUILD_GAMESETTINGS`); Game List module moved into it (migration `v200rc4`)~~ Done
2. ~~Game-settings region silently discarded on save (#357); stale 7-day guild cache; empty-MOTD header error (#28); faction dropdown snap (#29); default roster level 50 → 1~~ Done

### Phase 2.7: RC5 — dead ACP game buttons + plugin dependency gate (2.0.0-rc5)
1. ~~Wire up / clean up the ACP Games list page: remove redundant "Install from game plugin", wire custom-game Add and Default game Confirm (#371)~~ Done
2. ~~All 9 game plugins now hard-require bbGuild core ≥ 2.0.0-rc5 — `MIN_BBGUILD_VERSION` check in each plugin's `ext.php::is_enableable()` (the one enforced gate), composer `soft-require` bumped to `>=2.0.0-rc5` (advisory). Migration `depends_on` intentionally left at core `v200b4` (schema-correctness dep, not a version knob)~~ Done

### Phase 2.8: 2.0.0 stable — COMPLETE (2.0.0, tagged 2026-09-12)
1. ~~Close #244 (only remaining 2.0.0-milestone issue) — reduced scope to the essentials already shipped (specialization/roster/player CRUD unit coverage), split the remaining migration/full-CRUD coverage gap into a dedicated follow-up (#372, milestoned 2.1.0)~~ Done
2. ~~Version bump: `ext::BBGUILD_VERSION` / `composer.json` `2.0.0-rc5` → `2.0.0`~~ Done — version-number/pairing-lock step only, no schema or code change since rc5
3. ~~`bbguildwow`'s `MIN_BBGUILD_VERSION` / composer `soft-require` pairing bumped to `>=2.0.0`~~ Done

### Phase 3: 2.1.0 in progress (post-2.0.0-stable)
- #361 — Core character-sync scheduler contract + cron — **shipped**
- #362 — bbguildwow character-sync handler (armory equipment) — **shipped**
- #363 — Gear tooltips via bbTips + capture bonus IDs — **shipped** (implemented under bbguildwow's `2.1.0-b1` line; found fully committed but undocumented during this release pass, changelog/issue now reconciled)
- #360 — Portal: page-level guild tabs — **shipped** (merged ffeb450d, closed 2026-09-13)
- #364 — Character page polish (layout + async stats) (open)
- #365 — Per-character achievements view (open)
- #366 — Guild statistics portal module, subsumes #279 — **shipped** (5b04fe69, closed 2026-09-12; `portal.module.statistics` tagged as a built-in module in `config/portal_services.yml`)
- #367 — Complete plugin spec data for 8 non-WoW games (open)
- #369 — Character-based forum avatars, restores pbwowext#10 pt1 — **shipped**
- #372 — Unit test coverage: migrations + remaining guild/player CRUD paths, split from #244 (open)
- #331 Phase 3c — Recruitment spec filter (not started, 2.2.0)
- #331 Phase 5 — Migrate legacy free-text `player_spec` text → `player_spec_id` (not started, 2.2.0)
- 8 of 9 plugin Phase 4 sub-issues open (eq, eq2, ffxi, ffxiv, gw2, lineage2, lotro, swtor) — spec data only, 2.2.0
- #278 (achievements) and #290 (claim/unclaim) confirmed shipped in b2/pre-rc1 — removed from this list

## Key Files

- `ext.php` - Extension entry point, requirement checks
- `config/services.yml` - Dependency injection
- `config/routing.yml` - URL routes
- `event/main_listener.php` - phpBB event hooks
- `composer.json` - Package metadata
