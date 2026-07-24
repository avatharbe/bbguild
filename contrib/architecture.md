# bbGuild Architecture

## Overview

bbGuild is a guild management extension for phpBB 3.3+. It provides guild roster management, character tracking, achievements, recruitment, a portal system, and Battle.net API integration for World of Warcraft communities.

- **Vendor namespace:** `avathar\bbguild`
- **Entry point:** `ext.php`
- **Requirements:** PHP >= 8.1, phpBB >= 3.3, GD, cURL

## Directory Structure

```
bbguild/
├── acp/                    # ACP module definitions (*_info.php + *_module.php)
├── config/                 # DI services, routing, table parameters (YAML)
├── controller/             # Request controllers (ACP + frontend)
├── event/                  # phpBB event listeners
├── language/               # Localization (en, de, fr, it, nl, es_x_tu, pl)
├── migrations/             # Database migrations
│   ├── v200b3/             # 2.0.0-b3 — squashed base install (schema, data, config, permissions, modules)
│   ├── v200b4/             # 2.0.0-b4 — specialization system (bb_specializations, player_spec_id)
│   ├── v200rc1/            # 2.0.0-rc1 — permission fixes, bb_language column widen
│   └── v200rc2/            # 2.0.0-rc2 — ADMINISTRATORS char-management permissions
├── model/                  # Business logic and data access
│   ├── admin/              # Utilities: curl, log, constants, util
│   ├── api/                # Battle.net API client
│   ├── games/              # Game registry, abstract installer, custom game
│   └── player/             # Player, guild, rank models
├── portal/                 # Portal block engine + guild context
│   └── modules/            # Module infrastructure + built-in modules
├── styles/                 # Twig templates (prosilver)
├── ucp/                    # User Control Panel modules
├── images/                 # UI assets (emblems, icons, progressbar)
├── contrib/                # Documentation, diagrams, changelog
└── tests/                  # Unit tests
```

## Frontend Architecture (Portal-First)

The frontend uses a portal-first, single-page architecture:

1. **`view_controller::handleview()`** receives the request
2. **`guild_context`** resolves the guild, loads guild data, and assigns header/dropdown template vars
3. **`portal_renderer`** renders all enabled portal modules (MOTD, Roster, News, Recruitment, etc.)
4. **`main.html`** contains the guild header (normal flow) and includes `view/welcome.html` (portal renderer template)

All content is rendered as portal modules on a single page — there are no separate "welcome" and "roster" pages. The roster is a portal module (`portal/modules/roster.php`) displayed in the center column with filters and pagination.

### Template Structure

```
main.html
├── Guild header (name, faction, realm, member count, emblem)
├── view/welcome.html (portal renderer)
│   ├── portal-top (modules_top loop)
│   ├── portal-content-wrapper
│   │   ├── portal-right (modules_right loop)
│   │   │   └── recruitment_side.html, custom blocks, etc.
│   │   └── portal-center (modules_center loop)
│   │       ├── motd_center.html
│   │       ├── roster_center.html (filters, table, pagination)
│   │       └── news_center.html, activity, etc.
│   └── portal-bottom (modules_bottom loop)
└── overall_footer.html
```

## Service Layer

All services are defined in `config/services.yml` and `config/portal_services.yml`. Table names are parameterised in `config/tables.yml`.

### Core Services

| Service ID | Class | Purpose |
|---|---|---|
| `avathar.bbguild.controller` | `controller\view_controller` | Frontend routes (guild page) |
| `avathar.bbguild.admin.main` | `controller\admin_main` | ACP dashboard, settings, logs |
| `avathar.bbguild.admin.games` | `controller\admin_games` | Game/faction/race/class/role management |
| `avathar.bbguild.admin.guild` | `controller\admin_guild` | Guild/rank/player management |
| `avathar.bbguild.admin.portal` | `controller\admin_portal` | Portal module management |
| `avathar.bbguild.log` | `model\admin\log` | bbGuild activity log |
| `avathar.bbguild.curl` | `model\admin\curl` | HTTP client wrapper |
| `avathar.bbguild.util` | `model\admin\util` | Request utilities |
| `avathar.bbguild.game_registry` | `model\games\game_registry` | Game provider lookup |
| `avathar.bbguild.listener` | `event\main_listener` | phpBB event hooks |

### Portal Services

