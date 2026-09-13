# bbGuild — Database Schema

This documents the **core** bbGuild database schema only — the 17 tables
created by the migrations under `migrations/` in this repository. It does
**not** cover game-plugin tables: achievement tracking
(`bb_achievement`, `bb_achievement_track`, `bb_achievement_criteria`,
`bb_achievement_rewards`, `bb_relations_table`, `bb_criteria_track`),
character equipment (`bb_player_equipment`), and similar tables are created
by `bbguildwow`'s (and other game plugins') own migrations in their own
repositories, not here.

This replaces the previous `database.html`, a SQLEditor-generated dump that
had gone stale: it still documented AION-era columns on `bb_guild`
(`aion_legion_id`, `aion_server_id`, `battlegroup`) removed years ago, and it
listed the achievement tables above as if they were core — they never have
been since the game-plugin split. `database.html` has been deleted; the
schema below was derived directly from the `update_schema()` method of every
migration, in dependency order:

`v200b3` → `v200b4` → `v200rc1` → `v200rc2` → `v200rc4` → `v210b1` → `v210b2`

(`v210b3` is data-only — it re-seeds the default portal template — and made
no schema change.)

**Important caveats:**
- phpBB migrations don't declare database-level `FOREIGN KEY` constraints.
  Every relationship shown below is enforced only by application code
  (joins in `model/player/*.php`, `portal/*.php`, etc.), not by the database
  itself.
- Column types below are simplified to common SQL-ish names for readability
  in Mermaid. They're derived directly from phpBB's own migration type
  tokens: `UINT`/`USINT` → `int`/`smallint` (both unsigned in practice),
  `TINT:N` → `tinyint`, `BOOL` → `boolean`, `VCHAR:N`/`VCHAR_UNI:N` →
  `varchar(N)` (`VCHAR_UNI` with no explicit length defaults to 255),
  `TEXT_UNI` → `text`, `MTEXT` → `mediumtext`, `TIMESTAMP` → `timestamp`,
  `CHAR:N` → `char(N)`.
- Two tables referenced by DI parameters in `config/tables.yml` —
  `bb_bosstable` and `bb_zonetable` — are **not created by any migration
  yet**. Schema is still TBD, so they're omitted from the diagrams below.

## Table-count discrepancy (resolved)

