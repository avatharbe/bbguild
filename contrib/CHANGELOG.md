# Changelog

## 2.0.0-b4 28/04/2026
  - [NEW] Specialization system (#331) — model, migration, and ACP/UCP integration
    - `bb_specializations` table with `(spec_id, game_id, class_id, role_id, spec_name, spec_icon, spec_order)` and a nullable `player_spec_id` FK on `bb_players`
    - `specialization` model with `load`/`save`/`delete`/`get_for_class`/`get_translations`
    - Specializations panel on the Edit Game page (add/edit/delete with class + role dropdowns)
    - Spec dropdown on ACP and UCP player edit forms, JS-filtered by selected class
    - Optional Spec column on the roster portal module (listing + grid views), hidden for games without specs, with per-locale display names overlaid from `bb_language` (`attribute='spec'`)
    - Optional `specialization_provider_interface` (`get_specializations()`, `get_spec_label()`) for game plugins to declare their spec catalog without breaking the existing 9 plugin providers
    - `install_specs()` extension point on `abstract_game_install`; called during install/uninstall and guarded so older installs without `bb_specializations` are unaffected
  - [CHG] Repo and PHP namespaces dropped to no-separator form (`bbguildwow`, `bbguildeq`, …); composer name, dir name, PHP namespace, and GitHub repo all match. DB-stored config and cache keys (`bbguild_<game>_version`, `bbguild_eqdkp_start`, `bbguild_wow_oauth_token_*`, …) preserved with the original underscore form to avoid orphaning rows
  - [FIX] EPV CI green: event docblocks gain `@since`, `acp_editguild_*` events split into `acp_editguild_*` + `acp_addguild_*` to satisfy the unique-event-name rule, `unserialize` calls in `model/admin/log.php` replaced with `json_decode`
  - [FIX] `avathar.bbguild.log` service definition gained the missing `@user` constructor argument (latent bug since 547ec380; surfaced when the container rebuilt)
  - [FIX] `tests/functional/.gitkeep` so PHPUnit's functional test suite resolves on CI checkout
  - [DOCS] Test plan documents added under `tests/` (epv / unit / functional / smoke / integration)

## 2.0.0-b3 15/03/2026
  - [NEW] Game edition field on guilds for WoW Classic support (#15) — edition dropdown in guild ACP, flows to child plugins via template events
  - [NEW] Auto-disable child game-plugin extensions when bbGuild core is disabled
  - [NEW] Block game deletion when guilds or players still reference it
  - [NEW] Player portraits displayed on roster
  - [NEW] Player detail page (#288) with character info, guild history, and armory link
  - [NEW] UCP character claim/unclaim (#290) — users can link forum accounts to guild characters
  - [NEW] UCP character editing with portrait and specialization fields
  - [NEW] Guild emblem stored in phpBB `files/` directory instead of extension directory
  - [NEW] API sync log types for roster, specs, and portraits
  - [NEW] Template events for game plugins to inject achievement sync and edition controls
  - [FIX] Recruitment duplicate rows and cartesian product on frontend
  - [FIX] Guild disappearing after game change in ACP
  - [FIX] Emblem URL resolution in ACP guild and player views
  - [FIX] Image path resolution for game plugins
  - [FIX] UCP character edit: title, null portrait, image paths
  - [CHG] Roster name column uses class-color text instead of background
  - [CHG] Guild header emblem moved to left with flexbox layout
  - [CHG] Guest users granted `u_bbguild` permission to view guild pages
  - [CHG] Squashed all migrations into single `v200b3` release migration

## 2.0.0-b2 08/03/2026
  - [NEW] Player detail page (#288) and UCP claim/unclaim (#290)
  - [NEW] Roster as portal module with grid/listing layout switcher
  - [NEW] Recruitment ACP integrated as tab in Edit Guild
  - [NEW] Portal ACP integrated as tab in Edit Guild
  - [CHG] Slimmed view_controller to 4 constructor args; guild_context as proper DI service
  - [CHG] ACP/UCP modules resolve table names from container parameters
  - [CHG] Removed dead code: viewwelcome, viewroster, viewnavigation, iviews, admin_player, model/blocks
  - [FIX] Multiple bug fixes (#336, #337, #341, #343, #344)

## 2.0.0-b1 05/03/2026
  - [NEW] Guild portal system — guild-scoped block engine with 3-column layout (top, center, right)
  - [NEW] Portal modules: Message of the Day, Roster, Recruitment
  - [NEW] Module plugin system via tagged services (`bbguild.portal.module`)
  - [NEW] Default layout template copied to new guilds
  - [NEW] Multi-guild dropdown in breadcrumb navbar
  - [CHG] Welcome page rewritten to use portal renderer
  - [CHG] Removed sidebar navigation — full-width layout
  - [CHG] Log system refactored from XML serialization to phpBB log pattern

## 2.0.0-a11 04/03/2026
  - [FIX] Multiple ACP and namespace fixes (#299, #301)
  - [CHG] Game plugin architecture — game support extracted into separate `bbguild_<game>` extensions
  - [CHG] Language cleanup — archived DKP keys, removed dead entries
  - [CHG] ACP UI — replaced radio button pairs with checkboxes

## 2.0.0-a10 02/03/2026
  - [FIX] phpBB 3.3 and PHP 8.x compatibility fixes
  - [CHG] Minimum requirements: PHP >= 7.4.0, phpBB >= 3.3.0

## 2.0.0-a5 27/03/2016
  - [NEW] Front page design updated to look like Blizzard Armory
  - [NEW] Default game setting

## 2.0.0-a4 13/03/2016
  - [NEW] Guild news page with Blizzard news feed data

## 2.0.0-a2 21/02/2016
  - [NEW] View controller with guild roster front page

## 2.0.0-a1
  - [NEW] Conversion from phpBB MOD to extension
  - [CHG] DKP no longer part of core
