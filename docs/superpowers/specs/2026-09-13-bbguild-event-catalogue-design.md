# Design — bbGuild Event Catalogue (in-process API surface, phase 1 of #370)

- **Milestone:** 2.3.0 (Integrations) — first half of issue #370's decoupling layer
- **Extension:** `avathar/bbguild` (core)
- **Date:** 2026-09-13
- **Status:** Approved design, ready for implementation plan

## 1. Context & problem

Issue #370 asks for a "bbGuild API surface" with two distinct facets: an in-process phpBB event catalogue for sibling extensions running inside the same phpBB install, and an out-of-process read API for consumers entirely outside phpBB (in-game addons, a Discord bot process, external sites). These are structurally different problems — one extends an existing PHP mechanism, the other is new HTTP-facing infrastructure with its own auth/versioning/rate-limiting concerns. This design covers **only the event catalogue**. The read API is deliberately scoped as a separate, second design, not something this one substitutes for.

Today, bbguild core fires exactly one front-end domain event (`avathar.bbguild.player_detail_display`) plus nine ACP submit/display hooks. Several real domain actions that sibling extensions would plausibly want to react to — character add/edit/claim/delete, sync completion, roster/portal rendering, recruitment/news changes — fire no events at all. There is also no single discoverable document listing what bbguild fires; a would-be integrator has to grep the codebase.

## 2. Goals / non-goals

**Goals**
1. Add `trigger_event()` calls at the concrete, currently-unhooked call sites identified below, using the exact pattern already established in this codebase (`phpbb\event\dispatcher_interface::trigger_event()`, `compact($vars)` payloads, `@event`/`@var`/`@since` docblocks).
2. Retroactively document the 10 events that already exist, so the catalogue is a single complete source of truth from day one.
3. Ship a `contrib/Events.md` file mirroring the exact structure and tone already established in `avathar/recenttopics`' `contrib/Events.md`, linked from bbguild's `README.md`.
4. Establish the stability contract: once documented, an event's name and argument set is a breaking change to alter, requiring a major version bump — same rule recenttopics already applies.

**Non-goals (explicit, deferred)**
- The read API (HTTP, auth, rate-limiting, versioning) — separate design, second spec.
- Building any of the actual consumer extensions (Discord integration, events calendar, bbAccounts hooks, bbDKP Lua companion) — none exist yet in this repo. This design only adds the hooks; it does not build the listeners on the other end.
- Any new event-dispatch *mechanism* — phpBB's existing dispatcher/EventSubscriberInterface pattern is used as-is.
- Deprecated event aliases — not applicable, these are new events, not renames.
- Raid/event scheduling events — bbguild 2.x has no raid/event-scheduling feature (that was bbDKP-era functionality; current README scope is roster/recruitment/news/achievements). Not inventing a domain action that doesn't exist.

## 3. Event list

### 3.1 Retroactively documented (already firing today)

| Event | Placement |
|---|---|
| `avathar.bbguild.player_detail_display` | `controller/view_controller.php::playerdetail()` |
| `avathar.bbguild.acp_addguild_submit` | `controller/admin_guild.php` |
| `avathar.bbguild.acp_addguild_display` | `controller/admin_guild.php` |
| `avathar.bbguild.acp_editguild_submit` | `controller/admin_guild.php` |
| `avathar.bbguild.acp_editguild_display` | `controller/admin_guild.php` |
| `avathar.bbguild.acp_editgames_submit` | `controller/admin_games.php` |
| `avathar.bbguild.acp_editgames_display` | `controller/admin_games.php` |
| `avathar.bbguild.acp_config_display` | `controller/admin_main.php` |
| `avathar.bbguild.acp_config_submit` | `controller/admin_main.php` |
| `avathar.bbguild.acp_listplayers_display` | `acp/player_module.php` |

No code changes needed for these — documentation only (Events.md entries + confirming existing docblocks have `@since`).

### 3.2 New — grounded in the game-plugin relationship (primary driver)

These are the concrete, currently-unhooked call sites, chosen because they extend the one relationship that already works today (bbguild core ↔ game plugins via `player_detail_display`), and because bbAccounts (account-linking) is the most concrete named sibling that would react to character lifecycle changes.

| Event | Placement | Fires when |
|---|---|---|
| `avathar.bbguild.character_add` | `ucp/bbguild_module.php::main()` | A character is successfully added |
| `avathar.bbguild.character_edit` | `ucp/bbguild_module.php::main()` | A character's details are successfully updated |
| `avathar.bbguild.character_delete` | `ucp/bbguild_module.php::main()` | A character is deleted |
| `avathar.bbguild.character_claim` | `ucp/bbguild_module.php::main()` | A user claims an existing character |
| `avathar.bbguild.character_unclaim` | `ucp/bbguild_module.php::main()` | A user unclaims a character |
| `avathar.bbguild.character_sync_completed` | `cron/task/character_sync.php` | A game-API sync finishes for a character (currently zero trigger points in this file) |
| `avathar.bbguild.roster_display` | `portal/modules/roster.php::display_listing()` and `::display_grid()`, inside each `foreach ($characters[0] as $char)` loop | Once per character row, in both the listing and grid layouts |
| `avathar.bbguild.portal_module_display` | `portal/portal_renderer.php::render()`, inside the `foreach ($portal_modules as $row)` loop | Once per portal module, after `get_module_template()` resolves it |