| Service ID | Class | Purpose |
|---|---|---|
| `avathar.bbguild.guild_context` | `portal\guild_context` | Resolves guild, loads data, assigns header template vars |
| `avathar.bbguild.portal.renderer` | `portal\portal_renderer` | Renders portal layout for a guild |
| `avathar.bbguild.portal.columns` | `portal\columns` | Column constants and helpers |
| `avathar.bbguild.portal.module_helper` | `portal\module_helper` | Module rendering helper (template, language, columns) |
| `avathar.bbguild.portal.module_registry` | `portal\module_registry` | Available module type lookup |
| `avathar.bbguild.portal.modules.manager` | `portal\modules\manager` | Module CRUD operations |
| `avathar.bbguild.portal.modules.database_handler` | `portal\modules\database_handler` | Module config persistence |

Built-in portal modules (tagged `bbguild.portal.module`):
- `portal_motd` - Message of the Day
- `portal_recruit` - Recruitment Status
- `portal_roster` - Guild Roster (center-only, with grid/listing layout switcher, filters, and pagination)

## Routes

Defined in `config/routing.yml`:

| Route | Path | Controller |
|---|---|---|
| `avathar_bbguild_guild` | `/guild/{guild_id}` | `view_controller::handleview` |
| `avathar_bbguild_00` | `/guild/{page}/{guild_id}` | `view_controller::handleview` (compat) |
| `avathar_bbguild_01` | `/getfaction` | AJAX: faction selector |
| `avathar_bbguild_02` | `/getguildrank/{guild_id}` | AJAX: rank selector |
| `avathar_bbguild_03` | `/getplayerList/{game_id}` | AJAX: player list |
| `avathar_bbguild_04` | `/getclassrace/{game_id}` | AJAX: class/race selector |

The primary route is `/guild/{guild_id}`. The legacy `/guild/{page}/{guild_id}` route is kept for backward compatibility but `$page` is ignored.

## ACP Modules

Registered via `update_data()` in `migrations/v200b3/release_2_0_0_b3.php`:

| Category | Module | Controller |
|---|---|---|
| ACP_BBGUILD_MAINPAGE | Dashboard | `admin_main` |
| ACP_BBGUILD_MAINPAGE | Settings | `admin_main` |
| ACP_BBGUILD_MAINPAGE | Logs | `admin_main` |
| ACP_BBGUILD_MAINPAGE | Portal | `admin_portal` |
| ACP_BBGUILD_GUILD | Guilds | `admin_guild` |
| ACP_BBGUILD_GUILD | Ranks | `admin_guild` |
| ACP_BBGUILD_GUILD | Players | `admin_guild` |
| ACP_BBGUILD_GAME | Games | `admin_games` |
| ACP_BBGUILD_GAME | Factions | `admin_games` |
| ACP_BBGUILD_GAME | Races | `admin_games` |
| ACP_BBGUILD_GAME | Classes | `admin_games` |
| ACP_BBGUILD_GAME | Roles | `admin_games` |

## Database Schema

16 tables in core (see `database.html` for full column details). Achievement tables
(`bb_achievement`, `bb_achievement_track`, `bb_achievement_criteria`,
`bb_achievement_rewards`, `bb_relations_table`, `bb_criteria_track`) are **not**
created by core — they belong to the `bbguildwow` game plugin's own migrations.

