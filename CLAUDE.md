# bbGuild - Extension Analysis

## Overview

**bbGuild** is a Guild Management System for phpBB 3.3+ designed for World of Warcraft gaming communities. It provides guild roster management, character tracking, achievements, recruitment, and integration with the Battle.net API.

- **Author:** Andreas Vandenberghe (Sajaki)
- **Version:** 2.0.0-b2 (beta)
- **License:** GPL-2.0-only
- **Repository:** https://github.com/avatharbe/bbguild

## Project Status

| Metric | Value |
|--------|-------|
| First commit | May 28, 2010 |
| Status | Phase 2 in progress, Portal refactored |
| Tracking issue | [#303](https://github.com/avatharbe/bbguild/issues/303) |

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
│   ├── basics/           # Initial install (schema, data, config, permissions, modules)
│   └── v200b2/           # Release 2.0.0-b2 (schema fix, version stamp)
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

### Database Tables (21)

**Core:** bb_games, bb_guild, bb_players, bb_ranks, bb_logs, bb_news, bb_motd

**Portal:** bb_portal_modules, bb_portal_config

**Game Content:** bb_classes, bb_races, bb_factions, bb_gameroles, bb_language

**Recruitment:** bb_recruit

**Achievements (WoW-only):** bb_achievement, bb_achievement_track, bb_achievement_criteria, bb_achievement_rewards, bb_relations_table, bb_criteria_track

**Not yet created (roadmap):** bb_bosstable, bb_zonetable (defined in tables.yml, schema TBD)

### Supported Games (via plugins)

WoW (Battle.net API), GW2, LOTRO, EQ, EQ2, FFXI, FFXIV, SWTOR, Lineage 2, Custom

Game plugins live at `ext/avathar/bbguild_<game>/`. Each provides a provider + installer; game data is seeded on-demand from ACP (not via migrations). Archived: AION, DAOC, Rift, TERA, Vanguard, Warhammer.

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

## Incomplete Features (Must Have)

- #290 - UCP bbguild page
- #288 - Individual player page
- #278 - Achievements pane

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

### Phase 2: Feature completion
- #290 — UCP bbguild page
- #288 — Individual player page
- #278 — Achievements pane
- Update Battle.net API integration (OAuth 2.0)
- Add unit tests (#244)

## Key Files

- `ext.php` - Extension entry point, requirement checks
- `config/services.yml` - Dependency injection
- `config/routing.yml` - URL routes
- `event/main_listener.php` - phpBB event hooks
- `composer.json` - Package metadata