### 3.3 New — lighter, best-guess for Discord/notification-style consumers

No Discord extension exists yet to validate against, so these are scoped to the minimum plausible shape (an ID + what changed), not a rich payload guessed against a spec that doesn't exist yet.

`avathar.bbguild.news_posted` was considered and dropped: `bb_news` is referenced only as a carried-around constructor property in three controllers (`admin_main.php`, `admin_guild.php`, `admin_games.php`) and has no ACP module registration or INSERT/UPDATE call site anywhere in the codebase — the same kind of vestigial, never-wired-up leftover the #373 audit found in the language file. Not adding an event for a feature that doesn't exist.

`show_editguildrecruitment()` has three distinct branches (`$add`, `$update`, and a `delete` action) — matching the delete coverage already given to characters in §3.2, all three get an event.

| Event | Placement | Fires when |
|---|---|---|
| `avathar.bbguild.recruitment_posted` | `controller/admin_guild.php::show_editguildrecruitment()`, `$add` branch | A new recruitment posting is created |
| `avathar.bbguild.recruitment_updated` | same, `$update` branch | An existing recruitment posting changes |
| `avathar.bbguild.recruitment_deleted` | same, `delete` action branch | A recruitment posting is deleted |
| `avathar.bbguild.motd_updated` | `controller/admin_guild.php::UpdateGuild()` (~line 491, the confirmed MOTD save block) | A guild's Message of the Day is saved |

### 3.4 Consumed from other extensions

None today. bbguild core has no `@?`-nullable soft-coupled DI reference to any sibling extension. Section 2 of `Events.md` documents this honestly as empty rather than inventing a placeholder integration.

## 4. Payload convention

Match the existing pattern exactly — `compact($vars)` with the specific IDs involved, not full row/object dumps:

```php
/**
 * @event avathar.bbguild.character_add
 * @var int player_id The newly created character's id
 * @var int guild_id  The guild the character belongs to
 * @var int user_id   The phpBB user who added it
 * @since 2.3.0
 */
$vars = ['player_id', 'guild_id', 'user_id'];
extract($this->dispatcher->trigger_event('avathar.bbguild.character_add', compact($vars)));
```

Consumers needing more than the IDs re-fetch via bbguild's own services — keeps events light, stable, and cheap to fire even when nothing listens.

## 5. Documentation — `contrib/Events.md`

New file, mirroring `avathar/recenttopics/contrib/Events.md` structure exactly:

1. **What are phpBB events? / What is the DI container?** — same educational preamble (phpBB events fire regardless of which extension wrote this doc; copying it keeps a consistent onboarding experience across the user's extensions).
2. **Own Events Emitted (Public API)** — one subsection per event from §3.1–3.3 above, each with: what it's for, example use case, placement, since, arguments, known listeners (all "none" initially — no consumers exist yet). Includes the stability note: *"Changing anything listed here is a breaking change and requires a major version bump."*
3. **Events & Services Consumed from Other Extensions** — documents that this is currently empty, with the same soft-coupling (`@?` nullable DI) pattern explained for when it's needed later.
4. **phpBB Core Events Used Internally** — table of `main_listener.php`'s existing subscriptions (`core.common`, `core.user_setup`, `core.page_header`, `core.permissions`), not part of the public API.

`README.md` gets one line added, matching recenttopics' phrasing: *"For extension developers: custom PHP events and integration details are documented in [contrib/Events.md](contrib/Events.md)."*

## 6. Testing

- PHPUnit: for each new `trigger_event()` call site, a test asserting the event fires with the documented argument names when the underlying action succeeds (following whatever pattern the existing test suite uses for the current `player_detail_display` event, if any exists — otherwise a straightforward mock-dispatcher assertion).
- Manual: exercise character add/edit/claim/delete/unclaim via UCP and confirm no regression (these code paths are being touched, not just wrapped).
- Lint: `php -l` on every touched file; purge cache; verify UCP character flows still work end-to-end in the browser.

## 7. Follow-up

Once this ships, the read API design (facet 2 of #370) is scoped as its own spec — separate auth model, versioning, rate-limiting, and a decision on which consumer (Discord ext, bbDKP Lua companion, or events calendar) gets built first as the reference implementation.
