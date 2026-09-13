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
- **Known listeners:** `avathar/bbguildwow` (`on_player_detail_display()` shows the character's active WoW specialization)

---

### 1.2 `avathar.bbguild.acp_addguild_submit` / `acp_editguild_submit`

**What this event is for:** Fires after the guild-add/guild-edit form values are read, before the guild is saved. Lets game plugins set edition or other game-specific fields on the guild object before it persists.

- **Placement:** `controller/admin_guild.php::AddGuild()` / `::UpdateGuild()`
- **Since:** 2.0.0-b2
- **Arguments:**
  - `updateguild` (guilds) — The guild object being created/updated. Writable.
  - `game_id` (string) — The game identifier from the form
- **Known listeners:** `avathar/bbguildwow` (`on_editguild_submit()`, subscribed to both event names)

---

### 1.3 `avathar.bbguild.acp_addguild_display` / `acp_editguild_display`

**What this event is for:** Fires while building the guild-add/guild-edit ACP form template, letting a game plugin add its own fields.

- **Placement:** `controller/admin_guild.php::show_addguild()` / `::BuildTemplateEditGuild()`
- **Since:** 2.0.0-b2
- **Arguments:**
  - `updateguild` (guilds) — The guild object being displayed
  - `game_id` (string) — The game identifier
  - `has_api` (bool) — Whether this game has API support
- **Known listeners:** `avathar/bbguildwow` (`on_editguild_display()`, subscribed to both event names)

---

### 1.4 `avathar.bbguild.acp_editgames_submit` / `acp_editgames_display`

**What this event is for:** Same pattern as 1.2/1.3, for the ACP "edit game" form (classes, races, factions, specializations).

- **Placement:** `controller/admin_games.php` (submit handler and `showgame()`)
- **Since:** 2.0.0-b2
- **Arguments:**
  - `editgame` (game) — The game object being saved/displayed
  - `game_id` (string) — The game identifier
  - `has_api` (bool) — Whether this game has API support
- **Known listeners:** `avathar/bbguildwow` (`on_editgames_submit()`, `on_editgames_display()`)

---

### 1.5 `avathar.bbguild.acp_config_display` / `acp_config_submit`

**What this event is for:** Fires while building/saving the main bbGuild config page, letting a sibling extension add its own config fields. Unlike every other event in this catalogue, these two fire via the raw Symfony `dispatcher->dispatch('event.name')` call with **no payload** — there is nothing to read or write, only a notification that the page is being built/saved.

- **Placement:** `controller/admin_main.php` (config display and `update_config()`)
- **Since:** 2.0.0-b1
- **Arguments:** none
- **Known listeners:** `avathar/bbguildwow` (`on_config_display()`/`on_config_submit()` add/save the "Show Achievement Points" checkbox)

---

### 1.6 `avathar.bbguild.acp_listplayers_display`

**What this event is for:** Fires while building the ACP player-list page.

- **Placement:** `acp/player_module.php` (listplayers template build)
- **Since:** 2.0.0-b2
- **Arguments:**
  - `game_id` (string) — The game identifier for the current guild
  - `has_api` (bool) — Whether this game has API support
- **Known listeners:** `avathar/bbguildwow` (subscribes but currently a no-op stub reserved for future WoW-specific player list vars)

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
  - `guild_id` (int) — The guild whose portal is rendering
  - `row` (array) — The portal module's database row (`module_id`, `module_type`, etc.)
  - `template_module` (mixed) — The resolved template file/name for this module. Writable.
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

Note that game plugins such as `avathar/bbguildwow` integrate the *other* direction — they listen to the events in section 1 above and pull bbGuild's own services (e.g. `avathar.bbguild.log`, `avathar.bbguild.asset_url_resolver`) and table-name parameters directly from the container. That is a hard dependency in the game-plugin-to-core direction (game plugins already require bbGuild to be installed), not a soft-coupled one.

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