### Core Tables
| Table | Purpose |
|---|---|
| `bb_games` | Supported games |
| `bb_guild` | Guilds |
| `bb_players` | Player characters |
| `bb_ranks` | Guild ranks |
| `bb_logs` | Activity log |
| `bb_news` | Guild news items |
| `bb_motd` | Message of the Day |
| `bb_recruit` | Recruitment postings |
| `bb_specializations` | Class/role specializations (#331 — e.g. Frost Mage vs Fire Mage) |

### Game Content Tables
| Table | Purpose |
|---|---|
| `bb_classes` | Character classes per game |
| `bb_races` | Races per game |
| `bb_factions` | Factions per game |
| `bb_gameroles` | Roles (tank, healer, dps, etc.) |
| `bb_language` | Game content localisation |

### Portal Tables
| Table | Purpose |
|---|---|
| `bb_portal_modules` | Module layout per guild (column, order, status) |
| `bb_portal_config` | Module config values per guild |

### Planned (schema TBD, not yet created)
| Table | Purpose |
|---|---|
| `bb_bosstable` | Boss encounters |
| `bb_zonetable` | Raid zones |

## Game Plugin System

Games are external extensions at `ext/avathar/bbguild<game>/` (no separator —
composer name, directory, PHP namespace, and GitHub repo all match; dropped
in 2.0.0-b4 since phpBB's extension class loader can't handle a hyphenated
directory name and EPV rejects underscores in the composer name). Each
plugin provides:

- `composer.json` + `ext.php` (standard phpBB extension)
- `game/<game>_provider.php` implementing `game_provider_interface`
- `game/<game>_installer.php` extending `abstract_game_install`
- `config/services.yml` tagging the provider with `bbguild.game_provider`
- Language files and game images

Providers are collected via `phpbb\di\service_collection` (tagged `bbguild.game_provider`) and accessed through `game_registry`.

### Available Plugins
| Plugin | Game | API |
|---|---|---|
| `bbguildwow` | World of Warcraft | Battle.net |
| `bbguildgw2` | Guild Wars 2 | - |
| `bbguildlotro` | Lord of the Rings Online | - |
| `bbguildeq` | EverQuest | - |
| `bbguildeq2` | EverQuest 2 | - |
| `bbguildffxi` | Final Fantasy XI | - |
| `bbguildffxiv` | Final Fantasy XIV | - |
| `bbguildswtor` | Star Wars: The Old Republic | - |
| `bbguildlineage2` | Lineage 2 | - |

A built-in "Custom" game is included in core (`model/games/library/install_custom.php`).

## Log System

The log system (`model/admin/log.php`) follows the phpBB log design pattern:

- **Storage:** Each log entry stores a `log_type` (language key like `L_ACTION_GUILD_ADDED`) and `log_action` (serialized array of `vsprintf` substitution args)
- **Display:** The log viewer resolves the language key and applies args via `vsprintf()` at render time
- **Format strings:** `ACTION_*` keys are short labels; `VLOG_*` keys are verbose format strings with `%s` placeholders (first `%s` is always the username, resolved from `log_userid`)
- **Valid types:** Defined in `log::$valid_action_types` mapping constants to type names

## Permissions

| Permission | Scope | Purpose |
|---|---|---|
| `a_bbguild` | Admin | ACP access |
| `u_bbguild` | User | View guild pages |
| `u_charclaim` | User | Claim a character |
| `u_charadd` | User | Add a character |
| `u_chardelete` | User | Delete a character |
| `u_charupdate` | User | Update a character |

Default grants are a mix of role-based (`ROLE_USER_STANDARD`/`ROLE_USER_FULL`,
`ROLE_ADMIN_FULL`/`ROLE_ADMIN_STANDARD`) and direct per-group grants. Direct
grants exist because installs that manage `u_` permissions via direct
per-group checkboxes instead of the stock role templates never inherit a
role-based grant at all — REGISTERED, ADMINISTRATORS, GLOBAL_MODERATORS, and
GUESTS each get an explicit direct `u_bbguild` grant for this reason
(`v200rc1`); ADMINISTRATORS additionally gets the full `u_char*` set so the
UCP "bbGuild" tab isn't hidden for an admin who isn't also in REGISTERED
(`v200rc2`). GLOBAL_MODERATORS intentionally stays view-only.

## Migration Chain

```
v200b3/release_2_0_0_b3   (squashed base install: schema, data, config, permissions, modules)
    -> v200b4/release_2_0_0_b4   (specialization system: bb_specializations, player_spec_id)
    -> v200rc1/release_2_0_0_rc1 (permission fixes, bb_language column widen, bbguild_version cleanup)
    -> v200rc2/release_2_0_0_rc2 (ADMINISTRATORS char-management permissions)
```

Each migration's `effectively_installed()` checks a concrete artifact it
itself creates (a table, a column, a permission grant) rather than a
version-string comparison — the version now lives solely in
`ext::BBGUILD_VERSION`, not in `phpbb_config` (see `contrib/CHANGELOG.md`'s
2.0.0-rc1 entry for why).

## DKP Plugin

The DKP (Dragon Kill Points) system ships as a separate extension at
https://github.com/avatharbe/bbDKP — a ground-up rewrite (not just planned;
`v2.0.0-alpha1`/`alpha2` already shipped) using bbAccounts as its canonical
ledger. It provides raid tracking, loot management, point pools, and its own
log types. See issue [#321](https://github.com/avatharbe/bbguild/issues/321).
