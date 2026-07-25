# Changelog

## 2.0.0-rc4 25/07/2026
  - [FIX] The region selected on a game's ACP settings page (e.g. WoW: America / Europe / Korea …) was silently discarded on save and always reverted to the stored default (#357) — `SaveGameSettings()` read every other field but never the region dropdown, so `setRegion()` was never called before `update_game()`. The region is now persisted.
  - [FIX] A Battle.net armory sync could leave the ACP/frontend showing a week-stale guild row — `guilds::get_guild()` cached its per-guild read for 7 days, but `update_guild_battleNet()` writes `bb_guild` directly with no cache-destroy, so the stale row (faction, roster flag, player count) survived until the cache expired. This primary-key read is no longer cached.
  - [FIX] ACP guild update failed with "Cannot modify header information — headers already sent" when the message-of-the-day field was left empty (#28) — the empty MOTD was still run through `generate_text_for_storage()`, whose empty-message branch references the posting-only `TOO_FEW_CHARS` language key (not loaded in the ACP); on PHP 8 with `display_errors` on, the resulting "Undefined array key" warning printed before the ACP headers and corrupted the response. The message parser is now skipped entirely when the MOTD is empty (an empty MOTD is valid).
  - [FIX] Faction dropdown on the ACP edit-guild form snapped to the last option (Horde) whenever the game dropdown was changed (#29) — the AJAX faction rebuild created every `<option>` with `defaultSelected = true`, so the last one always won the selection; now created with `defaultSelected = false`
  - [CHG] Default minimum roster level lowered from 50 to 1 — 50 hid every guild member below that level from the roster (both at Battle.net sync time and at display time), too high a floor for a default. New installs seed 1 so all members are visible out of the box; existing installs keep their configured value and the setting stays adjustable in the ACP.

## 2.0.0-rc3 24/07/2026
  - [FIX] Fatal error `Call to undefined method player::getPlayerId()` on the UCP character-add form, UCP character-edit form, and ACP roster's character-edit form (#354) — `player` only ever exposed `player_id` as a public property (plus an unrelated `get_player_id($playername, $playerrealm, $guild_id)` lookup method); all three portrait-URL call sites now read the property directly

## 2.0.0-rc2 24/07/2026
  - [FIX] Roster search form (layout/filter dropdowns, name search, submit button) disappeared entirely when a search returned zero results, leaving no way to search again without navigating away — the form was nested inside the same conditional as the results listing
  - [FIX] Class filter did nothing in the roster's "grouped by class" grid view — `display_grid()` called `player::get_classes()` with a hardcoded `0` for the class id instead of the actual selected class, so every class still got a heading regardless of the filter (armor-type filters were unaffected, since that branch doesn't depend on class id)
  - [CHG] Split the roster's combined class/armor-type filter into two independent dropdowns (`class_filter` and `armor_filter`) — the single merged `filter` pulldown (disambiguated server-side by checking which lookup array the value matched) was legacy EQDKP design and is what made the class-filter bug above possible in the first place
  - [FIX] Roster search field and filter button rendered oversized — the filter box reused phpBB's reserved `search-box` class, which every active style already sizes for the full header search widget; renamed to `roster-search-box` and given its own compact sizing, decoupled from any site-style search-box CSS
  - [FIX] Roster search field/button sat ~2px off from the layout/class/armor dropdowns — the site style sizes `<select>` and `<button>` on different box-sizing/line-height bases; every control in the filter row now gets the same explicit height/line-height/box-sizing so they line up regardless
  - [FIX] UCP "bbGuild" tab (character claim/add) was entirely hidden for admin accounts not also in REGISTERED — v200rc1 only granted ADMINISTRATORS/GLOBAL_MODERATORS the `u_bbguild` view-only floor, but the UCP modes require `u_charclaim`/`u_charadd` specifically; ADMINISTRATORS now also gets `u_charclaim`/`u_charadd`/`u_chardelete`/`u_charupdate` (GLOBAL_MODERATORS intentionally stays view-only)
  - [DOCS] README fixed: stale `bbguild_<game>` naming corrected to `bbguild<game>`, and the specialization system (#331) — previously undocumented — added to the feature list

## 2.0.0-rc1 24/07/2026
  - [FIX] SQL injection risk (#352): `game_id` interpolated unescaped into raw-concatenated queries across `game.php` and the `rpg/{classes,races,roles,faction}` models (~33 sites) — now consistently escaped via `sql_escape()`, matching the pattern already used correctly in a handful of sibling methods
  - [FIX] Follow-up hardening (#352): `class_id`/`c_index`, `race_id`, `role_id`/`role_pkid`, and `faction_id` cast to `(int)` at every remaining raw-concatenated SQL site in those same models — not previously exploitable (every caller already passed pre-cast ints) but fragile without the query itself being protected
  - [FIX] `u_bbguild`, `u_charclaim`, `u_charadd`, `u_chardelete`, `u_charupdate` defaulting to "No" for REGISTERED, ADMINISTRATORS, and GLOBAL_MODERATORS on installs that manage user permissions via direct per-group grants instead of the stock role templates — v200b3's role-based grant never reached them; now also granted directly, matching the pattern already used for GUESTS
  - [FIX] Fresh-install failure: `bbguild_version` was never created via `config.add` anywhere in the migration history (only ever updated), so a genuinely from-scratch install failed with `CONFIG_NOT_EXIST` during the v200b4 migration
  - [FIX] `bb_language.language` column (`CHAR:2`) too narrow for this project's own `es_x_tu`-style locale codes (used across recenttopics/bbaccounts/bbpoints) — widened to `VCHAR:10`; previously crashed bbguildwow's install outright with a hard SQL error, and would hit any game plugin shipping that locale
  - [FIX] PHP 8.2+ "creation of dynamic property" deprecation warnings on every `player` instantiation — `ext_path`/`time`/`games` are now declared properties instead of ad-hoc constructor assignments
  - [FIX] 9 raw `$_GET` reads in `admin_games.php`/`acp/player_module.php` replaced with `$this->request->is_set(..., GET)`, consistent with every neighboring input check in those same methods
  - [FIX] `@unlink()` + `@`-suppressed `file_exists()` in `delete_guild()`'s emblem cleanup replaced with the phpBB filesystem service (`exists()`/`remove()`), preserving the existing best-effort semantics
  - [FIX] Claiming/unclaiming a character (#290) and the guild achievements pane (#278) confirmed already shipped in earlier b-releases; stale roadmap notes removed
  - [CHG] `bbguild_version` moved out of `phpbb_config` entirely into `ext::BBGUILD_VERSION`, a class constant (#353) — matches `avatharbe/recenttopics`'s `ext::RT_VERSION` pattern; the ACP version-check panel and every migration's `effectively_installed()` now read from it (or a concrete per-migration artifact check) instead of a config row
  - [DOCS] Individual player page (#288) deferred to 2.1.0 as a portal-module conversion — the existing standalone page ships and works in rc1
  - [DOCS] Unit test coverage added for guild CRUD (`update_guilddefault`, `get_guild`, `update_guild`) and the character claim/unclaim flow (#244)
  - All 8 issues in the GitHub 2.0.0-rc1 milestone confirmed shipped: guild pane, achievements pane, player page, claiming/unclaiming, UCP language files, UCP bugfixes, roster portal grid view, game-plugin compatibility

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