`CLAUDE.md` currently says "16 tables in core" and lists 9 Core + 2 Portal +
5 Game Content = 16. That count is stale by one table: migration `v210b2`
(`add_portal_tabs.php`, shipping #360 on 2026-09-13) added `bb_portal_tabs`,
which brings Portal to 3 tables and the true current total to **17**. The
16-table figure was accurate before `v210b2` merged; `CLAUDE.md`'s Database
Tables section just hasn't been updated since. This document reflects the
current, correct count of 17.

---

## Diagram 1 — Core guild & player

The guild/rank/player/character backbone, plus the guild-scoped content
tables (news, MOTD, recruitment) and the specialization system (#331).
`bb_logs` is included for completeness but has no guild-scoped relationship
— it's a global activity log keyed by `log_userid`, which points at phpBB's
own `users` table, outside this schema.

```mermaid
erDiagram
    bb_games ||--o{ bb_guild : "game_id"
    bb_guild ||--o{ bb_ranks : "guild_id"
    bb_guild ||--o{ bb_players : "player_guild_id"
    bb_ranks ||--o{ bb_players : "player_rank_id (scoped by guild_id)"
    bb_specializations |o--o{ bb_players : "player_spec_id (optional)"
    bb_guild ||--o{ bb_news : "guild_id"
    bb_guild ||--o{ bb_motd : "guild_id"
    bb_guild ||--o{ bb_recruit : "guild_id"

    bb_games {
        int id PK
        varchar(10) game_id UK
        varchar(255) game_name
        varchar(3) region
        varchar(30) status
        varchar(20) imagename
        int armory_enabled
        varchar(255) bossbaseurl
        varchar(255) zonebaseurl
        varchar(255) apikey
        varchar(5) apilocale
        varchar(255) privkey
    }

    bb_guild {
        smallint id PK "not auto_increment; 0 = reserved Guildless placeholder"
        varchar(255) name
        varchar(255) realm
        varchar(3) region
        boolean roster
        int players
        varchar(255) emblemurl
        varchar(10) game_id
        varchar(20) game_edition
        int min_armory
        boolean rec_status
        boolean guilddefault
        boolean armory_enabled
        varchar(255) armoryresult
        int recruitforum
        int faction
    }

    bb_ranks {
        smallint guild_id PK
        smallint rank_id PK "99 = conventional 'Out/kicked' rank"
        varchar(50) rank_name
        boolean rank_hide
        varchar(75) rank_prefix
        varchar(75) rank_suffix
    }

    bb_players {
        int player_id PK
        varchar(10) game_id
        varchar(100) player_name
        varchar player_region
        varchar(30) player_realm
        varchar(100) player_title
        smallint player_level
        smallint player_race_id
        smallint player_class_id
        smallint player_rank_id
        varchar(20) player_role
        text player_comment
        timestamp player_joindate
        timestamp player_outdate
        smallint player_guild_id
        smallint player_gender_id
        int player_achiev
        varchar(255) player_armory_url
        varchar player_portrait_url
        varchar(100) player_spec "deprecated free text; superseded by player_spec_id"
        int phpbb_user_id "0 = unclaimed"
        boolean player_status
        varchar(255) deactivate_reason
        timestamp last_update "bumped by any edit, incl. manual ACP edits"
        int player_spec_id "added v200b4 (#331)"
        int player_last_synced "added v210b1 (#361); only bumped by cron sync"
    }

    bb_specializations {
        int spec_id PK
        varchar(10) game_id
        smallint class_id
        smallint role_id
        varchar(100) spec_name
        varchar(100) spec_icon
        smallint spec_order
    }

    bb_news {
        int news_id PK
        int guild_id
        varchar news_headline
        text news_message
        timestamp news_date
        int user_id
        varchar(255) bbcode_bitfield
        varchar(8) bbcode_uid
        int bbcode_options
    }

    bb_motd {
        int motd_id PK
        int guild_id
        varchar motd_title
        text motd_msg
        timestamp motd_timestamp
        varchar(255) bbcode_bitfield
        varchar(8) bbcode_uid
        int user_id
        int bbcode_options
    }

    bb_recruit {
        int id PK
        smallint guild_id
        int role_id
        int class_id
        int level
        smallint positions
        smallint applicants
        smallint status
        int applytemplate_id
        timestamp last_update
        text note
    }

    bb_logs {
        int log_id PK
        timestamp log_date
        varchar(255) log_type
        text log_action
        varchar(45) log_ipaddress
        varchar(32) log_sid
        varchar log_result
        int log_userid "references phpBB's own users table, not shown here"
    }
```

## Diagram 2 — Portal

The page-level tab system (#360, `v210b2`) sits above the pre-existing
column/module layout engine: a guild has one or more tabs, and each portal
module belongs to exactly one tab (`module_tab`), which in turn scopes its
column/order position. `bb_guild` is repeated here (PK + name only) purely
to anchor the relationship lines — see Diagram 1 for its full column list.

```mermaid
erDiagram
    bb_guild ||--o{ bb_portal_tabs : "guild_id"
    bb_guild ||--o{ bb_portal_modules : "guild_id"
    bb_portal_tabs ||--o{ bb_portal_modules : "module_tab"
    bb_guild ||--o{ bb_portal_config : "guild_id"

    bb_guild {
        smallint id PK
        varchar(255) name
    }

    bb_portal_tabs {
        int tab_id PK
        smallint guild_id
        varchar(100) tab_name
        varchar(100) tab_slug UK "unique per guild_id"
        smallint tab_order
        tinyint tab_status
    }

    bb_portal_modules {
        int module_id PK
        smallint guild_id
        varchar(255) module_classname
        tinyint module_column
        tinyint module_order
        varchar(255) module_name
        varchar(255) module_image_src
        varchar(100) module_icon
        smallint module_icon_size
        smallint module_image_width
        smallint module_image_height
        varchar(255) module_group_ids
        tinyint module_status
        int module_tab "added v210b2 (#360)"
    }

    bb_portal_config {
        varchar(255) config_name PK
        mediumtext config_value
        smallint guild_id PK
    }
```

## Diagram 3 — Game content reference data

Per-game lookup tables (classes, races, factions, roles) plus the
localisation table (`bb_language`) that supplies display names for all of
them. `bb_specializations` bridges this diagram and Diagram 1 — it's
counted under "Core" in `CLAUDE.md`'s bucketing (it's player-facing state,
not static reference data) but its `class_id`/`role_id` columns tie it
directly into this subsystem, so it's shown here too. `bb_games` is
repeated (PK + game_id only) to anchor the relationship lines.

```mermaid
erDiagram
    bb_games ||--o{ bb_factions : "game_id"
    bb_games ||--o{ bb_classes : "game_id"
    bb_games ||--o{ bb_races : "game_id"
    bb_games ||--o{ bb_gameroles : "game_id"
    bb_games ||--o{ bb_language : "game_id"
    bb_games ||--o{ bb_specializations : "game_id"
    bb_factions ||--o{ bb_classes : "class_faction_id"
    bb_factions ||--o{ bb_races : "race_faction_id"
    bb_classes ||--o{ bb_specializations : "class_id (scoped by game_id)"
    bb_gameroles ||--o{ bb_specializations : "role_id (scoped by game_id)"

    bb_games {
        int id PK
        varchar(10) game_id UK
    }

    bb_factions {
        varchar(10) game_id
        smallint f_index PK
        smallint faction_id
        varchar(255) faction_name
        boolean faction_hide
    }

    bb_classes {
        smallint c_index PK
        varchar(10) game_id
        smallint class_id
        smallint class_faction_id
        smallint class_min_level
        smallint class_max_level
        varchar class_armor_type
        boolean class_hide
        varchar(255) imagename
        varchar(10) colorcode
    }

    bb_races {
        varchar(10) game_id PK
        smallint race_id PK
        smallint race_faction_id
        boolean race_hide
        varchar(255) image_female
        varchar(255) image_male
    }

    bb_gameroles {
        int role_pkid PK
        varchar(10) game_id
        int role_id
        varchar role_color
        varchar role_icon
        varchar role_cat_icon
    }

    bb_language {
        int id PK
        varchar(10) game_id
        int attribute_id
        varchar(10) language "widened from char(2) in v200rc1 for codes like es_x_tu"
        varchar(30) attribute "'class' | 'race' | 'role' | ..."
        varchar(255) name
        varchar(255) name_short
    }

    bb_specializations {
        int spec_id PK
        varchar(10) game_id
        smallint class_id
        smallint role_id
        varchar(100) spec_name
        varchar(100) spec_icon
        smallint spec_order
    }
```

---

## Notes on non-obvious columns

- **`player_spec` vs. `player_spec_id`** — `bb_players` carries both the
  legacy free-text `player_spec` column and the structured `player_spec_id`
  FK added in `v200b4` (#331). Migrating existing free-text values into the
  structured column is tracked as #331 Phase 5 and hadn't started as of
  2.1.0-b1.
- **`last_update` vs. `player_last_synced`** — `last_update` is bumped by
  *any* edit, including a manual ACP change. `player_last_synced` (added
  `v210b1`, #361) is bumped only by the character-sync cron task, so a
  manual edit can't make a genuinely stale character look freshly synced.
- **`bb_guild.id` and `bb_ranks`'s composite PK are not auto-increment** —
  guild and rank IDs are assigned explicitly by installer/ACP code.
  `guild_id = 0` is a reserved "Guildless" placeholder; `rank_id = 99` is
  the conventional "Out" (kicked) rank.
- **`bb_portal_modules.guild_col_order` index** changed shape in `v210b2`:
  it was `(guild_id, module_column, module_order)` before #360 and became
  `(guild_id, module_tab, module_column, module_order)` after, matching the
  new tab scoping.
- **`bb_language`** is a generic localisation table, not a strict
  one-entity-per-row FK target — the same table supplies names for classes,
  races, roles, and other attributes, disambiguated by the `attribute`
  column plus the `(game_id, attribute_id, language, attribute)` unique key.
